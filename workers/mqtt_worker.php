<?php
// MQTT worker: รับข้อมูลจาก ESP32 ผ่าน MQTT แล้วบันทึกลง database
// ต้องรันค้างไว้ตลอด (CLI) ไม่ใช่ผ่าน Apache
//
//   local : php workers/mqtt_worker.php
//   docker: service mqtt-worker (command: php workers/mqtt_worker.php)
//
// Topics:
//   pid/esp32/{id}/state   ESP32 -> server  JSON เหมือน body ของ PATCH api/update-setsById.php
//   pid/esp32/{id}/status  ESP32 -> server  "online" / "offline" (ตั้งเป็น Last Will ของ ESP32)

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../services/esp32_service.php';

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

date_default_timezone_set("Asia/Bangkok");

$mqtt_config = [
    'host' => $_ENV['MQTT_HOST'] ?? 'localhost',
    'port' => (int) ($_ENV['MQTT_PORT'] ?? 1883),
    'user' => ($_ENV['MQTT_USER'] ?? '') !== '' ? $_ENV['MQTT_USER'] : null,
    'pass' => ($_ENV['MQTT_PASS'] ?? '') !== '' ? $_ENV['MQTT_PASS'] : null,
];

const RETRY_DELAY_SECONDS = 5;

function logLine(string $message): void
{
    echo "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
}

// ================= Database =================
$db = null;

function getDb()
{
    global $db, $db_config;

    if ($db && pg_connection_status($db) === PGSQL_CONNECTION_OK) {
        return $db;
    }

    // reconnect จนกว่าจะได้ (DB อาจ restart อยู่)
    while (true) {
        $db = @pg_connect(sprintf(
            "host=%s port=%s dbname=%s user=%s password=%s",
            $db_config['host'],
            $db_config['port'],
            $db_config['name'],
            $db_config['user'],
            $db_config['pass']
        ));

        if ($db) {
            logLine("DB: connected to {$db_config['host']}:{$db_config['port']}/{$db_config['name']}");
            return $db;
        }

        logLine("DB: connect failed, retry in " . RETRY_DELAY_SECONDS . "s");
        sleep(RETRY_DELAY_SECONDS);
    }
}

// ================= Handlers =================
function saveState(string $id, array $data): void
{
    try {
        updateEsp32State(getDb(), $id, $data);
    } catch (Esp32ServiceException $e) {
        $pgError = pg_last_error(getDb());
        logLine("esp32 #$id: " . $e->getMessage() . ($pgError ? " ($pgError)" : ""));
    }
}

function getIdFromTopic(string $topic): ?string
{
    // pid/esp32/{id}/xxx
    $parts = explode('/', $topic);
    $id = $parts[2] ?? '';

    return ctype_digit($id) ? $id : null;
}

function onState(string $topic, string $message, bool $retained = false): void
{
    // state ต้องไม่ retain: ข้อความเก่าที่ค้างใน broker จะถูกส่งซ้ำทุกครั้งที่ worker เริ่มใหม่และทับค่าจริงใน DB
    if ($retained) {
        logLine("skip retained message on $topic: $message");
        return;
    }

    $id = getIdFromTopic($topic);
    $data = json_decode($message, true);

    if ($id === null || !is_array($data)) {
        logLine("skip invalid message on $topic: $message");
        return;
    }

    saveState($id, $data);
}

function onStatus(string $topic, string $message): void
{
    $id = getIdFromTopic($topic);
    $status = strtolower(trim($message));

    if ($id === null || !in_array($status, ['online', 'offline'])) {
        logLine("skip invalid message on $topic: $message");
        return;
    }

    logLine("esp32 #$id: $status");
    if ($status === 'online') {
        // ESP32 กลับมาออนไลน์ = reset wifi เสร็จแล้ว ล้าง flag
        saveState($id, ['is_connected' => true, 'is_resetwifi' => false]);
    } else {
        saveState($id, ['is_connected' => false]);
    }
}

// ================= Main loop =================
getDb();

$settings = (new ConnectionSettings)
    ->setUsername($mqtt_config['user'])
    ->setPassword($mqtt_config['pass'])
    ->setKeepAliveInterval(30)
    ->setConnectTimeout(5);

while (true) {
    try {
        $client = new MqttClient($mqtt_config['host'], $mqtt_config['port'], 'pid-worker-' . getmypid());
        $client->connect($settings, true);
        logLine("MQTT: connected to {$mqtt_config['host']}:{$mqtt_config['port']}");

        $client->subscribe('pid/esp32/+/state', onState(...), MqttClient::QOS_AT_MOST_ONCE);
        $client->subscribe('pid/esp32/+/status', onStatus(...), MqttClient::QOS_AT_LEAST_ONCE);

        $client->loop(true);
    } catch (Throwable $e) {
        logLine("MQTT: " . $e->getMessage() . ", reconnect in " . RETRY_DELAY_SECONDS . "s");
        sleep(RETRY_DELAY_SECONDS);
    }
}

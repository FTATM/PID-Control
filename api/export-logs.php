<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

include_once("../configs/pg_connect.php");

const EXPORT_MAX_ROWS = 200000;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

// ================= Params =================
// id    = device_id
// hours = ย้อนหลังกี่ชั่วโมงนับจากตอนนี้ (หรือ)
// from / to = ช่วงเวลา 'YYYY-MM-DD HH:MM[:SS]' (เว้น to = ถึงปัจจุบัน)
$deviceId = $_GET['id'] ?? '';
if ($deviceId === '') {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "please input id"]);
    exit;
}

$dev = pg_query_params($db, "SELECT id, device_id, name FROM esp32_sets WHERE device_id = $1 LIMIT 1;", [$deviceId]);
$device = $dev ? pg_fetch_assoc($dev) : false;
if (!$device) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "device not found"]);
    pg_close($db);
    exit;
}

$where = "esp32_id = $1";
$params = [$device['id']];

$parseTime = function ($v) {
    $v = trim(str_replace('T', ' ', (string) $v));
    return preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $v) ? $v : null;
};

if (isset($_GET['hours']) && $_GET['hours'] !== '') {
    $hours = max(1, min((int) $_GET['hours'], 24 * 365));
    $params[] = $hours;
    $where .= " AND created_at >= now() - make_interval(hours => $" . count($params) . ")";
} else {
    $from = $parseTime($_GET['from'] ?? '');
    $to = $parseTime($_GET['to'] ?? '');
    if (!empty($_GET['from']) && $from === null || !empty($_GET['to']) && $to === null) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "invalid from/to"]);
        pg_close($db);
        exit;
    }
    if ($from !== null) {
        $params[] = $from;
        $where .= " AND created_at >= $" . count($params);
    }
    if ($to !== null) {
        $params[] = $to;
        $where .= " AND created_at <= $" . count($params);
    }
}

$sql = "SELECT created_at, sp, pv, error, kp, ki, kd, mv, sv, relay,
               multi_kp, multi_ki, multi_kd, is_connected
        FROM esp32_logs
        WHERE $where
        ORDER BY created_at ASC, id ASC
        LIMIT " . (EXPORT_MAX_ROWS + 1);
$result = pg_query_params($db, $sql, $params);

if (!$result) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Query failed"]);
    pg_close($db);
    exit;
}

$rows = pg_fetch_all($result) ?: [];
$truncated = count($rows) > EXPORT_MAX_ROWS;
if ($truncated) {
    array_pop($rows);
}

echo json_encode([
    "success" => true,
    "device" => ["device_id" => $device['device_id'], "name" => $device['name']],
    "truncated" => $truncated,
    "max_rows" => EXPORT_MAX_ROWS,
    "data" => $rows
], JSON_UNESCAPED_UNICODE);

pg_close($db);

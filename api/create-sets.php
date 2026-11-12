<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// =============== Connect Database ================
include_once("../configs/pg_connect.php");

// ================= Method Check =================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit;
}

// ================= Get JSON =================
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON"
    ]);
    exit;
}

// ================= Allowed Fields =================
$allowedFields = [
    'device_id',
    'name',
    'sp',
    'error',
    'kp',
    'ki',
    'kd',
    'pv',
    'mv',
    'sv',
    'multi_kp',
    'multi_ki',
    'multi_kd',
    'is_connected',
    'is_resetwifi',
    'relay'
];

// device_id ต้องกรอก, ใช้ได้เฉพาะ a-z A-Z 0-9 _ - (ใช้เป็นส่วนหนึ่งของ MQTT topic) และห้ามซ้ำ
$data['device_id'] = trim((string) ($data['device_id'] ?? ''));
if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $data['device_id'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "device_id is required (1-64 chars: A-Z a-z 0-9 _ -)"
    ]);
    exit;
}

$columns = [];
$placeholders = [];
$params = [];
$index = 1;

foreach ($data as $key => $value) {
    if (in_array($key, $allowedFields)) {
        $columns[] = $key;
        $placeholders[] = "$" . $index;
        $params[] = $value;
        $index++;
    }
}

if (empty($columns)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "No valid fields to insert"
    ]);
    exit;
}

$sql = "
    INSERT INTO esp32_sets (" . implode(", ", $columns) . ")
    VALUES (" . implode(", ", $placeholders) . ")
    RETURNING *;
";

$result = pg_query_params($db, $sql, $params);

if (!$result) {
    if (strpos((string) pg_last_error($db), 'esp32_sets_device_id_key') !== false) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "device_id already exists"
        ]);
        pg_close($db);
        exit;
    }
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Insert failed"
    ]);
    pg_close($db);
    exit;
}

$inserted = pg_fetch_assoc($result);

http_response_code(201); // Created
echo json_encode([
    "success" => true,
    "message" => "Insert Success",
    "data" => $inserted
], JSON_UNESCAPED_UNICODE);

pg_close($db);

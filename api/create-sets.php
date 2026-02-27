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
    'is_resetwifi'
];

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

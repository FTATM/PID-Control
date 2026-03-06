<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

include_once("../configs/pg_connect.php");

// ================================ Method Check =======================================
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit;
}

// ================================ Receive Params ======================================
$id = $_GET['id'] ?? '';

if (empty($id)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "please input id"
    ]);
    pg_close($db);
    exit;
}

// ===================================== Query Main ======================================
$sql = "SELECT * FROM esp32_sets WHERE id = $1 LIMIT 1;";
$result = pg_query_params($db, $sql, [$id]);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Query failed"
    ]);
    pg_close($db);
    exit;
}

$row = pg_fetch_assoc($result);

if (!$row) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "data" => [],
        "message" => "no data found for this id"
    ]);
    pg_close($db);
    exit;
}

// ===================================== Query Logs ======================================
$sql_logs = "SELECT * FROM esp32_logs 
             WHERE esp32_id = $1 
             ORDER BY id DESC 
             LIMIT 30";

$result_logs = pg_query_params($db, $sql_logs, [$id]);

if ($result_logs) {
    $logs = pg_fetch_all($result_logs);
    $row['logs'] = $logs ? $logs : [];
} else {
    $row['logs'] = [];
}

// ===================================== Send Back ========================================
http_response_code(200);
echo json_encode([
    "success" => true,
    "data" => $row
], JSON_UNESCAPED_UNICODE);

pg_close($db);
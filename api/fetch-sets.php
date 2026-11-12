<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

include_once("../configs/pg_connect.php");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

// รายการอุปกรณ์ทั้งหมด + เวลา log ล่าสุด
$sql = "SELECT s.*,
               (SELECT MAX(l.created_at) FROM esp32_logs l WHERE l.esp32_id = s.id) AS last_seen
        FROM esp32_sets s
        ORDER BY s.id ASC;";
$result = pg_query($db, $sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Query failed"]);
    pg_close($db);
    exit;
}

http_response_code(200);
echo json_encode([
    "success" => true,
    "data" => pg_fetch_all($result) ?: []
], JSON_UNESCAPED_UNICODE);

pg_close($db);

<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Allow-Headers: Content-Type");

// =============== Connect Database ================
include_once("../configs/pg_connect.php");

// ================= Method Check =================
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit;
}

// ================= Get ID =================
// ถ้าใช้แบบ ?id=1
$id = $_GET['id'] ?? '';

if (empty($id)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "please input id"
    ]);
    exit;
}

// ================= Delete SQL =================
$sql = "DELETE FROM esp32_sets WHERE id = $1 RETURNING *;";
$params = [$id];

$result = pg_query_params($db, $sql, $params);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Delete failed"
    ]);
    exit;
}

if (pg_num_rows($result) === 0) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "message" => "Data not found"
    ]);
    exit;
}

$deleted = pg_fetch_assoc($result);

http_response_code(200);
echo json_encode([
    "success" => true,
    "message" => "Delete success",
    "data" => $deleted
], JSON_UNESCAPED_UNICODE);
pg_close($db);

<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PATCH");
header("Access-Control-Allow-Headers: Content-Type");

// =============== Connnect Database ================
include_once("../configs/pg_connect.php");
include_once("../services/esp32_service.php");

// ================= Method Check =================
if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit;
}

// ================= Get ID =================
$id = $_GET['id'] ?? '';

if (empty($id)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "please input id"
    ]);
    exit;
}

// ================= Get JSON =================
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
$is_resetwifi = false;

if (!$data) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON"
    ]);
    exit;
}

// ================= Update + Log =================
try {

    $updated = updateEsp32State($db, $id, $data);

    http_response_code(200);
    echo json_encode(["success" => true, "data" => $updated]);
} catch (Esp32ServiceException $e) {

    http_response_code($e->getCode());
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
} catch (Throwable $e) {

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Unexpected error",
        "error" => $e->getMessage()
    ]);
} finally {

    if (isset($db)) {
        pg_close($db);
    }
}

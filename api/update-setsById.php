<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PATCH");
header("Access-Control-Allow-Headers: Content-Type");

// =============== Connnect Database ================
include_once("../configs/pg_connect.php");

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

if (!$data) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON"
    ]);
    exit;
}

// ================= Allowed Fields =================
try {

    $allowedFields = ['name', 'sp', 'error', 'kp', 'ki', 'kd', 'pv', 'mv', 'sv', 'multi_kp', 'multi_ki', 'multi_kd', 'is_connected', 'is_resetwifi'];

    $setParts = [];
    $params = [];
    $index = 1;

    foreach ($data as $key => $value) {
        if (in_array($key, $allowedFields)) {
            $setParts[] = "$key = $" . $index;
            $params[] = $value;
            $index++;
        }
    }

    if (empty($setParts)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "No valid fields to update"]);
        exit;
    }

    // append id
    $params[] = $id;

    $sql = "UPDATE esp32_sets
        SET " . implode(", ", $setParts) . "
        WHERE id = $" . $index . "
        RETURNING *;
    ";

    pg_query($db, "BEGIN");

    $result = pg_query_params($db, $sql, $params);

    if (!$result) {
        pg_query($db, "ROLLBACK");
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Update failed"]);
        exit;
    }

    if (pg_num_rows($result) === 0) {
        pg_query($db, "ROLLBACK");
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Data not found"]);
        exit;
    }

    $updated = pg_fetch_assoc($result);

    // log
    $sql_log = "INSERT INTO esp32_logs
        (esp32_id,sp,error,kp,ki,kd,pv,mv,sv,multi_kp,multi_ki,multi_kd,is_connected,is_resetwifi,created_at)
        VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,now());
    ";

    $params_log = [
        $updated['id'],
        $updated['sp'],
        $updated['error'],
        $updated['kp'],
        $updated['ki'],
        $updated['kd'],
        $updated['pv'],
        $updated['mv'],
        $updated['sv'],
        $updated['multi_kp'],
        $updated['multi_ki'],
        $updated['multi_kd'],
        $updated['is_connected'],
        $updated['is_resetwifi']
    ];

    $result_log = pg_query_params($db, $sql_log, $params_log);

    if (!$result_log) {
        pg_query($db, "ROLLBACK");
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Insert Log failed"]);
        exit;
    }

    if (!pg_query($db, "COMMIT")) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Commit failed"]);
        exit;
    }

    http_response_code(200);
    echo json_encode(["success" => true, "data" => $updated]);
} catch (Throwable $e) {

    pg_query($db, "ROLLBACK");
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

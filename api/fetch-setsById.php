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
$limit = (int) ($_GET['limit'] ?? 15);   // จำนวน logs ที่ส่งกลับ (default 15 เหมือนเดิม)
$limit = max(1, min($limit, 1000));
$window = (int) ($_GET['window'] ?? 0);  // ช่วงเวลาของกราฟ (วินาที) 0 = ใช้ limit อย่างเดียว
$window = max(0, min($window, 7 * 24 * 3600));

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
if ($window > 0) {
    // logs ย้อนหลัง $window วินาทีนับจาก log ล่าสุด
    // ถ้าเกิน $limit แถว เลือกทุกๆ N แถว เพื่อให้กราฟช่วงยาวไม่หนักเกินไป
    $sql_logs = "WITH w AS (
                     SELECT * FROM esp32_logs
                     WHERE esp32_id = $1
                       AND created_at >= (SELECT MAX(created_at) FROM esp32_logs WHERE esp32_id = $1)
                                         - make_interval(secs => $2)
                 ), n AS (
                     SELECT w.*,
                            ROW_NUMBER() OVER (ORDER BY id DESC) AS rn,
                            COUNT(*) OVER () AS total
                     FROM w
                 )
                 SELECT * FROM n
                 WHERE (rn - 1) % GREATEST(1, CEIL(total / $3::numeric)::int) = 0
                 ORDER BY id DESC";
    $result_logs = pg_query_params($db, $sql_logs, [$id, $window, $limit]);
} else {
    $sql_logs = "SELECT * FROM esp32_logs
                 WHERE esp32_id = $1
                 ORDER BY id DESC
                 LIMIT $2";
    $result_logs = pg_query_params($db, $sql_logs, [$id, $limit]);
}

if ($result_logs) {
    $logs = pg_fetch_all($result_logs) ?: [];
    foreach ($logs as &$log) {
        unset($log['rn'], $log['total']);
    }
    unset($log);
    $row['logs'] = $logs;
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
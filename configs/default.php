<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

// ================================ Connnect Database ======================================
include_once("../includes/fn/pg_connect.php");

// ✅ ใช้ตัวแปรเดียวกันกับที่เชื่อมต่อ
$db = pg_connect("$host $port $dbname $credentials");
if (!$db) {
    echo json_encode(["success" => false, "message" => "can not connect to database"]);
    exit;
}
// ========================================================================================




// ================================ Receive Params ======================================
// $branch_id = $_GET['bid'] ?? '';
// $branch_id = $_POST['bid'] ?? '';
// $raw_string = file_get_contents('php://input');
// $json = json_decode($raw_string);
// $json->id;

// echo $branch_id;

if (empty($branch_id)) {
    echo json_encode(["success" => false, "message" => "please input branch id"]);
    pg_close($db);
    exit;
}
// ========================================================================================




// ===================================== SQL Prepare ======================================
// $sql = "SELECT m.*, 
//         l.id AS l_id,
//         l.group_id AS l_group_id,
//         l.device_id AS l_device_id,
//         l.type_id AS l_type_id,
//         l.datax_id AS l_datax_id,
//         l.data_value AS l_data_value,
//         l.createtime AS l_createtime
//  FROM page_data_manage_monitor m
//         LEFT JOIN data_log l ON m.group_id = l.group_id AND  m.device_id = l.device_id AND m.type_id = l.type_id AND m.datax_id = l.datax_id
//         WHERE m.branch_id = $1 AND status = '1'
//         ORDER BY m.monitor_id DESC
//         ";

$sql = "SELECT * FROM page_data_manage_monitor
        WHERE branch_id = $1 AND status = '1'
        ORDER BY monitor_id DESC
        ";

$params = [
    $branch_id
];
// ========================================================================================




// ================================= Query Or Excute ======================================
$result = pg_query_params($db, $sql, $params);

if (!$result) {
    echo json_encode(["success" => false, "message" => "Query failed"]);
    pg_close($db);
    exit;
}
// ========================================================================================




// ===================================== Keep result ======================================
$data = [];


while ($monitor = pg_fetch_assoc($result)) {
    $data[$monitor['monitor_id']] = $monitor;
    $data[$monitor['monitor_id']]['logs'] = [];
    $sql_log = "SELECT * FROM data_log
        WHERE group_id = $1 AND  device_id = $2 AND type_id = $3 AND datax_id = $4
        ORDER BY id DESC
        ";

    $params_log = [
        $monitor['group_id'],
        $monitor['device_id'],
        $monitor['type_id'],
        $monitor['datax_id'],
    ];


    $result_log = pg_query_params($db, $sql_log, $params_log);

    while ($log = pg_fetch_assoc($result_log)) {
        $data[$monitor['monitor_id']]['logs'][] = $log;
    }
}


// ========================================================================================




// ===================================== send back ========================================
if (count($data) > 0) {
    echo json_encode(["success" => true, "data" => array_values($data)], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(["success" => false, "data" => [], "message" => "no data found for this branch id"]);
}
// ========================================================================================

// ================================ Disconnect Database ====================================
// ✅ ปิดการเชื่อมต่อ
pg_close($db);
// ========================================================================================

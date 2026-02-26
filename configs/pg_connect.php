<?php
require_once __DIR__ . '/config.php';
#####PGSQL#######
$host   = "host = " . $db_config['host']   ?? '127.0.0.1';
$port   = "port = " . $db_config['port']   ?? '5432';
$dbname = "dbname = " . $db_config['name'] ?? 'smartfarm';
$user   = $db_config['user']   ?? 'postgres';
$pass   = $db_config['pass']   ?? 'postgres';
$credentials = "user=$user password=$pass";
date_default_timezone_set("Asia/Bangkok");

// ✅ ใช้ตัวแปรเดียวกันกับที่เชื่อมต่อ
$db = pg_connect("$host $port $dbname $credentials");
if (!$db) {
  echo json_encode(["status" => "error", "message" => "can not connect to database"]);
  exit;
}

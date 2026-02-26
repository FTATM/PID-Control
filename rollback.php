<?php

require 'includes/fn/pg_connect.php';

$conn = pg_connect("$host $port $dbname $credentials");

$result = pg_query(
    $conn,
    "SELECT version FROM migrations ORDER BY id DESC LIMIT 1"
);

$row = pg_fetch_assoc($result);

if (!$row) {
    echo "Nothing to rollback.\n";
    exit;
}

$version = $row['version'];
$file = __DIR__ . "/database/migrations/$version";

$migration = require $file;

pg_query($conn, "BEGIN");

try {
    $migration->down($conn);

    pg_query_params(
        $conn,
        "DELETE FROM migrations WHERE version = $1",
        [$version]
    );

    pg_query($conn, "COMMIT");

    echo "Rolled back: $version\n";
} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    throw $e;
}

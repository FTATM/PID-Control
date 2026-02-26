<?php

require 'includes/fn/pg_connect.php';
require 'database/migration_runner.php';

$conn = pg_connect("$host $port $dbname $credentials");

$runner = new MigrationRunner($conn, __DIR__ . '/database/migrations');
$runner->migrate();

echo "All migrations complete.\n";

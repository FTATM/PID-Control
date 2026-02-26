<?php

class MigrationRunner
{
    private $conn;
    private $path;

    public function __construct($conn, $path)
    {
        $this->conn = $conn;
        $this->path = $path;
    }

    public function migrate()
    {
        $files = glob($this->path . '/*.php');
        sort($files);

        foreach ($files as $file) {

            $version = basename($file);

            $check = pg_query_params(
                $this->conn,
                "SELECT 1 FROM migrations WHERE version = $1",
                [$version]
            );

            if (pg_num_rows($check) == 0) {

                echo "Running: $version\n";

                $migration = require $file;

                pg_query($this->conn, "BEGIN");

                try {
                    $migration->up($this->conn);

                    pg_query_params(
                        $this->conn,
                        "INSERT INTO migrations (version) VALUES ($1)",
                        [$version]
                    );

                    pg_query($this->conn, "COMMIT");

                    echo "Done: $version\n";

                } catch (Exception $e) {

                    pg_query($this->conn, "ROLLBACK");
                    throw $e;
                }
            }
        }
    }
}

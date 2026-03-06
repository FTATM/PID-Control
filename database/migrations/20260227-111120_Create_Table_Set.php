<?php

return new class {

    public function up($conn)
    {
        pg_query($conn, "BEGIN");

        // ---------- esp32_sets ----------
        $sql1 = "
        CREATE TABLE public.esp32_sets (
            id int4 GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
            name varchar DEFAULT 'esp32',
            sp decimal(10,2) DEFAULT 0 NOT NULL,
            error decimal(10,2) DEFAULT 0 NOT NULL,
            kp decimal(10,2) DEFAULT 0 NOT NULL,
            ki decimal(10,2) DEFAULT 0 NOT NULL,
            kd decimal(10,2) DEFAULT 0 NOT NULL,
            pv decimal(10,2) DEFAULT 0 NOT NULL,
            mv decimal(10,2) DEFAULT 0 NOT NULL,
            sv decimal(10,2) DEFAULT 0 NOT NULL,
            multi_kp int4 DEFAULT 1 NOT NULL,
            multi_ki int4 DEFAULT 1 NOT NULL,
            multi_kd int4 DEFAULT 1 NOT NULL,
            is_connected boolean DEFAULT false NOT NULL,
            is_resetWifi boolean DEFAULT false NOT NULL
        );
        ";

        $result1 = pg_query($conn, $sql1);

        if (!$result1) {
            pg_query($conn, "ROLLBACK");
            throw new Exception(pg_last_error($conn));
        }

        // ---------- logs ----------
        $sql2 = "
        CREATE TABLE public.esp32_logs (
            id int8 GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
            esp32_id int4 NOT NULL,
            sp decimal(10,2) NOT NULL,
            error decimal(10,2) NOT NULL,
            kp decimal(10,2) NOT NULL,
            ki decimal(10,2) NOT NULL,
            kd decimal(10,2) NOT NULL,
            pv decimal(10,2) NOT NULL,
            mv decimal(10,2) NOT NULL,
            sv decimal(10,2) NOT NULL,
            multi_kp int4 DEFAULT 1 NOT NULL,
            multi_ki int4 DEFAULT 1 NOT NULL,
            multi_kd int4 DEFAULT 1 NOT NULL,
            is_connected boolean NOT NULL,
            is_resetWifi boolean NOT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL
        );

        CREATE INDEX logs_esp32_id_idx ON public.esp32_logs (esp32_id);
        CREATE INDEX logs_created_at_idx ON public.esp32_logs (created_at);
        ";

        $result2 = pg_query($conn, $sql2);

        if (!$result2) {
            pg_query($conn, "ROLLBACK");
            throw new Exception(pg_last_error($conn));
        }

        pg_query($conn, "COMMIT");
    }

    public function down($conn)
    {
        pg_query($conn, "BEGIN");

        $result = pg_query($conn, "
            DROP TABLE IF EXISTS public.esp32_logs;
            DROP TABLE IF EXISTS public.esp32_sets;
        ");

        if (!$result) {
            pg_query($conn, "ROLLBACK");
            throw new Exception(pg_last_error($conn));
        }

        pg_query($conn, "COMMIT");
    }
};

<?php

return new class {

    public function up($conn)
    {
        // device_id = รหัสอุปกรณ์ที่ผู้ใช้กำหนดเอง (ใช้ใน MQTT topic / ?id= / API) ห้ามซ้ำ
        // id เดิมยังเป็น primary key ภายใน (esp32_logs.esp32_id อ้างถึง id นี้)
        // ข้อมูลเดิมได้ device_id = id เดิม จึงไม่กระทบ topic ที่ ESP32 ใช้อยู่
        pg_query($conn, "BEGIN");

        $result = pg_query($conn, "
            ALTER TABLE public.esp32_sets
                ADD COLUMN IF NOT EXISTS device_id varchar(64);

            UPDATE public.esp32_sets SET device_id = id::text WHERE device_id IS NULL;

            ALTER TABLE public.esp32_sets
                ALTER COLUMN device_id SET NOT NULL;

            CREATE UNIQUE INDEX IF NOT EXISTS esp32_sets_device_id_key
                ON public.esp32_sets (device_id);
        ");

        if (!$result) {
            $err = pg_last_error($conn);
            pg_query($conn, "ROLLBACK");
            throw new Exception($err);
        }

        pg_query($conn, "COMMIT");
    }

    public function down($conn)
    {
        $result = pg_query($conn, "
            DROP INDEX IF EXISTS public.esp32_sets_device_id_key;
            ALTER TABLE public.esp32_sets DROP COLUMN IF EXISTS device_id;
        ");

        if (!$result) {
            throw new Exception(pg_last_error($conn));
        }
    }
};

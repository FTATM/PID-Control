<?php

return new class {

    public function up($conn)
    {
        // เพิ่มค่า relay ให้ทั้งค่าล่าสุด (esp32_sets) และประวัติ (esp32_logs)
        // DEFAULT 0 ทำให้ข้อมูลเดิมได้ค่า 0 อัตโนมัติ
        $result = pg_query($conn, "
            ALTER TABLE public.esp32_sets
                ADD COLUMN IF NOT EXISTS relay decimal(10,2) DEFAULT 0 NOT NULL;

            ALTER TABLE public.esp32_logs
                ADD COLUMN IF NOT EXISTS relay decimal(10,2) DEFAULT 0 NOT NULL;
        ");

        if (!$result) {
            throw new Exception(pg_last_error($conn));
        }
    }

    public function down($conn)
    {
        $result = pg_query($conn, "
            ALTER TABLE public.esp32_logs DROP COLUMN IF EXISTS relay;
            ALTER TABLE public.esp32_sets DROP COLUMN IF EXISTS relay;
        ");

        if (!$result) {
            throw new Exception(pg_last_error($conn));
        }
    }
};

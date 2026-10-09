<?php

return new class {

    public function up($conn)
    {
        // ลบ op ที่ migration 20261009-000000 เวอร์ชันแรกเคยเพิ่มไว้ (ฐานข้อมูลใหม่จะไม่มีคอลัมน์นี้อยู่แล้ว)
        $result = pg_query($conn, "
            ALTER TABLE public.esp32_logs DROP COLUMN IF EXISTS op;
            ALTER TABLE public.esp32_sets DROP COLUMN IF EXISTS op;
        ");

        if (!$result) {
            throw new Exception(pg_last_error($conn));
        }
    }

    public function down($conn)
    {
        // op ถูกยกเลิกแล้ว ไม่ต้องสร้างกลับ
    }
};

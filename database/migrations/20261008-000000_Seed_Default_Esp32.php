<?php

return new class {

    public function up($conn)
    {
        // สร้าง ESP32 ตัวแรก (id = 1) ให้ระบบใหม่ใช้งานได้ทันที
        // ใส่เฉพาะตอนตารางว่าง จึงไม่กระทบข้อมูลเดิมของเครื่องที่ใช้งานอยู่แล้ว
        $result = pg_query($conn, "
            INSERT INTO public.esp32_sets (name)
            SELECT 'esp32-1'
            WHERE NOT EXISTS (SELECT 1 FROM public.esp32_sets);
        ");

        if (!$result) {
            throw new Exception(pg_last_error($conn));
        }
    }

    public function down($conn)
    {
        // ไม่ลบข้อมูล (อาจถูกใช้งานและมี logs แล้ว)
    }
};

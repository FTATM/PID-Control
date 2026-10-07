<?php
// ใช้ร่วมกันระหว่าง HTTP API (api/update-setsById.php) และ MQTT worker (workers/mqtt_worker.php)
// เพื่อให้ทั้ง 2 ทางบันทึกข้อมูลเหมือนกันทุกอย่าง

class Esp32ServiceException extends Exception
{
    // $code = HTTP status code (400, 404, 500)
}

const ESP32_ALLOWED_FIELDS = ['name', 'sp', 'error', 'kp', 'ki', 'kd', 'pv', 'mv', 'sv', 'multi_kp', 'multi_ki', 'multi_kd', 'is_connected', 'is_resetwifi'];

/**
 * UPDATE esp32_sets ตาม field ที่ส่งมา แล้ว INSERT ลง esp32_logs (ใน transaction เดียวกัน)
 * คืนค่า row ล่าสุดของ esp32_sets
 *
 * @throws Esp32ServiceException
 */
function updateEsp32State($db, $id, array $data): array
{
    $setParts = [];
    $params = [];
    $index = 1;

    foreach ($data as $key => $value) {
        if (in_array($key, ESP32_ALLOWED_FIELDS)) {
            // JSON true/false -> 't'/'f' (pg_query_params แปลง false เป็น '' ซึ่ง PostgreSQL ไม่รับ)
            if (is_bool($value)) {
                $value = $value ? 't' : 'f';
            }
            $setParts[] = "$key = $" . $index;
            $params[] = $value;
            $index++;
        }
    }

    if (empty($setParts)) {
        throw new Esp32ServiceException("No valid fields to update", 400);
    }

    // append id
    $params[] = $id;

    $sql = "UPDATE esp32_sets
        SET " . implode(", ", $setParts) . "
        WHERE id = $" . $index . "
        RETURNING *;
    ";

    pg_query($db, "BEGIN");

    try {
        $result = pg_query_params($db, $sql, $params);

        if (!$result) {
            throw new Esp32ServiceException("Update failed", 500);
        }

        if (pg_num_rows($result) === 0) {
            throw new Esp32ServiceException("Data not found", 404);
        }

        $updated = pg_fetch_assoc($result);

        // log
        $sql_log = "INSERT INTO esp32_logs
            (esp32_id,sp,error,kp,ki,kd,pv,mv,sv,multi_kp,multi_ki,multi_kd,is_connected,is_resetwifi,created_at)
            VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,now());
        ";

        $params_log = [
            $updated['id'],
            $updated['sp'],
            $updated['error'],
            $updated['kp'],
            $updated['ki'],
            $updated['kd'],
            $updated['pv'],
            $updated['mv'],
            $updated['sv'],
            $updated['multi_kp'],
            $updated['multi_ki'],
            $updated['multi_kd'],
            $updated['is_connected'],
            $updated['is_resetwifi']
        ];

        $result_log = pg_query_params($db, $sql_log, $params_log);

        if (!$result_log) {
            throw new Esp32ServiceException("Insert Log failed", 500);
        }

        if (!pg_query($db, "COMMIT")) {
            throw new Esp32ServiceException("Commit failed", 500);
        }
    } catch (Throwable $e) {
        pg_query($db, "ROLLBACK");
        throw $e;
    }

    return $updated;
}

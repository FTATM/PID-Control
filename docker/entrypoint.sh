#!/bin/sh
set -e

APP_DIR=/var/www/html/PID

H="${DB_HOST:-db}"
P="${DB_PORT:-5432}"
N="${DB_NAME:-pid}"
U="${DB_USER:-postgres}"
W="${DB_PASS:-postgres}"   # ค่าเริ่มต้นเดียวกับ service db ใน docker-compose

# configs/config.php อ่านค่าจากไฟล์ .env ด้วย phpdotenv
# จึงสร้าง .env ภายใน container จาก environment ที่ docker-compose ส่งเข้ามา
cat > "$APP_DIR/.env" <<EOF
DB_HOST='$H'
DB_PORT='$P'
DB_NAME='$N'
DB_USER='$U'
DB_PASS='$W'
CAMERAS='${CAMERAS:-}'
APP_ENV='${APP_ENV:-production}'
APP_DEBUG='${APP_DEBUG:-false}'
MQTT_HOST='${MQTT_HOST:-mqtt}'
MQTT_PORT='${MQTT_PORT:-1883}'
MQTT_WS_PORT='${MQTT_WS_PORT:-9001}'
MQTT_USER='${MQTT_USER:-}'
MQTT_PASS='${MQTT_PASS:-}'
EOF
chown www-data:www-data "$APP_DIR/.env"

# phpdotenv (createImmutable) จะไม่เขียนทับตัวแปรที่มีอยู่แล้วใน environment
# ล้างออกเพื่อให้ไฟล์ .env เป็นแหล่งค่าเดียว
unset DB_HOST DB_PORT DB_NAME DB_USER DB_PASS CAMERAS APP_ENV APP_DEBUG \
      MQTT_HOST MQTT_PORT MQTT_WS_PORT MQTT_USER MQTT_PASS

# รอให้ PostgreSQL พร้อม
echo "Waiting for database at $H:$P ..."
until PGPASSWORD="$W" pg_isready -h "$H" -p "$P" -U "$U" >/dev/null 2>&1; do
  sleep 1
done

# รัน migration เฉพาะ container web (mqtt-worker ตั้ง RUN_MIGRATIONS=false กันรันชนกัน)
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  # migration_runner.php ต้องมีตาราง migrations อยู่ก่อน
  PGPASSWORD="$W" psql -q -h "$H" -p "$P" -U "$U" -d "$N" -c "
  CREATE TABLE IF NOT EXISTS public.migrations (
      id SERIAL PRIMARY KEY,
      version VARCHAR(255) NOT NULL UNIQUE,
      executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );" 2>&1 | grep -v 'already exists' || true

  echo "Running migrations ..."
  cd "$APP_DIR" && php migrate.php
fi

cd "$APP_DIR"

exec "$@"

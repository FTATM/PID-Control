#!/bin/sh
# สร้าง config ของ Mosquitto จาก environment แล้วเริ่ม broker
set -e

CONF=/mosquitto/config/mosquitto.conf
PASSWD=/mosquitto/config/passwd
mkdir -p /mosquitto/config /mosquitto/data /mosquitto/log

cat > "$CONF" <<EOF
persistence true
persistence_location /mosquitto/data/
log_dest stdout
log_type error
log_type warning
log_type notice
log_type information

# MQTT ปกติ (ESP32, PLC, backend)
listener 1883 0.0.0.0
protocol mqtt

# MQTT over WebSocket (สำหรับหน้าเว็บ/JavaScript)
listener 9001 0.0.0.0
protocol websockets
EOF

if [ -n "$MQTT_USER" ] && [ -n "$MQTT_PASS" ]; then
  rm -f "$PASSWD"
  touch "$PASSWD" && chmod 0700 "$PASSWD"
  mosquitto_passwd -b "$PASSWD" "$MQTT_USER" "$MQTT_PASS"
  chown mosquitto:mosquitto "$PASSWD" 2>/dev/null || true
  printf 'allow_anonymous false\npassword_file %s\n' "$PASSWD" >> "$CONF"
  echo "MQTT: authentication enabled (user: $MQTT_USER)"
else
  echo 'allow_anonymous true' >> "$CONF"
  echo "MQTT: WARNING anonymous access enabled (set MQTT_USER / MQTT_PASS in .env)"
fi

chown -R mosquitto:mosquitto /mosquitto/data /mosquitto/log 2>/dev/null || true
exec mosquitto -c "$CONF"

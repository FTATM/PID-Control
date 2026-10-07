// =====================================================================
// ESP32 MQTT test for PID Dashboard v1.1.0
//
// จำลอง PID loop (กระบวนการคล้ายฮีตเตอร์) แล้วส่งค่าผ่าน MQTT
//   publish   pid/esp32/{id}/state   JSON ทุก SEND_INTERVAL_MS
//   publish   pid/esp32/{id}/status  "online" (retain) + Last Will "offline"
//   subscribe pid/esp32/{id}/cmd     {"reset_wifi":true}
//
// Libraries (Arduino IDE > Library Manager):
//   - PubSubClient  by Nick O'Leary
//   - ArduinoJson   by Benoit Blanchon (v7)
// Board: ESP32 Dev Module, Serial Monitor 115200
// =====================================================================
#include <WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>

// ===================== Config =====================
const char* WIFI_SSID = "YOUR_WIFI";
const char* WIFI_PASS = "YOUR_WIFI_PASSWORD";

const char* MQTT_HOST = "192.168.1.44";   // LAN IP ของเครื่องที่รัน Docker (ไม่ใช่ localhost)
const uint16_t MQTT_PORT = 1883;
const char* MQTT_USER = "";               // เว้นว่างถ้า broker ไม่ได้ตั้งรหัสผ่าน
const char* MQTT_PASS = "";

const int ESP32_ID = 1;                         // ต้องตรงกับ id ใน esp32_sets
const unsigned long SEND_INTERVAL_MS = 200;     // ส่งทุก 0.2 วินาที (5 ครั้ง/วินาที)
const unsigned long SP_CHANGE_MS = 30000;       // สลับ SP ทุก 30 วินาที ให้เห็นกราฟขยับ

// ===================== MQTT =====================
char topicState[40];
char topicStatus[40];
char topicCmd[40];
char clientId[24];

WiFiClient net;
PubSubClient mqtt(net);

// ===================== Fake PID process =====================
float sp = 50.0;
float pv = 25.0;
float mv = 0.0;
float kp = 2.0, ki = 0.5, kd = 0.1;
float integral = 0.0;
float lastError = 0.0;

unsigned long sentCount = 0;
unsigned long failCount = 0;

void simulate(float dt) {
  // PID controller
  float error = sp - pv;
  integral = constrain(integral + error * dt, -100.0f, 100.0f);
  float derivative = (error - lastError) / dt;
  lastError = error;
  mv = constrain(kp * error + ki * integral + kd * derivative, 0.0f, 100.0f);

  // process: อุณหภูมิห้อง 25, MV 100% = +80 องศา, ตอบสนองแบบ first-order
  float target = 25.0 + mv * 0.8;
  pv += (target - pv) * dt * 0.3;
}

// ===================== WiFi =====================
void connectWifi() {
  if (WiFi.status() == WL_CONNECTED) return;

  Serial.printf("WiFi: connecting to %s", WIFI_SSID);
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.printf("\nWiFi: connected, IP %s\n", WiFi.localIP().toString().c_str());
}

// ===================== MQTT =====================
void onMessage(char* topic, byte* payload, unsigned int length) {
  Serial.printf("MQTT <- %s %.*s\n", topic, length, (char*)payload);

  JsonDocument doc;
  if (deserializeJson(doc, payload, length)) {
    Serial.println("cmd: invalid JSON");
    return;
  }

  if (doc["reset_wifi"] | false) {
    Serial.println("cmd: reset_wifi -> disconnect and reconnect WiFi");
    // firmware จริง: wifiManager.resetSettings(); ESP.restart();
    mqtt.publish(topicStatus, "offline", true);
    mqtt.disconnect();
    WiFi.disconnect();
    delay(1000);
  }
}

bool connectMqtt() {
  Serial.printf("MQTT: connecting to %s:%u as %s ... ", MQTT_HOST, MQTT_PORT, clientId);

  const char* user = strlen(MQTT_USER) ? MQTT_USER : nullptr;
  const char* pass = strlen(MQTT_PASS) ? MQTT_PASS : nullptr;

  // Last Will: ถ้า ESP32 หลุด broker จะส่ง "offline" (retain) แทน
  if (!mqtt.connect(clientId, user, pass, topicStatus, 0, true, "offline")) {
    // -2 = ต่อ IP/port ไม่ได้ (เช็ค IP, firewall, Docker)  4 = user/pass ผิด  5 = ไม่มีสิทธิ์
    Serial.printf("failed, rc=%d\n", mqtt.state());
    return false;
  }

  Serial.println("connected");
  mqtt.publish(topicStatus, "online", true);
  mqtt.subscribe(topicCmd, 1);
  return true;
}

void publishState() {
  JsonDocument doc;
  doc["sp"] = sp;
  doc["pv"] = roundf(pv * 100) / 100;
  doc["mv"] = roundf(mv * 100) / 100;
  doc["error"] = roundf((sp - pv) * 100) / 100;
  doc["kp"] = kp;
  doc["ki"] = ki;
  doc["kd"] = kd;

  char buf[256];
  serializeJson(doc, buf);

  if (mqtt.publish(topicState, buf)) {
    sentCount++;
  } else {
    failCount++;
  }
}

// ===================== Main =====================
void setup() {
  Serial.begin(115200);
  delay(500);

  snprintf(topicState, sizeof(topicState), "pid/esp32/%d/state", ESP32_ID);
  snprintf(topicStatus, sizeof(topicStatus), "pid/esp32/%d/status", ESP32_ID);
  snprintf(topicCmd, sizeof(topicCmd), "pid/esp32/%d/cmd", ESP32_ID);
  snprintf(clientId, sizeof(clientId), "esp32-%d", ESP32_ID);

  mqtt.setServer(MQTT_HOST, MQTT_PORT);
  mqtt.setCallback(onMessage);
  mqtt.setBufferSize(512);   // ค่าเริ่มต้น 256 อาจไม่พอ ทำให้ publish ล้มเหลวเงียบ ๆ

  connectWifi();
}

void loop() {
  connectWifi();

  static unsigned long lastTry = 0;
  if (!mqtt.connected() && millis() - lastTry > 3000) {
    lastTry = millis();
    connectMqtt();
  }
  mqtt.loop();

  // สลับ SP 50 <-> 70
  static unsigned long lastSpChange = 0;
  if (millis() - lastSpChange >= SP_CHANGE_MS) {
    lastSpChange = millis();
    sp = (sp == 50.0) ? 70.0 : 50.0;
    Serial.printf("SP -> %.0f\n", sp);
  }

  static unsigned long lastSend = 0;
  if (millis() - lastSend >= SEND_INTERVAL_MS) {
    lastSend = millis();
    simulate(SEND_INTERVAL_MS / 1000.0);
    if (mqtt.connected()) {
      publishState();
    }
  }

  // สรุปทุก 1 วินาที (ไม่ print ทุกครั้งที่ส่ง)
  static unsigned long lastLog = 0;
  if (millis() - lastLog >= 1000) {
    lastLog = millis();
    Serial.printf("SP=%.1f PV=%.2f MV=%.2f | sent=%lu fail=%lu | mqtt=%s\n",
                  sp, pv, mv, sentCount, failCount, mqtt.connected() ? "ok" : "down");
  }
}

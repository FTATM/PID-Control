# รัน PID-Control ด้วย Docker

## สิ่งที่ต้องมี
- Docker Desktop (Windows/Mac) หรือ Docker Engine + Compose plugin (Linux)

## วิธีรัน
```bash
git clone https://github.com/FTATM/PID-Control.git
cd PID-Control
cp .env.example .env      # แก้ DB_PASS / DB_NAME ตามต้องการ
docker compose up -d --build
```

เปิดเว็บที่ http://localhost:8080/PID/

ตอน start ครั้งแรก:
1. PostgreSQL สร้าง database และตาราง `migrations` ให้อัตโนมัติ
2. container `web` รอ DB พร้อม แล้วรัน `php migrate.php` ให้เอง (ทุกครั้งที่ start, รันเฉพาะ migration ใหม่)
3. container `mqtt-worker` เริ่มรับข้อมูลจาก ESP32 ผ่าน MQTT แล้วบันทึกลง DB

**ESP32 ตัวแรก (id = 1, ชื่อ `esp32-1`) ถูกสร้างให้อัตโนมัติ** โดย migration `20261008-000000_Seed_Default_Esp32.php` (สร้างเฉพาะตอนตาราง `esp32_sets` ว่าง ไม่กระทบข้อมูลเดิม)
ESP32 ใช้ id นี้ใน topic `pid/esp32/1/...` ได้ทันที

ถ้าต้องการเพิ่ม ESP32 ตัวถัดไป (id 2, 3, ...):
```powershell
curl.exe -X POST http://localhost:8080/PID/api/create-sets.php -H "Content-Type: application/json" -d '{\"name\":\"esp32-2\"}'
```

## หมายเหตุเรื่อง .env
- container `web` และ `mqtt-worker` อ่านค่าทั้งหมดจาก .env (`env_file: .env`) จึงใช้ .env ไฟล์เดิมร่วมกับ XAMPP ได้
- ยกเว้น 4 ค่าที่ compose กำหนดให้เสมอ (ค่าใน .env **ไม่มีผล** ภายใน Docker): `DB_HOST=db`, `DB_PORT=5432`, `MQTT_HOST=mqtt`, `MQTT_PORT=1883`
  - `MQTT_PORT` ใน .env ใช้กำหนด **port ฝั่งเครื่อง** ที่ ESP32 ต่อเข้ามา (เช่น `MQTT_PORT=1884` → ESP32 ต่อ `IP-เครื่อง:1884`) ส่วนภายใน Docker ยังเป็น 1883 เสมอ
  - `MQTT_WS_PORT` คือ port ฝั่งเครื่องสำหรับหน้าเว็บ (browser) ใช้ค่าจาก .env ตรง ๆ
- ค่า `DB_NAME`, `DB_USER`, `DB_PASS` จาก .env ใช้ทั้งฝั่งเว็บและ PostgreSQL (ถ้าเว้นว่าง `DB_PASS` จะใช้ `postgres`)
- ตัวแปรเสริม: `WEB_PORT` (ค่าเริ่มต้น 8080), `DB_EXPOSE_PORT` (ค่าเริ่มต้น 5433 สำหรับต่อ DBeaver/pgAdmin)
- ถ้าเปลี่ยน `DB_PASS` หลังจากรันครั้งแรกแล้ว ต้องลบ volume เดิม (`docker compose down -v`) เพราะ PostgreSQL ตั้งรหัสผ่านแค่ตอนสร้างครั้งแรก

## ESP32
แนะนำให้ส่งผ่าน **MQTT** (เร็วกว่า, หน้าเว็บอัปเดต realtime) ดูหัวข้อ MQTT ด้านล่าง
ตัวอย่าง firmware สำหรับทดสอบ: `firmware/esp32_mqtt_test/esp32_mqtt_test.ino`

แบบเดิม (HTTP) ยังใช้ได้: อุปกรณ์ต้องยิง API มาที่ IP ของเครื่องที่รัน Docker พร้อม port เช่น
`http://192.168.1.50:8080/PID/api/update-setsById.php?id=1`
(ถ้าอยากใช้ port 80 เหมือนเดิม ตั้ง `WEB_PORT=80` ใน .env)

## MQTT (Mosquitto)
Service `mqtt` เปิด 2 port:

| ใช้จาก | Host | Port |
|---|---|---|
| ESP32 / PLC / โปรแกรมบนเครื่องอื่น | IP-เครื่องที่รัน Docker | 1883 |
| หน้าเว็บ (JavaScript, MQTT over WebSocket) | IP-เครื่องที่รัน Docker | 9001 |
| โค้ด PHP ภายใน container `web` / `mqtt-worker` | `mqtt` | 1883 |

**Topics** (ดูรายละเอียดและตัวอย่างได้จากปุ่ม **MQTT Guide** มุมขวาล่างของหน้าเว็บ)

| Topic | ESP32 | Payload |
|---|---|---|
| `pid/esp32/{id}/state` | publish | JSON เช่น `{"sp":50,"pv":25.4,"mv":12.5}` |
| `pid/esp32/{id}/status` | publish (retain) + Last Will | `online` / `offline` (ข้อความธรรมดา ไม่ใช่ JSON) |
| `pid/esp32/{id}/cmd` | subscribe | `{"reset_wifi":true}` |

- `mqtt-worker` (image เดียวกับ `web`) subscribe `state` และ `status` แล้วบันทึกลง `esp32_sets` + `esp32_logs`
- หน้าเว็บต่อ broker ผ่าน WebSocket port `MQTT_WS_PORT` โดยตรง จึงอัปเดตทันทีที่ ESP32 ส่ง ถ้าไม่มีข้อมูล MQTT จะกลับไปอ่านจาก DB ทุก 1 วินาที
- **Windows Firewall:** ESP32 ต่อเข้ามาไม่ได้ถ้า port ถูกบล็อก (โดยเฉพาะเมื่อ network เป็น Public) เปิด PowerShell แบบ Administrator แล้วรัน
  ```powershell
  New-NetFirewallRule -DisplayName "PID MQTT" -Direction Inbound -Protocol TCP -LocalPort 1883,9001,8080 -Action Allow -Profile Any
  ```
- ตั้ง `MQTT_USER` / `MQTT_PASS` ใน .env เพื่อบังคับใช้รหัสผ่าน (แนะนำ) ถ้าเว้นว่างจะเปิดให้เชื่อมต่อแบบไม่ต้องล็อกอิน
- ค่า `MQTT_HOST`, `MQTT_PORT`, `MQTT_USER`, `MQTT_PASS` ถูกเขียนลง .env ของเว็บให้แล้ว อ่านจาก PHP ได้ด้วย `$_ENV['MQTT_HOST']`
- ข้อความที่ส่งแบบ retain และ session ถูกเก็บไว้ใน volume `mqttdata`

ทดสอบ:
```bash
docker compose exec mqtt mosquitto_sub -t 'pid/#' -v -u USER -P PASS
docker compose exec mqtt mosquitto_pub -t pid/esp32/1/state -m '{\"sp\":50,\"pv\":25.4}' -u USER -P PASS
```
(ไม่ได้ตั้งรหัสผ่าน ให้ตัด `-u USER -P PASS` ออก, ใน cmd.exe ใช้ `-m "{\"sp\":50}"` แทน single quote)

## Deploy ด้วย GitHub Container Registry (ghcr.io)

ทุกครั้งที่ push เข้า `master` ไฟล์ `.github/workflows/docker-publish.yml` จะ build image แล้วอัปโหลดขึ้น ghcr.io ให้อัตโนมัติ ได้ 2 image:
- `ghcr.io/ftatm/pid-control` (เว็บ PHP)
- `ghcr.io/ftatm/pid-control-mqtt` (Mosquitto)

รองรับทั้ง x86 (PC/VPS) และ ARM (Raspberry Pi)

**Tag ที่ได้**

| ทำอะไร | Tag ของ image |
|---|---|
| push เข้า master | `latest` และ `sha-xxxxxxx` |
| `git tag v1.0.0 && git push --tags` | `1.0.0` และ `sha-xxxxxxx` |

**ตั้งค่าครั้งแรก (ทำบน GitHub)**
1. push ไฟล์ทั้งหมดขึ้น repo แล้วเปิดแท็บ **Actions** รอให้ workflow เป็นสีเขียว (รอบแรกประมาณ 5–10 นาที)
2. ไปที่หน้าโปรไฟล์ → **Packages** จะเห็น `pid-control` และ `pid-control-mqtt`
3. เลือกว่า package จะเป็น public หรือ private ได้ที่ **Package settings** → **Change visibility**

**บนเซิร์ฟเวอร์ (ไม่ต้อง clone โค้ด)**

ต้องมีแค่ 2 ไฟล์: `docker-compose.prod.yml` และ `.env` (คัดลอกจาก `.env.example` แล้วแก้รหัสผ่าน)
```bash
# ถ้า package เป็น private ต้อง login ก่อน (ครั้งเดียว)
# ใช้ Personal Access Token (classic) ที่มีสิทธิ์ read:packages เป็นรหัสผ่าน
docker login ghcr.io -u <github-username>

docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d
```

**อัปเดตเวอร์ชันใหม่:** push โค้ดเข้า master แล้วรอให้ Actions build เสร็จ จากนั้นบนเซิร์ฟเวอร์สั่ง `pull` และ `up -d` อีกครั้ง

**ล็อกเวอร์ชัน:** ใส่ `IMAGE_TAG=1.0.0` ใน .env ของเซิร์ฟเวอร์ ถ้ามีปัญหาก็ย้อนกลับไปเวอร์ชันเก่าได้ทันที

## คำสั่งที่ใช้บ่อย
```bash
docker compose logs -f web              # ดู log เว็บ/migration
docker compose logs -f mqtt             # ดู log MQTT broker
docker compose logs -f mqtt-worker      # ดู log การบันทึกข้อมูลจาก ESP32 (online/offline, error)
docker compose exec web php migrate.php # รัน migration เอง
docker compose exec db psql -U postgres -d pid
docker compose down                     # หยุด (ข้อมูลยังอยู่)
docker compose down -v                  # หยุดและลบข้อมูล DB ทั้งหมด
```

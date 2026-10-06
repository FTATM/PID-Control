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

## หมายเหตุเรื่อง .env
- `DB_HOST` ใน .env **ไม่มีผล** ใน Docker: compose กำหนดเป็น `db` ให้เสมอ จึงใช้ .env ไฟล์เดิมร่วมกับ XAMPP ได้
- ค่า `DB_NAME`, `DB_USER`, `DB_PASS`, `CAMERAS` จาก .env จะถูกส่งเข้า container ทั้งฝั่งเว็บและ PostgreSQL
- ตัวแปรเสริม: `WEB_PORT` (ค่าเริ่มต้น 8080), `DB_EXPOSE_PORT` (ค่าเริ่มต้น 5433 สำหรับต่อ DBeaver/pgAdmin)
- ถ้าเปลี่ยน `DB_PASS` หลังจากรันครั้งแรกแล้ว ต้องลบ volume เดิม (`docker compose down -v`) เพราะ PostgreSQL ตั้งรหัสผ่านแค่ตอนสร้างครั้งแรก

## ESP32
อุปกรณ์ต้องยิง API มาที่ IP ของเครื่องที่รัน Docker พร้อม port เช่น
`http://192.168.1.50:8080/PID/api/update-setsById.php?id=1`
(ถ้าอยากใช้ port 80 เหมือนเดิม ตั้ง `WEB_PORT=80` ใน .env)

## MQTT (Mosquitto)
Service `mqtt` เปิด 2 port:

| ใช้จาก | Host | Port |
|---|---|---|
| ESP32 / PLC / โปรแกรมบนเครื่องอื่น | IP-เครื่องที่รัน Docker | 1883 |
| หน้าเว็บ (JavaScript, MQTT over WebSocket) | IP-เครื่องที่รัน Docker | 9001 |
| โค้ด PHP ภายใน container `web` | `mqtt` | 1883 |

- ตั้ง `MQTT_USER` / `MQTT_PASS` ใน .env เพื่อบังคับใช้รหัสผ่าน (แนะนำ) ถ้าเว้นว่างจะเปิดให้เชื่อมต่อแบบไม่ต้องล็อกอิน
- ค่า `MQTT_HOST`, `MQTT_PORT`, `MQTT_USER`, `MQTT_PASS` ถูกเขียนลง .env ของเว็บให้แล้ว อ่านจาก PHP ได้ด้วย `$_ENV['MQTT_HOST']`
- ข้อความที่ส่งแบบ retain และ session ถูกเก็บไว้ใน volume `mqttdata`

ทดสอบ:
```bash
docker compose exec mqtt mosquitto_sub -t 'pid/#' -v -u USER -P PASS
docker compose exec mqtt mosquitto_pub -t pid/esp32/1/pv -m 25.4 -u USER -P PASS
```

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
docker compose exec web php migrate.php # รัน migration เอง
docker compose exec db psql -U postgres -d pid
docker compose down                     # หยุด (ข้อมูลยังอยู่)
docker compose down -v                  # หยุดและลบข้อมูล DB ทั้งหมด
```

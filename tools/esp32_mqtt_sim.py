"""
ESP32 MQTT simulator for PID Dashboard

ทำงานเหมือน firmware/esp32_mqtt_test/esp32_mqtt_test.ino แต่รันบนคอมพิวเตอร์ (ไม่ต้องมีบอร์ด)
  publish   pid/esp32/{id}/state   JSON ทุก --interval วินาที
  publish   pid/esp32/{id}/status  "online" (retain) + Last Will "offline"
  subscribe pid/esp32/{id}/cmd     {"reset_wifi":true}

ติดตั้ง:  pip install paho-mqtt
ใช้งาน:   python tools/esp32_mqtt_sim.py --host 192.168.1.44
          python tools/esp32_mqtt_sim.py --host localhost --id 1 --interval 0.1
หยุด:     Ctrl+C (ส่ง "offline" ก่อนออก)
"""
import argparse
import json
import random
import time

import paho.mqtt.client as mqtt


# ===================== Fake PID process =====================
class FakePid:
    def __init__(self, kp, ki, kd):
        self.sp = 50.0
        self.pv = 25.0
        self.mv = 0.0
        self.kp, self.ki, self.kd = kp, ki, kd
        self.integral = 0.0
        self.last_error = 0.0

    def step(self, dt, noise):
        # PID controller
        error = self.sp - self.pv
        self.integral = clamp(self.integral + error * dt, -100.0, 100.0)
        derivative = (error - self.last_error) / dt
        self.last_error = error
        self.mv = clamp(self.kp * error + self.ki * self.integral + self.kd * derivative, 0.0, 100.0)

        # process: อุณหภูมิห้อง 25, MV 100% = +80 องศา, ตอบสนองแบบ first-order
        target = 25.0 + self.mv * 0.8
        self.pv += (target - self.pv) * dt * 0.3
        if noise:
            self.pv += random.uniform(-noise, noise)

    def state(self):
        return {
            "sp": round(self.sp, 2),
            "pv": round(self.pv, 2),
            "mv": round(self.mv, 2),
            "error": round(self.sp - self.pv, 2),
            "kp": self.kp,
            "ki": self.ki,
            "kd": self.kd,
            "relay": 1 if self.mv >= 50 else 0,
        }


def clamp(value, low, high):
    return max(low, min(value, high))


def make_client(client_id):
    # รองรับทั้ง paho-mqtt 1.x และ 2.x
    if hasattr(mqtt, "CallbackAPIVersion"):
        return mqtt.Client(mqtt.CallbackAPIVersion.VERSION2, client_id=client_id)
    return mqtt.Client(client_id=client_id)


def main():
    parser = argparse.ArgumentParser(description="Simulate an ESP32 sending PID data over MQTT")
    parser.add_argument("--host", default="localhost", help="IP ของเครื่องที่รัน Docker / broker")
    parser.add_argument("--port", type=int, default=1883, help="MQTT_PORT ใน .env (ไม่ใช่ WebSocket 9001)")
    parser.add_argument("--user", default="", help="MQTT_USER (เว้นว่างถ้าไม่มี)")
    parser.add_argument("--password", default="", help="MQTT_PASS (เว้นว่างถ้าไม่มี)")
    parser.add_argument("--id", type=int, default=1, help="ต้องตรงกับ id ใน esp32_sets")
    parser.add_argument("--interval", type=float, default=0.2, help="ส่งทุกกี่วินาที (default 0.2 = 5 ครั้ง/วินาที)")
    parser.add_argument("--sp-change", type=float, default=30, help="สลับ SP 50 <-> 70 ทุกกี่วินาที")
    parser.add_argument("--noise", type=float, default=0.0, help="สุ่ม noise ใส่ PV (เช่น 0.3)")
    parser.add_argument("--kp", type=float, default=2.0)
    parser.add_argument("--ki", type=float, default=0.5)
    parser.add_argument("--kd", type=float, default=0.1)
    args = parser.parse_args()

    topic_state = f"pid/esp32/{args.id}/state"
    topic_status = f"pid/esp32/{args.id}/status"
    topic_cmd = f"pid/esp32/{args.id}/cmd"

    client = make_client(f"esp32-sim-{args.id}")
    if args.user:
        client.username_pw_set(args.user, args.password or None)
    # Last Will: ถ้า script หลุด broker จะส่ง "offline" (retain) แทน
    client.will_set(topic_status, "offline", qos=0, retain=True)
    client.reconnect_delay_set(min_delay=1, max_delay=5)

    def on_connect(client, userdata, flags, rc, *extra):
        # rc: 0 = สำเร็จ, 4/5 = user/pass ผิดหรือไม่มีสิทธิ์
        if rc != 0:
            print(f"MQTT: connect failed, rc={rc}")
            return
        print(f"MQTT: connected to {args.host}:{args.port}")
        client.publish(topic_status, "online", qos=1, retain=True)
        client.subscribe(topic_cmd, qos=1)

    def on_disconnect(client, userdata, *extra):
        print("MQTT: disconnected, reconnecting ...")

    def on_message(client, userdata, msg):
        payload = msg.payload.decode(errors="replace")
        print(f"MQTT <- {msg.topic} {payload}")
        try:
            cmd = json.loads(payload)
        except ValueError:
            print("cmd: invalid JSON")
            return
        if isinstance(cmd, dict) and cmd.get("reset_wifi"):
            # จำลอง ESP32 รีเซ็ต WiFi: offline สักครู่แล้วกลับมา online
            print("cmd: reset_wifi -> offline 3s then reconnect")
            client.publish(topic_status, "offline", qos=1, retain=True)
            userdata["paused_until"] = time.time() + 3

    userdata = {"paused_until": 0.0, "was_paused": False}
    client.user_data_set(userdata)
    client.on_connect = on_connect
    client.on_disconnect = on_disconnect
    client.on_message = on_message

    while True:
        try:
            print(f"MQTT: connecting to {args.host}:{args.port} as esp32-sim-{args.id} ...")
            client.connect(args.host, args.port, keepalive=15)
            break
        except OSError as err:
            # connection refused / timeout = เช็ค IP, port, firewall, Docker
            print(f"MQTT: {err}, retry in 3s")
            time.sleep(3)

    client.loop_start()

    pid = FakePid(args.kp, args.ki, args.kd)
    sent = 0
    last_sp_change = last_log = time.time()
    next_send = time.time()

    try:
        while True:
            now = time.time()

            if now - last_sp_change >= args.sp_change:
                last_sp_change = now
                pid.sp = 70.0 if pid.sp == 50.0 else 50.0
                print(f"SP -> {pid.sp:.0f}")

            paused = now < userdata["paused_until"]
            if userdata["was_paused"] and not paused:
                client.publish(topic_status, "online", qos=1, retain=True)
                print("reset_wifi done -> online")
            userdata["was_paused"] = paused

            pid.step(args.interval, args.noise)
            if client.is_connected() and not paused:
                # state ห้าม retain (หน้าเว็บ/worker จะข้ามข้อความ retained)
                client.publish(topic_state, json.dumps(pid.state()), qos=0, retain=False)
                sent += 1

            if now - last_log >= 1:
                last_log = now
                s = pid.state()
                print(f"SP={s['sp']:.1f} PV={s['pv']:.2f} MV={s['mv']:.2f} relay={s['relay']} "
                      f"| sent={sent} | mqtt={'ok' if client.is_connected() else 'down'}")

            next_send += args.interval
            time.sleep(max(0.0, next_send - time.time()))
    except KeyboardInterrupt:
        print("\nstop -> offline")
        client.publish(topic_status, "offline", qos=1, retain=True).wait_for_publish(2)
        client.loop_stop()
        client.disconnect()


if __name__ == "__main__":
    main()

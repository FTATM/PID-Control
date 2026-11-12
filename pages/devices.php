<?php
require_once __DIR__ . '/../configs/config.php';

$pageTitle = "Devices";
$subTitle = "Device Management";

// ค่าสำหรับต่อ MQTT over WebSocket จากหน้าเว็บ (ใช้ส่งคำสั่ง Reset WiFi)
$mqttWebConfig = [
    'port' => (int) ($_ENV['MQTT_WS_PORT'] ?? 9001),
    'user' => $_ENV['MQTT_USER'] ?? '',
    'pass' => $_ENV['MQTT_PASS'] ?? '',
];
?>

<!DOCTYPE html>
<html class="dark" lang="th">
<?php include("../scripts/ref.html"); ?>
<?php include("../styles/css-default.html"); ?>
<?php include("../styles/css-icon.html"); ?>

<head>
    <title><?php echo $pageTitle ?></title>
</head>

<body class="min-h-screen flex flex-col main-container">

    <?php include "../components/header.php"; ?>

    <main class="flex-1 bg-[#f5f5f5] dark:bg-canvas-dark p-6">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center justify-between mb-4">
                <a href="home.php" class="flex items-center gap-1 text-sm font-bold text-stone-500 dark:text-stone-300 hover:text-primary">
                    <span class="material-icons-outlined text-base">arrow_back</span> กลับหน้าควบคุม
                </a>
                <button onclick="openForm()" class="flex items-center gap-1 px-4 py-2 rounded-lg bg-primary text-white text-sm font-bold hover:opacity-90">
                    <span class="material-icons-outlined text-base">add</span> เพิ่มอุปกรณ์
                </button>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-stone-200 dark:border-slate-700 overflow-x-auto">
                <table class="w-full text-sm text-left dark:text-white">
                    <thead class="text-xs uppercase text-stone-500 dark:text-stone-300 border-b border-stone-200 dark:border-slate-700">
                        <tr>
                            <th class="px-4 py-3">Device ID</th>
                            <th class="px-4 py-3">ชื่อ</th>
                            <th class="px-4 py-3">สถานะ</th>
                            <th class="px-4 py-3">PV</th>
                            <th class="px-4 py-3">SP</th>
                            <th class="px-4 py-3">ข้อมูลล่าสุด</th>
                            <th class="px-4 py-3 text-right">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="device-body"></tbody>
                </table>
                <p id="empty" class="hidden p-6 text-center text-stone-400">ยังไม่มีอุปกรณ์</p>
            </div>
        </div>
    </main>

    <!-- Modal เพิ่ม/แก้ไข -->
    <div id="form-modal" class="hidden fixed inset-0 z-50 bg-black/50 items-center justify-center">
        <div class="bg-white dark:bg-slate-900 dark:text-white rounded-xl p-6 w-[90%] max-w-sm shadow-xl">
            <h2 id="form-title" class="text-lg font-bold mb-4">เพิ่มอุปกรณ์</h2>
            <label class="text-xs font-bold uppercase text-stone-500 dark:text-stone-300">Device ID (ห้ามซ้ำ, A-Z a-z 0-9 _ -)</label>
            <input id="form-device-id" type="text" maxlength="64" class="w-full mt-1 mb-4 rounded-lg border border-stone-300 dark:border-slate-600 dark:bg-slate-800 px-3 py-2 disabled:opacity-60" placeholder="esp32-2">
            <label class="text-xs font-bold uppercase text-stone-500 dark:text-stone-300">ชื่ออุปกรณ์</label>
            <input id="form-name" type="text" maxlength="100" class="w-full mt-1 mb-4 rounded-lg border border-stone-300 dark:border-slate-600 dark:bg-slate-800 px-3 py-2" placeholder="Boiler 1">
            <div class="flex justify-end gap-2">
                <button onclick="closeForm()" class="px-4 py-2 rounded-lg bg-stone-100 dark:bg-slate-800 text-sm font-bold">ยกเลิก</button>
                <button onclick="saveForm()" class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-bold">บันทึก</button>
            </div>
        </div>
    </div>

    <?php include "../components/footer.php"; ?>
    <?php include "../scripts/js.html"; ?>
    <script src="https://cdn.jsdelivr.net/npm/mqtt@5/dist/mqtt.min.js"></script>
    <script>const MQTT_CONFIG = <?php echo json_encode($mqttWebConfig); ?>;</script>
    <script>
        let devices = [];
        let mqttClient = null;
        let editingId = null;

        document.addEventListener('DOMContentLoaded', () => {
            loadDevices();
            setInterval(loadDevices, 5000);
            connectMqtt();
        });

        function connectMqtt() {
            if (typeof mqtt === 'undefined') return;
            const protocol = location.protocol === 'https:' ? 'wss' : 'ws';
            mqttClient = mqtt.connect(`${protocol}://${location.hostname}:${MQTT_CONFIG.port}`, {
                username: MQTT_CONFIG.user || undefined,
                password: MQTT_CONFIG.pass || undefined,
                reconnectPeriod: 3000,
                connectTimeout: 5000,
            });
            mqttClient.on('error', (err) => console.error('MQTT error: ' + err.message));
        }

        async function resetWifi(deviceId) {
            const d = devices.find(x => x.device_id === deviceId);
            const ok = await Swal.fire({
                icon: 'warning',
                title: 'Reset WiFi?',
                text: `ส่งคำสั่ง Reset WiFi ไปที่ "${d?.name ?? deviceId}" (${deviceId})`,
                showCancelButton: true,
                confirmButtonText: 'Reset',
                cancelButtonText: 'ยกเลิก'
            });
            if (!ok.isConfirmed) return;

            // 1) MQTT ก่อน (ESP32 ที่ใช้ MQTT ได้รับทันที)
            let sentMqtt = false;
            if (mqttClient?.connected) {
                mqttClient.publish(`pid/esp32/${deviceId}/cmd`, JSON.stringify({ reset_wifi: true }), { qos: 1 });
                sentMqtt = true;
            }

            // 2) บันทึก flag ลง DB (ESP32 ที่ใช้ HTTP อ่าน is_resetwifi จาก DB)
            let savedDb = false;
            try {
                const res = await fetch(`../api/update-setsById.php?id=${encodeURIComponent(deviceId)}`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ is_connected: 'f', is_resetwifi: 't' })
                });
                const raw = await res.json();
                savedDb = res.ok && raw.success === true;
            } catch (err) {
                console.error('Reset Wifi Error:', err);
            }

            if (!sentMqtt && !savedDb) {
                Swal.fire({ icon: 'error', title: 'Reset WiFi failed', text: 'Cannot reach the MQTT broker or the API.' });
            } else if (!sentMqtt) {
                Swal.fire({ icon: 'warning', title: 'Saved to DB only', text: 'MQTT broker is not connected, so an ESP32 using MQTT will not receive this command.' });
            } else {
                Swal.fire({ icon: 'success', title: 'Reset WiFi sent', toast: true, position: 'top-end', timer: 2500, showConfirmButton: false });
            }
            loadDevices();
        }

        // ใส่ค่าใน onclick อย่างปลอดภัย
        function jsArg(v) {
            return esc(JSON.stringify(String(v)));
        }

        function esc(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        async function loadDevices() {
            try {
                const res = await fetch('../api/fetch-sets.php');
                const raw = await res.json();
                devices = raw.data ?? [];
                render();
            } catch (err) {
                console.error(err);
            }
        }

        function render() {
            const body = document.getElementById('device-body');
            document.getElementById('empty').classList.toggle('hidden', devices.length > 0);
            body.innerHTML = devices.map(d => {
                const on = d.is_connected === 't' || d.is_connected === true;
                return `
                <tr class="border-b border-stone-100 dark:border-slate-800">
                    <td class="px-4 py-3 font-mono">${esc(d.device_id)}</td>
                    <td class="px-4 py-3 font-bold">${esc(d.name)}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1 text-xs">
                            <span class="w-2 h-2 rounded-full ${on ? 'bg-green-500' : 'bg-red-500'}"></span>${on ? 'Online' : 'Offline'}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-mono">${esc(d.pv)}</td>
                    <td class="px-4 py-3 font-mono">${esc(d.sp)}</td>
                    <td class="px-4 py-3 text-xs text-stone-500 dark:text-stone-300">${d.last_seen ? esc(d.last_seen.substring(0, 19)) : '-'}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="home.php?id=${encodeURIComponent(d.device_id)}" title="เปิดหน้าควบคุม" class="inline-block p-1 text-blue-500 hover:text-blue-700"><span class="material-icons-outlined">open_in_new</span></a>
                        <button onclick="resetWifi(${jsArg(d.device_id)})" ${on ? '' : 'disabled'} title="${on ? 'Reset WiFi' : 'Reset WiFi (ใช้ได้เมื่อ Online)'}" class="p-1 text-amber-500 hover:text-amber-700 disabled:opacity-30 disabled:cursor-not-allowed"><span class="material-icons-outlined">wifi_off</span></button>
                        <button onclick="openForm(${jsArg(d.device_id)})" title="แก้ไขชื่อ" class="p-1 text-stone-500 dark:text-stone-300 hover:text-primary"><span class="material-icons-outlined">edit</span></button>
                        <button onclick="removeDevice(${jsArg(d.device_id)})" title="ลบ" class="p-1 text-red-500 hover:text-red-700"><span class="material-icons-outlined">delete</span></button>
                    </td>
                </tr>`;
            }).join('');
        }

        function openForm(id = null) {
            editingId = id;
            const d = devices.find(x => x.device_id === id);
            document.getElementById('form-title').textContent = id ? `แก้ไขอุปกรณ์ ${id}` : 'เพิ่มอุปกรณ์';
            const idInput = document.getElementById('form-device-id');
            idInput.value = id ?? '';
            idInput.disabled = !!id;   // device_id แก้ภายหลังไม่ได้ (ผูกกับ MQTT topic)
            document.getElementById('form-name').value = d ? d.name : '';
            const m = document.getElementById('form-modal');
            m.classList.remove('hidden');
            m.classList.add('flex');
            document.getElementById('form-name').focus();
        }

        function closeForm() {
            const m = document.getElementById('form-modal');
            m.classList.add('hidden');
            m.classList.remove('flex');
        }

        async function saveForm() {
            const name = document.getElementById('form-name').value.trim();
            const deviceId = document.getElementById('form-device-id').value.trim();
            if (!editingId && !/^[A-Za-z0-9_-]{1,64}$/.test(deviceId)) return alert('Device ID ใช้ได้เฉพาะ A-Z a-z 0-9 _ - (ไม่เกิน 64 ตัว)');
            if (!name) return alert('กรุณากรอกชื่ออุปกรณ์');

            const url = editingId ? `../api/update-setsById.php?id=${encodeURIComponent(editingId)}` : '../api/create-sets.php';
            try {
                const res = await fetch(url, {
                    method: editingId ? 'PATCH' : 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(editingId ? { name } : { device_id: deviceId, name })
                });
                const raw = await res.json();
                if (!raw.success) return alert(raw.message || 'บันทึกไม่สำเร็จ');
                closeForm();
                loadDevices();
            } catch (err) {
                alert('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้');
            }
        }

        async function removeDevice(id) {
            const d = devices.find(x => x.device_id === id);
            if (!confirm(`ลบอุปกรณ์ "${d?.name ?? id}" (Device ID ${id}) และประวัติข้อมูลทั้งหมด?`)) return;
            try {
                const res = await fetch(`../api/delete-setsById.php?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
                const raw = await res.json();
                if (!raw.success) return alert(raw.message || 'ลบไม่สำเร็จ');
                loadDevices();
            } catch (err) {
                alert('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้');
            }
        }
    </script>
</body>

</html>

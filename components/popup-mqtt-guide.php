<?php
// คู่มือ MQTT Topics (เปิดจากปุ่ม "MQTT Guide" ในหน้า home)
// ต้องมี $mqttWebConfig จาก pages/home.php
$guideId = (int) ($_GET['id'] ?? 1);
$guideHost = htmlspecialchars(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
$guideWsPort = (int) ($mqttWebConfig['port'] ?? 9001);
$guideAuth = ($mqttWebConfig['user'] ?? '') !== '' ? ' -u USER -P PASS' : '';

$guideTopics = [
    [
        'topic' => "pid/esp32/$guideId/state",
        'dir' => 'ESP32 publishes → Server',
        'qos' => '0',
        'retain' => 'No',
        'desc' => 'Live values. Saved to DB by the worker and shown on this page instantly. Send only the fields that changed or all of them.',
        'payload' => '{"sp":50,"pv":25.4,"mv":12.5,"error":24.6,"kp":1.2,"ki":0.5,"kd":0.1}',
    ],
    [
        'topic' => "pid/esp32/$guideId/status",
        'dir' => 'ESP32 publishes → Server',
        'qos' => '0 or 1',
        'retain' => 'Yes',
        'desc' => 'Connection status as plain text (not JSON). Publish "online" with retain after connecting, and set "offline" (retain) as the Last Will so the broker sends it if the ESP32 dies.',
        'payload' => 'online  |  offline',
    ],
    [
        'topic' => "pid/esp32/$guideId/cmd",
        'dir' => 'Server → ESP32 subscribes',
        'qos' => '1',
        'retain' => 'No',
        'desc' => 'Commands from the dashboard. ESP32 must subscribe to this topic.',
        'payload' => '{"reset_wifi":true}',
    ],
];

$guideFields = 'name, sp, error, kp, ki, kd, pv, mv, sv, multi_kp, multi_ki, multi_kd, is_connected, is_resetwifi, relay';
?>
<div id="popup-mqtt-guide" onclick="if (event.target === this) hidePopup('popup-mqtt-guide')"
    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm hidden">
    <div class="bg-white dark:bg-slate-900 w-full max-w-3xl max-h-[85vh] rounded-[2rem] shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden">

        <!-- ===== Header ===== -->
        <div class="px-8 py-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="material-icons-outlined text-primary">hub</span>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">MQTT Guide</h2>
                <span id="mqtt-mode" class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">-</span>
            </div>
            <button onclick="hidePopup('popup-mqtt-guide')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                <span class="material-icons-round">close</span>
            </button>
        </div>

        <div class="px-8 py-6 space-y-6 overflow-y-auto text-sm text-slate-700 dark:text-slate-300">

            <!-- ===== Connection ===== -->
            <section>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Connection</h3>
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-3">
                        <div class="text-[11px] text-slate-400">Broker host</div>
                        <div class="font-mono font-bold dark:text-white"><?php echo $guideHost; ?></div>
                    </div>
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-3">
                        <div class="text-[11px] text-slate-400">ESP32 / PLC (MQTT)</div>
                        <div class="font-mono font-bold dark:text-white">port 1883</div>
                    </div>
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-3">
                        <div class="text-[11px] text-slate-400">Web browser (WebSocket)</div>
                        <div class="font-mono font-bold dark:text-white">port <?php echo $guideWsPort; ?></div>
                    </div>
                </div>
                <p class="text-xs text-slate-400 mt-2">
                    ESP32 must use the <b>LAN IP</b> of the machine running the broker (e.g. 192.168.1.50), not <code>localhost</code>.
                    Showing data for ESP32 id <b class="font-mono"><?php echo $guideId; ?></b> (change with <code>?id=</code> in the URL).
                </p>
            </section>

            <!-- ===== Topics ===== -->
            <section>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Topics</h3>
                <div class="space-y-3">
                    <?php foreach ($guideTopics as $t): ?>
                        <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <code class="font-mono font-bold text-primary"><?php echo $t['topic']; ?></code>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-300"><?php echo $t['dir']; ?></span>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">QoS <?php echo $t['qos']; ?></span>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">Retain: <?php echo $t['retain']; ?></span>
                            </div>
                            <p class="text-xs mb-2"><?php echo $t['desc']; ?></p>
                            <pre class="font-mono text-xs bg-slate-50 dark:bg-slate-800/50 rounded-lg px-3 py-2 overflow-x-auto"><?php echo htmlspecialchars($t['payload']); ?></pre>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-slate-400 mt-2">
                    Fields accepted in <code>state</code> (same as the HTTP API): <span class="font-mono"><?php echo $guideFields; ?></span>.
                    Unknown fields are ignored. Wildcard to watch everything: <code>pid/#</code>
                </p>
            </section>

            <!-- ===== ESP32 tips ===== -->
            <section>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">ESP32 Tips</h3>
                <ul class="list-disc pl-5 space-y-1 text-xs">
                    <li>Library <b>PubSubClient</b>: call <code>client.setBufferSize(512)</code>. The default 256 bytes is too small for the full JSON and publish fails silently.</li>
                    <li>Connect with Last Will: topic <code>pid/esp32/<?php echo $guideId; ?>/status</code>, message <code>offline</code> (plain text), retain <code>true</code>. Then publish <code>online</code> with retain <code>true</code>.<br>
                        <code class="whitespace-nowrap">client.connect("esp32-<?php echo $guideId; ?>", user, pass, "pid/esp32/<?php echo $guideId; ?>/status", 0, true, "offline");</code><br>
                        <code class="whitespace-nowrap">client.publish("pid/esp32/<?php echo $guideId; ?>/status", "online", true);</code></li>
                    <li>Send <code>state</code> at <b>2–5 times per second</b>. Every message is saved as a row in <code>esp32_logs</code>.</li>
                    <li>Use a unique client id per device, e.g. <code>esp32-<?php echo $guideId; ?></code>. Two devices with the same id kick each other off.</li>
                    <li>Send numbers as numbers (<code>25.4</code>) and booleans as <code>true</code>/<code>false</code>.</li>
                    <li>The HTTP API still works. If no MQTT data arrives for 3× the ESP32 send interval (min 3 s, max 30 s), this page falls back to reading the DB every 1 s.</li>
                </ul>
            </section>

            <!-- ===== Test commands ===== -->
            <section>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Test from PC (PowerShell)</h3>
                <div class="space-y-2">
                    <div>
                        <div class="text-[11px] text-slate-400 mb-1">Watch all messages</div>
                        <pre class="font-mono text-xs bg-slate-50 dark:bg-slate-800/50 rounded-lg px-3 py-2 overflow-x-auto">docker exec -it pid-mqtt mosquitto_sub -t "pid/#" -v<?php echo $guideAuth; ?></pre>
                    </div>
                    <div>
                        <div class="text-[11px] text-slate-400 mb-1">Fake an ESP32 sending values</div>
                        <pre class="font-mono text-xs bg-slate-50 dark:bg-slate-800/50 rounded-lg px-3 py-2 overflow-x-auto">docker exec pid-mqtt mosquitto_pub -t pid/esp32/<?php echo $guideId; ?>/state -m '{\"sp\":50,\"pv\":25.4}'<?php echo $guideAuth; ?></pre>
                    </div>
                    <div>
                        <div class="text-[11px] text-slate-400 mb-1">Run the worker (saves MQTT data to DB) on local dev</div>
                        <pre class="font-mono text-xs bg-slate-50 dark:bg-slate-800/50 rounded-lg px-3 py-2 overflow-x-auto">php workers/mqtt_worker.php</pre>
                    </div>
                </div>
                <p class="text-xs text-slate-400 mt-2">In cmd.exe use double quotes instead: <code>-m "{\"sp\":50}"</code>. Single quotes become part of the message and the worker skips it.</p>
            </section>
        </div>
    </div>
</div>

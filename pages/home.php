<?php
$pageTitle = "PID Control";
$subTitle = "Realtime Control Project";
?>

<!DOCTYPE html>
<html class="light" lang="th">
<?php include("../scripts/ref.html"); ?>
<?php include("../styles/css-default.html"); ?>
<?php include("../styles/css-icon.html"); ?>

<head>
    <title><?php echo $pageTitle ?></title>
</head>

<body class="h-screen flex flex-col overflow-hidden main-container">

    <?php include "../components/header.php"; ?>
    <!-- Main Content -->
    <main class="flex-1 relative overflow-hidden grid-bg bg-canvas-light dark:bg-canvas-dark">
        <!-- เส้นโยงกลับ -->
        <div class="feedback-line"></div>

        <div class="absolute right-[42.01%] top-[63%]">
            <div class="w-[8vw] bg-blue-50 border-2 border-blue-200 rounded-2xl p-4 shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-bold text-blue-500 dark:text-white-400 uppercase tracking-tight">PROCESS (PV)</span>
                    <span class="material-icons-outlined text-blue-400 text-sm">track_changes</span>
                </div>
                <div id="sv" class="text-3xl font-mono font-bold text-stone-600 dark:text-white-300">0</div>
            </div>
        </div>

        <div class="flex items-center w-full p-[3vw]">
            <!-- ==== SETPOINT (SP) ==== -->
            <div class="w-[8vw] bg-red-50 dark:bg-red-950/20 border-2 border-red-200 dark:border-red-900/50 rounded-2xl p-4 shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-bold text-black-500 dark:text-white-400 uppercase tracking-tight">Setpoint (SP)</span>
                    <span class="material-icons-outlined text-red-400 text-sm">track_changes</span>
                </div>
                <div id="sp" class="text-3xl font-mono font-bold text-stone-600 dark:text-white-300">30.24</div>
            </div>
            <div class="arrow-line flex-1"></div>
            <div class="w-[2vw] h-[2vw] rounded-full border-2 text-[2vw] flex justify-center items-center border border-slate-300 shadow-sm z-10">
                <span class="text-xs font-bold">Σ</span>
            </div>
            <div class="arrow-line flex-1"></div>
            <!-- ==== Error ==== -->
            <div class="w-[8vw] bg-orange-50 dark:bg-orange-950/20 border-2 border-orange-200 dark:border-orange-900/50 rounded-2xl p-4 shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-bold text-orange-500 dark:text-orange-400 uppercase tracking-tight">Error (ERR)</span>
                    <span class="material-icons-outlined text-orange-400 text-sm">warning_amber</span>
                </div>
                <div id="error" class="text-3xl font-mono font-bold text-orange-600 dark:text-orange-300">157.24</div>
            </div>
            <div class="arrow-line flex-1"></div>
            <div class="min-h-[40vh] flex gap-[0.25vw]">
                <div class="flex flex-col justify-between">
                    <!-- ==== KP ==== -->
                    <div class="w-[8vw] bg-white dark:bg-red-950/20 border-2 border-slate-200 dark:border-red-900/50 rounded-2xl p-4 shadow-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold text-black-500 dark:text-white-400 uppercase tracking-tight">KP (PROP)</span>
                            <span class="material-icons-outlined text-slate-400 text-sm">track_changes</span>
                        </div>
                        <div id="kp" class="text-3xl font-mono font-bold text-stone-600 dark:text-white-300">0.00</div>
                    </div>
                    <!-- ==== KI ==== -->
                    <div class="w-[8vw] bg-white dark:bg-red-950/20 border-2 border-slate-200 dark:border-red-900/50 rounded-2xl p-4 shadow-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold text-black-500 dark:text-white-400 uppercase tracking-tight">KI (INTEG)</span>
                            <span class="material-icons-outlined text-slate-400 text-sm">track_changes</span>
                        </div>
                        <div id="ki" class="text-3xl font-mono font-bold text-stone-600 dark:text-white-300">0.00</div>
                    </div>
                    <!-- ==== KD ==== -->
                    <div class="w-[8vw] bg-white dark:bg-red-950/20 border-2 border-slate-200 dark:border-red-900/50 rounded-2xl p-4 shadow-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold text-black-500 dark:text-white-400 uppercase tracking-tight">KD (DERIV)</span>
                            <span class="material-icons-outlined text-slate-400 text-sm">track_changes</span>
                        </div>
                        <div id="kd" class="text-3xl font-mono font-bold text-stone-600 dark:text-white-300">0.00</div>
                    </div>
                </div>
                <div class="flex flex-col justify-between">
                    <div class="w-[5vw] max-h-[100px] flex items-center">
                        <div class="arrow-line flex-1"></div>
                        <div class="w-[3vw] h-[100px] flex flex-col justify-between items-center bg-white border-2 border-slate-200 rounded-2xl p-[0.5vw] shadow-lg">
                            <div id="multi-kp-1" class="text-[0.75vw]">1</div>
                            <div id="multi-kp-10" class="text-[0.75vw]">10</div>
                            <div id="multi-kp-100" class="text-[0.75vw]">100</div>
                        </div>
                        <div class="arrow-line flex-1"></div>
                    </div>
                    <div class="w-[5vw] max-h-[100px] flex items-center">
                        <div class="arrow-line flex-1"></div>
                        <div class="w-[3vw] h-[100px] flex flex-col justify-between items-center bg-white border-2 border-slate-200 rounded-2xl p-[0.5vw] shadow-lg">
                            <div id="multi-ki-1" class="text-[0.75vw]">1</div>
                            <div id="multi-ki-10" class="text-[0.75vw]">10</div>
                            <div id="multi-ki-100" class="text-[0.75vw]">100</div>
                        </div>
                        <div class="arrow-line flex-1"></div>
                    </div>
                    <div class="w-[5vw] max-h-[100px] flex items-center">
                        <div class="arrow-line flex-1"></div>
                        <div class="w-[3vw] h-[100px] flex flex-col justify-between items-center bg-white border-2 border-slate-200 rounded-2xl p-[0.5vw] shadow-lg">
                            <div id="multi-kd-1" class="text-[0.75vw]">1</div>
                            <div id="multi-kd-10" class="text-[0.75vw]">10</div>
                            <div id="multi-kd-100" class="text-[0.75vw]">100</div>
                        </div>
                        <div class="arrow-line flex-1"></div>
                    </div>
                </div>
                <div class="w-[30vw] bg-white border-2  border-slate-200 p-[0.5vw] rounded-2xl shadow-lg">

                    <canvas id="chart"></canvas>
                </div>
            </div>
            <div class="arrow-line flex-1"></div>
            <div class="w-[8vw] bg-blue-50 border-2 border-blue-200 rounded-2xl p-4 shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-bold text-blue-500 dark:text-white-400 uppercase tracking-tight">PROCESS (PV)</span>
                    <span class="material-icons-outlined text-blue-400 text-sm">track_changes</span>
                </div>
                <div id="pv" class="text-3xl font-mono font-bold text-stone-600 dark:text-white-300">0.00</div>
            </div>
            <div class="arrow-line flex-1"></div>
            <div class="w-[2vw] h-[2vw] rounded-full border-2 text-[2vw] flex justify-center items-center border border-slate-300 shadow-sm z-10">
                <span class="text-xs font-bold">Σ</span>
            </div>
            <div class="arrow-line flex-1"></div>
            <div class="w-[2vw] h-[2vw] relative text-[2vw] flex justify-center items-center shadow-sm z-10">
                <span class="text-xs font-bold">Output</span>
                <!-- ==== MV ==== -->
                <div class="absolute bottom-[10vh] w-[8vw] border-2 bg-green-50 border-green-200 dark:border-green-900/50 rounded-2xl p-4 shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold text-green-500 dark:text-white-400 uppercase tracking-tight">MV</span>
                        <span class="material-icons-outlined text-green-400 text-sm">track_changes</span>
                    </div>
                    <div id="mv" class="text-3xl font-mono font-bold text-stone-600 dark:text-white-300">0.00</div>
                </div>
            </div>
            <div class="arrow-line flex-1"></div>

        </div>
        <div class="absolute bottom-6 left-6 flex items-center gap-3">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-lg min-w-[280px]">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-icons-outlined text-sm text-slate-400">sensors</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Connectivity</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-2 h-2 rounded-full bg-green-500"></div>
                        <span class="text-[10px] font-medium text-slate-400">Connected</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-2 border border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-xs font-mono">IOT</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-2 border border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-xs font-mono">USB</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                    </div>
                </div>
                <button class="w-full py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg text-xs font-bold uppercase flex items-center justify-center gap-2 transition-colors">
                    <span class="material-icons-outlined text-sm">wifi_off</span>
                    Reset Wifi ESP32
                </button>
            </div>
        </div>
        <div class="absolute bottom-6 right-6 flex flex-col gap-2">
            <button class="flex items-center justify-between gap-4 px-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-md hover:translate-y-[-2px] transition-transform">
                <span class="text-xs font-bold text-slate-600 dark:text-slate-400">CSV Report</span>
                <span class="material-icons-outlined text-blue-500 text-lg">description</span>
            </button>
            <button class="flex items-center justify-between gap-4 px-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-md hover:translate-y-[-2px] transition-transform">
                <span class="text-xs font-bold text-slate-600 dark:text-slate-400">Excel Data</span>
                <span class="material-icons-outlined text-green-500 text-lg">grid_on</span>
            </button>
        </div>
    </main>

    <?php include "../components/footer.php"; ?>
    <?php include "../scripts/js.html"; ?>
    <?php include "../scripts/js-home.html"; ?>
</body>

</html>
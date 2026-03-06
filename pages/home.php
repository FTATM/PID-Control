<?php
$pageTitle = "PID Control";
$subTitle = "Realtime Control Project";
?>

<!DOCTYPE html>
<html class="dark" lang="th">
<?php include("../scripts/ref.html"); ?>
<?php include("../styles/css-default.html"); ?>
<?php include("../styles/css-icon.html"); ?>

<head>
    <title><?php echo $pageTitle ?></title>
</head>

<body class="h-screen flex flex-col overflow-hidden main-container">

    <?php include "../components/header.php"; ?>
    <!-- Main Content -->
    <main class="flex-1 relative overflow-hidden grid-bg bg-[#f5f5f5] dark:bg-canvas-dark">
        <!-- เส้นโยงกลับ -->
        <div class="feedback-line"></div>

        <!-- ==== PROCESS (PV) ==== -->
        <div class="absolute right-[42.01%] top-[63%]">
            <div
                class="w-[8vw] bg-blue-50 boxx dark:bg-blue-950 border-2 border-blue-200 dark:border-blue-900 rounded-2xl p-4 z-10 shadow-xl dark:shadow-black/50">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-bold text-blue-500 dark:text-blue-50 uppercase tracking-tight">PROCESS
                        (PV)</span>
                    <span class="material-icons-outlined text-blue-400 text-sm">track_changes</span>
                </div>
                <div id="pv" class="text-3xl font-mono font-bold text-blue-950 dark:text-white">0</div>
            </div>
        </div>

        <div class="flex items-center w-full p-[3vw]">
            <!-- ==== SETPOINT (SP) ==== -->
            <div
                class="w-[8vw] bg-red-50 boxx dark:bg-red-950 border-2 border-red-400 dark:border-red-900 rounded-2xl p-4 shadow-md dark:shadow-xl dark:shadow-black/50">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-bold text-red-500 dark:text-red-400 uppercase tracking-tight">Setpoint
                        (SP)</span>
                    <span class="material-icons-outlined text-red-400 text-sm">track_changes</span>
                </div>
                <div id="sp" class="text-3xl font-mono font-bold text-red-600 dark:text-red-400">30.24</div>
            </div>
            <div class="arrow-line flex-1"></div>
            <div
                class="w-[2vw] h-[2vw] rounded-full bg-gradient-to-br from-gray-700 via-white to-gray-700 shadow-xl dark:shadow-black/50 border border-gray-400 flex items-center justify-center z-10">
                <span class="text-[0.75vw] font-bold">Σ</span>
            </div>
            <div class="arrow-line flex-1"></div>

            <!-- ==== Error ==== -->
            <div
                class="w-[8vw] bg-orange-50 boxx dark:bg-orange-950 border-2 border-orange-200 dark:border-orange-900 rounded-2xl p-4 shadow-xl dark:shadow-black/50">
                <div class="flex items-center justify-between mb-2">
                    <span
                        class="text-[10px] font-bold text-orange-500 dark:text-orange-100 uppercase tracking-tight">Error
                        (ERR)</span>
                    <span class="material-icons-outlined text-orange-400 text-sm">warning_amber</span>
                </div>
                <div id="error" class="text-3xl font-mono font-bold text-orange-600 dark:text-white">157.24</div>
            </div>
            <div class="arrow-line flex-1"></div>
            <div class="min-h-[40vh] flex gap-[0.25vw]">
                <div class="flex flex-col justify-between">

                    <!-- ==== KP ==== -->
                    <div
                        class="w-[8vw] bg-white boxx dark:bg-white/5 border-2 border-slate-200 dark:border-white/5 rounded-2xl p-4 shadow-xl dark:shadow-black/50">
                        <div class="flex items-center justify-between mb-2">
                            <span
                                class="text-[10px] font-bold text-black-500 dark:text-stone-200 uppercase tracking-tight">KP
                                (PROP)</span>
                            <span
                                class="material-symbols-outlined text-slate-700 dark:text-slate-200 text-sm">settings</span>
                        </div>
                        <div class="flex justify-between">
                            <div id="kp" class="text-3xl font-mono font-bold text-stone-600 dark:text-white">0.00</div>
                            <div id="multi-kp" class="text-[0.75vw] dark:text-white">x1</div>
                        </div>
                    </div>

                    <!-- ==== KI ==== -->
                    <div
                        class="w-[8vw] bg-white boxx dark:bg-white/5 border-2 border-slate-200 dark:border-white/5 rounded-2xl p-4 shadow-xl dark:shadow-black/50">
                        <div class="flex items-center justify-between mb-2">
                            <span
                                class="text-[10px] font-bold text-black-500 dark:text-stone-200 uppercase tracking-tight">KI
                                (INTEG)</span>
                            <span
                                class="material-symbols-outlined text-slate-700 dark:text-slate-200 text-sm">tune</span>
                        </div>
                        <div class="flex justify-between">
                            <div id="ki" class="text-3xl font-mono font-bold text-stone-600 dark:text-white">0.00</div>
                            <div id="multi-ki" class="text-[0.75vw] dark:text-white">x1</div>
                        </div>
                    </div>

                    <!-- ==== KD ==== -->
                    <div
                        class="w-[8vw] bg-white boxx dark:bg-white/5 border-2 border-slate-200 dark:border-white/5 rounded-2xl p-4 shadow-xl dark:shadow-black/50">
                        <div class="flex items-center justify-between mb-2">
                            <span
                                class="text-[10px] font-bold text-black-500 dark:text-stone-200 uppercase tracking-tight">KD
                                (DERIV)</span>
                            <span
                                class="material-symbols-outlined text-slate-700 dark:text-slate-200 text-sm">show_chart</span>
                        </div>
                        <div class="flex justify-between">
                            <div id="kd" class="text-3xl font-mono font-bold text-stone-600 dark:text-white">0.00</div>
                            <div id="multi-kd" class="text-[0.75vw] dark:text-white">x1</div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col justify-between">
                    <div class="w-[5vw] h-[10vh] max-h-[100px] flex items-center">
                        <div class="arrow-line flex-1"></div>
                    </div>
                    <div class="w-[5vw] h-[10vh] max-h-[100px] flex items-center">
                        <div class="arrow-line flex-1"></div>
                    </div>
                    <div class="w-[5vw] h-[10vh] max-h-[100px] flex items-center">
                        <div class="arrow-line flex-1"></div>
                    </div>
                </div>
                <div
                    class="w-[30vw] flex flex-col bg-white boxx dark:bg-white/5 border-2 border-slate-200 dark:border-white/20 p-[0.5vw] rounded-2xl shadow-xl dark:shadow-black/50">
                    <div class="p-[0.25vw] dark:text-white">Graph</div>
                    <div class="flex-1">
                        <canvas id="chart"></canvas>
                    </div>
                </div>
            </div>
            <div class="arrow-line flex-1"></div>

            <!-- ==== MV ==== -->
            <div
                class="w-[8vw] boxx bg-green-50 dark:bg-green-950 border-2 border-green-200 dark:border-green-900 rounded-2xl p-4 z-10 shadow-xl dark:shadow-black/50">
                <div class="flex items-center justify-between mb-2">
                    <span
                        class="text-[10px] font-bold text-green-500 dark:text-white-400 uppercase tracking-tight">MV</span>
                    <span class="material-icons-outlined text-green-400 text-sm">track_changes</span>
                </div>
                <div id="mv" class="text-3xl font-mono font-bold text-stone-600 dark:text-white">0.00</div>
            </div>

            <!-- ==== PV ==== -->
            <!-- <div class="w-[8vw] bg-blue-50 border-2 border-blue-200 rounded-2xl p-4 shadow-xl dark:shadow-black/50">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-bold text-blue-500 dark:text-white-400 uppercase tracking-tight">PROCESS (PV)</span>
                    <span class="material-icons-outlined text-blue-400 text-sm">track_changes</span>
                </div>
                <div id="pv" class="text-3xl font-mono font-bold text-stone-600 dark:text-white">0.00</div>
            </div> -->
            <div class="arrow-line flex-1"></div>
            <div
                class="w-[2vw] h-[2vw] rounded-full bg-gradient-to-br from-gray-700 via-white to-gray-700 shadow-xl dark:shadow-black/50 border border-gray-400 flex items-center justify-center z-10">
                <span class="text-[0.75vw] font-bold">Σ</span>
            </div>
            <div class="arrow-line flex-1"></div>
            <div class="w-[2vw] h-[2vw] relative text-[2vw] flex justify-center items-center shadow-sm z-10">
                <span class="text-[0.5vw] font-bold dark:text-white">Output</span>

                <!-- ==== MV ==== -->
                <!-- <div class="absolute bottom-[10vh] w-[8vw] border-2 bg-green-50 border-green-200 dark:border-green-900/50 rounded-2xl p-4 shadow-xl dark:shadow-black/50">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold text-green-500 dark:text-white-400 uppercase tracking-tight">MV</span>
                        <span class="material-icons-outlined text-green-400 text-sm">track_changes</span>
                    </div>
                    <div id="mv" class="text-3xl font-mono font-bold text-stone-600 dark:text-white">0.00</div>
                </div> -->
            </div>
            <div class="arrow-line flex-1"></div>

        </div>
        <div class="absolute bottom-6 left-6 flex items-center gap-3">
            <div class="bg-white box rounded-xl p-4 border border-stone-400 dark:shadow-black/50 min-w-[280px]">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-icons-outlined text-xl text-slate-400 dark:text-white">sensors</span>
                        <span
                            class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-white">Connectivity</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-2 h-2 rounded-full bg-green-500"></div>
                        <span class="text-[10px] font-medium text-slate-400 dark:text-white">Connected</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div
                        class="bg-slate-50 export-btn dark:bg-slate-800/50 rounded-lg p-2 flex items-center justify-between">
                        <span class="text-[0.75vw] font-mono dark:text-white">IOT</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-green-300 dark:text-white"></span>
                    </div>
                    <div
                        class="bg-slate-50 export-btn dark:bg-slate-800/50 rounded-lg p-2 flex items-center justify-between">
                        <span class="text-[0.75vw] font-mono dark:text-white">USB</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-white"></span>
                    </div>
                </div>
                <button
                    class="export-btn w-full py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg text-[0.75vw] dark:text-white font-bold uppercase flex items-center justify-center gap-2 transition-colors">
                    <span class="material-icons-outlined text-sm dark:text-white">wifi_off</span>
                    Reset Wifi ESP32
                </button>
            </div>
        </div>
        <div class="absolute bottom-6 right-6 flex flex-col gap-3">
            <button class="export-btn">
                <span class="material-icons-outlined text-blue-400 text-lg">description</span>
                <span class="export-btn__label">CSV Report</span>
            </button>

            <button class="export-btn">
                <span class="material-icons-outlined text-green-400 text-lg">grid_on</span>
                <span class="export-btn__label">Excel Data</span>
            </button>
        </div>
    </main>

    <?php include "../components/footer.php"; ?>
    <?php include "../scripts/js.html"; ?>
    <?php include "../scripts/js-home.html"; ?>


</body>

</html>
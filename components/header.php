<?php
$currentDate = date('d M Y');
date_default_timezone_set('Asia/Bangkok');
$currentTime = date('H:i:s');
?>

<!-- Header -->
<header class="flex items-center justify-between p-[0.5vw] border-b border-stone-200 bg-white shrink-0 dark:bg-slate-950 dark:border-slate-800">
    <div class="flex items-center gap-3">
        
        <div class="w-[4rem] h-[4rem] rounded-xl flex items-center justify-center shadow-sm dark:text-white dark:bg-canvas-dark">
            <span class="ic--twotone-hub text-2xl"></span>
        </div>
        <div>
            <h1 class="text-[#1d130c] dark:text-white text-[1.5vw] font-bold leading-none">
                <?php echo isset($pageTitle) ? $pageTitle : "Not Set Name in param \$pageTitle"; ?>
            </h1>
            <p class="text-[0.75vw] text-stone-500 dark:text-stone-400 font-medium uppercase tracking-wider mt-0.5"><?php echo isset($subTitle) ? $subTitle : "---" ?></p>
        </div>
    </div>
    <div class="flex items-center gap-4">
        <div id="theme-toggle-container" onclick="document.documentElement.classList.toggle('dark')">
            <div id="theme-toggle-thumb">
                <span class="material-icons-round text-[14px] text-orange-500 block dark:hidden">wb_sunny</span>
                <span class="material-icons-round text-[14px] text-slate-700 hidden dark:block">dark_mode</span>
            </div>
        </div>
        <div class="flex flex-col items-end border-l border-stone-200 pl-4">
            <span class="text-[0.75vw] font-bold text-stone-400 dark:text-stone-200 uppercase tracking-widest leading-none mb-0.5">อัปเดตล่าสุด</span>
            <span class="text-[0.75vw] font-bold text-stone-600 dark:text-stone-300 text-center" id="last-update"><?php echo $currentTime; ?></span>
            <span class="text-[0.75vw] text-stone-600 dark:text-stone-300 font-bold leading-none" id="start-date"> -- --- ---- </span>
        </div>
    </div>
</header>
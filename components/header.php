<?php
$currentDate = date('d M Y');
date_default_timezone_set('Asia/Bangkok');
$currentTime = date('H:i:s');
?>

<!-- Header -->
<header class="flex items-center justify-between p-[0.5vw] border-b border-stone-200 bg-white shrink-0">
    <div class="flex items-center gap-3">
        <?php include 'navbar.php'; ?>
        <div class="size-9 w-[4rem] h-[4rem] bg-[#FF8021] rounded-xl flex items-center justify-center text-white shadow-sm shadow-primary/20">
            <span class="emojione-monotone--chicken text-2xl text-white"></span>
        </div>
        <div>
            <h1 class="text-[#1d130c] text-[1.5vw] font-bold leading-none">
                <?php echo isset($pageTitle) ? $pageTitle : "Not Set Name in param \$pageTitle"; ?>
            </h1>
            <p class="text-[0.75vw] text-stone-500 font-medium uppercase tracking-wider mt-0.5"><?php echo isset($subTitle) ? $subTitle : "---" ?></p>
        </div>
    </div>
    <div class="flex items-center gap-4">
        <div class="flex flex-col items-end border-l border-stone-200 pl-4">
            <span class="text-[0.75vw] font-bold text-stone-400 uppercase tracking-widest leading-none mb-0.5">อัปเดตล่าสุด</span>
            <span class="text-[0.75vw] font-bold text-stone-600 text-center" id="last-update"><?php echo $currentTime; ?></span>
            <span class="text-[0.75vw] text-stone-600 font-bold leading-none" id="start-date"> -- --- ---- </span>
        </div>
    </div>
</header>
<div id="popup-add" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm hidden">
    <div class="bg-white dark:bg-slate-900 w-full max-w-[25vw] rounded-[2rem] shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-[1vw] font-bold font-display text-slate-900 dark:text-white">เพิ่มรายการรายได้ใหม่</h2>
            <button onclick="hidePopup('popup-add')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                <span class="material-icons-round text-[1vw]">close</span>
            </button>
        </div>
        <div class="p-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-1">
                    <label class="text-[0.75vw] font-semibold text-slate-700 dark:text-slate-300 font-display">สาขา (Branch)</label>
                    <div class="relative">
                        <select id="select-branch" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-[0.75vw] focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all appearance-none">
                            <option value="0">-- เลือกฟาร์ม --</option>
                        </select>
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-[0.75vw] font-semibold text-slate-700 dark:text-slate-300 font-display">จำนวนเงิน (Amount)</label>
                    <div class="relative">
                        <input id="amount" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-[0.75vw] focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" placeholder="0.00" type="text" />
                        <!-- <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">THB</span> -->
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-[0.75vw] font-semibold text-slate-700 dark:text-slate-300 font-display">หมวดหมู่ (Category)</label>
                    <div class="relative">
                        <select id="select-category" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-[0.75vw] focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all appearance-none">
                            <option value="0">-- เลือกหมวดหมู่ --</option>
                        </select>
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-[0.75vw] font-semibold text-slate-700 dark:text-slate-300 font-display">วันที่ทำรายการ (Income Date)</label>
                    <div class="relative">
                        <input id="start-date" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-[0.75vw] focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all " type="date" />
                        <!-- <span class="material-icons-round absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">calendar_today</span> -->
                    </div>
                </div>
            </div>
            <div class="space-y-1">
                <label class="text-[0.75vw] font-semibold text-slate-700 dark:text-slate-300 font-display">รายละเอียด (Description)</label>
                <textarea id="description" class="w-full bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-[0.75vw] focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all resize-none" placeholder="ระบุรายละเอียดเพิ่มเติม..." rows="3"></textarea>
            </div>
        </div>
        <div class="px-8 py-6 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
            <button onclick="hidePopup('popup-add')" class="px-6 py-2.5 rounded-xl text-[0.65vw] font-semibold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all font-display">
                ยกเลิก </br> (Cancel)
            </button>
            <button onclick="saveData('popup-add')" class="px-8 py-2.5 rounded-xl text-[0.65vw] font-semibold text-white bg-primary hover:bg-orange-600 shadow-lg shadow-orange-500/20 transition-all font-display">
                บันทึกข้อมูล </br> (Save Data)
            </button>
        </div>
    </div>
</div>
<div id="popup-delete" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm hidden">
    <div class="bg-white dark:bg-slate-900 w-full max-w-[20vw] rounded-[2rem] shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden text-center">
        <div class="pt-10 pb-6 px-8 flex flex-col items-center">
            <div class="w-20 h-20 bg-rose-50 dark:bg-rose-900/20 rounded-full flex items-center justify-center mb-6 border-4 border-rose-100 dark:border-rose-900/40">
                <span class="material-symbols-outlined text-rose-500 text-[2vw] font-bold">priority_high</span>
            </div>
            <h2 class="text-[1vw] font-bold font-display text-slate-900 dark:text-white mb-3">ยืนยันการลบข้อมูล</h2>
            <p class="text-slate-500 dark:text-slate-400 text-[0.75vw] font-medium leading-relaxed mb-10 px-4">
                คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้? <br />
                <span class="text-rose-500 font-semibold">การดำเนินการนี้ไม่สามารถย้อนกลับได้</span>
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 w-full pb-6">
                <button onclick="hidePopup('popup-delete')" class="w-full sm:w-auto px-8 py-3 rounded-xl text-[0.65vw] font-semibold text-slate-500 dark:text-slate-400 border-2 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all font-display order-2 sm:order-1">
                    ยกเลิก </br> (Cancel)
                </button>
                <button onclick="deleteData(this,'popup-delete')" class="w-full sm:w-auto px-8 py-3 rounded-xl text-[0.65vw] font-semibold text-white bg-rose-500 hover:bg-rose-600 shadow-lg shadow-rose-500/25 transition-all font-display order-1 sm:order-2">
                    ยืนยันการลบ </br> (Confirm Delete)
                </button>
            </div>
        </div>
    </div>
</div>
<div id="popup-result-add" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm hidden">
    <div
        class="bg-white dark:bg-slate-900 w-full max-w-sm rounded-[2.5rem] shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col items-center p-10 text-center animate-success">
        <div class="w-24 h-24 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center mb-6">
            <span
                class="material-symbols-outlined text-[2vw] text-emerald-600 dark:text-emerald-400 font-bold">check_circle</span>
        </div>
        <h2 class="text-[1vw] font-bold font-display text-emerald-600 dark:text-emerald-400 mb-3">บันทึกข้อมูลสำเร็จ</h2>
        <p class="text-slate-500 dark:text-slate-400 text-[0.75vw] font-medium leading-relaxed px-2 mb-8">
            ข้อมูลรายได้ของคุณถูกบันทึกลงในระบบเรียบร้อยแล้ว
        </p>
        <button onclick="hidePopup('popup-result-add')"
            class="w-full py-3.5 px-8 rounded-2xl text-[0.65vw] font-semibold text-white bg-primary hover:bg-orange-600 shadow-lg shadow-orange-500/30 hover:shadow-orange-500/40 transition-all font-display">
            ตกลง </br> (OK)
        </button>
    </div>
</div>

<div id="popup-result-delete" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-[2px] hidden">
    <div class="bg-white dark:bg-slate-900 w-full max-w-sm rounded-[2.5rem] shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col items-center p-10 text-center">
        <div class="w-24 h-24 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center mb-6">
            <span class="material-symbols-outlined text-[2vw] text-emerald-600 dark:text-emerald-400 font-bold">check_circle</span>
        </div>
        <h2 class="text-[1vw] font-bold font-display text-slate-900 dark:text-white mb-3">ลบข้อมูลสำเร็จ</h2>
        <p class="text-slate-500 dark:text-slate-400 text-[0.75vw] font-medium leading-relaxed px-2 mb-8">
            ข้อมูลรายการนี้ถูกลบออกจากระบบเรียบร้อยแล้ว
        </p>
        <button onclick="hidePopup('popup-result-delete')"
            class="w-full py-3.5 px-8 rounded-2xl text-[0.65vw] font-semibold text-white bg-primary hover:bg-orange-600 shadow-lg shadow-orange-500/30 hover:shadow-orange-500/40 transition-all font-display">
            ตกลง </br> (OK)
        </button>
    </div>
</div>

<div id="popup-result-error" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden">
    <div class="bg-white dark:bg-slate-900 w-full max-w-sm rounded-[2.5rem] shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col items-center p-10 text-center">
        <div class="w-24 h-24 bg-rose-100 dark:bg-rose-900/30 rounded-full flex items-center justify-center mb-6">
            <span class="material-symbols-outlined text-[2vw] text-rose-600 dark:text-rose-400 font-bold">cancel</span>
        </div>
        <h2 class="text-[1vw] font-bold font-display text-slate-900 dark:text-white mb-3">
            เกิดข้อผิดพลาด
        </h2>
        <p class="text-slate-500 dark:text-slate-400 text-[0.75vw] font-medium leading-relaxed px-4 mb-8">
            ไม่สามารถดำเนินการได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง
        </p>
        <button onclick="hidePopup('popup-result-error')"
        class="w-full py-3.5 px-8 rounded-2xl text-[0.65vw] font-semibold text-white bg-primary hover:bg-orange-600 shadow-lg shadow-orange-500/30 hover:shadow-orange-500/40 transition-all font-display">
            ตกลง </br> (OK)
        </button>
    </div>
</div>
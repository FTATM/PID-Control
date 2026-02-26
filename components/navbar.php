<?php
// กำหนดหน้าที่กำลังเปิดอยู่
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!-- ปุ่ม Hamburger (3 ขีด) -->
<button class="hamburger-btn" id="hamburgerBtn">
    <span></span>
    <span></span>
    <span></span>
</button>

<!-- Overlay (พื้นหลังมืด) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar Menu -->
<nav class="sidebar-menu flex flex-col" id="sidebarMenu">

    <!-- 🔝 Header ของ Sidebar -->
    <div class="flex items-center justify-between p-3">
        <div>
            <img src="../assets/logo.png" alt="ฟิวด์เทค ออร์โตเมชั่น จำกัด" class="w-[5rem] object-cover rounded-md">
            <!-- <p class="text-lg font-bold">FieldTech Automation Co.,ltd</p> -->
        </div>
        <button
            id="closeBtn"
            title="ปิดเมนู"
            class="close-btn 
                   w-8 h-8 flex items-center justify-center
                   rounded-full bg-stone-100 text-stone-600
                   hover:bg-orange-100 hover:text-[#ff8021]
                   transition">
            ✕
        </button>
    </div>

    <ul class="nav-list flex-1">
        <div class="pt-6 pb-2 px-6">
            <h3 class="text-[1vw] font-bold text-slate-900 dark:text-white uppercase tracking-wider">Menu pages</h3>
        </div>
        <li>
            <a href="home.php" class="<?php echo ($current_page == 'home') ? 'active' : ''; ?>">
                <span class="nav-icon home"></span>Menu 1
            </a>
        </li>
        <li>
            <a href="menu2.php" class="<?php echo ($current_page == 'menu2') ? 'active' : ''; ?>">
                <span class="nav-icon home"></span>Menu 2
            </a>
        </li>
        <li>
            <a href="menu3.php" class="<?php echo ($current_page == 'menu3') ? 'active' : ''; ?>">
                <span class="nav-icon home"></span>Menu 3
            </a>
        </li>
    </ul>

</nav>


<script>
    // Get elements
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const closeBtn = document.getElementById('closeBtn');
    const sidebarMenu = document.getElementById('sidebarMenu');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    // เปิดเมนู
    function openMenu() {
        sidebarMenu.classList.add('active');
        sidebarOverlay.classList.add('active');
        hamburgerBtn.classList.add('hide');
        document.body.classList.add('menu-open');
    }

    // ปิดเมนู
    function closeMenu() {
        sidebarMenu.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        hamburgerBtn.classList.remove('hide');
        document.body.classList.remove('menu-open');
    }

    // Event Listeners
    hamburgerBtn.addEventListener('click', openMenu);
    closeBtn.addEventListener('click', closeMenu);
    sidebarOverlay.addEventListener('click', closeMenu);

    // ปิดเมนูเมื่อกด ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebarMenu.classList.contains('active')) {
            closeMenu();
        }
    });

    // ปิดเมนูเมื่อคลิกลิงก์ (สำหรับ Single Page Application)
    const navLinks = document.querySelectorAll('.nav-list a');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            closeMenu();
        });
    });

    // ป้องกันการปิดเมื่อคลิกใน sidebar
    sidebarMenu.addEventListener('click', (e) => {
        e.stopPropagation();
    });
</script>
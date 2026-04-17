<?php
$base_url = (strpos($_SERVER['PHP_SELF'], '/sub/') !== false) ? '../' : '';
$current_page = basename($_SERVER['PHP_SELF']);
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
if (isset($_SESSION['LAST_ACTIVITY'])) {

    if (time() - $_SESSION['LAST_ACTIVITY'] > $_SESSION['EXPIRE_TIME']) {

        session_unset();
        session_destroy();

        header("Location: koneksi/login.php?timeout=1");
        exit;
    }
}

$_SESSION['LAST_ACTIVITY'] = time();

?>
<header>
  <div class="container nav">

    <!-- TOGGLE + LOGO (MOBILE ONLY) -->
    <div class="mobile-menu">
        <button id="toggleSidebarHeader" class="toggle-btn">☰</button>
        <span class="logo-mobile">MYDAILY</span>
    </div>

    <div class="welcome">
        Welcome, <strong><?= htmlspecialchars($username); ?></strong>
    </div>

    <a href="koneksi/logout.php" class="logout">Logout</a>
  </div>
</header>
<aside class="sidebar no-transition mobile-collapsed" id="sidebar">

    <button id="toggleSidebar" class="toggle-btn">
        ☰
    </button>
    <div class="sidebar-logo">
        <a href="<?= $base_url ?>index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">
            <span class="logo-full">MYDAILY</span>
            <span class="logo-mini">MY</span>
        </a>
    </div>
    <a href="<?= $base_url ?>dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
        <i class="menu-icon fa-solid fa-house"></i>
        <span class="menu-text">Dashboard</span>
    </a>

    <a href="<?= $base_url ?>task.php" class="<?= $current_page == 'task.php' ? 'active' : '' ?>">
        <i class="menu-icon fa-solid fa-list-check"></i>
        <span class="menu-text">Tugas</span>
    </a>

    <a href="<?= $base_url ?>task_repeat.php" class="<?= $current_page == 'task_repeat.php' ? 'active' : '' ?>">
        <i class="menu-icon fa-solid fa-repeat"></i>
        <span class="menu-text">Tugas (Berulang)</span>
    </a>

    <a href="<?= $base_url ?>backup_data.php" class="<?= $current_page == 'backup_data.php' ? 'active' : '' ?>">
        <i class="menu-icon fa-solid fa-database"></i>
        <span class="menu-text">BackUp Data</span>
    </a>

    <a href="<?= $base_url ?>money_plan.php" class="<?= $current_page == 'money_plan.php' ? 'active' : '' ?>">
        <i class="menu-icon fa-solid fa-wallet"></i>
        <span class="menu-text">Pengatur Keuangan</span>
    </a>

    <a href="<?= $base_url ?>notes.php" class="<?= $current_page == 'notes.php' ? 'active' : '' ?>">
        <i class="menu-icon fa-solid fa-note-sticky"></i>
        <span class="menu-text">Catatan</span>
    </a>

    <a href="<?= $base_url ?>profile.php" class="<?= $current_page == 'profile.php' ? 'active' : '' ?>">
        <i class="menu-icon fa-solid fa-user"></i>
        <span class="menu-text">Profil</span>
    </a>
</aside>
<?php include 'sub/floating_clock.php'; ?>
<script>
const sidebar = document.getElementById("sidebar");
const toggleBtn = document.getElementById("toggleSidebar"); // desktop
const toggleBtnHeader = document.getElementById("toggleSidebarHeader"); // mobile

function isMobile() {
    return window.innerWidth <= 768;
}

// default mobile = tertutup
if (isMobile()) {
    sidebar.classList.remove("show");
}

// desktop state
if (!isMobile()) {
    if (localStorage.getItem("sidebar") === "collapsed") {
        sidebar.classList.add("collapsed");
    }
}

// remove anim delay
window.addEventListener("load", function() {
    sidebar.classList.remove("no-transition");
});

// TOGGLE DESKTOP
if (toggleBtn) {
    toggleBtn.addEventListener("click", function () {
        sidebar.classList.toggle("collapsed");

        if (sidebar.classList.contains("collapsed")) {
            localStorage.setItem("sidebar", "collapsed");
        } else {
            localStorage.setItem("sidebar", "expanded");
        }
    });
}

// TOGGLE MOBILE (HEADER)
if (toggleBtnHeader) {
    toggleBtnHeader.addEventListener("click", function () {
        sidebar.classList.toggle("show");
    });
}
</script>
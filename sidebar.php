<link rel="icon" type="image/x-icon" href="favicon.ico">
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_url = (strpos($_SERVER['PHP_SELF'], '/sub/') !== false) ? '../' : '';
$current_page = basename($_SERVER['PHP_SELF']);
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
if (isset($_SESSION['LAST_ACTIVITY'])) {
    $_SESSION['EXPIRE_TIME'] = 1800; // 
    if (time() - $_SESSION['LAST_ACTIVITY'] > $_SESSION['EXPIRE_TIME']) {

        session_unset();
        session_destroy();

        header("Location: koneksi/login.php?timeout=1");
        exit;
    }
}
$_SESSION['LAST_ACTIVITY'] = time();

header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
?>
<header>
  <div class="container nav">

    <div class="mobile-menu">
        <button id="toggleSidebarHeader" class="toggle-btn">☰</button>
        <span class="logo-mobile">MYDAILY</span>
    </div>

    <div class="welcome">
        Welcome, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></strong>
    </div>

    <form action="koneksi/logout.php" method="POST">
        <button type="submit" class="logout">Logout</button>
    </form>

  </div>
</header>
<aside class="sidebar no-transition mobile-collapsed" id="sidebar">

    <button id="toggleSidebar" class="toggle-btn">
        ☰
    </button>
    <a href="<?= $base_url ?>dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
        <i class="menu-icon fa-solid fa-house"></i>
        <span class="menu-text">Dashboard</span>
    </a>

    <a href="<?= $base_url ?>task.php" class="<?= $current_page == 'task.php' ? 'active' : '' ?>">
        <i class="menu-icon fa-solid fa-list-check"></i>
        <span class="menu-text">Tugas</span>
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

<!-- PINDAH KE SINI -->
<div id="sidebarBackdrop" class="sidebar-backdrop"></div>
<?php include 'sub/floating_clock.php'; ?>
<script>
const sidebar = document.getElementById("sidebar");
const toggleBtn = document.getElementById("toggleSidebar");
const toggleBtnHeader = document.getElementById("toggleSidebarHeader");
const backdrop = document.getElementById("sidebarBackdrop");

function isMobile() {
    return window.innerWidth <= 768;
}

// INIT STATE
function initSidebar() {
    if (isMobile()) {
        sidebar.classList.remove("collapsed");
        sidebar.classList.remove("show");
        backdrop.classList.remove("active");
    } else {
        if (localStorage.getItem("sidebar") === "collapsed") {
            sidebar.classList.add("collapsed");
        }
    }
}

initSidebar();

// REMOVE TRANSITION DELAY
window.addEventListener("load", () => {
    sidebar.classList.remove("no-transition");
});

// DESKTOP TOGGLE
if (toggleBtn) {
    toggleBtn.addEventListener("click", () => {
        sidebar.classList.toggle("collapsed");

        localStorage.setItem(
            "sidebar",
            sidebar.classList.contains("collapsed") ? "collapsed" : "expanded"
        );
    });
}

// MOBILE TOGGLE
if (toggleBtnHeader) {
    toggleBtnHeader.addEventListener("click", () => {
        sidebar.classList.toggle("show");
        backdrop.classList.toggle("active");
    });
}

// CLICK BACKDROP = CLOSE
backdrop.addEventListener("click", () => {
    sidebar.classList.remove("show");
    backdrop.classList.remove("active");
});

// AUTO FIX SAAT RESIZE
window.addEventListener("resize", initSidebar);
document.querySelectorAll(".sidebar a").forEach(link => {
    link.addEventListener("click", () => {
        if (isMobile()) {
            sidebar.classList.remove("show");
            backdrop.classList.remove("active");
        }
    });
});
document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
        sidebar.classList.remove("show");
        backdrop.classList.remove("active");
    }
});

document.querySelectorAll(".sidebar a").forEach(link => {
    link.addEventListener("click", function () {
        this.classList.add("loading");
    });
});
</script>
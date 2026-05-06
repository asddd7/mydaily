<link rel="icon" type="image/x-icon" href="favicon.ico">
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['id'] ?? 0;

$stmtNotif = $conn->prepare("
    SELECT id AS task_id, nama_tugas AS nama_task
    FROM tugas
    WHERE selesai = 0 
      AND user_id = ?
      AND parent_id IS NULL

    UNION

    SELECT 
        parent.id AS task_id,
        parent.nama_tugas AS nama_task
    FROM tugas child
    JOIN tugas parent ON child.parent_id = parent.id
    WHERE child.selesai = 0 
    AND child.user_id = ?
    AND parent.selesai = 0

    GROUP BY task_id, nama_task
");
$stmtNotif->bind_param("ii", $user_id, $user_id);
$stmtNotif->execute();
$resultNotif = $stmtNotif->get_result();

$jumlahNotif = $resultNotif->num_rows;

$base_url = '../';
$current_page = basename($_SERVER['PHP_SELF']);
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
if (isset($_SESSION['LAST_ACTIVITY'])) {
    define('SESSION_TIMEOUT', 18000);
    if (time() - $_SESSION['LAST_ACTIVITY'] > $_SESSION['EXPIRE_TIME']) {

        session_unset();
        session_destroy();

        header("Location: koneksi/login.php?timeout=1");
        exit;
    }
}
$_SESSION['LAST_ACTIVITY'] = time();

// saat generate token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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

    <div class="header-center">
        <div class="welcome">
            Welcome, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></strong>
        </div>

    <div class="notif-wrapper">
        <button id="notifBtn" class="notif-btn">
            🔔
            <?php if ($jumlahNotif > 0): ?>
                <span class="notif-badge" id="notifCount"><?= $jumlahNotif ?></span>
            <?php endif; ?>
        </button>

    <div id="notifDropdown" class="notif-dropdown">

    <?php
    $notifList = [];
    while ($row = $resultNotif->fetch_assoc()) {
        $notifList[] = $row;
    }

    $limit = 3;
    $total = count($notifList);
    ?>

    <?php foreach (array_slice($notifList, 0, $limit) as $row): ?>
        <a href="<?= $base_url ?>task.php?task_id=<?= $row['task_id'] ?>">
            <?= htmlspecialchars($row['nama_task']) ?>
        </a>
    <?php endforeach; ?>

    <?php if ($total > $limit): ?>
        <a href="<?= $base_url ?>task.php" class="lihat-semua">
            🔽 Lihat lebih banyak (<?= $total - $limit ?>)
        </a>
    <?php endif; ?>

    </div>
    </div>

    <div class="header-right">
    <form action="koneksi/logout.php" method="POST" onsubmit="return confirmLogout()">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
        <button type="submit" class="logout">Logout</button>
    </form>
    </div>

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

    <a href="<?= $base_url ?>koneksi/database_structure.php" class="<?= $current_page == 'database_structure.php' ? 'active' : '' ?>">
        <i class="menu-icon fa-solid fa-table"></i>
        <span class="menu-text">Struktur DB</span>
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
        link.addEventListener("click", function () {
        this.classList.add("loading");
    });
});
document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
        sidebar.classList.remove("show");
        backdrop.classList.remove("active");
    }
});

function updateNotif() {
    fetch("koneksi/get_notif.php?ts=" + Date.now(), {
        cache: "no-store"
    })
    .then(res => res.json())
    .then(data => {
        const badge = document.getElementById("notifCount");
        if (!badge) return;

        if (data.count > 0) {
            badge.style.display = "inline-block";
            badge.innerText = data.count;
        } else {
            badge.style.display = "none";
        }
    })
    .catch(err => console.error(err));
}

window.addEventListener("storage", function(e) {
    if (e.key === "notif_update") {
        updateNotif(); // ✅ BENAR
    }
});

function confirmLogout() {
    if (window.confirm("Apakah yakin ingin logout?")) {
        return true;
    }
    return false;
}

const notifBtn = document.getElementById("notifBtn");
const notifDropdown = document.getElementById("notifDropdown");

notifBtn.addEventListener("click", function(e) {
    e.stopPropagation();
    notifDropdown.classList.toggle("show");
});

document.addEventListener("click", function() {
    notifDropdown.classList.remove("show");
});
</script>
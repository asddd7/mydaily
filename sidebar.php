<?php
$base_url = (strpos($_SERVER['PHP_SELF'], '/sub/') !== false) ? '../' : '';
$current_page = basename($_SERVER['PHP_SELF']);
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>
<!-- Header -->
<header>
  <div class="container nav">
    <div class="welcome">
        Welcome, <strong><?= htmlspecialchars($username); ?></strong>
    </div>
    <a href="koneksi/logout.php" class="logout">Logout</a>
  </div>
</header>
<!-- Sidebar -->
<aside class="sidebar" id="sidebar">

    <!-- Logo -->
    <div class="sidebar-logo">
        <a href="<?= $base_url ?>index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>"><span class="logo-full">MYDAILY</span></a>
    </div>

    <!-- Menu -->
    <a href="<?= $base_url ?>dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
        <i class="menu-icon fa-solid fa-house"></i>
        <span class="menu-text">Dashboard</span>
    </a>

    <a href="<?= $base_url ?>task.php" class="<?= $current_page == 'task.php.php' ? 'active' : '' ?>">
        <i class="menu-icon fa-solid fa-list-check"></i>
        <span class="menu-text">Tugas</span>
    </a>

    <a href="<?= $base_url ?>task_repeat.php" class="<?= $current_page == 'task_repeat.php.php' ? 'active' : '' ?>">
        <i class="menu-icon fa-solid fa-repeat"></i>
        <span class="menu-text">Tugas (Berulang)</span>
    </a>

    <a href="<?= $base_url ?>backup_data.php" class="<?= $current_page == 'backup_data.php.php' ? 'active' : '' ?>">
        <i class="menu-icon fa-solid fa-database"></i>
        <span class="menu-text">BackUp Data</span>
    </a>

    <a href="<?= $base_url ?>money_plan.php" class="<?= $current_page == 'money_plan.php.php' ? 'active' : '' ?>">
        <i class="menu-icon fa-solid fa-wallet"></i>
        <span class="menu-text">Pengatur Keuangan</span>
    </a>

    <a href="<?= $base_url ?>notes.php" class="<?= $current_page == 'notes.php.php' ? 'active' : '' ?>">
        <i class="menu-icon fa-solid fa-note-sticky"></i>
        <span class="menu-text">Catatan</span>
    </a>

    <a href="<?= $base_url ?>profile.php" class="<?= $current_page == 'profile.php.php' ? 'active' : '' ?>">
        <i class="menu-icon fa-solid fa-user"></i>
        <span class="menu-text">Profil</span>
    </a>
</aside>
<?php include 'sub/floating_clock.php'; ?>
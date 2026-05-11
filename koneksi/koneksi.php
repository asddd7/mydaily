<?php

// ======================
// START SESSION
// ======================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ======================
// DATABASE CONFIG
// ======================
$host = "127.0.0.1";
$user = "root";
$pass = "";
$db   = "daily";

$conn = mysqli_connect($host, $user, $pass, $db);

// ======================
// CHECK CONNECTION
// ======================
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// ======================
// TIMEZONE
// ======================
date_default_timezone_set('Asia/Jakarta');
mysqli_query($conn, "SET time_zone = '+07:00'");

// ======================
// SECURITY HEADERS
// ======================
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");

// ======================
// SESSION TIMEOUT
// ======================
$session_timeout = 18000; // 5 jam

if (isset($_SESSION['LAST_ACTIVITY'])) {

    if ((time() - $_SESSION['LAST_ACTIVITY']) > $session_timeout) {

        session_unset();
        session_destroy();

        header("Location: login.php?timeout=1");
        exit;
    }
}

$_SESSION['LAST_ACTIVITY'] = time();

// ======================
// CSRF TOKEN
// ======================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
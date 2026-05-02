<?php

$host = "127.0.0.1"; // lebih stabil daripada localhost
$user = "root";
$pass = "";
$db   = "daily";

$conn = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (mysqli_connect_errno()) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');
mysqli_query($conn, "SET time_zone = '+07:00'");

?>
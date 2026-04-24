<?php

$host = "localhost";
$user="root";
$pass="";
$db="daily";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
date_default_timezone_set('Asia/Jakarta');
mysqli_query($conn, "SET time_zone = '+07:00'");
?>
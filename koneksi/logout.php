<?php
session_start();

// cek apakah request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

// cek csrf token ada atau tidak
if (
    !isset($_SESSION['csrf_token']) ||
    !isset($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    die("Invalid CSRF token");
}

// destroy session
session_unset();
session_destroy();

header("Location: login.php");
exit;
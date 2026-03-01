<?php
include '../koneksi/koneksi.php';
session_start();

$user_id = $_SESSION['id'];

$id = (int)$_POST['id'];
$selesai = (int)$_POST['selesai'];

$stmt = $conn->prepare("
    UPDATE calendar_marks 
    SET selesai=? 
    WHERE id=? AND user_id=?
");
$stmt->bind_param("iii", $selesai, $id, $user_id);
$stmt->execute();

echo "Status diperbarui";
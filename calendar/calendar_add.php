<?php
include '../koneksi/koneksi.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$user_id  = $_SESSION['id'];
$username = $_SESSION['username'] ?? 'Guest';
$tanggal = $_POST['tanggal'] ?? '';
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';

if ($tanggal && $title) {
    $stmt = $conn->prepare("INSERT INTO calendar_marks (user_id, tanggal, title, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $tanggal, $title, $description);
    if ($stmt->execute()) {
        echo "Penanda berhasil disimpan!";
    } else {
        echo "Gagal menyimpan penanda.";
    }
    $stmt->close();
} else {
    echo "Data tidak lengkap!";
}
?>

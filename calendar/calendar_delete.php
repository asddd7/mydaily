<?php
include '../koneksi/koneksi.php';
session_start();

if (!isset($_SESSION['login'])) {
    echo "Unauthorized";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id'] ?? '';
    $user_id = $_SESSION['id'];

    if (!empty($id)) {

        $stmt = $conn->prepare("DELETE FROM calendar_marks WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $id, $user_id);

        if ($stmt->execute()) {
            echo "Penanda berhasil dihapus!";
        } else {
            echo "Gagal menghapus penanda.";
        }

        $stmt->close();
    } else {
        echo "ID tidak valid.";
    }
}
?>

<?php
include 'koneksi.php';
session_start();

if (!isset($_SESSION['login'])) {
    die("Akses ditolak!");
}

$user_id = $_SESSION['id'] ?? 0;


$file_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM backup_files WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $file_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $file = $result->fetch_assoc();
    $file_path = "../uploads/" . $file['file_name'];

    if (file_exists($file_path)) {
        // Buat Nama File
        $download_name = $file['custom_name'] ? $file['custom_name'] . "." . pathinfo($file['file_name'], PATHINFO_EXTENSION) 
                                              : $file['file_name'];
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($download_name) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));

        readfile($file_path);
        exit;
    } else {
        die("File tidak ditemukan di server.");
    }
} else {
    die("File tidak ditemukan.");
}

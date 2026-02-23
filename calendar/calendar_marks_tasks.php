<?php
include '../koneksi/koneksi.php';
session_start();

$user_id = $_SESSION['id'] ?? 0;

$stmt = $conn->prepare("SELECT id, nama_tugas AS title, deadline AS tanggal 
                        FROM tugas 
                        WHERE user_id=? AND parent_id IS NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$marks = [];
while($row = $result->fetch_assoc()){
    $marks[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'tanggal' => $row['tanggal'],
        'url' => '../task.php?date=' . $row['tanggal']
    ];
}
$stmt->close();

header('Content-Type: application/json');
echo json_encode($marks);

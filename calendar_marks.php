<?php
include '../koneksi/koneksi.php';
session_start();

$user_id = $_SESSION['id'];
$month = $_GET['month'];
$year  = $_GET['year'];

$query = $conn->prepare("
    SELECT id, tanggal, title, description, selesai
    FROM calendar_marks
    WHERE user_id=? 
    AND MONTH(tanggal)=?
    AND YEAR(tanggal)=?
    ORDER BY tanggal ASC
");
$query->bind_param("iii", $user_id, $month, $year);
$query->execute();
$result = $query->get_result();

$data = [];
while($row = $result->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);

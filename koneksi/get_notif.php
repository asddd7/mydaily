<?php
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include 'koneksi/koneksi.php';
session_start();

$user_id = $_SESSION['id'] ?? 0;

$stmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM (
        SELECT id 
        FROM tugas 
        WHERE selesai = 0 AND user_id = ? AND parent_id IS NULL

        UNION

        SELECT parent.id
        FROM tugas child
        JOIN tugas parent ON child.parent_id = parent.id
        WHERE child.selesai = 0 AND child.user_id = ?
        GROUP BY parent.id
    ) as notif
");

$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode([
    "count" => $result['total'] ?? 0
]);
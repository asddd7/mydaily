<?php
include '../koneksi/koneksi.php';
session_start();
if(!isset($_SESSION['login'])) exit('Login required');

$user_id = $_SESSION['id'];

$id = intval($_POST['id']);
$is_done = intval($_POST['is_done']);

// Update quest
$stmt = $conn->prepare("UPDATE daily_quest SET is_done=? WHERE id=? AND user_id=?");
$stmt->bind_param("iii", $is_done, $id, $user_id);
if($stmt->execute()){
    // Cek achievement unlock
    $stmt2 = $conn->prepare("SELECT COUNT(*) as done_count FROM daily_quest WHERE user_id=? AND is_done=1");
    $stmt2->bind_param("i",$user_id);
    $stmt2->execute();
    $done_count = $stmt2->get_result()->fetch_assoc()['done_count'];
    $stmt2->close();

    // Misal unlock achievement tiap 50 poin (5 quest x 10 poin)
    if($done_count > 0 && $done_count % 5 == 0){
        $title = "Quest Master: $done_count";
        $desc = "Menyelesaikan $done_count quest harian!";
        $stmt3 = $conn->prepare("INSERT INTO achievements (user_id,title,description) VALUES (?,?,?)");
        $stmt3->bind_param("iss",$user_id,$title,$desc);
        $stmt3->execute();
        $stmt3->close();
    }

    echo 'ok';
} else echo 'Gagal!';
$stmt->close();
?>
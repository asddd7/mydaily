<?php
session_start();
include 'koneksi/koneksi.php';

if (empty($_SESSION['login'])) {
    header("Location: koneksi/login.php");
    exit;
}

$user_id  = $_SESSION['id'];
$username = $_SESSION['username'];

date_default_timezone_set("Asia/Jakarta");
$today = date("Y-m-d");

$q_total = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM tugas 
    WHERE user_id='$user_id' 
    AND deadline='$today'
");
$total_task = mysqli_fetch_assoc($q_total)['total'];

$q_done = mysqli_query($conn, "
    SELECT COUNT(*) as done 
    FROM tugas 
    WHERE user_id='$user_id' 
    AND deadline='$today'
    AND selesai=1
");
$total_done = mysqli_fetch_assoc($q_done)['done'];

$total_pending = $total_task - $total_done;

$q_money = mysqli_query($conn, "
    SELECT SUM(amount) as total_money 
    FROM money_plan 
    WHERE username='$username'
    AND type='expense'
    AND tanggal='$today'
");

$money = mysqli_fetch_assoc($q_money)['total_money'];
$total_money = $money ? $money : 0;

$q_note = mysqli_query($conn, "
    SELECT title, created_at 
    FROM notes 
    WHERE user_id='$user_id' 
    ORDER BY created_at DESC 
    LIMIT 1
");

$latest_note = mysqli_fetch_assoc($q_note);

$quests = [];
$stmt = $conn->prepare("SELECT * FROM daily_quest WHERE user_id=? ORDER BY id ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $quests[] = $row;
}
$stmt->close();

$points = 0;
foreach ($quests as $q) {
    if ($q['is_done']) $points += 10;
}

$achievements = [];
$stmt = $conn->prepare("SELECT * FROM achievements WHERE user_id=? ORDER BY unlocked_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $achievements[] = $row;
}
$stmt->close();

// ===============================
// SYSTEM RESET QUEST HARIAN
// ===============================

// Cek apakah sudah ada quest hari ini
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM daily_quest WHERE user_id=? AND DATE(created_at)=?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$total_today = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

if($total_today == 0){

    // Cek jumlah quest selesai kemarin
    $stmt = $conn->prepare("
        SELECT COUNT(*) as done_count 
        FROM daily_quest 
        WHERE user_id=? 
        AND DATE(created_at) < ? 
        AND is_done=1
    ");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $done_count = $stmt->get_result()->fetch_assoc()['done_count'];
    $stmt->close();

    // Jika minimal 5 quest selesai
    if($done_count >= 5){

        // Hapus quest lama
        $stmt = $conn->prepare("DELETE FROM daily_quest WHERE user_id=? AND DATE(created_at) < ?");
        $stmt->bind_param("is", $user_id, $today);
        $stmt->execute();
        $stmt->close();
    }

    // Generate quest baru untuk hari ini
    $q_master = mysqli_query($conn, "SELECT * FROM daily_quest_master ORDER BY RAND() LIMIT 5");

    while($row = mysqli_fetch_assoc($q_master)){
        $stmt = $conn->prepare("INSERT INTO daily_quest (user_id, quest_title, created_at) VALUES (?,?,NOW())");
        $stmt->bind_param("is", $user_id, $row['quest_title']);
        $stmt->execute();
        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="content">

    <div class="card">
        <h3>📅 Daily Summary - <?= date("d F Y"); ?></h3>

        <div style="margin-top:20px;line-height:1.8;font-size:16px;">

            <hr style="margin:15px 0;">

            <p>
                <strong>💰 Total Pengeluaran Hari Ini:</strong> 
                Rp <?= number_format($total_money,0,',','.'); ?>
            </p>

            <hr style="margin:15px 0;">

            <p><strong>📝 Catatan Terbaru:</strong></p>

            <?php if ($latest_note) : ?>
                <div style="margin-top:8px;background:#f1f5f9;padding:12px;border-radius:8px;">
                    <strong><?= htmlspecialchars($latest_note['title']); ?></strong><br>
                    <small><?= date("d M Y H:i", strtotime($latest_note['created_at'])); ?></small>
                </div>
            <?php else : ?>
                <p>Belum ada catatan.</p>
            <?php endif; ?>

        </div>
    </div>

</div>
</body>
</html>
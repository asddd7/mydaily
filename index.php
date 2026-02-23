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

/* =========================
   TASK HARI INI
========================= */
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

/* =========================
   PENGELUARAN HARI INI
========================= */
$q_money = mysqli_query($conn, "
    SELECT SUM(amount) as total_money 
    FROM money_plan 
    WHERE username='$username'
    AND type='expense'
    AND tanggal='$today'
");

$money = mysqli_fetch_assoc($q_money)['total_money'];
$total_money = $money ? $money : 0;

/* =========================
   CATATAN TERBARU
========================= */
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

// Hitung poin user
$points = 0;
foreach ($quests as $q) {
    if ($q['is_done']) $points += 10; // misal 10 poin per quest
}

// Cek achievement
$achievements = [];
$stmt = $conn->prepare("SELECT * FROM achievements WHERE user_id=? ORDER BY unlocked_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $achievements[] = $row;
}
$stmt->close();

// === Generate 5 quest random hari ini jika belum ada ===
$q_check = $conn->prepare("SELECT COUNT(*) as total FROM daily_quest WHERE user_id=? AND DATE(created_at)=?");
$q_check->bind_param("is", $user_id, $today);
$q_check->execute();
$total_today = $q_check->get_result()->fetch_assoc()['total'];
$q_check->close();

if($total_today == 0){
    // Ambil 5 quest random dari master
    $q_master = mysqli_query($conn, "SELECT * FROM daily_quest_master ORDER BY RAND() LIMIT 5");
    while($row = mysqli_fetch_assoc($q_master)){
        $stmt = $conn->prepare("INSERT INTO daily_quest (user_id, quest_title) VALUES (?,?)");
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

            <p><strong>📝 Total Task Hari Ini:</strong> <?= $total_task; ?></p>

            <p style="color:green;">
                <strong>✔ Selesai:</strong> <?= $total_done; ?>
            </p>

            <p style="color:#dc2626;">
                <strong>⏳ Belum Selesai:</strong> <?= $total_pending; ?>
            </p>

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

        <div class="card">
        <h3>🎯 Daily Quest</h3>
        <p>Total Poin: <strong><?= $points; ?></strong></p>

        <?php if($quests): ?>
            <ul class="quest-list">
                <?php foreach($quests as $q): ?>
                <li>
                    <input type="checkbox" class="quest-checkbox" data-id="<?= $q['id']; ?>" <?= $q['is_done'] ? 'checked' : ''; ?>>
                    <span class="<?= $q['is_done'] ? 'done' : ''; ?>"><?= htmlspecialchars($q['quest_title']); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Tidak ada quest hari ini.</p>
        <?php endif; ?>

        <h4>🏆 Achievements</h4>
        <?php if($achievements): ?>
            <ul class="achievement-list">
                <?php foreach($achievements as $a): ?>
                <li><strong><?= htmlspecialchars($a['title']); ?></strong> - <?= htmlspecialchars($a['description']); ?> <small>(<?= date('d M Y', strtotime($a['unlocked_at'])); ?>)</small></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Belum ada achievement.</p>
        <?php endif; ?>
    </div>

</div>
<script>
    document.querySelectorAll('.quest-checkbox').forEach(cb => {
    cb.addEventListener('change', function() {
        const questId = this.dataset.id;
        const isDone = this.checked ? 1 : 0;

        fetch('sub/daily_quest_toggle.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: `id=${questId}&is_done=${isDone}`
        })
        .then(res => res.text())
        .then(res => {
            if(res === 'ok') location.reload(); // reload untuk update poin & achievement
            else alert(res);
        });
    });
});
</script>
</body>
</html>
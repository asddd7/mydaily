<?php
session_start();
include '../koneksi/koneksi.php';

if (!isset($_SESSION['login'])) {
    header("Location: koneksi/login.php");
    exit;
}

$user_id = $_SESSION['id'];
$username = $_SESSION['username'] ?? 'Guest';

if (!isset($_GET['id'])) {
    header("Location: notes.php");
    exit;
}

$id = (int) $_GET['id'];

$query = mysqli_query($conn, "SELECT * FROM notes 
                              WHERE id='$id' AND user_id='$user_id'");

$note = mysqli_fetch_assoc($query);

if (!$note) {
    echo "Catatan tidak ditemukan.";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($note['title']); ?></title>
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<div class="layout">
<?php include '../sidebar.php'; ?>
<div class="content">
    <div class="card">
        <h2><?= htmlspecialchars($note['title']); ?></h2>
        <small><?= $note['created_at']; ?></small>
        <hr>
        <p><?= nl2br(htmlspecialchars($note['content'])); ?></p>

        <br>
    </div>
<a href="../notes.php" class="btn-back">← Kembali</a>
</div>
</div>
</body>
</html>
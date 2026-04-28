<?php
session_start();
include '../koneksi/koneksi.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../koneksi/login.php");
    exit;
}

$user_id = $_SESSION['id'];

if (!isset($_GET['id'])) {
    header("Location: ../notes.php");
    exit;
}

$id = (int) $_GET['id'];
$isEdit = isset($_GET['edit']);

/* =========================
   UPDATE NOTE
========================= */
if (isset($_POST['update_note'])) {
    $title   = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);

    $imageQuery = "";

    if (!empty($_FILES['image']['name'])) {
        $imageName = time() . "_" . basename($_FILES['image']['name']);
        $tmp       = $_FILES['image']['tmp_name'];

        $uploadPath = __DIR__ . "/../uploads/" . $imageName;

        if (!move_uploaded_file($tmp, $uploadPath)) {
            die("Upload gagal!");
        }

        $imageQuery = ", image='$imageName'";
    }

    mysqli_query($conn, "UPDATE notes 
                         SET title='$title', content='$content' $imageQuery
                         WHERE id='$id' AND user_id='$user_id'");

    header("Location: note_detail.php?id=$id");
    exit;
}

/* =========================
   GET DATA
========================= */
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>

<div class="layout">
<?php include '../sidebar.php'; ?>
<div class="content">
<div class="card">

<?php if ($isEdit): ?>
<!-- =========================
     MODE EDIT
========================= -->

<h2>Edit Catatan</h2>

<form method="POST" enctype="multipart/form-data">

    <input type="text" name="title" 
           value="<?= htmlspecialchars($note['title']); ?>" required>

    <textarea name="content" rows="8" required><?= htmlspecialchars($note['content']); ?></textarea>

    <br>

    <?php if (!empty($note['image'])): ?>
        <small>Gambar saat ini:</small><br>
        <img src="../uploads/<?= htmlspecialchars($note['image']); ?>" 
             style="max-width:200px; margin:10px 0;">
    <?php endif; ?>

    <input type="file" name="image" accept="image/*">

    <br><br>

    <button type="submit" name="update_note">Update</button>
    <a href="note_detail.php?id=<?= $id; ?>" class="btn-back">Batal</a>

</form>

<?php else: ?>
<!-- =========================
     MODE VIEW
========================= -->

<h2><?= htmlspecialchars($note['title']); ?></h2>
<small><?= $note['created_at']; ?></small>

<?php if (!empty($note['image']) && file_exists("../uploads/".$note['image'])): ?>
    <img src="../uploads/<?= htmlspecialchars($note['image']); ?>" class="note-detail-img">
<?php else: ?>
    <small style="color:gray;">Tidak ada gambar</small>
<?php endif; ?>

<hr>

<p><?= nl2br(htmlspecialchars($note['content'])); ?></p>

<br>

<a href="?id=<?= $id; ?>&edit=1" class="btn-edit">✏ Edit</a>

<?php endif; ?>

<br><br>
<a href="../notes.php" class="btn-back">← Kembali</a>

</div>
</div>
</div>

</body>
</html>
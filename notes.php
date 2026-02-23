<?php
session_start();
include 'koneksi/koneksi.php';

if (!isset($_SESSION['login'])) {
    header("Location: koneksi/login.php");
    exit;
}

$user_id  = $_SESSION['id'];
$username = $_SESSION['username'] ?? 'Guest';

// Tambah Note
if (isset($_POST['save_note'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);

    if (!empty($title) && !empty($content)) {
        mysqli_query($conn, "INSERT INTO notes (user_id, title, content) 
                             VALUES ('$user_id', '$title', '$content')");
    }
}

// Delete Note
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    mysqli_query($conn, "DELETE FROM notes WHERE id='$id' AND user_id='$user_id'");
}

$notes = mysqli_query($conn, "SELECT * FROM notes 
                              WHERE user_id='$user_id' 
                              ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Notes</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="layout">
<?php include 'sidebar.php'; ?>
<div class="content">

    <div class="card">
        <h3>Daftar Catatan</h3>

        <button class="btn-add-task" onclick="openModal()">+ Tambah Catatan</button>

        <br><br>

        <?php while ($row = mysqli_fetch_assoc($notes)) : ?>
            <div class="note-item">
                <h4>
                <a href="sub/note_detail.php?id=<?= $row['id']; ?>" class="note-link">
                    <?= htmlspecialchars($row['title']); ?>
                </a>
                </h4>
                <small><?= $row['created_at']; ?></small>
                
                <a href="?delete=<?= $row['id']; ?>" 
                   onclick="return confirm('Hapus catatan ini?')"
                   class="delete-mark">Hapus</a>
                <hr>
            </div>
        <?php endwhile; ?>

    </div>

</div>
</div>

<!-- Modal -->
<div class="modal" id="noteModal">
    <div class="modal-content">
        <h3>Tambah Catatan</h3>

        <form method="POST">
            <input type="text" name="title" placeholder="Judul Catatan" required>
            <textarea name="content" rows="8" placeholder="Tulis catatan panjang di sini..." required></textarea>

            <div style="display:flex; gap:10px; margin-top:10px;">
                <button type="submit" name="save_note" style="flex:1;">Simpan</button>
                <button type="button" onclick="closeModal()" style="flex:1; background:#ef4444;">Tutup</button>
            </div>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById("noteModal");

function openModal(){
    modal.classList.add("show");
}

function closeModal(){
    modal.classList.remove("show");
}

window.addEventListener("click", function(e){
    if(e.target === modal){
        closeModal();
    }
});

document.addEventListener("keydown", function(e){
    if(e.key === "Escape"){
        closeModal();
    }
});
</script>

</body>
</html>
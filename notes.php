<?php
session_start();
include 'koneksi/koneksi.php';
mysqli_query($conn, "SET time_zone = '+07:00'");

if (!isset($_SESSION['login'])) {
    header("Location: koneksi/login.php");
    exit;
}

$user_id  = $_SESSION['id'];
$username = $_SESSION['username'] ?? 'Guest';

/* =========================
   TAMBAH NOTE
========================= */
if (isset($_POST['save_note'])) {
    $title   = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);

    if (!empty($title) && !empty($content)) {
        mysqli_query($conn, "INSERT INTO notes (user_id, title, content) 
                             VALUES ('$user_id', '$title', '$content')");
        header("Location: notes.php");
        exit;
    }
}

/* =========================
   UPDATE NOTE
========================= */
if (isset($_POST['update_note'])) {
    $note_id = (int) $_POST['note_id'];
    $title   = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);

    mysqli_query($conn, "UPDATE notes 
                         SET title='$title', content='$content' 
                         WHERE id='$note_id' AND user_id='$user_id'");
    
    header("Location: notes.php");
    exit;
}

/* =========================
   DELETE NOTE
========================= */
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    mysqli_query($conn, "DELETE FROM notes WHERE id='$id' AND user_id='$user_id'");
    header("Location: notes.php");
    exit;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<div class="layout">
<?php include 'sidebar.php'; ?>
<div class="content">

    <div class="card">
        <h3>Daftar Catatan</h3>

        <button class="btn-add-task" onclick="openAddModal()">+ Tambah Catatan</button>

        <br><br>

        <?php while ($row = mysqli_fetch_assoc($notes)) : ?>
            <div class="note-item">
                <h4>
                    <a href="sub/note_detail.php?id=<?= $row['id']; ?>" class="note-link">
                        <?= htmlspecialchars($row['title']); ?>
                    </a>
                </h4>

                <small><?= $row['created_at']; ?></small>

                <div style="margin-top:5px;">
                <button class="btn-edit" onclick='openEditModal(
                    <?= json_encode($row["id"]); ?>,
                    <?= json_encode($row["title"]); ?>,
                    <?= json_encode($row["content"]); ?>
                )'>Edit</button>

                    <a href="?delete=<?= $row['id']; ?>" 
                       onclick="return confirm('Hapus catatan ini?')"
                       class="delete-mark">Hapus</a>
                </div>
                <hr>
            </div>
        <?php endwhile; ?>

    </div>

</div>
</div>

<!-- =========================
     MODAL TAMBAH
========================= -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <h3>Tambah Catatan</h3>

        <form method="POST">
            <input type="text" name="title" placeholder="Judul Catatan" required>
            <textarea name="content" rows="8" placeholder="Tulis catatan..." required></textarea>

            <div style="display:flex; gap:10px; margin-top:10px;">
                <button type="submit" name="save_note" style="flex:1;">Simpan</button>
                <button type="button" onclick="closeModal()" style="flex:1; background:#ef4444;">Tutup</button>
            </div>
        </form>
    </div>
</div>

<!-- =========================
     MODAL EDIT
========================= -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <h3>Edit Catatan</h3>

        <form method="POST">
            <input type="hidden" name="note_id" id="edit_id">

            <input type="text" name="title" id="edit_title" required>
            <textarea name="content" rows="8" id="edit_content" required></textarea>

            <div style="display:flex; gap:10px; margin-top:10px;">
                <button type="submit" name="update_note" style="flex:1;">Update</button>
                <button type="button" onclick="closeModal()" style="flex:1; background:#ef4444;">Tutup</button>
            </div>
        </form>
    </div>
</div>

<script>
const addModal  = document.getElementById("addModal");
const editModal = document.getElementById("editModal");

function openAddModal(){
    addModal.classList.add("show");
}

function openEditModal(id, title, content){
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_title").value = title;
    document.getElementById("edit_content").value = content;

    editModal.classList.add("show");
}

function closeModal(){
    addModal.classList.remove("show");
    editModal.classList.remove("show");
}

window.addEventListener("click", function(e){
    if(e.target === addModal) closeModal();
    if(e.target === editModal) closeModal();
});

document.addEventListener("keydown", function(e){
    if(e.key === "Escape"){
        closeModal();
    }
});
</script>

</body>
</html>
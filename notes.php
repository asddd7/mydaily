<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

    $imageName = null;

    if (!empty($_FILES['image']['name'])) {
        $imageName = time() . "_" . basename($_FILES['image']['name']);
        $tmp       = $_FILES['image']['tmp_name'];

        $uploadPath = __DIR__ . "/uploads/" . $imageName;

        if (!move_uploaded_file($tmp, $uploadPath)) {
            die("Upload gagal! cek folder uploads permission.");
        }
    }

    mysqli_query($conn, "INSERT INTO notes (user_id, title, content, image) 
                         VALUES ('$user_id', '$title', '$content', '$imageName')");

    header("Location: notes.php");
    exit;
}

/* =========================
   UPDATE NOTE
========================= */
if (isset($_POST['update_note'])) {
    $note_id = (int) $_POST['note_id'];
    $title   = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);

    $imageQuery = "";

    if (!empty($_FILES['image']['name'])) {
        $imageName = time() . "_" . basename($_FILES['image']['name']);
        $tmp       = $_FILES['image']['tmp_name'];

        $uploadPath = __DIR__ . "/uploads/" . $imageName;

        if (!move_uploaded_file($tmp, $uploadPath)) {
            die("Upload gagal! cek folder uploads permission.");
        }

        $imageQuery = ", image='$imageName'";
    }

    mysqli_query($conn, "UPDATE notes 
                         SET title='$title', content='$content' $imageQuery
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

            <?php if (!empty($row['image'])): ?>
                <img src="uploads/<?= htmlspecialchars($row['image']); ?>" 
                    style="max-width:200px; display:block; margin-top:10px;">
            <?php endif; ?>

            <h4>
                <a href="sub/note_detail.php?id=<?= $row['id']; ?>" class="note-link">
                    <?= htmlspecialchars($row['title']); ?>
                </a>
            </h4>

            <small><?= $row['created_at']; ?></small>

                <div style="margin-top:5px;">
                <a href="sub/note_detail.php?id=<?= $row['id']; ?>&edit=1" class="btn-edit">Edit</a>

                    <a href="?delete=<?= $row['id']; ?>" 
                       onclick="return confirm('Hapus catatan ini?')"
                       class="delete-mark">Hapus</a>
                </div>
            </div>
            <hr>
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

    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="title" placeholder="Judul Catatan" required>
        <textarea name="content" rows="8" placeholder="Tulis catatan..." required></textarea>

        <input type="file" name="image" accept="image/*">

        <div style="display:flex; gap:10px; margin-top:10px;">
            <button type="submit" name="save_note">Simpan</button>
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
        <div id="edit_image_preview" style="margin:10px 0;"></div>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="note_id" id="edit_id">

        <input type="text" name="title" id="edit_title" required>
        <textarea name="content" id="edit_content" required></textarea>

        <input type="file" name="image" accept="image/*">

        <button type="submit" name="update_note">Update</button>
    </form>
    </div>
</div>

<script>
const addModal  = document.getElementById("addModal");
const editModal = document.getElementById("editModal");

function openAddModal(){
    addModal.classList.add("show");
}

function openEditModal(id, title, content, image){
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_title").value = title;
    document.getElementById("edit_content").value = content;

    let preview = document.getElementById("edit_image_preview");

    if(image && image !== "null"){
        preview.innerHTML = `
            <small>Gambar saat ini:</small><br>
            <img src="uploads/${image}" 
                 style="max-width:150px; border-radius:8px; margin-top:5px;">
        `;
    } else {
        preview.innerHTML = "<small style='color:gray'>Tidak ada gambar</small>";
    }

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
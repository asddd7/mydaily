<?php
include 'koneksi/koneksi.php';
session_start();

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$user_id  = $_SESSION['id'];
$username = $_SESSION['username'] ?? 'Guest';
$success = "";
$error = "";

// Folder upload
$upload_dir = "uploads/";

// Buat folder kalau belum ada
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (isset($_POST['upload_file'])) {

    $custom_name = trim($_POST['custom_name']);

    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $file_name = basename($_FILES['file']['name']);
        $file_tmp  = $_FILES['file']['tmp_name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['xls','xlsx','pdf','doc','docx','jpg','jpeg','png','gif'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_name = time() . "_" . $file_name;
            $target_file = $upload_dir . $new_name;

            if (move_uploaded_file($file_tmp, $target_file)) {
                $stmt = $conn->prepare("INSERT INTO backup_files (user_id, file_name, custom_name) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $user_id, $new_name, $custom_name);
                if ($stmt->execute()) {
                    $success = "File berhasil diupload!";
                } else {
                    $error = "Gagal menyimpan info file ke database.";
                }
                $stmt->close();
            } else {
                $error = "Gagal menyimpan file.";
            }
        } else {
            $error = "Tipe file tidak diizinkan!";
        }
    } else {
        $error = "Tidak ada file yang dipilih!";
    }
}

// Delete
if (isset($_GET['delete'])) {
    $file_id = intval($_GET['delete']);

    $stmt = $conn->prepare("SELECT file_name FROM backup_files WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $file_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $file = $result->fetch_assoc();
        $file_path = $upload_dir . $file['file_name'];

        if (file_exists($file_path)) {
            unlink($file_path);
        }

        $stmt_del = $conn->prepare("DELETE FROM backup_files WHERE id=? AND user_id=?");
        $stmt_del->bind_param("ii", $file_id, $user_id);
        $stmt_del->execute();
        $stmt_del->close();

        $success = "File berhasil dihapus!";
    } else {
        $error = "File tidak ditemukan!";
    }

    $stmt->close();
}

$stmt = $conn->prepare("SELECT * FROM backup_files WHERE user_id=? ORDER BY uploaded_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$files = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Backup Data</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<div class="layout">
<?php include 'sidebar.php'; ?>

<main class="content">

<div class="card">
    <h3>Backup Data</h3>

    <?php if ($success): ?>
        <p style="color:green;font-weight:bold;"><?= $success ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p style="color:red;font-weight:bold;"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="backup-form">
        <label>Nama File (opsional):</label><br>
        <input type="text" name="custom_name" placeholder="Misal: Laporan Januari"><br><br>

        <label>Pilih file (Excel, Word, PDF, Gambar):</label><br>
        <input type="file" name="file" required><br><br>

        <button type="submit" name="upload_file" class="btn-add-task">Upload</button>
    </form>
</div>

<div class="card">
    <h3>File yang sudah diupload</h3>
    <?php if (!empty($files)): ?>
    <ul>
    <?php foreach ($files as $f): ?>
        <li>
            <a href="download.php?id=<?= $f['id'] ?>">
                <?= htmlspecialchars($f['custom_name'] ?: $f['file_name']) ?>
            </a>
            <small>(<?= date('d-m-Y H:i', strtotime($f['uploaded_at'])) ?>)</small>
            &nbsp;
            <a href="?delete=<?= $f['id'] ?>" 
            class="btn-delete"
            onclick="return confirm('Yakin ingin menghapus file ini?')">
            Hapus
            </a>
        </li>
    <?php endforeach; ?>
    </ul>
    <?php else: ?>
        <p>Belum ada file yang diupload.</p>
    <?php endif; ?>
</div>

</main>
</div>
</body>
</html>

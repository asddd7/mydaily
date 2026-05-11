<?php
include 'koneksi/koneksi.php';

$user_id = $_SESSION['id'];

$upload_dir = "uploads/files/";

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

/* UPLOAD */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {

    $file = $_FILES['file'];

    if ($file['error'] === 0) {

        $original = basename($file['name']);

        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        $mime = mime_content_type($file['tmp_name']);

        $allowed = [
            'jpg','jpeg','png','gif',
            'pdf','doc','docx',
            'xls','xlsx',
            'zip','rar','txt'
        ];

        if (in_array($ext, $allowed)) {

            $new_name = uniqid() . "." . $ext;

            move_uploaded_file(
                $file['tmp_name'],
                $upload_dir . $new_name
            );

            $stmt = $conn->prepare("
                INSERT INTO file_manager
                (
                    user_id,
                    file_name,
                    file_original,
                    file_size,
                    file_type
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "issis",
                $user_id,
                $new_name,
                $original,
                $file['size'],
                $ext
            );

            $stmt->execute();

            echo "<script>
                alert('Upload berhasil');
                location='file_manager.php';
            </script>";
        }
    }
}

/* DELETE */
if (isset($_GET['delete'])) {

    $id = (int)$_GET['delete'];

    $stmt = $conn->prepare("
        SELECT file_name
        FROM file_manager
        WHERE id = ?
        AND user_id = ?
    ");

    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        $path = $upload_dir . $row['file_name'];

        if (file_exists($path)) {
            unlink($path);
        }

        $del = $conn->prepare("
            DELETE FROM file_manager
            WHERE id = ?
            AND user_id = ?
        ");

        $del->bind_param("ii", $id, $user_id);
        $del->execute();
    }

    header("Location:file_manager.php");
    exit;
}

/* GET FILES */
$stmt = $conn->prepare("
    SELECT *
    FROM file_manager
    WHERE user_id = ?
    ORDER BY id DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$files = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
.calendar-marks ul{list-style:none;padding-left:0;margin-top:10px;}
.calendar-marks li{margin-bottom:5px;}
.today{background:#00f;color:#fff;border-radius:50%;text-align:center;}
.btn-done{background:#22c55e;color:#fff;border:none;padding:5px 8px;border-radius:6px;cursor:pointer;transition:0.2s;}
.btn-done:hover:not(:disabled){background:#16a34a;}
.btn-done:disabled{opacity:0.6;cursor:not-allowed;}
</style>
</head>
<body>
<div class="layout">
<?php include 'sidebar.php'; ?>
<main class="content">
<div class="main-content">
    <div class="card">

    <div class="page-header">
        <h1>File Manager</h1>
    </div>

    <div class="upload-card">

        <form method="POST" enctype="multipart/form-data">

            <input type="file" name="file" required>

            <button type="submit">
                Upload File
            </button>

        </form>

    </div>

    <div class="file-grid">

        <?php while($file = $files->fetch_assoc()): ?>

            <?php
            $ext = $file['file_type'];

            $icon = "fa-file";

            if (in_array($ext, ['jpg','jpeg','png','gif'])) {
                $icon = "fa-file-image";
            }

            elseif ($ext == 'pdf') {
                $icon = "fa-file-pdf";
            }

            elseif (in_array($ext, ['doc','docx'])) {
                $icon = "fa-file-word";
            }

            elseif (in_array($ext, ['xls','xlsx'])) {
                $icon = "fa-file-excel";
            }

            elseif (in_array($ext, ['zip','rar'])) {
                $icon = "fa-file-zipper";
            }
            ?>

            <div class="file-card">

                <div class="file-icon">
                    <i class="fa-solid <?= $icon ?>"></i>
                </div>

                <div class="file-info">

                    <div class="file-name">
                        <?= htmlspecialchars($file['file_original']) ?>
                    </div>

                    <div class="file-size">
                        <?= round($file['file_size'] / 1024, 2) ?> KB
                    </div>

                </div>

                <div class="file-actions">

                    <a
                        href="uploads/files/<?= $file['file_name'] ?>"
                        download
                        class="download-btn"
                    >
                        Download
                    </a>

                    <a
                        href="?delete=<?= $file['id'] ?>"
                        onclick="return confirm('Hapus file ini?')"
                        class="delete-btn"
                    >
                        Delete
                    </a>

                </div>

            </div>

        <?php endwhile; ?>

    </div>

</div>
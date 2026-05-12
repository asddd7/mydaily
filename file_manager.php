<?php
include 'koneksi/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = $_SESSION['id'];

$upload_dir = "uploads/files/";

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

/* UPLOAD */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {

    $file = $_FILES['file'];

    if ($file['error'] === 0) {

        $original = preg_replace(
            "/[^a-zA-Z0-9._-]/",
            "_",
            basename($file['name'])
        );

        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        $allowed = [
            'jpg','jpeg','png','gif',
            'mp4','mov','avi','mkv','webm',
            'pdf','doc','docx',
            'xls','xlsx',
            'zip','rar','txt'
        ];

        if (!in_array($ext, $allowed)) {

            die("Format file tidak diizinkan");
        }

        $max_size = 50 * 1024 * 1024;

        if ($file['size'] > $max_size) {

            echo "<script>
                alert('Ukuran file terlalu besar. Maksimal 50MB');
                history.back();
            </script>";

            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed_mime = [
            'image/jpeg',
            'image/png',
            'image/gif',

            'video/mp4',
            'video/webm',
            'video/x-msvideo',
            'video/quicktime',
            'video/x-matroska',
            'application/octet-stream',

            'application/pdf',

            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',

            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

            'application/zip',
            'application/x-rar-compressed',

            'text/plain'
        ];

        if (!in_array($mime, $allowed_mime)) {

            echo "<script>
                alert('Tipe file tidak valid');
                history.back();
            </script>";

            exit;
        }

        $new_name = bin2hex(random_bytes(16)) . "." . $ext;

        if (
            move_uploaded_file(
                $file['tmp_name'],
                $upload_dir . $new_name
            )
        ) {

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

/* UPDATE */

/* EDIT NAMA FILE */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_file'])) {

    $id = (int)$_POST['file_id'];

    $new_name = trim($_POST['new_name']);

    if ($new_name !== '') {

        $new_name = preg_replace(
            "/[^a-zA-Z0-9._ -]/",
            "_",
            $new_name
        );

        $stmt = $conn->prepare("
            UPDATE file_manager
            SET file_original = ?
            WHERE id = ?
            AND user_id = ?
        ");

        $stmt->bind_param(
            "sii",
            $new_name,
            $id,
            $user_id
        );

        $stmt->execute();

        echo "<script>
            alert('Nama file berhasil diubah');
            location='file_manager.php';
        </script>";

        exit;
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

<div class="card">
<div class="main-content">

    <div class="page-header">
        <h1>File Manager</h1>
    </div>

    <div class="upload-card">

        <form method="POST" enctype="multipart/form-data">

            <input 
                type="file" 
                name="file" 
                required
                accept=".jpg,.jpeg,.png,.gif,.mp4,.mov,.avi,.mkv,.webm,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar,.txt"
            >

            <button type="submit" class="btn-add-task">
                Upload File
            </button>

        </form>

    </div>

    <div class="file-grid">

        <?php while($file = $files->fetch_assoc()): ?>

            <?php
            $ext = strtolower(trim(pathinfo($file['file_name'], PATHINFO_EXTENSION)));
            $ext = preg_replace('/[^a-z0-9]/', '', $ext);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, "uploads/files/" . $file['file_name']);
            finfo_close($finfo);

            $icons = [
            'jpg' => 'fa-file-image',
            'jpeg' => 'fa-file-image',
            'png' => 'fa-file-image',
            'gif' => 'fa-file-image',
            'pdf' => 'fa-file-pdf',
            'doc' => 'fa-file-word',
            'docx' => 'fa-file-word',
            'xls' => 'fa-file-excel',
            'xlsx' => 'fa-file-excel',
            'zip' => 'fa-file-zipper',
            'rar' => 'fa-file-zipper',
            'mp4' => 'fa-file-video',
            'mov' => 'fa-file-video',
            'avi' => 'fa-file-video',
            'mkv' => 'fa-file-video',
            'webm' => 'fa-file-video',
        ];

        $icon = $icons[$ext] ?? 'fa-file';
            ?>

            <div class="file-card">
                <?php if (in_array($ext, ['mp4','mov','avi','mkv','webm'])): ?>

            <video width="100%" controls style="border-radius:10px;margin-bottom:10px;">
            <?php
            $videoMime = [
                'mp4' => 'video/mp4',
                'webm' => 'video/webm',
                'avi' => 'video/x-msvideo',
                'mov' => 'video/quicktime',
                'mkv' => 'video/x-matroska'
            ];
            ?>

            <source 
                src="uploads/files/<?= $file['file_name'] ?>"
                type="<?= $videoMime[$ext] ?? 'video/mp4' ?>"
            >
            </video>

                <?php endif; ?>

                <?php if (in_array($ext, ['jpg','jpeg','png','gif'])): ?>

                    <img 
                        src="uploads/files/<?= $file['file_name'] ?>"
                        style="
                            width:100%;
                            height:180px;
                            object-fit:cover;
                            border-radius:10px;
                            margin-bottom:10px;
                        "
                    >

                <?php endif; ?>

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

                    <button
                        class="btn-edit"
                        onclick="openRenameModal(
                            <?= $file['id'] ?>,
                            '<?= htmlspecialchars($file['file_original'], ENT_QUOTES) ?>'
                        )"
                    >
                        Edit
                    </button>

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
</div>
<div id="renameModal" class="rename-modal">

            <div class="rename-box">

                <h3>Edit Nama File</h3>

                <form method="POST">

                    <input type="hidden" name="file_id" id="renameFileId">

                    <input type="hidden" name="edit_file" value="1">

                    <input
                        type="text"
                        name="new_name"
                        id="renameInput"
                        required
                    >

                    <div class="rename-actions">

                        <button type="submit">
                            Save
                        </button>

                        <button
                            type="button"
                            onclick="closeRenameModal()"
                        >
                            Cancel
                        </button>

                    </div>

                </form>

            </div>

<script>
function openRenameModal(id, name) {

    document
        .getElementById("renameModal")
        .classList.add("show");

    document
        .getElementById("renameFileId")
        .value = id;

    document
        .getElementById("renameInput")
        .value = name;
}

function closeRenameModal() {

    document
        .getElementById("renameModal")
        .classList.remove("show");
}
</script>
</main>
</div>
</body>
</html>
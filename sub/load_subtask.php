<?php
include '../koneksi/koneksi.php';
session_start();

if (!isset($_SESSION['login'])) {
    exit;
}

$user_id = $_SESSION['id'];

/* Subtask */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
    $task_id = intval($_POST['task_id']);
    $selesai = isset($_POST['selesai']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE tugas SET selesai=? WHERE id=? AND user_id=?");
    $stmt->bind_param("iii", $selesai, $task_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

/*Load Subtask*/
if (!isset($_GET['parent_id'])) {
    exit;
}

$parent_id = intval($_GET['parent_id']);

$stmt = $conn->prepare("SELECT * FROM tugas 
                        WHERE parent_id=? AND user_id=?
                        ORDER BY id ASC");
$stmt->bind_param("ii", $parent_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<ul>";

    while ($row = $result->fetch_assoc()) {
        $checked = $row['selesai'] ? "checked" : "";
        $style = $row['selesai'] ? "text-decoration:line-through;color:gray;" : "";

    }

    echo "</ul>";
} else {
    echo "<p style='color:gray;'>Belum ada subtask.</p>";
}

/* DELETE SUBTASK */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_subtask'])) {
    $id = intval($_POST['delete_subtask']);

    $stmt = $conn->prepare("DELETE FROM tugas WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $stmt->close();
}

/* EDIT SUBTASK */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_subtask_id'], $_POST['edit_subtask_nama'])) {
    $id = intval($_POST['edit_subtask_id']);
    $nama = trim($_POST['edit_subtask_nama']);

    if ($nama) {
        $stmt = $conn->prepare("UPDATE tugas SET nama_tugas=? WHERE id=? AND user_id=?");
        $stmt->bind_param("sii", $nama, $id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

?>

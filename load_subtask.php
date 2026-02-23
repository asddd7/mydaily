<?php
include 'koneksi/koneksi.php';
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

        echo "
        <li style='margin-bottom:6px;'>
            <form method='post' style='display:inline;' 
                  onsubmit='updateSubtask(event, {$parent_id})'>
                <input type='hidden' name='task_id' value='{$row['id']}'>
                <input type='checkbox' name='selesai' value='1'
                       onchange='this.form.submit()' $checked>
            </form>
            <span style='$style'>
                " . htmlspecialchars($row['nama_tugas']) . "
            </span>
        </li>";
    }

    echo "</ul>";
} else {
    echo "<p style='color:gray;'>Belum ada subtask.</p>";
}
?>

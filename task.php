<?php
include 'koneksi/koneksi.php';
session_start();
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

if (!isset($_SESSION['login'])) {
    header("Location: koneksi/login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'Guest';
$user_id  = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['nama_tugas'], $_POST['deadline'])) {
        $nama_tugas = trim($_POST['nama_tugas']);
        $deadline   = $_POST['deadline'];

        if ($nama_tugas && $deadline) {
            $stmt = $conn->prepare("INSERT INTO tugas (nama_tugas, deadline, user_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $nama_tugas, $deadline, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    if (isset($_POST['parent_id'], $_POST['nama_subtask'])) {
        $parent_id    = intval($_POST['parent_id']);
        $nama_subtask = trim($_POST['nama_subtask']);

        if ($nama_subtask) {
            $stmt = $conn->prepare("INSERT INTO tugas (nama_tugas, deadline, user_id, parent_id) 
                                    VALUES (?, CURDATE(), ?, ?)");
            $stmt->bind_param("sii", $nama_subtask, $user_id, $parent_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    if (isset($_POST['task_id'])) {
        $task_id = intval($_POST['task_id']);
        $selesai = isset($_POST['selesai']) ? 1 : 0;

        $stmt = $conn->prepare("UPDATE tugas SET selesai=? WHERE id=? AND user_id=?");
        $stmt->bind_param("iii", $selesai, $task_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    if (isset($_POST['delete_task'])) {
        $task_id = intval($_POST['delete_task']);

        $stmt = $conn->prepare("DELETE FROM tugas WHERE parent_id=? AND user_id=?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM tugas WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    if (isset($_POST['copy_task'])) {
        $task_id = intval($_POST['copy_task']);

        $stmt = $conn->prepare("SELECT nama_tugas FROM tugas WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $resultCopy = $stmt->get_result();
        $taskData = $resultCopy->fetch_assoc();
        $stmt->close();

        if ($taskData) {

            $stmt = $conn->prepare("INSERT INTO tugas (nama_tugas, deadline, user_id) VALUES (?, CURDATE(), ?)");
            $stmt->bind_param("si", $taskData['nama_tugas'], $user_id);
            $stmt->execute();
            $new_parent_id = $stmt->insert_id;
            $stmt->close();

            $stmt = $conn->prepare("SELECT nama_tugas FROM tugas WHERE parent_id=? AND user_id=?");
            $stmt->bind_param("ii", $task_id, $user_id);
            $stmt->execute();
            $subtasks = $stmt->get_result();

            while ($sub = $subtasks->fetch_assoc()) {
                $stmtInsert = $conn->prepare("INSERT INTO tugas (nama_tugas, deadline, user_id, parent_id) 
                                            VALUES (?, CURDATE(), ?, ?)");
                $stmtInsert->bind_param("sii", $sub['nama_tugas'], $user_id, $new_parent_id);
                $stmtInsert->execute();
                $stmtInsert->close();
            }

            $stmt->close();
        }
    }

    if (isset($_POST['edit_task_id'], $_POST['edit_nama_tugas'], $_POST['edit_deadline'])) {

        $task_id  = intval($_POST['edit_task_id']);
        $nama     = trim($_POST['edit_nama_tugas']);
        $deadline = $_POST['edit_deadline'];

        if ($nama && $deadline) {
            $stmt = $conn->prepare("UPDATE tugas SET nama_tugas=?, deadline=? WHERE id=? AND user_id=?");
            $stmt->bind_param("ssii", $nama, $deadline, $task_id, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    /* ======================
   UPDATE URUTAN (DRAG DROP)
====================== */
if (isset($_POST['update_order'])) {

    foreach ($_POST['update_order'] as $index => $task_id) {

        $stmt = $conn->prepare("UPDATE tugas SET urutan=? WHERE id=? AND user_id=?");
        $stmt->bind_param("iii", $index, $task_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    exit;
}

}

$stmt = $conn->prepare("SELECT * FROM tugas 
                        WHERE user_id=? AND parent_id IS NULL
                        ORDER BY deadline ASC, urutan ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$tugas_list = [];
while ($row = $result->fetch_assoc()) {
    $tugas_list[] = $row;
}
$stmt->close();

$tugas_per_tanggal = [];
foreach ($tugas_list as $tugas) {
    $tgl = $tugas['deadline'];
    $tugas_per_tanggal[$tgl][] = $tugas;
}

if (isset($_POST['delete_subtask'])) {
    $id = intval($_POST['delete_subtask']);

    $stmt = $conn->prepare("DELETE FROM tugas WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $stmt->close();
}

if (isset($_POST['edit_subtask_id'], $_POST['edit_subtask_nama'])) {

    $id = intval($_POST['edit_subtask_id']);
    $nama = trim($_POST['edit_subtask_nama']);

    if ($nama) {
        $stmt = $conn->prepare("UPDATE tugas SET nama_tugas=? WHERE id=? AND user_id=?");
        $stmt->bind_param("sii", $nama, $id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

if (isset($_POST['import_excel'])) {

    $file = $_FILES['file_excel']['tmp_name'];

    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();

    $data = [];
    $map = [];

    $highestRow = $sheet->getHighestRow();

    for ($row = 2; $row <= $highestRow; $row++) {

        $nama_tugas = trim($sheet->getCell("A{$row}")->getValue());
        $deadlineRaw = $sheet->getCell("B{$row}")->getValue();
        $parent     = trim($sheet->getCell("C{$row}")->getValue());

        if (!$nama_tugas) continue;

        // =========================
        // FIX DEADLINE (PALING AMAN)
        // =========================
        $deadline = null;

        // 1. Jika Excel date serial
        if (is_numeric($deadlineRaw)) {
            $deadline = Date::excelToDateTimeObject($deadlineRaw);
        }

        // 2. Jika string tanggal
        else {
            $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y'];

            foreach ($formats as $format) {
                $date = DateTime::createFromFormat($format, $deadlineRaw);
                if ($date !== false) {
                    $deadline = $date;
                    break;
                }
            }
        }

        // 3. fallback kalau gagal
        if ($deadline instanceof DateTime) {
            $deadline = $deadline->format('Y-m-d');
        } else {
            $deadline = date('Y-m-d');
        }

        // =========================
        // INSERT TASK
        // =========================
        $stmt = $conn->prepare("
            INSERT INTO tugas (nama_tugas, deadline, user_id, parent_id)
            VALUES (?, ?, ?, NULL)
        ");
        $stmt->bind_param("ssi", $nama_tugas, $deadline, $user_id);
        $stmt->execute();

        $id = $stmt->insert_id;
        $stmt->close();

        $map[$nama_tugas] = $id;

        $data[] = [
            'id' => $id,
            'parent' => $parent
        ];
    }

    // =========================
    // SET PARENT CHILD
    // =========================
    foreach ($data as $row) {

        if (!empty($row['parent']) && isset($map[$row['parent']])) {

            $stmt = $conn->prepare("
                UPDATE tugas SET parent_id=? WHERE id=? AND user_id=?
            ");
            $stmt->bind_param("iii", $map[$row['parent']], $row['id'], $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    echo "<script>alert('Import berhasil!');window.location.href=document.referrer;</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daftar Tugas</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="layout">
<?php include 'sidebar.php'; ?>

<main class="content">

<div class="card">
<h3>📌 Daftar Tugas</h3>

<button class="btn-action" onclick="openModal('utama')">Tambah Tugas</button>
<a href="template_import.xlsx" class="btn-action">Download Template Excel</a>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="file_excel" accept=".xlsx" required>
    <button class="btn-add-task" type="submit" name="import_excel">Import Excel</button>
</form>
<?php if (!empty($tugas_per_tanggal)): ?>
    <?php foreach ($tugas_per_tanggal as $tanggal => $tugas_tgl): ?>
        <h4><?= date('d M Y', strtotime($tanggal)); ?></h4>
        <table>
            <tr>
                <th>Tugas</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
            <?php foreach ($tugas_tgl as $tugas): ?>

            <?php
            $stmtSub = $conn->prepare("SELECT * FROM tugas WHERE parent_id=? AND user_id=? ORDER BY CASE WHEN urutan IS NULL THEN 1 ELSE 0 END,
            urutan ASC,
            id ASC");
            $stmtSub->bind_param("ii", $tugas['id'], $user_id);
            $stmtSub->execute();
            $subtasks = $stmtSub->get_result();
            ?>
            <?php 
                $checked = $tugas['selesai'] ? "checked" : "";
                $style   = $tugas['selesai'] ? "text-decoration:line-through;color:gray;" : "";
            ?>
            <tr class="main-task" data-id="<?= $tugas['id']; ?>" onclick="toggleSubtask(<?= $tugas['id']; ?>)">
                <td style="<?= $style ?>"><?= htmlspecialchars($tugas['nama_tugas']); ?></td>
                <td>
                    <input type="checkbox" onchange="toggleTask(<?= $tugas['id'] ?>, this)" <?= $checked ?>>
                </td>
                <td>
                    <button class="btn-action"
                        onclick="event.stopPropagation(); openModal('tugas', <?= $tugas['id']; ?>, 'Tambah Subtask')">+</button>

                    <button class="btn-action"
                        onclick="event.stopPropagation(); openEditTask(
                            <?= $tugas['id']; ?>,
                            '<?= addslashes($tugas['nama_tugas']); ?>',
                            '<?= $tugas['deadline']; ?>'
                        )">
                        <i class="fa-solid fa-pen"></i>
                    </button>

                    <button class="btn-action" onclick="event.stopPropagation(); copyTask(<?= $tugas['id']; ?>)">
                        <i class="fa-solid fa-copy"></i>
                    </button>

                    <button class="btn-action" onclick="event.stopPropagation(); deleteTask(<?= $tugas['id']; ?>)">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php while ($sub = $subtasks->fetch_assoc()): ?>

            <?php 
                $subChecked = $sub['selesai'] ? "checked" : "";
                $subStyle   = $sub['selesai'] ? "text-decoration:line-through;color:gray;" : "";
            ?>

            <tr class="subtask subtask-<?= $tugas['id']; ?>" style="background:#f9fafb; display:none;">
                <td style="padding-left:30px; <?= $subStyle ?>">
                    └── <?= htmlspecialchars($sub['nama_tugas']); ?>
                </td>
                <td>
                    <input type="checkbox"
                        onchange="toggleTask(<?= $sub['id'] ?>, this)"
                        <?= $sub['selesai'] ? "checked" : "" ?>>
                </td>
                <td>
                    <button class="btn-action" onclick="editSubtask(<?= $sub['id'] ?>, '<?= addslashes($sub['nama_tugas']) ?>')">
                        <i class="fa-solid fa-pen"></i>
                    </button>

                    <button class="btn-action" onclick="deleteSubtask(<?= $sub['id'] ?>)">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php endforeach; ?>
        </table>
    <?php endforeach; ?>
<?php else: ?>
<p style="text-align:center;">Belum ada tugas.</p>
<?php endif; ?>
</div>

</main>
</div>

<!-- MODAL -->
<div id="taskModal" class="modal">
<div class="modal-content">

<h3 id="modalTitle">Tambah Tugas</h3>

<form method="post" id="modalForm">
    <input type="hidden" name="parent_id" id="modalParentId">
    <div id="modalInputs"></div>
    <div style="display:flex; justify-content:space-between; margin-top:12px;">
        <button type="submit" id="modalSubmit" style="flex:1; margin-right:5px;">Simpan</button>
        <button type="button" onclick="closeModal()" style="flex:1; margin-left:5px; background:#ef4444;">Tutup</button>
    </div>
</form>

<div id="subtaskList"></div>

</div>
</div>

<script>
function openModal(type, id = null, title = 'Tambah Tugas') {
    const modal = document.getElementById("taskModal");
    const modalTitle = document.getElementById("modalTitle");
    const modalParentId = document.getElementById("modalParentId");
    const modalInputs = document.getElementById("modalInputs");
    const modalSubmit = document.getElementById("modalSubmit");
    const subtaskList = document.getElementById("subtaskList");

    modal.classList.add("show");
    modalTitle.innerText = title;
    subtaskList.innerHTML = '';

    if(type === 'utama') {
        modalParentId.value = '';
        modalInputs.innerHTML = `
            <input type="text" name="nama_tugas" placeholder="Nama Tugas" required>
            <input type="date" name="deadline" required>
        `;
        modalSubmit.innerText = 'Simpan Tugas';
        modalSubmit.setAttribute('name', 'submit');
    } else if(type === 'tugas') {
        modalParentId.value = id;
        modalInputs.innerHTML = `
            <input type="text" name="nama_subtask" placeholder="Nama Subtask" required>
        `;
        modalSubmit.innerText = 'Simpan Subtask';
    
        fetch("sub/load_subtask.php?parent_id=" + id)
            .then(res => res.text())
            .then(data => subtaskList.innerHTML = data);
    }
}

function closeModal() {
    document.getElementById("taskModal").classList.remove("show");
}

window.onclick = function(event) {
    const modal = document.getElementById("taskModal");
    if (event.target === modal) closeModal();
}

function toggleTask(taskId, checkbox) {
    const formData = new FormData();
    formData.append('task_id', taskId);
    if(checkbox.checked) formData.append('selesai', 1);

    fetch('', {method:'POST', body:formData})
        .then(()=> {
            if(checkbox.checked){
                checkbox.parentElement.previousElementSibling.style.textDecoration='line-through';
                checkbox.parentElement.previousElementSibling.style.color='gray';
            } else {
                checkbox.parentElement.previousElementSibling.style.textDecoration='none';
                checkbox.parentElement.previousElementSibling.style.color='black';
            }
        });
}

function updateSubtask(e, parentId){
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    fetch("sub/load_subtask.php?parent_id=" + parentId, {method:"POST", body:formData})
    .then(()=> fetch("sub/load_subtask.php?parent_id=" + parentId))
    .then(res => res.text())
    .then(data => document.getElementById("subtaskList").innerHTML = data);
}

function deleteTask(id){
    if(confirm("Hapus task ini beserta subtask?")){
        const formData = new FormData();
        formData.append("delete_task", id);

        fetch("", {
            method: "POST",
            body: formData
        })
        .then(() => location.reload());
    }
}

function deleteSubtask(id){
    if(confirm("Hapus subtask ini?")){
        const formData = new FormData();
        formData.append("delete_subtask", id);

        fetch("", {
            method:"POST",
            body:formData
        })
        .then(()=> location.reload()); // biar langsung update
    }
}

function copyTask(id){
    const formData = new FormData();
    formData.append("copy_task", id);

    fetch("", {method:"POST", body:formData})
    .then(()=> location.reload());
}

function openEditTask(id, nama, deadline){
    const modal = document.getElementById("taskModal");
    const modalTitle = document.getElementById("modalTitle");
    const modalInputs = document.getElementById("modalInputs");
    const modalParentId = document.getElementById("modalParentId");

    modal.classList.add("show");
    modalTitle.innerText = "Edit Tugas";

    modalParentId.value = '';

    modalInputs.innerHTML = `
        <input type="hidden" name="edit_task_id" value="${id}">
        <input type="text" name="edit_nama_tugas" value="${nama}" required>
        <input type="date" name="edit_deadline" value="${deadline}" required>
    `;
}
function deleteSubtask(id){
    if(confirm("Hapus subtask ini?")){
        const formData = new FormData();
        formData.append("delete_subtask", id);

        fetch("", {
            method:"POST",
            body:formData
        })
        .then(()=> location.reload()); // biar langsung update
    }
}

function editSubtask(id, nama){
    const newName = prompt("Edit subtask:", nama);

    if(newName && newName.trim() !== ""){

        const formData = new FormData();
        formData.append("edit_subtask_id", id);
        formData.append("edit_subtask_nama", newName);

        fetch("", {
            method:"POST",
            body:formData
        })
        .then(()=> location.reload());
    }
}

// =============================
// DRAG & DROP TASK
// =============================

let dragItem = null;

document.querySelectorAll("table").forEach(table => {

    table.addEventListener("dragstart", function(e){
        dragItem = e.target;
        e.target.style.opacity = "0.5";
    });

    table.addEventListener("dragend", function(e){
        e.target.style.opacity = "1";
    });

    table.addEventListener("dragover", function(e){
        e.preventDefault();
        const afterElement = getDragAfterElement(table, e.clientY);
        if(afterElement == null){
            table.appendChild(dragItem);
        } else {
            table.insertBefore(dragItem, afterElement);
        }
    });

    table.addEventListener("drop", function(){
        saveOrder(table);
    });

});

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll("tr[draggable='true']:not(.dragging)")];

    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;

        if(offset < 0 && offset > closest.offset){
            return { offset: offset, element: child }
        } else {
            return closest
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function saveOrder(table){
    const ids = [];
    table.querySelectorAll("tr[draggable='true']").forEach(row=>{
        ids.push(row.getAttribute("data-id"));
    });

    const formData = new FormData();
    ids.forEach(id => formData.append("update_order[]", id));

    fetch("", {
        method:"POST",
        body:formData
    });
}

function toggleSubtask(parentId) {
    const subtasks = document.querySelectorAll('.subtask-' + parentId);

    subtasks.forEach(row => {
        if (row.style.display === "none") {
            row.style.display = "table-row";
        } else {
            row.style.display = "none";
        }
    });
}
</script>

</body>
</html>

<?php
include 'koneksi/koneksi.php';
session_start();

if (!isset($_SESSION['login'])) {
    header("Location: koneksi/login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'Guest';
$user_id  = $_SESSION['id'];

/* ======================
   PROSES FORM
====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Tambah tugas utama
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

    // Tambah subtask
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

    // Update selesai
    if (isset($_POST['task_id'])) {
        $task_id = intval($_POST['task_id']);
        $selesai = isset($_POST['selesai']) ? 1 : 0;

        $stmt = $conn->prepare("UPDATE tugas SET selesai=? WHERE id=? AND user_id=?");
        $stmt->bind_param("iii", $selesai, $task_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

/* ======================
   AMBIL TUGAS UTAMA
====================== */
$stmt = $conn->prepare("SELECT * FROM tugas 
                        WHERE user_id=? AND parent_id IS NULL
                        ORDER BY deadline ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$tugas_list = [];
while ($row = $result->fetch_assoc()) {
    $tugas_list[] = $row;
}
$stmt->close();

/* ======================
   KELOMPOK PER TANGGAL
====================== */
$tugas_per_tanggal = [];
foreach ($tugas_list as $tugas) {
    $tgl = $tugas['deadline'];
    $tugas_per_tanggal[$tgl][] = $tugas;
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
<button class="btn-add-task" onclick="openModal('utama')">Tambah Tugas</button>

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
                $checked = $tugas['selesai'] ? "checked" : "";
                $style   = $tugas['selesai'] ? "text-decoration:line-through;color:gray;" : "";
            ?>
            <tr>
                <td style="<?= $style ?>"><?= htmlspecialchars($tugas['nama_tugas']); ?></td>
                <td>
                    <input type="checkbox" onchange="toggleTask(<?= $tugas['id'] ?>, this)" <?= $checked ?>>
                </td>
                <td>
                    <button class="btn-add-task" onclick="openModal('tugas', <?= $tugas['id']; ?>, 'Tambah Subtask untuk <?= addslashes($tugas['nama_tugas']); ?>')">Tambah Subtask</button>
                </td>
            </tr>
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
// Modal
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
    
        // Load subtask
        fetch("load_subtask.php?parent_id=" + id)
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

// Toggle task utama status
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

// Subtask update
function updateSubtask(e, parentId){
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    fetch("load_subtask.php?parent_id=" + parentId, {method:"POST", body:formData})
    .then(()=> fetch("load_subtask.php?parent_id=" + parentId))
    .then(res => res.text())
    .then(data => document.getElementById("subtaskList").innerHTML = data);
}
</script>

</body>
</html>

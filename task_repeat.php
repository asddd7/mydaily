<?php
include 'koneksi/koneksi.php';
session_start();

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$user_id  = $_SESSION['id'];
$username = $_SESSION['username'] ?? 'Guest';
$today = date('Y-m-d');
$today_day = date('D'); // Mon, Tue, Wed

/* =========================
   TAMBAH TASK
========================= */
if (isset($_POST['add_task'])) {

    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $repeat_type = $_POST['repeat_type'];
    $repeat_days = null;

    if ($repeat_type == 'custom' && isset($_POST['days'])) {
        $repeat_days = implode(",", $_POST['days']);
    }

    // Ambil tipe waktu
    $time_option = $_POST['time_option'];
    $start_time = $end_time = null;

    if ($time_option == 'range') {
        $start_time = $_POST['start_time'] ?? null;
        $end_time   = $_POST['end_time'] ?? null;
    } else { // single time
        $start_time = $_POST['single_time'] ?? null;
        $end_time = null;
    }

    $query = "INSERT INTO task_repeat 
    (user_id, title, repeat_type, repeat_days, start_time, end_time)
    VALUES ('$user_id','$title','$repeat_type','$repeat_days','$start_time','$end_time')";

    if (!mysqli_query($conn, $query)) {
        die("Insert gagal: " . mysqli_error($conn));
    }

}

/* =========================
   CHECK / UNCHECK TASK
========================= */
if (isset($_GET['complete'])) {
    $id = intval($_GET['complete']);
    $task = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM task_repeat WHERE id='$id' AND user_id='$user_id'"));
    if ($task) {
        $today_check = date('Y-m-d');
        mysqli_query($conn, "UPDATE task_repeat SET last_completed_date='$today_check' WHERE id='$id' AND user_id='$user_id'");
    }
}

/* =========================
   HAPUS TASK
========================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM task_repeat WHERE id='$id' AND user_id='$user_id'");
}

/* =========================
   AMBIL DATA
========================= */
$data_query = "SELECT * FROM task_repeat WHERE user_id='$user_id' ORDER BY start_time ASC";
$data = mysqli_query($conn, $data_query);

if (!$data) {
    die("Query gagal: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Task Repeat</title>
    <style>
        /* Tambahan kecil untuk toggle waktu */
        #timeRangeBox, #singleTimeBox {
            margin:10px 0;
        }
    </style>
</head>
<body>
<div class="layout">
<?php include 'sidebar.php'; ?>

<main class="content">
<div class="card">
    <h3>Daftar Task Repeat</h3>
    <h3><button name="submit" class="btn-add-task" onclick="openModal('utama')">Tambah Tugas</button></h3>
    <table class="task-repeat">
        <tr>
            <th>✔</th>
            <th>Task</th>
            <th>Repeat</th>
            <th>Waktu</th>
            <th>Aksi</th>
        </tr>

        <?php while($row = mysqli_fetch_assoc($data)) : 
            $is_completed = false;

            // === LOGIC RESET ===
            if ($row['repeat_type'] == 'daily') {
                if ($row['last_completed_date'] == $today) $is_completed = true;
            } else {
                $days_array = explode(",", $row['repeat_days']);
                if (in_array($today_day, $days_array) && $row['last_completed_date'] == $today) $is_completed = true;
            }
        ?>
        <tr>
            <td>
                <?php if (!$is_completed): ?>
                    <a href="?complete=<?= $row['id'] ?>">⬜</a>
                <?php else: ?>
                    ✅
                <?php endif; ?>
            </td>

            <td style="<?= $is_completed ? 'text-decoration:line-through; opacity:0.6;' : '' ?>">
                <?= htmlspecialchars($row['title']) ?>
            </td>

            <td>
                <?= $row['repeat_type']=='daily' ? 'Setiap Hari' : htmlspecialchars($row['repeat_days']) ?>
            </td>

            <td>
            <?php
                $start = date('H:i', strtotime($row['start_time']));
                $end   = $row['end_time'];

                if (!empty($end) && $end !== '00:00:00') {
                    $end = date('H:i', strtotime($end));
                    echo $start . ' - ' . $end;
                } else {
                    echo $start;
                }
            ?>
            </td>

            <td>
                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Hapus task?')">Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>


<!-- MODAL -->
<div id="taskModal" class="modal">
  <div class="modal-content">
    
    <div class="modal-header">
      <h3 id="modalTitle">Tambah Task Repeat</h3>
    </div>

    <form method="POST" id="modalForm">

      <input type="hidden" name="parent_id" id="modalParentId">

      <div id="modalInputs">

        <input type="text" name="title" placeholder="Nama Task" required>

        <div class="radio-group">
          <label>
            <input type="radio" name="repeat_type" value="daily" checked>
            Setiap Hari
          </label>

          <label>
            <input type="radio" name="repeat_type" value="custom">
            Hari Tertentu
          </label>
        </div>

        <div id="daysBox" class="days-box">
          <?php
          $days = ['Sen'=>'Senin','Sel'=>'Selasa','Rab'=>'Rabu','Kms'=>'Kamis','Jum'=>'Jumat','Sab'=>'Sabtu','Min'=>'Minggu'];
          foreach ($days as $key => $value) {
              echo "<label>
                      <input type='checkbox' name='days[]' value='$key'> $value
                    </label>";
          }
          ?>
        </div>

        <label class="section-title">Pilih Tipe Waktu:</label>

        <div class="radio-group">
          <label>
            <input type="radio" name="time_option" value="single" checked onclick="toggleTimeOption('single')">
            Waktu Saja
          </label>
          <label>
            <input type="radio" name="time_option" value="range" onclick="toggleTimeOption('range')">
            Rentang Waktu
          </label>
        </div>

        <div id="singleTimeBox">
          <label>Waktu:</label>
          <input type="time" name="single_time" required>
        </div>

        <div id="timeRangeBox">
          <label>Mulai:</label>
          <input type="time" name="start_time">
          <label>Selesai:</label>
          <input type="time" name="end_time">
        </div>

        </div>
        <div style="display:flex; justify-content:space-between; margin-top:12px;">
            <button type="submit" name="add_task" id="modalSubmit" style="flex:1; margin-right:5px;">Simpan</button>
            <button type="button" onclick="closeModal()" style="flex:1; margin-left:5px; background:#ef4444;">Tutup</button>
        </div>

    </form>

  </div>
</div>


</main>
</div>

<script>
function toggleDays(show) {
    document.getElementById("daysBox").style.display = show ? "block" : "none";
}

function toggleTimeOption(option) {
    if(option === 'single') {
        document.getElementById("singleTimeBox").style.display = 'block';
        document.getElementById("timeRangeBox").style.display = 'none';
    } else {
        document.getElementById("singleTimeBox").style.display = 'none';
        document.getElementById("timeRangeBox").style.display = 'block';
    }
}

const modal = document.getElementById("taskModal");
const modalTitle = document.getElementById("modalTitle");
const modalParentId = document.getElementById("modalParentId");
const modalSubmit = document.getElementById("modalSubmit");
const subtaskList = document.getElementById("subtaskList");

function openModal(type, id = null, title = 'Tambah Task') {
    modal.classList.add("show");
    modalTitle.innerText = title;

    if (type === 'utama') {
        modalParentId.value = '';
        modalSubmit.innerText = 'Simpan Task';
        modalSubmit.name = 'add_task';

    } else if (type === 'tugas') {
        modalParentId.value = id;
        modalSubmit.innerText = 'Simpan Subtask';
        modalSubmit.name = 'submit_sub';
    }
}

function closeModal() {
    modal.classList.remove("show");
}

/* Klik luar modal untuk close */
window.addEventListener("click", function(e) {
    if (e.target === modal) {
        closeModal();
    }
});

/* Toggle Hari */
function toggleDays(show) {
    document.getElementById("daysBox").style.display = show ? "block" : "none";
}

/* Toggle Waktu */
function toggleTimeOption(type) {
    document.getElementById("singleTimeBox").style.display = type === "single" ? "block" : "none";
    document.getElementById("timeRangeBox").style.display = type === "range" ? "block" : "none";
}

document.addEventListener("DOMContentLoaded", function() {

    const repeatRadios = document.querySelectorAll("input[name='repeat_type']");
    const daysBox = document.getElementById("daysBox");

    const timeRadios = document.querySelectorAll("input[name='time_option']");
    const singleTimeBox = document.getElementById("singleTimeBox");
    const timeRangeBox = document.getElementById("timeRangeBox");

    function handleRepeatChange() {
        const selected = document.querySelector("input[name='repeat_type']:checked").value;
        daysBox.style.display = selected === "custom" ? "block" : "none";
    }

    function handleTimeChange() {
        const selected = document.querySelector("input[name='time_option']:checked").value;

        if (selected === "single") {
            singleTimeBox.style.display = "block";
            timeRangeBox.style.display = "none";

            document.querySelector("input[name='single_time']").required = true;
            document.querySelector("input[name='start_time']").required = false;
            document.querySelector("input[name='end_time']").required = false;

        } else {
            singleTimeBox.style.display = "none";
            timeRangeBox.style.display = "block";

            document.querySelector("input[name='single_time']").required = false;
            document.querySelector("input[name='start_time']").required = true;
            document.querySelector("input[name='end_time']").required = true;
        }
    }

    repeatRadios.forEach(radio => {
        radio.addEventListener("change", handleRepeatChange);
    });

    timeRadios.forEach(radio => {
        radio.addEventListener("change", handleTimeChange);
    });

    // Jalankan saat pertama kali load
    handleRepeatChange();
    handleTimeChange();
});

</script>

</body>
</html>

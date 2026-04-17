<?php
include 'koneksi/koneksi.php';
session_start();

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$user_id  = $_SESSION['id'];
$username = $_SESSION['username'] ?? 'Guest';
$today    = date('Y-m-d');

// ✅ FIX 1: Mapping hari PHP (bahasa Inggris) → singkatan Indonesia yang disimpan di DB
$day_map = [
    'Mon' => 'Sen',
    'Tue' => 'Sel',
    'Wed' => 'Rab',
    'Thu' => 'Kms',
    'Fri' => 'Jum',
    'Sat' => 'Sab',
    'Sun' => 'Min',
];
$today_day = $day_map[date('D')] ?? date('D');

/* =========================
   TAMBAH TASK
========================= */
if (isset($_POST['add_task'])) {
    $title       = mysqli_real_escape_string($conn, $_POST['title']);
    $repeat_type = $_POST['repeat_type'];
    $repeat_days = null;

    if ($repeat_type === 'custom' && isset($_POST['days'])) {
        $repeat_days = implode(",", $_POST['days']);
    }

    $time_option = $_POST['time_option'] ?? 'single';
    $start_time  = null;
    $end_time    = null;

    if ($time_option === 'range') {
        $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
        $end_time   = !empty($_POST['end_time'])   ? $_POST['end_time']   : null;
    } else {
        $start_time = !empty($_POST['single_time']) ? $_POST['single_time'] : null;
        $end_time   = null;
    }

    $st = $start_time ? "'" . mysqli_real_escape_string($conn, $start_time) . "'" : 'NULL';
    $et = $end_time   ? "'" . mysqli_real_escape_string($conn, $end_time)   . "'" : 'NULL';
    $rd = $repeat_days ? "'" . mysqli_real_escape_string($conn, $repeat_days) . "'" : 'NULL';

    $query = "INSERT INTO task_repeat (user_id, title, repeat_type, repeat_days, start_time, end_time)
              VALUES ('$user_id', '$title', '$repeat_type', $rd, $st, $et)";

    if (!mysqli_query($conn, $query)) {
        die("Insert gagal: " . mysqli_error($conn));
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* =========================
   TOGGLE COMPLETE / UNCHECK
========================= */
if (isset($_GET['complete'])) {
    $id   = intval($_GET['complete']);
    $task = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT * FROM task_repeat WHERE id='$id' AND user_id='$user_id'")
    );
    if ($task) {
        // ✅ Toggle: jika sudah selesai hari ini → reset, jika belum → tandai selesai
        if ($task['last_completed_date'] === $today) {
            mysqli_query($conn, "UPDATE task_repeat SET last_completed_date = NULL WHERE id='$id' AND user_id='$user_id'");
        } else {
            mysqli_query($conn, "UPDATE task_repeat SET last_completed_date = '$today' WHERE id='$id' AND user_id='$user_id'");
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* =========================
   HAPUS TASK
========================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM task_repeat WHERE id='$id' AND user_id='$user_id'");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* =========================
   AMBIL DATA
========================= */
$data = mysqli_query($conn, "SELECT * FROM task_repeat WHERE user_id='$user_id' ORDER BY start_time ASC");
if (!$data) {
    die("Query gagal: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Repeat</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ✅ FIX 2 & 3: Sembunyikan box yang tidak diperlukan secara default */
        #daysBox      { display: none; margin-top: 10px; }
        #timeRangeBox { display: none; margin-top: 10px; }
        #singleTimeBox{ display: block; margin-top: 10px; }

        .radio-group { display: flex; flex-wrap: wrap; gap: 8px; margin: 10px 0; }
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            padding: 7px 14px;
            border-radius: 8px;
            border: 1px solid var(--clr-border, rgba(255,255,255,0.1));
            font-size: 13px;
            font-weight: 500;
            transition: .2s;
        }
        .radio-group label:has(input:checked) {
            border-color: var(--clr-accent, #4f8ef7);
            background: rgba(79,142,247,0.1);
            color: var(--clr-accent, #4f8ef7);
        }
        .days-box {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 10px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
        }
        .days-box label {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 6px;
            border: 1px solid rgba(255,255,255,0.1);
            font-size: 12px;
            cursor: pointer;
            transition: .2s;
        }
        .days-box label:has(input:checked) {
            background: rgba(79,142,247,0.15);
            border-color: rgba(79,142,247,0.4);
        }
        .section-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            opacity: 0.55;
            display: block;
            margin-top: 14px;
            margin-bottom: 2px;
        }
        .task-status {
            font-size: 18px;
            text-decoration: none;
            cursor: pointer;
            transition: transform .2s;
            display: inline-block;
        }
        .task-status:hover { transform: scale(1.2); }
    </style>
</head>
<body>
<div class="layout">
    <?php include 'sidebar.php'; ?>

    <main class="content">
        <div class="card">
            <h3><i class="fa-solid fa-rotate"></i> Daftar Task Repeat</h3>
            <button class="btn-add-task" onclick="openModal()">
                <i class="fa-solid fa-plus"></i> Tambah Tugas
            </button>

            <div class="table-wrapper" style="margin-top: 20px;">
                <table>
                    <thead>
                        <tr>
                            <th style="width:50px; text-align:center;">✔</th>
                            <th>Task</th>
                            <th>Pengulangan</th>
                            <th>Waktu</th>
                            <th style="width:80px; text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $count = 0;
                    while ($row = mysqli_fetch_assoc($data)) :
                        $is_completed = false;

                        if ($row['repeat_type'] === 'daily') {
                            // ✅ Harian: cek apakah sudah diselesaikan hari ini
                            $is_completed = ($row['last_completed_date'] === $today);
                        } else {
                            // ✅ Custom: cek apakah hari ini ada di daftar hari DAN sudah diselesaikan
                            $days_array   = array_map('trim', explode(",", $row['repeat_days'] ?? ''));
                            $is_today_day = in_array($today_day, $days_array);
                            $is_completed = $is_today_day && ($row['last_completed_date'] === $today);
                        }

                        $count++;
                    ?>
                    <tr>
                        <td style="text-align:center;">
                            <a class="task-status" href="?complete=<?= $row['id'] ?>"
                               title="<?= $is_completed ? 'Klik untuk batal' : 'Klik untuk selesai' ?>">
                                <?= $is_completed ? '✅' : '⬜' ?>
                            </a>
                        </td>
                        <td style="<?= $is_completed ? 'text-decoration:line-through; opacity:0.5;' : '' ?>">
                            <?= htmlspecialchars($row['title']) ?>
                        </td>
                        <td>
                            <?php if ($row['repeat_type'] === 'daily'): ?>
                                <span class="badge badge-blue">Setiap Hari</span>
                            <?php else: ?>
                                <span class="badge badge-yellow"><?= htmlspecialchars($row['repeat_days']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $start = $row['start_time'] ? date('H:i', strtotime($row['start_time'])) : '-';
                            $end   = $row['end_time'];

                            if (!empty($end) && $end !== '00:00:00') {
                                echo $start . ' – ' . date('H:i', strtotime($end));
                            } else {
                                echo $start;
                            }
                            ?>
                        </td>
                        <td style="text-align:center;">
                            <a href="?delete=<?= $row['id'] ?>"
                               onclick="return confirm('Hapus task \'<?= htmlspecialchars(addslashes($row['title'])) ?>\'?')"
                               class="btn-delete" style="font-size:12px; text-decoration:none;">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>

                    <?php if ($count === 0): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; opacity:0.45; padding:28px;">
                            Belum ada task. Tambahkan yang pertama!
                        </td>
                    </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- MODAL TAMBAH TASK -->
<div id="taskModal" class="modal">
    <div class="modal-content">

        <div class="modal-header">
            <h3>Tambah Task Repeat</h3>
        </div>

        <form method="POST" id="modalForm">

            <label>Nama Task</label>
            <input type="text" name="title" placeholder="Contoh: Olahraga pagi" required>

            <label class="section-title">Pengulangan</label>
            <div class="radio-group">
                <label>
                    <input type="radio" name="repeat_type" value="daily" checked>
                    <i class="fa-solid fa-calendar-days"></i> Setiap Hari
                </label>
                <label>
                    <input type="radio" name="repeat_type" value="custom">
                    <i class="fa-solid fa-calendar-week"></i> Hari Tertentu
                </label>
            </div>

            <!-- ✅ FIX 2: display:none secara default, tampil hanya saat custom dipilih -->
            <div id="daysBox">
                <div class="days-box">
                    <?php
                    $days = [
                        'Sen' => 'Sen',
                        'Sel' => 'Sel',
                        'Rab' => 'Rab',
                        'Kms' => 'Kms',
                        'Jum' => 'Jum',
                        'Sab' => 'Sab',
                        'Min' => 'Min',
                    ];
                    foreach ($days as $key => $label) {
                        echo "<label><input type='checkbox' name='days[]' value='$key'> $label</label>";
                    }
                    ?>
                </div>
            </div>

            <label class="section-title">Tipe Waktu</label>
            <div class="radio-group">
                <label>
                    <input type="radio" name="time_option" value="single" checked>
                    <i class="fa-regular fa-clock"></i> Waktu Saja
                </label>
                <label>
                    <input type="radio" name="time_option" value="range">
                    <i class="fa-solid fa-arrows-left-right"></i> Rentang Waktu
                </label>
            </div>

            <!-- ✅ FIX 3: singleTimeBox tampil default, timeRangeBox sembunyi -->
            <div id="singleTimeBox">
                <label>Waktu</label>
                <input type="time" name="single_time">
            </div>

            <div id="timeRangeBox">
                <label>Mulai</label>
                <input type="time" name="start_time">
                <label style="margin-top:8px;">Selesai</label>
                <input type="time" name="end_time">
            </div>

            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" name="add_task" style="flex:1;">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Task
                </button>
                <button type="button" onclick="closeModal()" style="flex:1;">
                    <i class="fa-solid fa-xmark"></i> Batal
                </button>
            </div>

        </form>
    </div>
</div>

<script>
/* ─── MODAL ─────────────────────────────────────── */
const modal = document.getElementById("taskModal");

function openModal() {
    modal.classList.add("show");
}

function closeModal() {
    modal.classList.remove("show");
}

// Klik luar modal = tutup
window.addEventListener("click", function(e) {
    if (e.target === modal) closeModal();
});

/* ─── FIX 4: Hapus semua duplikat, satu fungsi per tugas ─── */
document.addEventListener("DOMContentLoaded", function () {

    const repeatRadios  = document.querySelectorAll("input[name='repeat_type']");
    const timeRadios    = document.querySelectorAll("input[name='time_option']");
    const daysBox       = document.getElementById("daysBox");
    const singleTimeBox = document.getElementById("singleTimeBox");
    const timeRangeBox  = document.getElementById("timeRangeBox");
    const singleInput   = document.querySelector("input[name='single_time']");
    const startInput    = document.querySelector("input[name='start_time']");
    const endInput      = document.querySelector("input[name='end_time']");

    function handleRepeatChange() {
        const selected = document.querySelector("input[name='repeat_type']:checked").value;
        daysBox.style.display = selected === "custom" ? "block" : "none";
    }

    function handleTimeChange() {
        const selected = document.querySelector("input[name='time_option']:checked").value;
        const isSingle = selected === "single";

        singleTimeBox.style.display = isSingle ? "block" : "none";
        timeRangeBox.style.display  = isSingle ? "none"  : "block";

        singleInput.required = isSingle;
        startInput.required  = !isSingle;
        endInput.required    = !isSingle;
    }

    repeatRadios.forEach(r => r.addEventListener("change", handleRepeatChange));
    timeRadios.forEach(r   => r.addEventListener("change", handleTimeChange));

    // Jalankan saat load untuk set state awal yang benar
    handleRepeatChange();
    handleTimeChange();
});
</script>

</body>
</html>
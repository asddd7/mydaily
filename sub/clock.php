<?php
session_start();
include '../koneksi/koneksi.php';

if(empty($_SESSION['login'])){
    header("Location: ../koneksi/login.php");
    exit;
}

$user_id  = $_SESSION['id'];

if(isset($_POST['mode'])){
    $mode = $_POST['mode'];

    // default
    $countdown_seconds = 0;
    $work = 25;
    $break = 5;

    // HANDLE MODE
    if($mode === "countdown"){
        if(!empty($_POST['minutes'])){
            $countdown_seconds = intval($_POST['minutes']) * 60;
        }
    }

    if($mode === "pomodoro"){
        if(!empty($_POST['work']))  $work  = intval($_POST['work']);
        if(!empty($_POST['break'])) $break = intval($_POST['break']);
    }

    // SATU QUERY SAJA (fix bug overwrite)
    $stmt = $conn->prepare("
        UPDATE users 
        SET clock_mode=?, countdown_seconds=?, pomodoro_work=?, pomodoro_break=? 
        WHERE id=?
    ");
    $stmt->bind_param("siiii",$mode,$countdown_seconds,$work,$break,$user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: clock.php?success=1");
    exit;
}

// ambil data sekarang
$q = $conn->prepare("SELECT clock_mode FROM users WHERE id=?");
$q->bind_param("i",$user_id);
$q->execute();
$current = $q->get_result()->fetch_assoc()['clock_mode'];
$q->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Clock Settings</title>
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<div class="layout">
<?php include '../sidebar.php'; ?>
<div class="content">
    <div class="card">
        <h2>⚙ Clock Settings</h2>

        <?php if(isset($_GET['success'])): ?>
            <p style="color:green;">Setting berhasil disimpan!</p>
        <?php endif; ?>

        <form method="POST">

        <label class="radio-container">
            <input type="radio" name="mode" value="clock" <?= $current=='clock'?'checked':''; ?>>
            <span class="radio-custom"></span>
            Digital Clock
        </label>

        <label class="radio-container">
            <input type="radio" name="mode" value="stopwatch" <?= $current=='stopwatch'?'checked':''; ?>>
            <span class="radio-custom"></span>
            Stopwatch
        </label>

        <label class="radio-container">
            <input type="radio" name="mode" value="countdown" <?= $current=='countdown'?'checked':''; ?>>
            <span class="radio-custom"></span>
            Countdown
        </label>

        <div id="countdownInput" style="margin-top:10px; display:none;">
            <input type="number" name="minutes" placeholder="Countdown (menit)" min="1">
        </div>

        <label class="radio-container">
            <input type="radio" name="mode" value="pomodoro" <?= $current=='pomodoro'?'checked':''; ?>>
            <span class="radio-custom"></span>
            Pomodoro
        </label>

        <div style="margin-top:10px;">
            <input type="number" name="work" placeholder="Work (menit)" min="1">
            <input type="number" name="break" placeholder="Break (menit)" min="1">
        </div>

        <br>
        <button type="submit" class="btn-add-task">Simpan</button>
    </form>

        <br>
        <button type="button" onclick="goBack()" class="btn-back">← Kembali</button>
        </div>
    </div>
</div>
<script>
const radios = document.querySelectorAll('input[name="mode"]');
const countdownInput = document.getElementById('countdownInput');
const pomodoroInputs = document.querySelector('input[name="work"]').parentElement;

function toggleInputs(){
    let selected = document.querySelector('input[name="mode"]:checked').value;

    countdownInput.style.display = (selected === 'countdown') ? 'block' : 'none';
    pomodoroInputs.style.display = (selected === 'pomodoro') ? 'block' : 'none';
}

radios.forEach(r => r.addEventListener('change', toggleInputs));

// run pertama
toggleInputs();
function goBack(){
    if(document.referrer !== ""){
        window.history.back();
    } else {
        window.location.href = "../index.php";
    }
}
</script>
</body>
</html>

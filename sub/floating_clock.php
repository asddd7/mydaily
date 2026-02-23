<?php
if(!isset($_SESSION)) session_start();
include_once __DIR__ . '/../koneksi/koneksi.php';

$user_id = $_SESSION['id'] ?? 0;
$clock_mode = 'clock';

if($user_id){
    $q = $conn->prepare("SELECT clock_mode FROM users WHERE id=?");
    $q->bind_param("i",$user_id);
    $q->execute();
    $res = $q->get_result()->fetch_assoc();
    if($res && $res['clock_mode']){
        $clock_mode = $res['clock_mode'];
    }
    $q->close();
}

$clock_mode = 'clock';
$countdown_seconds = 0;

if($user_id){
    $q = $conn->prepare("SELECT clock_mode, countdown_seconds FROM users WHERE id=?");
    $q->bind_param("i",$user_id);
    $q->execute();
    $res = $q->get_result()->fetch_assoc();

    if($res){
        $clock_mode = $res['clock_mode'];
        $countdown_seconds = $res['countdown_seconds'];
    }

    $q->close();
}

$clock_mode = 'clock';
$work = 25;
$break = 5;

if($user_id){
    $q = $conn->prepare("SELECT clock_mode, pomodoro_work, pomodoro_break FROM users WHERE id=?");
    $q->bind_param("i",$user_id);
    $q->execute();
    $res = $q->get_result()->fetch_assoc();

    if($res){
        $clock_mode = $res['clock_mode'];
        $work = $res['pomodoro_work'];
        $break = $res['pomodoro_break'];
    }

    $q->close();
}

?>

<div id="floatingClock" class="floating-clock">
    <a href="sub/clock.php">
        <span id="globalClock"></span>
    </a>
</div>

<style>
.floating-clock{
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #111;
    color: #fff;
    padding: 12px 20px;
    border-radius: 50px;
    font-size: 18px;
    font-weight: bold;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    z-index: 9999;
    cursor: move;
    user-select: none;
}
.floating-clock a{
    color:#fff;
    text-decoration:none;
}
</style>

<script>
const mode = "<?= $clock_mode ?>";
const clockElement = document.getElementById("globalClock");

if(mode === "clock"){
    function updateClock(){
        const now = new Date();
        const h = now.getHours().toString().padStart(2,'0');
        const m = now.getMinutes().toString().padStart(2,'0');
        const s = now.getSeconds().toString().padStart(2,'0');
        clockElement.textContent = h+":"+m+":"+s;
    }
    setInterval(updateClock,1000);
    updateClock();
}

if(mode === "stopwatch"){
    
    let startTime = localStorage.getItem("stopwatchStart");

    if(!startTime){
        startTime = Date.now();
        localStorage.setItem("stopwatchStart", startTime);
    }

    function updateStopwatch(){
        const now = Date.now();
        const diff = Math.floor((now - startTime) / 1000);

        let h = Math.floor(diff/3600).toString().padStart(2,'0');
        let m = Math.floor((diff%3600)/60).toString().padStart(2,'0');
        let s = (diff%60).toString().padStart(2,'0');

        clockElement.textContent = h+":"+m+":"+s;
    }

    setInterval(updateStopwatch,1000);
    updateStopwatch();
}

if(mode === "countdown"){
    
    let duration = <?= $countdown_seconds ?>;

    if(!localStorage.getItem("countdownEnd")){
        let endTime = Date.now() + duration * 1000;
        localStorage.setItem("countdownEnd", endTime);
    }

    function updateCountdown(){
        let endTime = localStorage.getItem("countdownEnd");
        let now = Date.now();
        let diff = Math.floor((endTime - now) / 1000);

        if(diff <= 0){
            clockElement.textContent = "00:00:00";
            localStorage.removeItem("countdownEnd");
            alert("⏰ Waktu Habis!");
            return;
        }

        let h = Math.floor(diff/3600).toString().padStart(2,'0');
        let m = Math.floor((diff%3600)/60).toString().padStart(2,'0');
        let s = (diff%60).toString().padStart(2,'0');

        clockElement.textContent = h+":"+m+":"+s;
    }

    setInterval(updateCountdown,1000);
    updateCountdown();
}
if(mode === "pomodoro"){

    const workDuration = <?= $work ?> * 60;
    const breakDuration = <?= $break ?> * 60;

    if(!localStorage.getItem("pomodoroEnd")){
        startPomodoro("work");
    }

    function startPomodoro(type){
        let duration = type === "work" ? workDuration : breakDuration;
        let endTime = Date.now() + duration * 1000;

        localStorage.setItem("pomodoroType", type);
        localStorage.setItem("pomodoroEnd", endTime);
    }

    function updatePomodoro(){
        let endTime = localStorage.getItem("pomodoroEnd");
        let type = localStorage.getItem("pomodoroType");

        if(!endTime || !type){
            startPomodoro("work");
            return;
        }

        let now = Date.now();
        let diff = Math.floor((endTime - now) / 1000);

        if(diff <= 0){

            if(type === "work"){
                alert("🍅 Work selesai! Saatnya break ☕");
                startPomodoro("break");
            } else {
                alert("☕ Break selesai! Kembali kerja 💪");
                startPomodoro("work");
            }

            return;
        }

        let h = Math.floor(diff/3600).toString().padStart(2,'0');
        let m = Math.floor((diff%3600)/60).toString().padStart(2,'0');
        let s = (diff%60).toString().padStart(2,'0');

        clockElement.textContent = (type === "work" ? "🍅 " : "☕ ") + h+":"+m+":"+s;
    }

    setInterval(updatePomodoro,1000);
    updatePomodoro();
}
</script>

<?php
include 'koneksi/koneksi.php';
session_start();

// Generate CSRF token (hanya jika belum ada)
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['login'])) {
    header("Location: koneksi/login.php");
    exit;
}

$user_id  = $_SESSION['id'];
$username = $_SESSION['username'] ?? 'Guest';
$tanggal_hari_ini = date('Y-m-d');

$stmt = $conn->prepare("
    SELECT SUM(amount) as total_money 
    FROM money_plan 
    WHERE username=? AND type='expense' AND tanggal=?
");
$stmt->bind_param("ss", $username, $tanggal_hari_ini);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$total_money = $data['total_money'] ?? 0;
$stmt->close();

$q_note = mysqli_query($conn, "
    SELECT title, created_at 
    FROM notes 
    WHERE user_id='$user_id' 
    ORDER BY created_at DESC 
    LIMIT 1
");

$latest_note = mysqli_fetch_assoc($q_note);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validasi CSRF
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
        die("CSRF terdeteksi!");
    }

    $jam_sekarang = date('H:i:s');

    // Mulai
    if (isset($_POST['absen_masuk'])) {
        $stmt = $conn->prepare("SELECT * FROM absen WHERE user_id=? AND tanggal=?");
        $stmt->bind_param("is", $user_id, $tanggal_hari_ini);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $stmt2 = $conn->prepare("INSERT INTO absen (user_id, tanggal, jam_masuk) VALUES (?, ?, ?)");
            $stmt2->bind_param("iss", $user_id, $tanggal_hari_ini, $jam_sekarang);
            $stmt2->execute();
            $stmt2->close();
            $success = "Check In berhasil!";
        } else {
            $error = "Anda sudah absen masuk hari ini.";
        }
        $stmt->close();
    }

    // Selesai
    if (isset($_POST['absen_pulang'])) {
        $stmt = $conn->prepare("UPDATE absen 
                                SET jam_pulang=? 
                                WHERE user_id=? AND tanggal=? AND jam_pulang IS NULL");
        $stmt->bind_param("sis", $jam_sekarang, $user_id, $tanggal_hari_ini);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $success = "Check Out berhasil!";
        } else {
            $error = "Anda belum absen masuk atau sudah absen pulang.";
        }
        $stmt->close();
    }
}


// Riwayat
$riwayat = [];
$stmt = $conn->prepare("SELECT * FROM absen WHERE user_id=? ORDER BY tanggal DESC LIMIT 10");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $riwayat[] = $row;
}
$stmt->close();

$check_in_today = false;
$check_out_today = false;
$stmt = $conn->prepare("SELECT jam_masuk, jam_pulang FROM absen WHERE user_id=? AND tanggal=? LIMIT 1");
$stmt->bind_param("is", $user_id, $tanggal_hari_ini);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $check_in_today = !empty($row['jam_masuk']);
    $check_out_today = !empty($row['jam_pulang']);
}
$stmt->close();

$stmt = $conn->prepare("
    SELECT SUM(amount) 
    FROM money_plan 
    WHERE username=? AND type='expense' AND tanggal=?
");
$stmt->bind_param("ss", $username, $tanggal_hari_ini);
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
        <h3>Daily Summary - <?= date("d F Y"); ?></h3>

        <div style="margin-top:20px;line-height:1.8;font-size:16px;">

            <hr style="margin:15px 0;">

            <p>
                <strong>Total Pengeluaran Hari Ini:</strong> 
                Rp <?= number_format($total_money,0,',','.'); ?>
            </p>

            <hr style="margin:15px 0;">

            <p><strong>Catatan Terbaru:</strong></p>

            <?php if ($latest_note) : ?>
                <div style="margin-top:8px;background:#f1f5f9;padding:12px;border-radius:8px;">
                    <strong><?= htmlspecialchars($latest_note['title']); ?></strong><br>
                    <small><?= date("d M Y H:i", strtotime($latest_note['created_at'])); ?></small>
                </div>
            <?php else : ?>
                <p>Belum ada catatan.</p>
            <?php endif; ?>

        </div>
    </div>

<div class="card checkin-card">
    <h3>Absen Hari Ini</h3>
    <?php if(!empty($success)) echo "<p class='success'>$success</p>"; ?>
    <?php if(!empty($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="post" class="absen-form">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf']; ?>">
        <button type="submit" name="absen_masuk" class="btn-checkin" <?= $check_in_today ? 'disabled style="opacity:0.6; cursor:not-allowed;"' : '' ?>>Mulai</button>
        <button type="submit" name="absen_pulang" class="btn-checkout" <?= (!$check_in_today || $check_out_today) ? 'disabled style="opacity:0.6; cursor:not-allowed;"' : '' ?>>Selesai</button>
    </form>

    <div class="card checkin-card" id="calendarSection">
    <div class="calendar-header">
        <button onclick="changeMonth(-1)">❮</button>
        <h4 id="monthYear"></h4>
        <button onclick="changeMonth(1)">❯</button>
    </div>
    <table class="calendar-table">
        <thead>
            <tr>
                <th>Min</th><th>Sen</th><th>Sel</th><th>Rab</th><th>Kam</th><th>Jum</th><th>Sab</th>
            </tr>
        </thead>
        <tbody id="calendarBody"></tbody>
    </table>

    <div class="calendar-marks">
        <h4>📌 Penanda Bulan Ini</h4>
        <ul id="marksList"></ul>
    </div>
    </div>
</div>

<div class="card">
    <h3>Riwayat Absen</h3>
    <?php if (!empty($riwayat)): ?>
    <div class="table-wrapper">
    <table class="checkin-history">
        <tr><th>Tanggal</th><th>Mulai</th><th>Selesai</th></tr>
        <?php foreach ($riwayat as $row): ?>
        <tr>
            <td><?= date('d M Y', strtotime($row['tanggal'])); ?></td>
            <td><?= $row['jam_masuk'] ?? '-'; ?></td>
            <td><?= $row['jam_pulang'] ?? '-'; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p style="text-align:center;">Tidak Ada Riwayat.</p>
    <?php endif; ?>
</div>
</main>
</div>
<div id="markerModal" class="modal">
  <div class="modal-content">

    <div class="modal-header">
      <h3>Tambah Penanda</h3>
    </div>

    <form id="markerForm">
      <input type="hidden" name="tanggal" id="markerDate">

        <label class="form-label">Judul</label>
        <input type="text" name="title" class="form-title" placeholder="Contoh: Meeting client" required>

        <label class="form-label">Catatan</label>
        <textarea name="description" class="form-desc" rows="3" placeholder="Tambahkan catatan..."></textarea>

      <div style="display:flex; justify-content:space-between; margin-top:12px;">
        <button type="submit">
            <i class="fa-solid fa-floppy-disk"></i> Simpan
        </button>
        <button type="button" class="close" onclick="closeModal()">
            <i class="fa-solid fa-xmark"></i> Batal
        </button>
      </div>

    </form>

  </div>
  </div>
</div>

<script>
let currentDate = new Date();

// Load Calendar
function renderCalendar(date) {
    const year = date.getFullYear(), month = date.getMonth();
    const monthYear = document.getElementById("monthYear");
    const calendarBody = document.getElementById("calendarBody");
    const firstDay = new Date(year, month, 1).getDay();
    const lastDate = new Date(year, month+1, 0).getDate();

    monthYear.textContent = date.toLocaleString('default',{month:'long', year:'numeric'});
    calendarBody.innerHTML = "";
    let row = document.createElement("tr");

    for(let i=0;i<firstDay;i++) row.innerHTML += "<td></td>";

    for(let day=1;day<=lastDate;day++){
        const cell = document.createElement("td");
        const today = new Date();

        const fullDate = `${year}-${String(month+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
        cell.setAttribute("data-date", fullDate);

        if(day===today.getDate() && month===today.getMonth() && year===today.getFullYear()){
            cell.classList.add("today");
        }

        cell.textContent = day;
        cell.addEventListener("click",()=>openModal(year,month,day));
        row.appendChild(cell);
        if((firstDay+day)%7===0){ calendarBody.appendChild(row); row=document.createElement("tr"); }
    }
    calendarBody.appendChild(row);
    loadMarks(year, month);
}

//Modal
function openModal(year,month,day){
    const modal = document.getElementById("markerModal");
    document.getElementById("markerDate").value =
      `${year}-${String(month+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
    
    modal.classList.add("show");
}
function closeModal(){
    document.getElementById("markerModal").classList.remove("show");
}

// Submit Marks
document.getElementById("markerForm").addEventListener("submit",function(e){
    e.preventDefault();
    const formData = new FormData(this);
    fetch("calendar/calendar_add.php",{method:"POST",body:formData})
    .then(res=>res.text()).then(res=>{
        alert(res); closeModal(); renderCalendar(currentDate);
    }).catch(err=>console.error(err));
});

// Ganti Bulan
function changeMonth(dir){ currentDate.setMonth(currentDate.getMonth()+dir); renderCalendar(currentDate); }

// Load Marks
function loadMarks(year,month){
    const marksList=document.getElementById('marksList');
    marksList.innerHTML="<li>Loading...</li>";

    fetch(`calendar/calendar_marks.php?month=${month+1}&year=${year}`)
    .then(res=>res.json())
    .then(data=>{
        marksList.innerHTML="";
        
        // RESET semua warna tanggal dulu
        document.querySelectorAll(".calendar-table td").forEach(td=>{
            td.classList.remove("marked-date","done-date");
        });

        if(data.length===0){
            marksList.innerHTML="<li>Tidak ada penanda bulan ini.</li>";
        } else {
            data.forEach(mark=>{
                
            document.querySelectorAll(".calendar-table td").forEach(td=>{
            if(td.getAttribute("data-date") === mark.tanggal){

                // Jika bukan today → kasih warna mark
                if(!td.classList.contains("today")){
                    td.classList.add("marked-date");

                    if(mark.selesai == 1){
                        td.classList.add("done-date");
                    }
                }

            }
            });

                // === List penanda seperti biasa ===
                const li=document.createElement('li');
                li.className = mark.selesai == 1 ? "done" : "";

                li.innerHTML = `
                    <div class="mark-text">
                        <strong>${mark.tanggal}</strong> ${mark.title}
                        ${mark.description ? '- ' + mark.description : ''}
                    </div>
                    <div class="mark-actions">
                        <button 
                            onclick="${mark.selesai == 0 ? `toggleMark(${mark.id},0)` : ''}"
                            class="btn-done"
                            ${mark.selesai == 1 ? 'disabled' : ''}
                        >
                            Selesai
                        </button>

                        <button onclick="deleteMark(${mark.id})" class="delete-mark">
                            Hapus
                        </button>
                    </div>
                `;
                marksList.appendChild(li);
            });
        }
    });
}

/* Toggle */
function toggleMark(id, currentStatus){
    const newStatus = currentStatus == 1 ? 0 : 1;

    fetch("calendar/calendar_toggle.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "id="+id+"&selesai="+newStatus
    })
    .then(res=>res.text())
    .then(()=>{
        renderCalendar(currentDate);
    });
}
renderCalendar(currentDate);
// hapus mark //
function deleteMark(id){
    if(!confirm("Yakin ingin menghapus penanda ini?")) return;

    fetch("calendar/calendar_delete.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "id=" + id
    })
    .then(res => res.text())
    .then(res => {
        alert(res);
        renderCalendar(currentDate);
    })
    .catch(err => console.error(err));
}

</script>

</body>
</html>

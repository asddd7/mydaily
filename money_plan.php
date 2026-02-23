<?php
session_start();
include 'koneksi/koneksi.php';

if (!isset($_SESSION['login'])) {
    header("Location: koneksi/login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'Guest';
$user_id  = $_SESSION['id'];

/* Create tABLE*/
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS money_plan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100),
    type ENUM('income','expense'),
    category VARCHAR(100),
    amount DECIMAL(12,2),
    description TEXT,
    tanggal DATE DEFAULT CURRENT_DATE
)");

if (isset($_POST['add_money'])) {
    $type = $_POST['type'];
    $category = htmlspecialchars($_POST['category']);
    $amount = $_POST['amount'];
    $description = htmlspecialchars($_POST['description']);
    $tanggal = $_POST['tanggal'];

    mysqli_query($conn, "INSERT INTO money_plan 
        (username, type, category, amount, description, tanggal)
        VALUES 
        ('$username','$type','$category','$amount','$description','$tanggal')");
}

/* Delete */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM money_plan WHERE id=$id AND username='$username'");
}

/* Filter Bulan */
$selected_month = intval($_GET['bulan'] ?? date('m'));
$selected_year  = intval($_GET['tahun'] ?? date('Y'));

/* Total Keseluruhan */
$income = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(amount) as total FROM money_plan 
     WHERE username='$username' AND type='income'"
))['total'] ?? 0;

$expense = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(amount) as total FROM money_plan 
     WHERE username='$username' AND type='expense'"
))['total'] ?? 0;

$saldo = $income - $expense;

/* Total Bulan */
$income_month = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(amount) as total FROM money_plan 
     WHERE username='$username' 
     AND type='income'
     AND MONTH(tanggal)='$selected_month'
     AND YEAR(tanggal)='$selected_year'"
))['total'] ?? 0;

$expense_month = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(amount) as total FROM money_plan 
     WHERE username='$username' 
     AND type='expense'
     AND MONTH(tanggal)='$selected_month'
     AND YEAR(tanggal)='$selected_year'"
))['total'] ?? 0;

$saldo_month = $income_month - $expense_month;

/* Data Bulan */
$data = mysqli_query($conn,
    "SELECT * FROM money_plan 
     WHERE username='$username'
     AND MONTH(tanggal)='$selected_month'
     AND YEAR(tanggal)='$selected_year'
     ORDER BY tanggal DESC, id DESC"
);

$months = [
    1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
    7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
];
?>

<!DOCTYPE html>
<html>
<head>
<title>Money Plan</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="layout">
<?php include 'sidebar.php'; ?>

<div class="content">

<div class="card">
<h2>💰 Total Saldo Keseluruhan</h2>
<h1 style="color:<?= $saldo < 0 ? 'red' : 'green'; ?>">
Rp <?= number_format($saldo,0,',','.'); ?>
</h1>
<p>
Pemasukan: <strong style="color:green;">Rp <?= number_format($income,0,',','.'); ?></strong> |
Pengeluaran: <strong style="color:red;">Rp <?= number_format($expense,0,',','.'); ?></strong>
</p>
<button class="btn-add-task" onclick="openModal()">+ Tambah Transaksi</button>
</div>

<!-- Filter Bulan -->
<div class="card">
<h3>📅 Rekap Bulanan</h3>

<form method="get" style="margin-bottom:15px;">
<select name="bulan" required>
<?php foreach($months as $num => $name): ?>
    <option value="<?= $num ?>" <?= $num==$selected_month?'selected':''; ?>><?= $name ?></option>
<?php endforeach; ?>
</select>

<select name="tahun" required>
<?php 
$start_year = 2023; 
$current_year = date('Y');
for($y=$start_year;$y<=$current_year;$y++): ?>
    <option value="<?= $y ?>" <?= $y==$selected_year?'selected':''; ?>><?= $y ?></option>
<?php endfor; ?>
</select>

<button type="submit" class="btn-add-task">Filter</button>
</form>

<p>Pemasukan: <strong style="color:green;">
Rp <?= number_format($income_month,0,',','.'); ?>
</strong></p>

<p>Pengeluaran: <strong style="color:red;">
Rp <?= number_format($expense_month,0,',','.'); ?>
</strong></p>

<p>Saldo Bulan Ini:
<strong style="color:<?= $saldo_month < 0 ? 'red' : 'green'; ?>">
Rp <?= number_format($saldo_month,0,',','.'); ?>
</strong></p>
</div>

<!-- Riwayat -->
<div class="card">
<h3>📋 Riwayat Transaksi (<?= $months[$selected_month] ?> <?= $selected_year ?>)</h3>

<table width="100%" border="1" cellpadding="8" cellspacing="0">
<tr>
<th>Tanggal</th>
<th>Jenis</th>
<th>Kategori</th>
<th>Jumlah</th>
<th>Aksi</th>
</tr>

<?php while($row = mysqli_fetch_assoc($data)) : ?>
<tr>
<td><?= date('d M Y', strtotime($row['tanggal'])); ?></td>
<td><?= $row['type']=='income' ? '🟢' : '🔴'; ?></td>
<td><?= htmlspecialchars($row['category']); ?></td>
<td>Rp <?= number_format($row['amount'],0,',','.'); ?></td>
<td>
<a href="?delete=<?= $row['id']; ?>" class="btn-delete" onclick="return confirm('Hapus transaksi?')">Hapus</a>
</td>
</tr>
<?php endwhile; ?>

</table>
</div>

</div>
</div>

<!-- Modal Tambah Data -->
<div class="modal" id="moneyModal">
<div class="modal-content">
<h2>Tambah Transaksi</h2>

<form method="post">
<select name="type" required>
<option value="income">Pemasukan</option>
<option value="expense">Pengeluaran</option>
</select>

<input type="text" name="category" placeholder="Kategori" required>
<input type="number" name="amount" placeholder="Jumlah" required>
<input type="date" name="tanggal" value="<?= date('Y-m-d'); ?>" required>
<input type="text" name="description" placeholder="Keterangan">

      <div style="display:flex; justify-content:space-between; margin-top:12px;">
        <button type="submit" style="flex:1; margin-right:5px;" name="add_money">Simpan</button>
        <button type="button" onclick="closeModal()" style="flex:1; margin-left:5px; background:#ef4444;">Tutup</button>
      </div>
</form>

</div>
</div>

<script>
const modal = document.getElementById("moneyModal");

function openModal(){
    modal.classList.add("show");
}

function closeModal(){
    modal.classList.remove("show");
}

window.addEventListener("click", function(e){
    if(e.target === modal){
        closeModal();
    }
});

document.addEventListener("keydown", function(e){
    if(e.key === "Escape"){
        closeModal();
    }
});
</script>

</body>
</html>

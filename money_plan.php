<?php
session_start();
include 'koneksi/koneksi.php';

if (!isset($_SESSION['login'])) {
    header("Location: koneksi/login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'Guest';
$user_id  = $_SESSION['id'];

if (isset($_POST['add_money'])) {
    $type = $_POST['type'];
    $category = htmlspecialchars($_POST['category']);
    $amount = $_POST['amount'];
    $description = htmlspecialchars($_POST['description']);
    $tanggal = $_POST['tanggal'];

    $payment_method = $_POST['payment_method'];

    mysqli_query($conn, "INSERT INTO money_plan 
    (username, type, category, amount, description, tanggal, payment_method)
    VALUES 
    ('$username','$type','$category','$amount','$description','$tanggal','$payment_method')");
}

/* Total Cash */
$cash_income = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(amount) as total FROM money_plan 
     WHERE username='$username' 
     AND type='income'
     AND payment_method='cash'"
))['total'] ?? 0;

$cash_expense = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(amount) as total FROM money_plan 
     WHERE username='$username' 
     AND type='expense'
     AND payment_method='cash'"
))['total'] ?? 0;

$cash_balance = $cash_income - $cash_expense;

/* Total Online */
$online_income = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(amount) as total FROM money_plan 
     WHERE username='$username' 
     AND type='income'
     AND payment_method='online'"
))['total'] ?? 0;

$online_expense = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(amount) as total FROM money_plan 
     WHERE username='$username' 
     AND type='expense'
     AND payment_method='online'"
))['total'] ?? 0;

$online_balance = $online_income - $online_expense;

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

if (isset($_POST['adjust_balance'])) {

    $real_cash   = intval($_POST['real_cash']);
    $real_online = intval($_POST['real_online']);

    $today = date('Y-m-d');

    /* HITUNG SELISIH CASH */
    $cash_diff = $real_cash - $cash_balance;

    if ($cash_diff != 0) {

        $type = $cash_diff > 0 ? 'income' : 'expense';
        $amount = abs($cash_diff);

        mysqli_query($conn, "
            INSERT INTO money_plan
            (username, type, category, amount, description, tanggal, payment_method)
            VALUES
            (
                '$username',
                '$type',
                'Penyesuaian Saldo',
                '$amount',
                'Auto adjust cash balance',
                '$today',
                'cash'
            )
        ");
    }

    /* HITUNG SELISIH ONLINE */
    $online_diff = $real_online - $online_balance;

    if ($online_diff != 0) {

        $type = $online_diff > 0 ? 'income' : 'expense';
        $amount = abs($online_diff);

        mysqli_query($conn, "
            INSERT INTO money_plan
            (username, type, category, amount, description, tanggal, payment_method)
            VALUES
            (
                '$username',
                '$type',
                'Penyesuaian Saldo',
                '$amount',
                'Auto adjust online balance',
                '$today',
                'online'
            )
        ");
    }

    header("Location: money_plan.php");
    exit;
}

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
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Money Plan</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>

<div class="layout">
<?php include 'sidebar.php'; ?>

<div class="content">

<div class="card">

<div class="money-card">   
<h2>💰 Total Saldo Keseluruhan</h2>
<h1 style="color:<?= $saldo < 0 ? 'red' : 'green'; ?>">
Rp <?= number_format($saldo,0,',','.'); ?>
</h1>
<hr>
<h2>💵 Cash Balance</h2>
<h1>Rp <?= number_format($cash_balance,0,',','.'); ?></h1>
<p>
  Pemasukan: <span class="income">Rp <?= number_format($cash_income,0,',','.') ?></span> |
  Pengeluaran: <span class="expense">Rp <?= number_format($cash_expense,0,',','.') ?></span>
</p>
<hr>
<h2>📱 Online Balance</h2>
<h1>Rp <?= number_format($online_balance,0,',','.'); ?></h1>
<p>
  Pemasukan: <span class="income">Rp <?= number_format($online_income,0,',','.') ?></span> |
  Pengeluaran: <span class="expense">Rp <?= number_format($online_expense,0,',','.') ?></span>
</p>
<button class="btn-back" onclick="openAdjustModal()">
⚖️ Sesuaikan Saldo
</button>
</div>


<button class="btn-add-task" onclick="openModal()">+ Tambah Transaksi</button>
</div>

<!-- Riwayat -->
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
<h3>📋 Riwayat Transaksi (<?= $months[$selected_month] ?> <?= $selected_year ?>)</h3>

<div class="table-responsive">
<table>

<thead>
<tr>
<th>Tanggal</th>
<th>Jenis</th>
<th>Kategori</th>
<th>Jumlah</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>
<?php while($row = mysqli_fetch_assoc($data)) : ?>
<tr>
<td data-label="Tanggal"><?= date('d M Y', strtotime($row['tanggal'])); ?></td>
<td data-label="Jenis" class="<?= $row['type']; ?>">
    <?= $row['type']=='income' ? 'Pemasukan' : 'Pengeluaran'; ?>
</td>
<td data-label="Kategori"><?= htmlspecialchars($row['category']); ?></td>
<td data-label="Jumlah">Rp <?= number_format($row['amount'],0,',','.'); ?></td>
<td data-label="Aksi">
<a href="?delete=<?= $row['id']; ?>" class="delete-btn">
    <i class="fas fa-trash"></i>
</a>
</td>
</tr>
<?php endwhile; ?>
</tbody>

</table>
</div>
</div>

</div>
</div>

<!-- Modal Tambah Data -->
<div class="modal money-modal" id="moneyModal">
  <div class="modal-content money-modal-content">

    <h3 id="modalTitle">➕ Tambah Transaksi</h3>

    <form method="post" class="form-grid">

        <div class="form-group">
            <label>Jenis Transaksi</label>
            <select name="type" class="form-select" required>
            <option value="income">🟢 Pemasukan</option>
            <option value="expense">🔴 Pengeluaran</option>
            </select>
        </div>

        
        <div class="form-group">
            <label>Tanggal</label>
            <input type="text" 
            name="tanggal" 
            class="flatpickr"
            value="<?= date('Y-m-d'); ?>" 
            placeholder="Pilih tanggal"
            required>
        </div>

        <div class="form-group">
            <label>Kategori</label>

            <!-- Dropdown -->
            <select id="category_select" class="form-select" onchange="syncCategory()" required>
                <option value="">-- Pilih Kategori --</option>
                <option value="Gaji">Gaji</option>
                <option value="Makan">Makan</option>
                <option value="Transport">Transport</option>
                <option value="Belanja">Belanja</option>
                <option value="Tagihan">Tagihan</option>
                <option value="Investasi">Investasi</option>
                <option value="Lainnya">Lainnya (Custom)</option>
            </select>
            

            <!-- Input manual -->
            <input type="text" name="category" id="category_input"
                placeholder="Atau ketik kategori sendiri"
                class="form-text" required>
        </div>

        <div class="form-group">
            <label>Nominal</label>

            <!-- Dropdown -->
            <select id="amount_select" class="form-select" onchange="syncAmount()">
                <option value="">-- Pilih Nominal --</option>
                <option value="10000">Rp 10.000</option>
                <option value="20000">Rp 20.000</option>
                <option value="50000">Rp 50.000</option>
                <option value="100000">Rp 100.000</option>
                <option value="200000">Rp 200.000</option>
                <option value="500000">Rp 500.000</option>
                <option value="1000000">Rp 1.000.000</option>
                <option value="custom">Custom</option>
            </select>

            <!-- Input manual -->
            <input type="number" name="amount" id="amount_input"
                placeholder="Atau isi nominal sendiri"
                class="form-text" required>
        </div>

        <div class="form-group full">
            <label>Keterangan (opsional)</label>
            <input type="text" class="form-text" name="description" placeholder="Catatan tambahan">
        </div>

        <div class="form-group">
            <label>Metode Pembayaran</label>
            <select name="payment_method" class="form-select" required>
                <option value="cash">💵 Cash</option>
                <option value="online">📱 Saldo Online</option>
            </select>
        </div>

      <div class="modal-footer">
        <button type="submit" name="add_money" id="modalSubmit">
            <i class="fa-solid fa-floppy-disk"></i> Simpan
        </button>
        <button type="button" class="close" onclick="closeModal()">
            <i class="fa-solid fa-xmark"></i> Batal
        </button>
      </div>

    </form>

  </div>
</div>

<!-- Modal Adjust Balance -->
<div class="modal money-modal" id="adjustModal">
  <div class="modal-content money-modal-content">

    <h3>⚖️ Sesuaikan Saldo Aktual</h3>

    <form method="post">

        <div class="form-group">
            <label>Cash Saat Ini</label>
            <input type="number" name="real_cash" class="form-text" required>
        </div>

        <div class="form-group">
            <label>Online Saat Ini</label>
            <input type="number" name="real_online" class="form-text" required>
        </div>

        <div class="modal-footer">
            <button type="submit" name="adjust_balance">
                Simpan Penyesuaian
            </button>

            <button type="button" class="close" onclick="closeAdjustModal()">
                Batal
            </button>
        </div>

    </form>

  </div>
</div>

<script>
const modal = document.getElementById("moneyModal");

function openModal() {
    document.getElementById("moneyModal").classList.add("show");
    document.body.classList.add("modal-open");
}

function closeModal() {
    document.getElementById("moneyModal").classList.remove("show");
    document.body.classList.remove("modal-open");
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

function syncCategory(){
    let select = document.getElementById("category_select");
    let input = document.getElementById("category_input");

    if(select.value === "Lainnya"){
        input.value = "";
        input.focus();
    } else {
        input.value = select.value;
    }
}

document.getElementById("category_input").addEventListener("input", function(){
    document.getElementById("category_select").value = "Lainnya";
});

function syncAmount(){
    let select = document.getElementById("amount_select");
    let input = document.getElementById("amount_input");

    if(select.value === "custom"){
        input.value = "";
        input.focus();
    } else {
        input.value = select.value;
    }
}

document.getElementById("amount_input").addEventListener("input", function(){
    document.getElementById("amount_select").value = "custom";
});
</script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
flatpickr(".flatpickr", {
    dateFormat: "Y-m-d",
    allowInput: true,
    disableMobile: true
});
</script>
<script>
const adjustModal = document.getElementById("adjustModal");

function openAdjustModal() {
    adjustModal.classList.add("show");
    document.body.classList.add("modal-open");
}

function closeAdjustModal() {
    adjustModal.classList.remove("show");
    document.body.classList.remove("modal-open");
}

window.addEventListener("click", function(e){
    if(e.target === adjustModal){
        closeAdjustModal();
    }
});
</script>
</body>
</html>

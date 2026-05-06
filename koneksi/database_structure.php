<?php
include 'koneksi.php';

$resultTables = $conn->query("SHOW TABLES");

$tables = [];
while ($row = $resultTables->fetch_array()) {
    $tables[] = $row[0];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Struktur Database</title>
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="layout">
<?php include '../sidebar.php'; ?>
<main class="content">

<h2>📊 Struktur Semua Tabel Database</h2>

<?php foreach ($tables as $table): ?>

<div class="table-box">
    <h3>📁 <?= $table ?></h3>

    <?php
    $resultDesc = $conn->query("DESCRIBE $table");

    $structure = "DESCRIBE $table;\n\n";
    while ($col = $resultDesc->fetch_assoc()) {
        $structure .= implode(" | ", $col) . "\n";
    }

    $id = "code_" . $table;
    ?>

    <button class="copy-btn" onclick="copyText('<?= $id ?>')">📋 Copy</button>

    <pre id="<?= $id ?>"><?= htmlspecialchars($structure) ?></pre>
</div>

<?php endforeach; ?>

</main>
</div>

<script>
function copyText(id) {
    const text = document.getElementById(id).innerText;

    navigator.clipboard.writeText(text).then(() => {
        alert("✅ Berhasil di-copy!");
    }).catch(err => {
        alert("❌ Gagal copy");
    });
}
</script>

</body>
</html>
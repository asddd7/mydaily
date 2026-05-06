<?php
include 'koneksi.php';

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../dashboard.php");
    exit;
}

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

<div class="search-wrapper">
    <h3>Cari Struktur Table</h3>
    <input type="text" id="searchTable" placeholder="Search...">
    <div class="dropdown" id="dropdownResult">
        <!-- isi hasil -->
    </div>
</div>

<?php foreach ($tables as $table): ?>

<div class="table-box" data-name="<?= strtolower($table) ?>" style="display:none;">
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

const tables = <?= json_encode($tables) ?>;
const input = document.getElementById("searchTable");
const dropdown = document.getElementById("dropdownResult");

input.addEventListener("input", function () {
    const value = this.value.toLowerCase();
    dropdown.innerHTML = "";

    if (value === "") {
        dropdown.style.display = "none";
        return;
    }

    const filtered = tables.filter(t => t.toLowerCase().includes(value));

    filtered.forEach(table => {
        const item = document.createElement("div");
        item.textContent = table;

        item.onclick = () => {
            input.value = table;
            dropdown.style.display = "none";
            showTable(table);
        };

        dropdown.appendChild(item);
    });

    dropdown.style.display = "block";
});

function showTable(name) {
    document.querySelectorAll(".table-box").forEach(box => {
        if (box.dataset.name === name.toLowerCase()) {
            box.style.display = "block";
        } else {
            box.style.display = "none";
        }
    });
}
</script>

</body>
</html>
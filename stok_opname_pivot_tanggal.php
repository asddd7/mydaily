<?php
declare(strict_types=1);

/**
 * stok_opname_pivot_tanggal.php
 *
 * Rekap horizontal stok opname per tanggal.
 *
 * Filter  : start_date, end_date, gudang, divisi
 * Default : 3 hari terakhir s/d hari ini
 * Baris   : item
 * Kolom   : tanggal
 * Nilai   : selisih = qty_fisik(unit1) − stok_sistem(unit1)
 * Catatan : jika 1 tanggal ada >1 dokumen untuk item yang sama → dijumlah
 *           pencarian barang dilakukan di frontend (JS), bukan SQL/backend
 */

/* ============================================================
 * KONEKSI & AKSES
 * ============================================================ */
include '/home/trialhostm/appconfig/connecttosql_users.php';

$akses_team_diperbolehkan = [6, 13];
include 'config_akses_users.php';

header('X-Content-Type-Options: nosniff');

// Normalisasi variabel koneksi ke $db
if (!isset($db) || !($db instanceof mysqli)) {
    foreach (['conn', 'koneksi', 'mysqli'] as $var) {
        if (isset($$var) && $$var instanceof mysqli) {
            $db = $$var;
            break;
        }
    }
}

if (!isset($db) || !($db instanceof mysqli)) {
    http_response_code(500);
    die('Koneksi database (mysqli) tidak ditemukan.');
}

$db->set_charset('utf8mb4');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
date_default_timezone_set('Asia/Jakarta');


/* ============================================================
 * HELPERS
 * ============================================================ */

/** Escape output untuk HTML */
function h(mixed $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** Format angka desimal */
function nf(mixed $x, int $decimals = 2): string
{
    return number_format((float) $x, $decimals, ',', '.');
}

/** Format tanggal dari Y-m-d → d/m/Y */
function fmtDate(string $ymd): string
{
    if ($ymd === '') return '';
    $parts = explode('-', $ymd);
    return count($parts) === 3
        ? "{$parts[2]}/{$parts[1]}/{$parts[0]}"
        : $ymd;
}

/** Validasi format Y-m-d */
function validDateYmd(?string $s): bool
{
    if (!$s || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return false;
    [$y, $m, $d] = array_map('intval', explode('-', $s));
    return checkdate($m, $d, $y);
}

/** Hasilkan array tanggal Y-m-d dari start ke end (inklusif) */
function daterangeYmd(string $start, string $end): array
{
    $out = [];
    $cur  = strtotime($start);
    $last = strtotime($end);
    while ($cur <= $last) {
        $out[] = date('Y-m-d', $cur);
        $cur   = strtotime('+1 day', $cur);
    }
    return $out;
}

/**
 * Konversi qty dari satuan tertentu ke unit1
 * berdasarkan mapping accurate_items.
 */
function to_unit1(float $qty, string $unit, ?array $conv): float
{
    if (empty($conv)) return $qty;

    $unit = strtolower(trim($unit));
    if ($unit === '') return $qty;

    $map = [
        'u2' => 'r2',
        'u3' => 'r3',
        'u4' => 'r4',
        'u5' => 'r5',
    ];

    $u1 = strtolower(trim((string) ($conv['u1'] ?? '')));
    if ($u1 !== '' && $unit === $u1) return $qty;

    foreach ($map as $uKey => $rKey) {
        $uName = strtolower(trim((string) ($conv[$uKey] ?? '')));
        $ratio = (float) ($conv[$rKey] ?? 0);
        if ($uName !== '' && $unit === $uName && $ratio > 0) {
            return $qty * $ratio;
        }
    }

    return $qty; // fallback: satuan tidak dikenal
}

/**
 * Format qty unit1 ke campuran unit terbesar → terkecil.
 * Contoh: 2 karton 3 pcs
 */
function format_mixed(float $qtyU1, ?array $conv): string
{
    if (empty($conv)) return '';

    $sign = $qtyU1 < 0 ? -1 : 1;
    $rem  = round(abs($qtyU1), 6);
    $u1   = trim((string) ($conv['u1'] ?? '')) ?: 'unit1';

    // Kumpulkan level konversi dari terbesar ke terkecil
    $candidates = [
        ['name' => (string) ($conv['u5'] ?? ''), 'ratio' => (float) ($conv['r5'] ?? 0)],
        ['name' => (string) ($conv['u4'] ?? ''), 'ratio' => (float) ($conv['r4'] ?? 0)],
        ['name' => (string) ($conv['u3'] ?? ''), 'ratio' => (float) ($conv['r3'] ?? 0)],
        ['name' => (string) ($conv['u2'] ?? ''), 'ratio' => (float) ($conv['r2'] ?? 0)],
    ];

    $levels = [];
    $seen   = [];
    foreach ($candidates as $c) {
        $name  = trim($c['name']);
        $ratio = (float) $c['ratio'];
        if ($name === '' || $ratio <= 0) continue;
        $key = strtolower($name) . '|' . $ratio;
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        $levels[]   = ['name' => $name, 'ratio' => $ratio];
    }

    usort($levels, static fn($a, $b) => $b['ratio'] <=> $a['ratio']);

    $parts = [];
    foreach ($levels as $lv) {
        $ratio = (float) $lv['ratio'];
        if ($ratio <= 0) continue;
        $cnt = (int) floor($rem / $ratio);
        if ($cnt > 0) {
            $parts[] = $cnt . ' ' . $lv['name'];
            $rem     = round($rem - $cnt * $ratio, 6);
        }
    }

    if (abs($rem) < 0.0001) $rem = 0.0;

    if ($rem > 0 || empty($parts)) {
        $remDisp = rtrim(rtrim(number_format($rem, 4, '.', ''), '0'), '.') ?: '0';
        $parts[] = $remDisp . ' ' . $u1;
    }

    $out = implode(' ', $parts);
    return $sign < 0 ? ('-' . $out) : $out;
}


/* ============================================================
 * INPUT & FILTER
 * ============================================================ */
$today        = date('Y-m-d');
$defaultStart = date('Y-m-d', strtotime('-2 days'));
$defaultEnd   = $today;

$start_date = validDateYmd($_GET['start_date'] ?? null) ? $_GET['start_date'] : $defaultStart;
$end_date   = validDateYmd($_GET['end_date']   ?? null) ? $_GET['end_date']   : $defaultEnd;
$gudang     = trim((string) ($_GET['gudang']    ?? ''));
$divisi_id  = isset($_GET['divisi_id']) ? (int) $_GET['divisi_id'] : 0;
$only_diff  = isset($_GET['only_diff']) ? 1 : 0;
$sort_by    = trim((string) ($_GET['sort_by'] ?? 'item_no')); // item_no | item_name | freq

// Pastikan urutan tanggal benar
if ($start_date > $end_date) {
    [$start_date, $end_date] = [$end_date, $start_date];
}


/* ============================================================
 * OPTIONS DROPDOWN (gudang & divisi)
 * ============================================================ */
$gudangOptions = [];
$divisiOptions = [];

try {
    $resGudang = $db->query(
        "SELECT DISTINCT TRIM(lokasi) AS lokasi
         FROM stok_opname
         WHERE lokasi IS NOT NULL AND TRIM(lokasi) <> ''
         ORDER BY lokasi ASC"
    );
    while ($r = $resGudang->fetch_assoc()) {
        $loc = trim((string) ($r['lokasi'] ?? ''));
        if ($loc !== '') $gudangOptions[] = $loc;
    }

    $resDiv = $db->query(
        "SELECT DISTINCT d.id, d.nama_divisi AS nama
         FROM accurate_items ai
         LEFT JOIN divisi d ON d.id = ai.divisi_id
         WHERE ai.divisi_id IS NOT NULL
           AND d.id IS NOT NULL
           AND COALESCE(d.suspended, 0) = 0
         ORDER BY d.nama_divisi ASC"
    );
    while ($r = $resDiv->fetch_assoc()) {
        $did = (int)   ($r['id']   ?? 0);
        $dnm = trim((string) ($r['nama'] ?? ''));
        if ($did > 0 && $dnm !== '') {
            $divisiOptions[] = ['id' => $did, 'nama' => $dnm];
        }
    }
} catch (Throwable $e) {
    error_log('[stok_opname_pivot][options] ' . $e->getMessage());
}


/* ============================================================
 * DATA UTAMA
 * ============================================================ */
$dates             = daterangeYmd($start_date, $end_date);
$docsCount         = 0;
$itemCount         = 0;
$itemWithDiffCount = 0;
$errorMsg          = '';

$pivot        = [];  // [item_no][tanggal] = selisih
$itemMeta     = [];  // [item_no] = [nama_barang, satuan, conv, divisi_id, divisi_nama]
$totalsByDate = array_fill_keys($dates, 0.0);
$docIds       = [];
$docDateMap   = [];

try {
    /* ----------------------------------------------------------
     * 1. Ambil dokumen opname sesuai filter
     * ---------------------------------------------------------- */
    if ($gudang !== '') {
        $stmtH = $db->prepare(
            "SELECT id, tanggal
             FROM stok_opname
             WHERE tanggal BETWEEN ? AND ? AND lokasi = ?
             ORDER BY tanggal ASC, id ASC"
        );
        $stmtH->bind_param('sss', $start_date, $end_date, $gudang);
    } else {
        $stmtH = $db->prepare(
            "SELECT id, tanggal
             FROM stok_opname
             WHERE tanggal BETWEEN ? AND ?
             ORDER BY tanggal ASC, id ASC"
        );
        $stmtH->bind_param('ss', $start_date, $end_date);
    }

    $stmtH->execute();
    $resH = $stmtH->get_result();
    while ($r = $resH->fetch_assoc()) {
        $id  = (int)    ($r['id']      ?? 0);
        $tgl = (string) ($r['tanggal'] ?? '');
        if ($id > 0 && $tgl !== '') {
            $docIds[]        = $id;
            $docDateMap[$id] = $tgl;
        }
    }
    $stmtH->close();

    $docsCount = count($docIds);

    if (!empty($docIds)) {
        /* ----------------------------------------------------------
         * 2. Ambil detail semua dokumen
         * ---------------------------------------------------------- */
        $placeDoc = implode(',', array_fill(0, count($docIds), '?'));
        $stmtD    = $db->prepare(
            "SELECT opname_id, item_no, nama_barang, satuan, stok_fisik
             FROM stok_opname_detail
             WHERE opname_id IN ($placeDoc)
             ORDER BY opname_id ASC, id ASC"
        );
        $stmtD->bind_param(str_repeat('i', count($docIds)), ...$docIds);
        $stmtD->execute();
        $resD = $stmtD->get_result();

        $rawDetails  = [];
        $itemNosAssoc = [];
        while ($r = $resD->fetch_assoc()) {
            $opnameId = (int)    ($r['opname_id'] ?? 0);
            $itemNo   = trim((string) ($r['item_no']   ?? ''));
            if ($opnameId <= 0 || $itemNo === '' || !isset($docDateMap[$opnameId])) continue;
            $rawDetails[]        = $r;
            $itemNosAssoc[$itemNo] = true;
        }
        $stmtD->close();

        $itemNos = array_keys($itemNosAssoc);

        /* ----------------------------------------------------------
         * 3. Ambil konversi unit + divisi dari accurate_items
         * ---------------------------------------------------------- */
        $convMap = [];
        if (!empty($itemNos)) {
            $placeItem = implode(',', array_fill(0, count($itemNos), '?'));
            $stmtC     = $db->prepare(
                "SELECT ai.item_no, ai.item_name, ai.divisi_id,
                        ai.unit1_name, ai.unit2_name, ai.unit3_name, ai.unit4_name, ai.unit5_name,
                        ai.ratio2, ai.ratio3, ai.ratio4, ai.ratio5,
                        d.nama_divisi
                 FROM accurate_items ai
                 LEFT JOIN divisi d ON d.id = ai.divisi_id
                 WHERE ai.item_no IN ($placeItem)"
            );
            $stmtC->bind_param(str_repeat('s', count($itemNos)), ...$itemNos);
            $stmtC->execute();
            $resC = $stmtC->get_result();

            while ($c = $resC->fetch_assoc()) {
                $ino = (string) $c['item_no'];
                $convMap[$ino] = [
                    'item_name'   => (string) ($c['item_name']   ?? ''),
                    'divisi_id'   => (int)    ($c['divisi_id']   ?? 0),
                    'divisi_nama' => (string) ($c['nama_divisi'] ?? ''),
                    'u1' => (string) ($c['unit1_name'] ?? ''),
                    'u2' => (string) ($c['unit2_name'] ?? ''),
                    'u3' => (string) ($c['unit3_name'] ?? ''),
                    'u4' => (string) ($c['unit4_name'] ?? ''),
                    'u5' => (string) ($c['unit5_name'] ?? ''),
                    'r2' => (float)  ($c['ratio2'] ?? 0),
                    'r3' => (float)  ($c['ratio3'] ?? 0),
                    'r4' => (float)  ($c['ratio4'] ?? 0),
                    'r5' => (float)  ($c['ratio5'] ?? 0),
                ];
            }
            $stmtC->close();
        }

        /* ----------------------------------------------------------
         * 4. Agregasi fisik per tanggal + item
         * ---------------------------------------------------------- */
        $fisikAgg = []; // [tanggal][item_no] = qty_fisik_u1

        foreach ($rawDetails as $r) {
            $opnameId    = (int) $r['opname_id'];
            $tgl         = (string) $docDateMap[$opnameId];
            $ino         = trim((string) $r['item_no']);
            $sat         = (string) ($r['satuan']    ?? '');
            $qtyF        = (float)  ($r['stok_fisik'] ?? 0);
            $conv        = $convMap[$ino] ?? null;
            $itemDivisiId = (int) ($conv['divisi_id'] ?? 0);

            // Filter divisi
            if ($divisi_id > 0 && $itemDivisiId !== $divisi_id) continue;

            $qtyU1 = to_unit1($qtyF, $sat, $conv);

            $fisikAgg[$tgl][$ino] = ($fisikAgg[$tgl][$ino] ?? 0.0) + $qtyU1;

            if (!isset($itemMeta[$ino])) {
                $nama = trim((string) ($r['nama_barang'] ?? ''));
                if ($nama === '') $nama = (string) ($conv['item_name'] ?? '');
                if ($nama === '') $nama = '-';

                $u1 = trim((string) ($conv['u1'] ?? ''));
                if ($u1 === '') $u1 = trim($sat);
                if ($u1 === '') $u1 = 'unit1';

                $itemMeta[$ino] = [
                    'nama_barang' => $nama,
                    'satuan'      => $u1,
                    'divisi_id'   => $itemDivisiId,
                    'divisi_nama' => (string) ($conv['divisi_nama'] ?? '-'),
                    'conv'        => $conv,
                ];
            }
        }

        $itemNosFiltered = array_keys($itemMeta);

        /* ----------------------------------------------------------
         * 5. Ambil stok sistem per tanggal + item
         * ---------------------------------------------------------- */
        $sysMap = []; // [tanggal][item_no] = qty

        if (!empty($itemNosFiltered) && !empty($dates) && $gudang !== '') {
            $placeItem = implode(',', array_fill(0, count($itemNosFiltered), '?'));
            $placeDate = implode(',', array_fill(0, count($dates), '?'));

            $stmtS = $db->prepare(
                "SELECT as_of_date, item_no, qty
                 FROM stok_per_tanggal
                 WHERE gudang = ?
                   AND as_of_date IN ($placeDate)
                   AND item_no IN ($placeItem)"
            );
            $typesS  = 's' . str_repeat('s', count($dates)) . str_repeat('s', count($itemNosFiltered));
            $paramsS = array_merge([$gudang], $dates, $itemNosFiltered);
            $stmtS->bind_param($typesS, ...$paramsS);
            $stmtS->execute();
            $resS = $stmtS->get_result();

            while ($s = $resS->fetch_assoc()) {
                $tgl = (string) ($s['as_of_date'] ?? '');
                $ino = (string) ($s['item_no']    ?? '');
                $qty = (float)  ($s['qty']        ?? 0);
                if ($tgl !== '' && $ino !== '') {
                    $sysMap[$tgl][$ino] = $qty;
                }
            }
            $stmtS->close();
        }

        /* ----------------------------------------------------------
         * 6. Bentuk pivot
         * ---------------------------------------------------------- */
        foreach ($itemMeta as $ino => $_meta) {
            foreach ($dates as $tgl) {
                $fisik   = (float) ($fisikAgg[$tgl][$ino] ?? 0);
                $sistem  = (float) ($sysMap[$tgl][$ino]   ?? 0);
                $selisih = $fisik - $sistem;

                $pivot[$ino][$tgl]   = $selisih;
                $totalsByDate[$tgl] += $selisih;
            }
        }

        /* ----------------------------------------------------------
         * 7. Filter only_diff
         * ---------------------------------------------------------- */
        if ($only_diff) {
            foreach ($pivot as $ino => $rowDates) {
                $hasDiff = false;
                foreach ($rowDates as $v) {
                    if (abs((float) $v) > 0.000001) { $hasDiff = true; break; }
                }
                if (!$hasDiff) {
                    unset($pivot[$ino], $itemMeta[$ino]);
                }
            }
        }

        /* ----------------------------------------------------------
         * 8. Sorting
         * ---------------------------------------------------------- */
        $rowFreq = [];
        foreach ($pivot as $ino => $rowDates) {
            $freq = 0;
            foreach ($rowDates as $v) {
                if (abs((float) $v) > 0.000001) $freq++;
            }
            $rowFreq[$ino] = $freq;
        }

        $itemKeys = array_keys($pivot);
        usort($itemKeys, function (string $a, string $b) use ($sort_by, $itemMeta, $rowFreq): int {
            $aName = mb_strtolower((string) ($itemMeta[$a]['nama_barang'] ?? ''));
            $bName = mb_strtolower((string) ($itemMeta[$b]['nama_barang'] ?? ''));

            return match ($sort_by) {
                'item_name' => [$aName, $a] <=> [$bName, $b],
                'freq'      => ($rowFreq[$b] <=> $rowFreq[$a]) ?: ([$aName, $a] <=> [$bName, $b]),
                default     => strnatcasecmp($a, $b), // item_no
            };
        });

        // Rebuild array sesuai urutan sort
        $pivot    = array_combine($itemKeys, array_map(fn($k) => $pivot[$k],    $itemKeys));
        $itemMeta = array_combine($itemKeys, array_map(fn($k) => $itemMeta[$k], $itemKeys));

        /* ----------------------------------------------------------
         * 9. Summary
         * ---------------------------------------------------------- */
        $itemCount = count($pivot);
        foreach ($pivot as $rowDates) {
            foreach ($rowDates as $v) {
                if (abs((float) $v) > 0.000001) {
                    $itemWithDiffCount++;
                    break;
                }
            }
        }
    }

} catch (Throwable $e) {
    $errorMsg = $e->getMessage();
    error_log('[stok_opname_pivot] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pivot Stok Opname per Tanggal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ── Variables ── */
        :root {
            --primary:       #0077b6;
            --primary-dark:  #005b88;
            --bg:            #f6fbff;
            --card:          #ffffff;
            --line:          #e2e8f0;
            --text:          #0f172a;
            --muted:         #64748b;
            --success:       #166534;
            --success-bg:    #dcfce7;
            --danger:        #b91c1c;
            --danger-bg:     #fee2e2;
            --neutral:       #475569;
            --neutral-bg:    #f1f5f9;
            --warning:       #92400e;
            --warning-bg:    #fef3c7;
        }

        /* ── Reset & Base ── */
        *, *::before, *::after { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0;
            font-family: Poppins, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(180deg, #fff, var(--bg));
            color: var(--text);
        }
        a { color: inherit; text-decoration: none; }

        /* ── Layout ── */
        .wrap {
            max-width: 1650px;
            margin: 0 auto;
            padding: 16px;
        }

        /* ── Header ── */
        header.top {
            position: sticky; top: 0; z-index: 20;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            color: #fff;
            padding: 14px 16px;
            border-bottom-left-radius: 18px;
            border-bottom-right-radius: 18px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, .12);
        }

        /* ── Card ── */
        .card {
            background: var(--card);
            border: 1px solid #eaf2fb;
            border-radius: 16px;
            padding: 14px;
            box-shadow: 0 8px 22px rgba(2, 68, 107, .06);
            margin-top: 12px;
        }

        /* ── Utilities ── */
        .row   { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-start; }
        .left  { text-align: left; }
        .center{ text-align: center; }
        .right { text-align: right; }
        .muted { color: var(--muted); font-size: 11px; }

        /* ── Filter Form ── */
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(160px, 1fr));
            gap: 12px;
        }
        .field label { display: block; font-size: 12px; color: #475569; margin-bottom: 6px; }
        .field input[type="date"],
        .field input[type="text"],
        .field select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 14px;
            outline: none;
            background: #fff;
        }
        .field input:focus,
        .field select:focus {
            border-color: #7dd3fc;
            box-shadow: 0 0 0 4px rgba(125, 211, 252, .25);
        }
        .checkline {
            display: flex; align-items: center; gap: 8px;
            padding-top: 26px;
            color: #334155; font-size: 14px;
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex; gap: 8px; align-items: center;
            border: 0; border-radius: 10px; padding: 10px 14px;
            font-weight: 700; cursor: pointer; text-decoration: none;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
            font-family: inherit;
        }
        .btn-primary   { background: linear-gradient(90deg, var(--primary), var(--primary-dark)); color: #fff; }
        .btn-secondary { background: #f8fafc; color: #0f172a; border: 1px solid #cbd5e1; }
        .btn:active { transform: translateY(1px); }

        /* ── Stats ── */
        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(180px, 1fr));
            gap: 12px;
        }
        .stat {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px;
            background: linear-gradient(180deg, #fff, #f8fbff);
        }
        .stat .lab { font-size: 12px; color: #64748b; }
        .stat .val { margin-top: 6px; font-size: 22px; font-weight: 700; color: #0f172a; }
        .stat .sub { margin-top: 4px; font-size: 12px; color: #64748b; }

        /* ── Alerts ── */
        .note, .error {
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 13px;
            margin-top: 12px;
        }
        .note  { background: var(--warning-bg); border: 1px solid #fde68a; color: var(--warning); }
        .error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; white-space: pre-wrap; }

        /* ── Badge ── */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
        }

        /* ── Table Wrapper ── */
        .table-tools {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .table-wrap {
            overflow: auto;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #fff;
            max-height: 75vh;
        }

        /* ── Table ── */
        table {
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;   /* kunci lebar kolom sesuai definisi */
            width: max-content;    /* total lebar = sticky cols + kolom tanggal dinamis */
            background: #fff;
        }

        /* Lebar kolom sticky dikunci via colgroup di HTML */
        col.col-no   { width: 50px; }
        col.col-kode { width: 130px; }
        col.col-nama { width: 300px; }
        col.col-div  { width: 130px; }
        col.col-unit { width: 90px; }
        col.col-hari { width: 110px; }
        col.col-tgl  { width: 120px; }  /* tiap kolom tanggal */
        th, td {
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 10px;
            font-size: 13px;
            vertical-align: middle;
            background: #fff;
        }
        thead th {
            position: sticky; top: 0; z-index: 5;
            background: #f8fafc;
            font-weight: 700;
            text-align: center;
            white-space: nowrap;
        }
        /* hover diatur di blok sticky di bawah */
        /*
         * Lebar kolom sticky (harus konsisten antara thead, tbody, tfoot):
         *   col-1 (No)          : 50px
         *   col-2 (Kode)        : 130px   → left = 50
         *   col-3 (Nama Barang) : 300px   → left = 180
         *   col-4 (Divisi)      : 130px   → left = 480
         *   col-5 (Unit)        : 90px    → left = 610
         *   col-6 (Hari Selisih): 110px   → left = 700
         *   kolom tanggal mulai : left = 810
         */

        /* Nilai background eksplisit agar konten scroll tidak tembus */
        td, th { background: #fff; }
        thead th { background: #f8fafc; }
        tfoot td, tfoot th { background: #f8fafc; }
        tbody tr:hover td { background: #fbfdff; }

        /* Sticky col definitions */
        .sticky-1 {
            position: sticky; left: 0;
            z-index: 4;
            background: #fff;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .sticky-2 {
            position: sticky; left: 50px;
            z-index: 4;
            background: #fff;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .sticky-3 {
            position: sticky; left: 180px;
            z-index: 4;
            background: #fff;
        }
        .sticky-4 {
            position: sticky; left: 480px;
            z-index: 4;
            background: #fff;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .sticky-5 {
            position: sticky; left: 610px;
            z-index: 4;
            background: #fff;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .sticky-6 {
            position: sticky; left: 700px;
            z-index: 4;
            /* Garis pemisah setelah kolom sticky terakhir */
            box-shadow: 3px 0 6px -2px rgba(0,0,0,.12);
            background: #fff;
        }

        /* thead sticky override */
        thead .sticky-1,
        thead .sticky-2,
        thead .sticky-3,
        thead .sticky-4,
        thead .sticky-5,
        thead .sticky-6 {
            z-index: 7;
            background: #f8fafc;
        }

        tfoot td, tfoot th {
            position: sticky; bottom: 0; z-index: 3;
            background: #f8fafc;
            font-weight: 700;
        }

        /* Sticky tfoot columns harus lebih tinggi dari kolom tanggal tfoot yg ikut scroll */
        tfoot .sticky-1,
        tfoot .sticky-2,
        tfoot .sticky-3,
        tfoot .sticky-4,
        tfoot .sticky-5,
        tfoot .sticky-6 {
            background: #f8fafc;
            z-index: 8;
        }

        /* hover row: paksa background sticky ikut hover */
        tbody tr:hover .sticky-1,
        tbody tr:hover .sticky-2,
        tbody tr:hover .sticky-3,
        tbody tr:hover .sticky-4,
        tbody tr:hover .sticky-5,
        tbody tr:hover .sticky-6 {
            background: #fbfdff;
        }

        /* ── Cell styles ── */
        .item-name { max-width: 400px; white-space: normal; line-height: 1.35; }
        .cell-val  { font-weight: 700; text-align: right; white-space: nowrap; }
        .cell-box  { border-radius: 10px; padding: 6px 8px; min-width: 94px; }
        .v-pos     { color: var(--success); background: var(--success-bg); }
        .v-neg     { color: var(--danger);  background: var(--danger-bg);  }
        .v-zero    { color: var(--neutral); background: var(--neutral-bg); }
        .mini {
            display: block;
            margin-top: 3px;
            font-size: 10px; font-weight: 400;
            color: #475569;
            white-space: normal;
            line-height: 1.2;
        }

        /* ── Search ── */
        .frontend-search { min-width: 280px; max-width: 420px; }

        /* ── Responsive ── */
        @media (max-width: 1100px) {
            .filter-grid { grid-template-columns: repeat(2, minmax(160px, 1fr)); }
            .stats        { grid-template-columns: repeat(2, minmax(160px, 1fr)); }
        }
        @media (max-width: 640px) {
            .filter-grid, .stats { grid-template-columns: 1fr; }
            .wrap                 { padding: 12px; }
            .frontend-search      { min-width: 100%; max-width: 100%; }
        }

        /* ── Print ── */
        @media print {
            @page { size: A4 landscape; margin: 8mm; }

            header.top, .filter-card, .note,
            .actions-print, .table-tools { display: none !important; }

            .wrap  { max-width: 100% !important; padding: 0 !important; }
            .card  { box-shadow: none !important; border: 1px solid #111 !important; }

            .table-wrap {
                overflow: visible !important;
                max-height: none !important;
                border: none !important;
            }
            table { min-width: 100% !important; width: 100% !important; }

            thead th, tfoot td, tfoot th { position: static !important; }
            .sticky-1, .sticky-2, .sticky-3,
            .sticky-4, .sticky-5, .sticky-6 { position: static !important; }

            th, td { font-size: 10px !important; padding: 4px 5px !important; color: #000 !important; }
            .cell-box { background: #fff !important; border: 1px solid #999 !important; }
        }
    </style>
</head>
<body>

<header class="top">
    <div class="row" style="justify-content:space-between; align-items:center">
        <div>
            <h2 style="margin:0">Pivot Stok Opname per Tanggal</h2>
            <div style="font-size:12px; opacity:.95; margin-top:3px">
                Rekap horizontal per barang — nilai sel = selisih unit1
            </div>
        </div>
        <div class="actions-print">
            <button type="button" class="btn btn-secondary" onclick="window.print()">
                <i class="fa fa-print"></i> Cetak
            </button>
        </div>
    </div>
</header>

<div class="wrap">

    <!-- ── Filter ── -->
    <section class="card filter-card">
        <form method="get" class="filter-grid" autocomplete="off">

            <div class="field">
                <label>Tanggal Awal</label>
                <input type="date" name="start_date" value="<?= h($start_date) ?>">
            </div>

            <div class="field">
                <label>Tanggal Akhir</label>
                <input type="date" name="end_date" value="<?= h($end_date) ?>">
            </div>

            <div class="field">
                <label>Gudang</label>
                <select name="gudang" required>
                    <option value="">-- Pilih Gudang --</option>
                    <?php foreach ($gudangOptions as $opt): ?>
                        <option value="<?= h($opt) ?>" <?= $gudang === $opt ? 'selected' : '' ?>>
                            <?= h($opt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>Divisi</label>
                <select name="divisi_id">
                    <option value="0">-- Semua Divisi --</option>
                    <?php foreach ($divisiOptions as $div): ?>
                        <option value="<?= $div['id'] ?>" <?= $divisi_id === $div['id'] ? 'selected' : '' ?>>
                            <?= h($div['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>Urutkan</label>
                <select name="sort_by">
                    <option value="item_no"   <?= $sort_by === 'item_no'   ? 'selected' : '' ?>>Kode Barang</option>
                    <option value="item_name" <?= $sort_by === 'item_name' ? 'selected' : '' ?>>Nama Barang</option>
                    <option value="freq"      <?= $sort_by === 'freq'      ? 'selected' : '' ?>>Frekuensi Selisih</option>
                </select>
            </div>

            <div class="field">
                <label>&nbsp;</label>
                <div class="row" style="gap:8px">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-magnifying-glass"></i> Tampilkan
                    </button>
                    <a href="<?= h($_SERVER['PHP_SELF']) ?>" class="btn btn-secondary">
                        <i class="fa fa-rotate-left"></i> Reset
                    </a>
                </div>
            </div>

            <div class="checkline" style="grid-column:1/-1">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer">
                    <input type="checkbox" name="only_diff" value="1" <?= $only_diff ? 'checked' : '' ?>>
                    Hanya tampil item yang ada selisih
                </label>
            </div>

        </form>

        <?php if ($errorMsg !== ''): ?>
            <div class="error">ERROR: <?= h($errorMsg) ?></div>
        <?php endif; ?>
    </section>

    <!-- ── Statistik ── -->
    <section class="card">
        <div class="stats">
            <div class="stat">
                <div class="lab">Range Tanggal</div>
                <div class="val">
                    <?= h(fmtDate($start_date)) ?>
                    <span style="font-size:14px; font-weight:600">s/d</span>
                    <?= h(fmtDate($end_date)) ?>
                </div>
                <div class="sub"><?= count($dates) ?> hari</div>
            </div>
            <div class="stat">
                <div class="lab">Jumlah Dokumen</div>
                <div class="val"><?= $docsCount ?></div>
                <div class="sub">Gudang: <?= $gudang !== '' ? h($gudang) : '-' ?></div>
            </div>
            <div class="stat">
                <div class="lab">Jumlah Item Tampil</div>
                <div class="val" id="statShownRows"><?= $itemCount ?></div>
                <div class="sub">Item dengan selisih: <?= $itemWithDiffCount ?></div>
            </div>
        </div>
    </section>

    <!-- ── Tabel Pivot ── -->
    <section class="card">
        <div class="table-tools">
            <div>
                <h3 style="margin:0; color:var(--primary)">
                    <i class="fa-solid fa-table-columns"></i> Rekap Pivot
                </h3>
                <div class="muted" style="margin-top:4px">
                    Nilai sel = <b>Qty Fisik − Stok Sistem</b> pada tanggal tersebut
                </div>
            </div>

            <div class="row" style="align-items:flex-end; gap:10px">
                <div class="field frontend-search" style="margin:0">
                    <label>Cari Barang (frontend)</label>
                    <input type="text" id="frontendSearch" placeholder="Ketik kode / nama barang / divisi">
                </div>
                <div class="row" style="gap:8px">
                    <span class="badge">Hijau = positif</span>
                    <span class="badge">Merah = negatif</span>
                    <span class="badge">Abu = nol</span>
                </div>
            </div>
        </div>

        <div class="table-wrap">
            <table id="pivotTable">
                <colgroup>
                    <col class="col-no">
                    <col class="col-kode">
                    <col class="col-nama">
                    <col class="col-div">
                    <col class="col-unit">
                    <col class="col-hari">
                    <?php foreach ($dates as $_): ?>
                        <col class="col-tgl">
                    <?php endforeach; ?>
                </colgroup>
                <thead>
                    <tr>
                        <th class="sticky-1">No</th>
                        <th class="sticky-2">Kode Barang</th>
                        <th class="sticky-3">Nama Barang</th>
                        <th class="sticky-4">Divisi</th>
                        <th class="sticky-5">Unit</th>
                        <th class="sticky-6 center">Hari Selisih</th>
                        <?php foreach ($dates as $tgl): ?>
                            <th><?= h(fmtDate($tgl)) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>

                <tbody id="pivotTbody">
                    <?php if ($gudang === ''): ?>
                        <tr>
                            <td colspan="<?= 6 + count($dates) ?>" class="center muted" style="padding:18px">
                                Pilih gudang terlebih dahulu lalu klik <b>Tampilkan</b>.
                            </td>
                        </tr>

                    <?php elseif (empty($pivot)): ?>
                        <tr>
                            <td colspan="<?= 6 + count($dates) ?>" class="center muted" style="padding:18px">
                                Tidak ada data untuk filter yang dipilih.
                            </td>
                        </tr>

                    <?php else: ?>
                        <?php
                        $no = 1;
                        foreach ($pivot as $itemNo => $rowDates):
                            $meta = $itemMeta[$itemNo] ?? [
                                'nama_barang' => '-',
                                'divisi_nama' => '-',
                                'satuan'      => 'unit1',
                                'conv'        => null,
                            ];

                            $freq = 0;
                            foreach ($dates as $tgl) {
                                if (abs((float) ($rowDates[$tgl] ?? 0)) > 0.000001) $freq++;
                            }

                            $searchText = mb_strtolower(implode(' ', [
                                $itemNo,
                                $meta['nama_barang'],
                                $meta['divisi_nama'],
                                $meta['satuan'],
                            ]));
                        ?>
                        <tr class="pivot-row" data-search="<?= h($searchText) ?>">
                            <td class="sticky-1 center row-no"><?= $no++ ?></td>
                            <td class="sticky-2"><?= h($itemNo) ?></td>
                            <td class="sticky-3 item-name"><?= h($meta['nama_barang']) ?></td>
                            <td class="sticky-4 center"><?= h($meta['divisi_nama'] ?: '-') ?></td>
                            <td class="sticky-5 center"><?= h($meta['satuan']) ?></td>
                            <td class="sticky-6 center"><?= $freq ?></td>

                            <?php foreach ($dates as $tgl):
                                $v   = (float) ($rowDates[$tgl] ?? 0);
                                $cls = 'v-zero';
                                if ($v >  0.000001) $cls = 'v-pos';
                                if ($v < -0.000001) $cls = 'v-neg';
                                $mix = !empty($meta['conv']) ? format_mixed($v, $meta['conv']) : '';
                            ?>
                                <td class="right">
                                    <div class="cell-box cell-val <?= $cls ?>">
                                        <?= nf($v, 2) ?>
                                        <?php if ($mix !== ''): ?>
                                            <span class="mini"><?= h($mix) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>

                <?php if (!empty($pivot)): ?>
                    <tfoot>
                        <tr>
                            <th class="sticky-1 center">—</th>
                            <th class="sticky-2 center" colspan="2" style="text-align:center">TOTAL PER TANGGAL</th>
                            <th class="sticky-3 center"></th>
                            <th class="sticky-4 center"></th>
                            <th class="sticky-5 center"></th>
                            <th class="sticky-6 center">—</th>
                            <?php foreach ($dates as $tgl):
                                $v     = (float) ($totalsByDate[$tgl] ?? 0);
                                $color = $v > 0 ? '#166534' : ($v < 0 ? '#b91c1c' : '#0f172a');
                            ?>
                                <th class="right" style="color:<?= h($color) ?>"><?= nf($v, 2) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </tfoot>
                <?php endif; ?>

            </table>
        </div>
    </section>

</div><!-- /.wrap -->

<script>
(function () {
    'use strict';

    const input    = document.getElementById('frontendSearch');
    const tbody    = document.getElementById('pivotTbody');
    const statEl   = document.getElementById('statShownRows');

    if (!input || !tbody) return;

    function applyFilter() {
        const q    = input.value.trim().toLowerCase();
        const rows = tbody.querySelectorAll('tr.pivot-row');
        let shown  = 0;
        let no     = 1;

        rows.forEach(row => {
            const hay = (row.dataset.search || '').toLowerCase();
            const ok  = !q || hay.includes(q);

            row.style.display = ok ? '' : 'none';

            if (ok) {
                const noCell = row.querySelector('.row-no');
                if (noCell) noCell.textContent = String(no++);
                shown++;
            }
        });

        if (statEl) statEl.textContent = String(shown);
    }

    input.addEventListener('input', applyFilter);

    // ── Batasi scroll kiri: kolom tanggal tidak bisa tertutup di balik sticky columns ──
    const tableWrap = document.querySelector('.table-wrap');
    if (tableWrap) {
        // Hitung lebar sticky columns dari DOM (lebih akurat daripada hardcode)
        function getStickyWidth() {
            const row = tableWrap.querySelector('thead tr');
            if (!row) return 0;
            let w = 0;
            row.querySelectorAll('th.sticky-1, th.sticky-2, th.sticky-3, th.sticky-4, th.sticky-5, th.sticky-6')
               .forEach(th => { w += th.offsetWidth; });
            return w;
        }

        let minScroll = 0;

        function initMinScroll() {
            const table      = tableWrap.querySelector('table');
            const stickyW    = getStickyWidth();
            const tableW     = table ? table.offsetWidth : 0;
            const wrapW      = tableWrap.clientWidth;
            // Jika lebar tabel lebih dari wrapper, kolom tanggal bisa tersembunyi.
            // Minimum scrollLeft = lebar sticky − lebar wrapper + 1 kolom tanggal (110px)
            // Namun jika tabel muat semua, tidak perlu batasan.
            minScroll = Math.max(0, stickyW - wrapW + 110);
        }

        function enforceMinScroll() {
            if (tableWrap.scrollLeft < minScroll) {
                tableWrap.scrollLeft = minScroll;
            }
        }

        // Inisialisasi setelah render
        requestAnimationFrame(() => {
            initMinScroll();
            enforceMinScroll();
        });

        tableWrap.addEventListener('scroll', enforceMinScroll);

        // Update saat resize window
        window.addEventListener('resize', () => {
            initMinScroll();
            enforceMinScroll();
        });
    }
}());
</script>
</body>
</html>
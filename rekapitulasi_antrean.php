<?php
session_start();
include 'koneksi.php';

// Menangani URL jika ada akhiran path agar tidak terkunci
if (!empty($_SERVER['PATH_INFO'])) {
    $target = trim($_SERVER['PATH_INFO'], '/');
    if (file_exists(__DIR__ . '/' . $target)) {
        header("Location: /Capstone-klinik/" . $target);
        exit;
    }
}

// Filter Periode
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'hari_ini';
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : '';
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : '';

$where_clause = "WHERE 1=1";
$judul_periode = "Hari Ini";

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

if ($filter === 'hari_ini') {
    $where_clause .= " AND DATE(k.tanggal_kunjungan) = '$today'";
    $judul_periode = "Hari Ini (" . date('d M Y') . ")";
} elseif ($filter === 'kemarin') {
    $where_clause .= " AND DATE(k.tanggal_kunjungan) = '$yesterday'";
    $judul_periode = "Kemarin (" . date('d M Y', strtotime('-1 day')) . ")";
} elseif ($filter === 'minggu_ini') {
    $where_clause .= " AND YEARWEEK(k.tanggal_kunjungan, 1) = YEARWEEK(CURDATE(), 1)";
    $judul_periode = "Minggu Ini";
} elseif ($filter === 'bulan_ini') {
    $where_clause .= " AND MONTH(k.tanggal_kunjungan) = MONTH(CURDATE()) AND YEAR(k.tanggal_kunjungan) = YEAR(CURDATE())";
    $judul_periode = "Bulan Ini (" . date('F Y') . ")";
} elseif ($filter === 'custom' && !empty($tgl_mulai) && !empty($tgl_selesai)) {
    $where_clause .= " AND DATE(k.tanggal_kunjungan) BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
    $judul_periode = "Rentang: $tgl_mulai s/d $tgl_selesai";
}

// 1. STATISTIK REKAPITULASI
$q_rekap_stat = mysqli_query($koneksi, "SELECT 
    COUNT(*) as total_pendaftaran,
    SUM(CASE WHEN k.status_antrean = 'Selesai' THEN 1 ELSE 0 END) as total_selesai,
    SUM(CASE WHEN k.status_antrean = 'Batal' THEN 1 ELSE 0 END) as total_batal,
    SUM(CASE WHEN k.status_antrean = 'Menunggu' THEN 1 ELSE 0 END) as total_menunggu,
    SUM(CASE WHEN k.status_antrean = 'Diperiksa' THEN 1 ELSE 0 END) as total_diperiksa
    FROM kunjungan k $where_clause");
$stat = mysqli_fetch_assoc($q_rekap_stat);

// 2. REKAPITULASI BERDASARKAN POLI / RUANG PELAYANAN
$q_per_poli = mysqli_query($koneksi, "SELECT 
    IFNULL(k.poli_tujuan, 'Poli Umum') as nama_poli,
    COUNT(*) as total,
    SUM(CASE WHEN k.status_antrean = 'Selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN k.status_antrean = 'Batal' THEN 1 ELSE 0 END) as batal
    FROM kunjungan k $where_clause 
    GROUP BY IFNULL(k.poli_tujuan, 'Poli Umum')");

// 3. TABEL DETAIL RIWAYAT ANTREAN
$q_riwayat = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.gender, p.status_bpjs 
    FROM kunjungan k 
    JOIN pasien p ON k.id_pasien = p.id_pasien 
    $where_clause 
    ORDER BY k.tanggal_kunjungan DESC, k.id_kunjungan DESC");

// 4. LOGIKA EKSPOR EXCEL JIKA ADA PARAMETER &export=excel
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filename = "Rekapitulasi_Antrean_" . date('Ymd_His') . ".xls";
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    ?>
    <table border="1">
        <tr>
            <th colspan="7" style="font-size: 16px; font-weight: bold; text-align: center;">REKAPITULASI ANTREAN PASIEN - PRAKTEK MANDIRI DRG. AZWARI</th>
        </tr>
        <tr>
            <th colspan="7" style="text-align: center;">Periode: <?php echo $judul_periode; ?></th>
        </tr>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>No. Antrean</th>
            <th>Nama Pasien</th>
            <th>Loket / Ruang Pelayanan</th>
            <th>Waktu Pendaftaran</th>
            <th>Status Akhir Antrean</th>
        </tr>
        <?php 
        $no = 1;
        while ($rx = mysqli_fetch_assoc($q_riwayat)) {
            echo "<tr>";
            echo "<td>" . $no++ . "</td>";
            echo "<td>" . date('Y-m-d', strtotime($rx['tanggal_kunjungan'])) . "</td>";
            echo "<td>" . ($rx['nomor_antrean'] ?: 'A-000') . "</td>";
            echo "<td>" . htmlspecialchars($rx['nama_lengkap']) . "</td>";
            echo "<td>" . htmlspecialchars($rx['poli_tujuan'] ?: 'Poli Umum') . " (" . htmlspecialchars($rx['loket_meja'] ?: 'Meja 1') . ")</td>";
            echo "<td>" . date('H:i:s', strtotime($rx['tanggal_kunjungan'])) . "</td>";
            echo "<td>" . $rx['status_antrean'] . "</td>";
            echo "</tr>";
        }
        ?>
    </table>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <base href="/Capstone-klinik/">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Antrean - Praktek Mandiri drg. Azwari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-main: #f0f4f8;
            --bg-card: #ffffff;
            --sidebar-w: 240px;
            --blue: #2563eb;
            --blue-light: #eff6ff;
            --green: #16a34a;
            --green-light: #f0fdf4;
            --amber: #d97706;
            --amber-light: #fffbeb;
            --purple: #7c3aed;
            --purple-light: #f5f3ff;
            --red: #dc2626;
            --red-light: #fef2f2;
            --text-dark: #1e293b;
            --text-mid: #475569;
            --text-light: #94a3b8;
            --border: #e2e8f0;
            --radius: 12px;
            --shadow: 0 1px 3px rgba(0,0,0,0.07), 0 4px 16px rgba(0,0,0,0.04);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-main);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--text-dark);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            z-index: 100;
            overflow-y: auto;
        }
        .sidebar-brand {
            padding: 22px 20px 18px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-brand .icon {
            width: 36px; height: 36px;
            background: var(--blue);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .sidebar-brand .icon svg { width: 20px; height: 20px; fill: white; }
        .sidebar-brand .name { font-size: 13px; font-weight: 700; color: white; line-height: 1.3; }
        .sidebar-brand .sub  { font-size: 10px; color: rgba(255,255,255,0.45); font-weight: 500; letter-spacing: 0.5px; }

        .sidebar-section {
            padding: 18px 14px 6px;
            font-size: 10px;
            font-weight: 700;
            color: rgba(255,255,255,0.3);
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .sidebar-nav { padding: 0 10px; flex: 1; }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            color: rgba(255,255,255,0.65);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            margin-bottom: 2px;
            transition: all 0.15s;
        }
        .nav-item:hover { background: rgba(255,255,255,0.08); color: white; }
        .nav-item.active { background: var(--blue); color: white; font-weight: 600; }
        .nav-item svg { width: 17px; height: 17px; flex-shrink: 0; }

        .sidebar-footer {
            padding: 14px 10px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .sidebar-footer a {
            display: flex; align-items: center; gap: 10px;
            color: rgba(255,255,255,0.5); text-decoration: none;
            font-size: 13px; padding: 10px 12px; border-radius: 8px;
            transition: all 0.15s;
        }
        .sidebar-footer a:hover { background: rgba(220,38,38,0.15); color: #f87171; }
        .sidebar-footer a svg { width: 16px; height: 16px; }

        /* ===== MAIN CONTENT ===== */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ===== TOPBAR ===== */
        .topbar {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
        }
        .topbar-left h1 { font-size: 17px; font-weight: 700; color: var(--text-dark); }
        .topbar-left .breadcrumb { font-size: 12px; color: var(--text-light); margin-top: 1px; }
        .topbar-right { display: flex; align-items: center; gap: 12px; }

        /* ===== CONTENT AREA ===== */
        .content { padding: 28px; flex: 1; }

        /* Filter Bar */
        .filter-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 18px 22px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
        }
        .filter-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .filter-btn {
            background: var(--bg-main);
            color: var(--text-mid);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid var(--border);
            transition: all 0.15s;
        }
        .filter-btn:hover { background: #e2e8f0; }
        .filter-btn.active { background: var(--blue); color: white; border-color: var(--blue); }

        .custom-date-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .custom-date-form input {
            padding: 7px 10px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 12px;
        }
        .custom-date-form button {
            background: #0284c7;
            color: white;
            border: none;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        /* Stat Grid 5 */
        .stat-grid-5 {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 18px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        .stat-val { font-size: 30px; font-weight: 800; color: var(--text-dark); margin: 6px 0; }
        .stat-lbl { font-size: 11.5px; font-weight: 700; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.5px; }

        /* Card Section */
        .card {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 22px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }
        .card-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 18px;
        }
        .card-title {
            font-size: 15px; font-weight: 700; color: var(--text-dark);
            display: flex; align-items: center; gap: 8px;
        }
        .card-title .dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--blue); flex-shrink: 0;
        }

        /* Tables */
        .table-responsive { width: 100%; overflow-x: auto; }
        .rekap-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .rekap-table thead th {
            padding: 12px 14px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-light);
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
        }
        .rekap-table tbody tr { border-bottom: 1px solid var(--border); }
        .rekap-table tbody tr:hover { background: #f8fafc; }
        .rekap-table td { padding: 12px 14px; vertical-align: middle; }

        .btn-export-excel {
            background: #16a34a;
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-export-excel:hover { background: #15803d; }

        .btn-print {
            background: var(--bg-main);
            color: var(--text-mid);
            border: 1px solid var(--border);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-status {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-status.Selesai { background: #dcfce7; color: #166534; }
        .badge-status.Menunggu { background: #fef3c7; color: #92400e; }
        .badge-status.Diperiksa { background: #e0f2fe; color: #0369a1; }
        .badge-status.Dipanggil { background: #ccfbf1; color: #0f766e; }
        .badge-status.Batal { background: #fee2e2; color: #991b1b; }

        /* Print Media Styles */
        @media print {
            .sidebar, .topbar, .filter-card, .btn-export-excel, .btn-print, .sidebar-footer { display: none !important; }
            .main { margin-left: 0 !important; width: 100% !important; }
            .content { padding: 0 !important; }
            .card { box-shadow: none !important; border: 1px solid #000 !important; }
        }

        /* ===== FOOTER ===== */
        .page-footer {
            padding: 16px 28px;
            border-top: 1px solid var(--border);
            font-size: 11.5px; color: var(--text-light);
            display: flex; justify-content: space-between; align-items: center;
            background: var(--bg-card);
        }

        @media (max-width: 1024px) {
            .stat-grid-5 { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .stat-grid-5 { grid-template-columns: repeat(2, 1fr); }
            :root { --sidebar-w: 0px; }
            .sidebar { display: none; }
            .main { margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<nav class="sidebar">
    <div class="sidebar-brand">
        <div class="icon">
            <svg viewBox="0 0 24 24"><path d="M19 10.5h-5.5V5c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v5.5H5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5h5.5V19c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5v-5.5H19c.83 0 1.5-.67 1.5-1.5s-.67-1.5-1.5-1.5z"/></svg>
        </div>
        <div>
            <div class="name">Praktek Mandiri drg. Azwari</div>
            <div class="sub">SISTEM INFORMASI KLINIK</div>
        </div>
    </div>

    <div class="sidebar-section">Menu Utama</div>
    <div class="sidebar-nav">
        <a href="dashboard_admin.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>
        <a href="pengumuman_antrean.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            Pengumuman Antrean
        </a>
        <a href="monitor_antrean.php" target="_blank" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            Monitor Antrean TV ↗
        </a>
        <a href="rekapitulasi_antrean.php" class="nav-item active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Rekapitulasi Antrean
        </a>
        <a href="index.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Loket Pendaftaran
        </a>
        <a href="dokter.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            Ruang Dokter
        </a>
        <a href="laporan.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Laporan Excel
        </a>
    </div>

    <div class="sidebar-footer">
        <a href="login.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Kembali ke Login
        </a>
    </div>
</nav>

<!-- MAIN -->
<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left">
            <h1>Rekapitulasi Antrean Pasien</h1>
            <div class="breadcrumb">Laporan dan analisis data antrean pelayanan klinik</div>
        </div>
        <div class="topbar-right">
            <?php
            $export_url = "rekapitulasi_antrean.php?export=excel&filter=" . urlencode($filter);
            if ($filter === 'custom') {
                $export_url .= "&tgl_mulai=" . urlencode($tgl_mulai) . "&tgl_selesai=" . urlencode($tgl_selesai);
            }
            ?>
            <a href="<?php echo $export_url; ?>" class="btn-export-excel">
                📊 Ekspor ke Excel
            </a>
            <button onclick="window.print()" class="btn-print">
                🖨️ Cetak Laporan
            </button>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content">

        <!-- FILTER PERIODE -->
        <div class="filter-card">
            <div class="filter-buttons">
                <a href="rekapitulasi_antrean.php?filter=hari_ini" class="filter-btn <?php echo ($filter === 'hari_ini') ? 'active' : ''; ?>">Hari Ini</a>
                <a href="rekapitulasi_antrean.php?filter=kemarin" class="filter-btn <?php echo ($filter === 'kemarin') ? 'active' : ''; ?>">Kemarin</a>
                <a href="rekapitulasi_antrean.php?filter=minggu_ini" class="filter-btn <?php echo ($filter === 'minggu_ini') ? 'active' : ''; ?>">Minggu Ini</a>
                <a href="rekapitulasi_antrean.php?filter=bulan_ini" class="filter-btn <?php echo ($filter === 'bulan_ini') ? 'active' : ''; ?>">Bulan Ini</a>
            </div>

            <form method="GET" action="rekapitulasi_antrean.php" class="custom-date-form">
                <input type="hidden" name="filter" value="custom">
                <input type="date" name="tgl_mulai" value="<?php echo htmlspecialchars($tgl_mulai ?: date('Y-m-d')); ?>" required>
                <span style="font-size:12px; color:var(--text-light);">s/d</span>
                <input type="date" name="tgl_selesai" value="<?php echo htmlspecialchars($tgl_selesai ?: date('Y-m-d')); ?>" required>
                <button type="submit">Filter Tanggal</button>
            </form>
        </div>

        <!-- 5 STAT CARDS REKAPITULASI -->
        <div class="stat-grid-5">
            <div class="stat-card">
                <div class="stat-lbl">Total Pendaftaran</div>
                <div class="stat-val"><?php echo (int)$stat['total_pendaftaran']; ?></div>
                <div style="font-size: 11px; color: var(--text-light);">Semua pendaftaran</div>
            </div>

            <div class="stat-card">
                <div class="stat-lbl" style="color: #16a34a;">Selesai Dilayani</div>
                <div class="stat-val" style="color: #16a34a;"><?php echo (int)$stat['total_selesai']; ?></div>
                <div style="font-size: 11px; color: var(--text-light);">Pelayanan tuntas</div>
            </div>

            <div class="stat-card">
                <div class="stat-lbl" style="color: #dc2626;">Dibatalkan</div>
                <div class="stat-val" style="color: #dc2626;"><?php echo (int)$stat['total_batal']; ?></div>
                <div style="font-size: 11px; color: var(--text-light);">Antrean dibatalkan</div>
            </div>

            <div class="stat-card">
                <div class="stat-lbl" style="color: #d97706;">Masih Menunggu</div>
                <div class="stat-val" style="color: #d97706;"><?php echo (int)$stat['total_menunggu']; ?></div>
                <div style="font-size: 11px; color: var(--text-light);">Belum dipanggil</div>
            </div>

            <div class="stat-card">
                <div class="stat-lbl" style="color: #0284c7;">Sedang Diperiksa</div>
                <div class="stat-val" style="color: #0284c7;"><?php echo (int)$stat['total_diperiksa']; ?></div>
                <div style="font-size: 11px; color: var(--text-light);">Dalam ruang dokter</div>
            </div>
        </div>

        <!-- REKAP PER POLI / LOKET -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span class="dot" style="background:#0d9488;"></span>
                    Rekapitulasi Berdasarkan Loket / Ruang Pelayanan
                </div>
            </div>

            <table class="rekap-table">
                <thead>
                    <tr>
                        <th>Nama Loket / Poli</th>
                        <th>Total Antrean Masuk</th>
                        <th>Selesai Dilayani</th>
                        <th>Dibatalkan</th>
                        <th>Persentase Penyelesaian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($q_per_poli) == 0): ?>
                        <tr><td colspan="5" style="text-align:center; color:var(--text-light); padding:20px;">Belum ada data pada periode ini.</td></tr>
                    <?php else: ?>
                        <?php while ($rpoli = mysqli_fetch_assoc($q_per_poli)): 
                            $pct = ($rpoli['total'] > 0) ? round(($rpoli['selesai'] / $rpoli['total']) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td style="font-weight:700; color:var(--text-dark);">
                                    <?php echo htmlspecialchars($rpoli['nama_poli']); ?>
                                </td>
                                <td><strong><?php echo $rpoli['total']; ?></strong> pasien</td>
                                <td style="color:#16a34a; font-weight:700;"><?php echo $rpoli['selesai']; ?></td>
                                <td style="color:#dc2626; font-weight:700;"><?php echo $rpoli['batal']; ?></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div style="flex:1; height:6px; background:#e2e8f0; border-radius:3px; overflow:hidden;">
                                            <div style="height:100%; width:<?php echo $pct; ?>%; background:#16a34a;"></div>
                                        </div>
                                        <span style="font-size:12px; font-weight:700;"><?php echo $pct; ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- TABEL DETAIL RIWAYAT ANTREAN -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span class="dot"></span>
                    Tabel Riwayat Antrean Pasien (<?php echo $judul_periode; ?>)
                </div>
            </div>

            <div class="table-responsive">
                <table class="rekap-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tanggal</th>
                            <th>No. Antrean</th>
                            <th>Nama Pasien</th>
                            <th>Loket / Ruang Pelayanan</th>
                            <th>Waktu Pendaftaran</th>
                            <th>Status Akhir Antrean</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($q_riwayat) == 0): ?>
                            <tr>
                                <td colspan="7" style="text-align:center; color:var(--text-light); padding:30px;">
                                    Tidak ada data antrean untuk periode yang dipilih.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; while ($r = mysqli_fetch_assoc($q_riwayat)): ?>
                                <tr>
                                    <td style="color:var(--text-light); font-weight:600;"><?php echo $no++; ?></td>
                                    <td><?php echo date('d M Y', strtotime($r['tanggal_kunjungan'])); ?></td>
                                    <td>
                                        <span style="font-weight:800; color:#0284c7; background:#e0f2fe; padding:2px 8px; border-radius:6px;">
                                            <?php echo htmlspecialchars($r['nomor_antrean'] ?: 'A-000'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight:700;"><?php echo htmlspecialchars($r['nama_lengkap']); ?></div>
                                        <div style="font-size:11px; color:var(--text-light);"><?php echo htmlspecialchars($r['gender']); ?> • <?php echo htmlspecialchars($r['status_bpjs'] ?: 'Umum'); ?></div>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($r['poli_tujuan'] ?: 'Poli Umum'); ?></div>
                                        <div style="font-size:11px; color:var(--text-light);"><?php echo htmlspecialchars($r['loket_meja'] ?: 'Meja 1'); ?></div>
                                    </td>
                                    <td style="color:var(--text-mid); font-size:12px;">
                                        <?php echo date('H:i:s', strtotime($r['tanggal_kunjungan'])); ?> WIB
                                    </td>
                                    <td>
                                        <span class="badge-status <?php echo htmlspecialchars($r['status_antrean']); ?>">
                                            <?php echo htmlspecialchars($r['status_antrean']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- end .content -->

    <!-- FOOTER -->
    <div class="page-footer">
        <span>© <?php echo date('Y'); ?> Praktek Mandiri drg. Azwari · Rekapitulasi Antrean Terpadu</span>
        <span>Laporan siap dicetak & diekspor</span>
    </div>

</div>

</body>
</html>

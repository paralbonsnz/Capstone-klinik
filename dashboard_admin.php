<?php
session_start();
include 'koneksi.php';

// Menangani URL jika ada akhiran path agar tidak terkunci (contoh: dashboard_admin.php/index.php)
if (!empty($_SERVER['PATH_INFO'])) {
    $target = trim($_SERVER['PATH_INFO'], '/');
    if (file_exists(__DIR__ . '/' . $target)) {
        header("Location: /Capstone-klinik/" . $target);
        exit;
    }
}

// ============================================================
// PROTEKSI HALAMAN: Hanya bisa diakses jika sudah login
// Session yang digunakan disesuaikan dengan sistem login yang ada.
// Karena login.php saat ini untuk pasien (session id_pasien),
// dan belum ada session admin/dokter terpisah, dashboard ini
// bisa diakses langsung (tanpa login guard) untuk kompatibilitas.
// Jika teman Anda menambahkan session login admin/dokter, 
// cukup uncomment blok di bawah dan sesuaikan nama session-nya.
// ============================================================
// if (!isset($_SESSION['id_admin']) && !isset($_SESSION['id_dokter'])) {
//     header("Location: login.php");
//     exit;
// }

// ============================================================
// QUERY DATA DARI DATABASE (hanya menggunakan tabel & kolom asli)
// Tabel: kunjungan (tanggal_kunjungan, status_antrean)
// Status yang ada di DB: 'Menunggu', 'Diperiksa', 'Selesai'
// ============================================================

// 1. Total kunjungan hari ini
$q_total_hari_ini = mysqli_query($koneksi,
    "SELECT COUNT(*) as total FROM kunjungan 
     WHERE DATE(tanggal_kunjungan) = CURDATE()"
);
$total_hari_ini = ($r = mysqli_fetch_assoc($q_total_hari_ini)) ? (int)$r['total'] : 0;

// 2. Pasien sedang menunggu
$q_menunggu = mysqli_query($koneksi,
    "SELECT COUNT(*) as total FROM kunjungan 
     WHERE status_antrean = 'Menunggu'"
);
$total_menunggu = ($r = mysqli_fetch_assoc($q_menunggu)) ? (int)$r['total'] : 0;

// 3. Pasien sedang diperiksa
$q_diperiksa = mysqli_query($koneksi,
    "SELECT COUNT(*) as total FROM kunjungan 
     WHERE status_antrean = 'Diperiksa'"
);
$total_diperiksa = ($r = mysqli_fetch_assoc($q_diperiksa)) ? (int)$r['total'] : 0;

// 4. Pasien sudah selesai hari ini
$q_selesai = mysqli_query($koneksi,
    "SELECT COUNT(*) as total FROM kunjungan 
     WHERE status_antrean = 'Selesai' 
     AND DATE(tanggal_kunjungan) = CURDATE()"
);
$total_selesai = ($r = mysqli_fetch_assoc($q_selesai)) ? (int)$r['total'] : 0;

// 5. Grafik kunjungan 7 hari terakhir (per hari)
$q_7hari = mysqli_query($koneksi,
    "SELECT DATE(tanggal_kunjungan) as tgl, COUNT(*) as jumlah 
     FROM kunjungan 
     WHERE DATE(tanggal_kunjungan) >= CURDATE() - INTERVAL 6 DAY 
     GROUP BY DATE(tanggal_kunjungan) 
     ORDER BY tgl ASC"
);

// Siapkan array lengkap 7 hari (isi 0 jika tidak ada data)
$hari_labels = [];
$hari_data   = [];
$hari_map    = [];
while ($r = mysqli_fetch_assoc($q_7hari)) {
    $hari_map[$r['tgl']] = (int)$r['jumlah'];
}
for ($i = 6; $i >= 0; $i--) {
    $tgl_key = date('Y-m-d', strtotime("-$i days"));
    $label   = date('d M', strtotime("-$i days"));
    $hari_labels[] = $label;
    $hari_data[]   = isset($hari_map[$tgl_key]) ? $hari_map[$tgl_key] : 0;
}

// 6. Perbandingan minggu ini vs minggu lalu
$q_minggu_ini = mysqli_query($koneksi,
    "SELECT COUNT(*) as total FROM kunjungan 
     WHERE YEARWEEK(tanggal_kunjungan, 1) = YEARWEEK(CURDATE(), 1)"
);
$total_minggu_ini = ($r = mysqli_fetch_assoc($q_minggu_ini)) ? (int)$r['total'] : 0;

$q_minggu_lalu = mysqli_query($koneksi,
    "SELECT COUNT(*) as total FROM kunjungan 
     WHERE YEARWEEK(tanggal_kunjungan, 1) = YEARWEEK(CURDATE() - INTERVAL 7 DAY, 1)"
);
$total_minggu_lalu = ($r = mysqli_fetch_assoc($q_minggu_lalu)) ? (int)$r['total'] : 0;

// Hitung selisih & persentase perbandingan
$selisih_minggu = $total_minggu_ini - $total_minggu_lalu;
if ($total_minggu_lalu > 0) {
    $persen_minggu = round(abs($selisih_minggu) / $total_minggu_lalu * 100, 1);
} else {
    $persen_minggu = $total_minggu_ini > 0 ? 100 : 0;
}

// 7. Daftar antrean aktif (Menunggu) untuk tabel cepat
$q_antrean_aktif = mysqli_query($koneksi,
    "SELECT k.id_kunjungan, p.nama_lengkap, k.keluhan, k.status_antrean, k.tanggal_kunjungan
     FROM kunjungan k 
     JOIN pasien p ON k.id_pasien = p.id_pasien 
     WHERE k.status_antrean IN ('Menunggu','Diperiksa') 
     ORDER BY k.id_kunjungan ASC 
     LIMIT 10"
);

// 8. Total pasien terdaftar di master data
$q_total_pasien = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pasien");
$total_pasien_db = ($r = mysqli_fetch_assoc($q_total_pasien)) ? (int)$r['total'] : 0;

// 9. Pasien dibatalkan hari ini
$q_batal = mysqli_query($koneksi,
    "SELECT COUNT(*) as total FROM kunjungan 
     WHERE status_antrean = 'Batal' 
     AND DATE(tanggal_kunjungan) = CURDATE()"
);
$total_batal = ($r = mysqli_fetch_assoc($q_batal)) ? (int)$r['total'] : 0;

// 10. Top 5 pasien rutin (berdasarkan total kunjungan selesai)
$q_top_pasien = mysqli_query($koneksi,
    "SELECT p.nama_lengkap, COUNT(k.id_kunjungan) as total_kunjungan, 
            MAX(k.tanggal_kunjungan) as terakhir 
     FROM kunjungan k 
     JOIN pasien p ON k.id_pasien = p.id_pasien 
     WHERE k.status_antrean = 'Selesai' 
     GROUP BY k.id_pasien 
     ORDER BY total_kunjungan DESC 
     LIMIT 5"
);

// Encode data grafik untuk JavaScript
$js_labels = json_encode($hari_labels);
$js_data   = json_encode($hari_data);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <base href="/Capstone-klinik/">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Klinik - Praktek Mandiri drg. Azwari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-main:    #f0f4f8;
            --bg-card:    #ffffff;
            --sidebar-w:  240px;
            --blue:       #2563eb;
            --blue-light: #eff6ff;
            --green:      #16a34a;
            --green-light:#f0fdf4;
            --amber:      #d97706;
            --amber-light:#fffbeb;
            --purple:     #7c3aed;
            --purple-light:#f5f3ff;
            --red:        #dc2626;
            --red-light:  #fef2f2;
            --text-dark:  #1e293b;
            --text-mid:   #475569;
            --text-light: #94a3b8;
            --border:     #e2e8f0;
            --radius:     12px;
            --shadow:     0 1px 3px rgba(0,0,0,0.07), 0 4px 16px rgba(0,0,0,0.04);
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
        .topbar-time {
            font-size: 12px; color: var(--text-mid);
            background: var(--bg-main);
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        .topbar-badge {
            width: 36px; height: 36px;
            background: var(--blue-light);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: var(--blue);
            font-size: 14px; font-weight: 700;
        }

        /* ===== CONTENT AREA ===== */
        .content { padding: 28px; flex: 1; }

        /* ===== STAT CARDS ===== */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: flex-start;
            gap: 16px;
            border: 1px solid var(--border);
            transition: transform 0.15s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .stat-icon svg { width: 22px; height: 22px; }
        .stat-icon.blue   { background: var(--blue-light);   color: var(--blue); }
        .stat-icon.green  { background: var(--green-light);  color: var(--green); }
        .stat-icon.amber  { background: var(--amber-light);  color: var(--amber); }
        .stat-icon.purple { background: var(--purple-light); color: var(--purple); }
        .stat-icon.red    { background: var(--red-light);    color: var(--red); }
        .stat-icon.teal   { background: #f0fdfa;             color: #0d9488; }
        .stat-info { flex: 1; }
        .stat-label { font-size: 12px; font-weight: 600; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .stat-value { font-size: 32px; font-weight: 800; color: var(--text-dark); line-height: 1; }
        .stat-desc  { font-size: 11.5px; color: var(--text-light); margin-top: 5px; }

        /* ===== GRID 2 KOLOM ===== */
        .grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px; }

        /* ===== CARD UMUM ===== */
        .card {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 22px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        .card-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 18px;
        }
        .card-title {
            font-size: 14px; font-weight: 700; color: var(--text-dark);
            display: flex; align-items: center; gap: 8px;
        }
        .card-title .dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--blue); flex-shrink: 0;
        }
        .card-badge {
            font-size: 11px; font-weight: 600; padding: 3px 10px;
            border-radius: 20px; background: var(--bg-main); color: var(--text-mid);
        }

        /* ===== CHART ===== */
        .chart-wrapper { position: relative; height: 220px; }

        /* ===== PERBANDINGAN MINGGU ===== */
        .week-compare {
            display: flex; flex-direction: column; gap: 14px;
        }
        .week-row {
            display: flex; flex-direction: column; gap: 6px;
        }
        .week-row-header {
            display: flex; justify-content: space-between; align-items: center;
        }
        .week-label { font-size: 12.5px; font-weight: 600; color: var(--text-mid); }
        .week-num   { font-size: 20px; font-weight: 800; color: var(--text-dark); }
        .week-bar-bg {
            height: 8px; background: var(--bg-main); border-radius: 4px; overflow: hidden;
        }
        .week-bar-fill { height: 100%; border-radius: 4px; transition: width 0.6s ease; }
        .week-bar-fill.this  { background: var(--blue); }
        .week-bar-fill.last  { background: var(--border); }

        .compare-badge {
            margin-top: 10px;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 12.5px; font-weight: 600;
            display: flex; align-items: center; gap: 8px;
        }
        .compare-badge.up   { background: var(--green-light); color: var(--green); }
        .compare-badge.down { background: var(--red-light);   color: var(--red); }
        .compare-badge.same { background: var(--bg-main);     color: var(--text-mid); }

        /* ===== TABEL ANTREAN ===== */
        .antrean-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .antrean-table thead th {
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-light);
            border-bottom: 1px solid var(--border);
        }
        .antrean-table tbody tr { border-bottom: 1px solid var(--border); }
        .antrean-table tbody tr:last-child { border-bottom: none; }
        .antrean-table tbody tr:hover { background: #fafbfc; }
        .antrean-table td { padding: 11px 12px; vertical-align: middle; }
        .antrean-table .nama { font-weight: 600; color: var(--text-dark); }
        .antrean-table .keluhan { font-size: 12px; color: var(--text-mid); margin-top: 2px; }

        /* Status badge di tabel */
        .badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 700;
        }
        .badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
        .badge.menunggu { background: #fef3c7; color: #92400e; }
        .badge.menunggu::before { background: #d97706; }
        .badge.diperiksa { background: #dcfce7; color: #166534; }
        .badge.diperiksa::before { background: #16a34a; animation: pulse 1.5s infinite; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .empty-state {
            text-align: center; padding: 40px 20px;
            color: var(--text-light); font-size: 13px;
        }
        .empty-state .icon { font-size: 36px; margin-bottom: 10px; }

        /* ===== QUICK LINKS ===== */
        .quicklink-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px; margin-bottom: 24px;
        }
        .quicklink-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px;
            text-decoration: none;
            display: flex; flex-direction: column; gap: 8px;
            transition: all 0.15s;
            box-shadow: var(--shadow);
        }
        .quicklink-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
        .quicklink-card .ql-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
        }
        .quicklink-card .ql-title { font-size: 13px; font-weight: 700; color: var(--text-dark); }
        .quicklink-card .ql-desc  { font-size: 11.5px; color: var(--text-light); }

        /* ===== FOOTER ===== */
        .page-footer {
            padding: 16px 28px;
            border-top: 1px solid var(--border);
            font-size: 11.5px; color: var(--text-light);
            display: flex; justify-content: space-between; align-items: center;
            background: var(--bg-card);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            :root { --sidebar-w: 0px; }
            .sidebar { display: none; }
            .main { margin-left: 0; }
            .content { padding: 16px; }
            .topbar { padding: 12px 16px; }
            .stat-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<!-- ==================== SIDEBAR ==================== -->
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
        <a href="dashboard_admin.php" class="nav-item active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
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
            Laporan & Export
        </a>
    </div>

    <div class="sidebar-footer">
        <a href="login.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Kembali ke Login
        </a>
    </div>
</nav>

<!-- ==================== MAIN CONTENT ==================== -->
<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left">
            <h1>Dashboard Klinik</h1>
            <div class="breadcrumb">Pantau statistik kunjungan pasien secara real-time</div>
        </div>
        <div class="topbar-right">
            <div class="topbar-time" id="jamSekarang">
                <?php echo date('d M Y, H:i'); ?> WIB
            </div>
            <div class="topbar-badge">🏥</div>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content">

        <!-- ===== QUICK ACCESS LINKS ===== -->
        <div class="quicklink-grid">
            <a href="index.php" class="quicklink-card">
                <div class="ql-icon" style="background:#eff6ff;">📋</div>
                <div class="ql-title">Loket Pendaftaran</div>
                <div class="ql-desc">Daftarkan pasien baru</div>
            </a>
            <a href="dokter.php" class="quicklink-card">
                <div class="ql-icon" style="background:#f0fdf4;">🩺</div>
                <div class="ql-title">Ruang Dokter</div>
                <div class="ql-desc">Periksa & isi rekam medis</div>
            </a>
            <a href="laporan.php" class="quicklink-card">
                <div class="ql-icon" style="background:#fefce8;">📊</div>
                <div class="ql-title">Laporan Excel</div>
                <div class="ql-desc">Export data kunjungan</div>
            </a>
            <a href="login.php" class="quicklink-card">
                <div class="ql-icon" style="background:#fdf4ff;">👤</div>
                <div class="ql-title">Portal Pasien</div>
                <div class="ql-desc">Login portal mandiri</div>
            </a>
        </div>

        <!-- ===== STAT CARDS ===== -->
        <div class="stat-grid">
            <!-- Total Kunjungan Hari Ini -->
            <div class="stat-card">
                <div class="stat-icon blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Kunjungan Hari Ini</div>
                    <div class="stat-value"><?php echo $total_hari_ini; ?></div>
                    <div class="stat-desc">Total pasien datang hari ini</div>
                </div>
            </div>

            <!-- Menunggu -->
            <div class="stat-card">
                <div class="stat-icon amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Sedang Menunggu</div>
                    <div class="stat-value"><?php echo $total_menunggu; ?></div>
                    <div class="stat-desc">Pasien dalam antrean aktif</div>
                </div>
            </div>

            <!-- Diperiksa -->
            <div class="stat-card">
                <div class="stat-icon green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Sedang Diperiksa</div>
                    <div class="stat-value"><?php echo $total_diperiksa; ?></div>
                    <div class="stat-desc">Pasien di ruang dokter</div>
                </div>
            </div>

            <!-- Selesai Hari Ini -->
            <div class="stat-card">
                <div class="stat-icon purple">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Selesai Hari Ini</div>
                    <div class="stat-value"><?php echo $total_selesai; ?></div>
                    <div class="stat-desc">Pasien selesai berobat hari ini</div>
                </div>
            </div>

            <!-- Dibatalkan Hari Ini -->
            <div class="stat-card">
                <div class="stat-icon red">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Dibatalkan Hari Ini</div>
                    <div class="stat-value"><?php echo $total_batal; ?></div>
                    <div class="stat-desc">Pasien batal berobat hari ini</div>
                </div>
            </div>

            <!-- Total Pasien Terdaftar -->
            <div class="stat-card">
                <div class="stat-icon teal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Total Pasien Terdaftar</div>
                    <div class="stat-value"><?php echo $total_pasien_db; ?></div>
                    <div class="stat-desc">Seluruh pasien di database</div>
                </div>
            </div>
        </div>

        <!-- ===== GRAFIK + PERBANDINGAN MINGGU ===== -->
        <div class="grid-2">

            <!-- Grafik 7 Hari -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span class="dot"></span>
                        Kunjungan 7 Hari Terakhir
                    </div>
                    <span class="card-badge">Per Hari</span>
                </div>
                <div class="chart-wrapper">
                    <canvas id="grafikHarian"></canvas>
                </div>
            </div>

            <!-- Perbandingan Minggu -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span class="dot" style="background:#7c3aed;"></span>
                        Perbandingan Mingguan
                    </div>
                </div>
                <div class="week-compare">

                    <?php
                    // Tentukan bar width relatif
                    $max_w = max($total_minggu_ini, $total_minggu_lalu, 1);
                    $w_ini  = round($total_minggu_ini  / $max_w * 100);
                    $w_lalu = round($total_minggu_lalu / $max_w * 100);
                    ?>

                    <div class="week-row">
                        <div class="week-row-header">
                            <span class="week-label">Minggu Ini</span>
                            <span class="week-num"><?php echo $total_minggu_ini; ?></span>
                        </div>
                        <div class="week-bar-bg">
                            <div class="week-bar-fill this" style="width:<?php echo $w_ini; ?>%;"></div>
                        </div>
                    </div>

                    <div class="week-row">
                        <div class="week-row-header">
                            <span class="week-label">Minggu Lalu</span>
                            <span class="week-num"><?php echo $total_minggu_lalu; ?></span>
                        </div>
                        <div class="week-bar-bg">
                            <div class="week-bar-fill last" style="width:<?php echo $w_lalu; ?>%;"></div>
                        </div>
                    </div>

                    <!-- Kesimpulan perbandingan -->
                    <?php if ($selisih_minggu > 0): ?>
                        <div class="compare-badge up">
                            📈 Naik <?php echo $persen_minggu; ?>% dibanding minggu lalu
                        </div>
                    <?php elseif ($selisih_minggu < 0): ?>
                        <div class="compare-badge down">
                            📉 Turun <?php echo $persen_minggu; ?>% dibanding minggu lalu
                        </div>
                    <?php else: ?>
                        <div class="compare-badge same">
                            ➡️ Sama dengan minggu lalu
                        </div>
                    <?php endif; ?>

                    <?php if ($total_minggu_lalu == 0 && $total_minggu_ini == 0): ?>
                        <p style="font-size:12px; color:var(--text-light); text-align:center; margin-top:8px;">
                            Belum ada data kunjungan minggu ini & lalu.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ===== TABEL ANTREAN AKTIF ===== -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span class="dot" style="background:#d97706;"></span>
                    Antrean Aktif Saat Ini
                </div>
                <a href="dokter.php" style="font-size:12px; color:var(--blue); text-decoration:none; font-weight:600;">
                    Kelola Antrean →
                </a>
            </div>

            <?php if (mysqli_num_rows($q_antrean_aktif) == 0): ?>
                <div class="empty-state">
                    <div class="icon">🎉</div>
                    <div>Tidak ada pasien dalam antrean aktif saat ini.</div>
                </div>
            <?php else: ?>
                <table class="antrean-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Pasien</th>
                            <th>Keluhan</th>
                            <th>Waktu Masuk</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($q_antrean_aktif)): ?>
                            <tr>
                                <td style="color:var(--text-light); font-weight:600;"><?php echo $no++; ?></td>
                                <td>
                                    <div class="nama"><?php echo htmlspecialchars($row['nama_lengkap']); ?></div>
                                </td>
                                <td>
                                    <div class="keluhan"><?php echo htmlspecialchars($row['keluhan']); ?></div>
                                </td>
                                <td style="color:var(--text-mid); font-size:12.5px;">
                                    <?php echo date('d M Y, H:i', strtotime($row['tanggal_kunjungan'])); ?>
                                </td>
                                <td>
                                    <?php if ($row['status_antrean'] == 'Menunggu'): ?>
                                        <span class="badge menunggu">Menunggu</span>
                                    <?php elseif ($row['status_antrean'] == 'Diperiksa'): ?>
                                        <span class="badge diperiksa">Diperiksa</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- ===== TABEL TOP 5 PASIEN RUTIN ===== -->
        <div class="card" style="margin-top:24px;">
            <div class="card-header">
                <div class="card-title">
                    <span class="dot" style="background:#0d9488;"></span>
                    Top 5 Pasien Rutin
                </div>
                <span class="card-badge">Berdasarkan Kunjungan Selesai</span>
            </div>

            <?php if (mysqli_num_rows($q_top_pasien) == 0): ?>
                <div class="empty-state">
                    <div class="icon">📊</div>
                    <div>Belum ada data kunjungan selesai untuk ditampilkan.</div>
                </div>
            <?php else: ?>
                <table class="antrean-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Pasien</th>
                            <th>Total Kunjungan</th>
                            <th>Kunjungan Terakhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no_top = 1; while ($row_top = mysqli_fetch_assoc($q_top_pasien)): ?>
                            <tr>
                                <td style="color:var(--text-light); font-weight:600;"><?php echo $no_top++; ?></td>
                                <td>
                                    <div class="nama"><?php echo htmlspecialchars($row_top['nama_lengkap']); ?></div>
                                </td>
                                <td>
                                    <span style="font-weight:700; color:var(--blue);"><?php echo $row_top['total_kunjungan']; ?>x</span>
                                </td>
                                <td style="color:var(--text-mid); font-size:12.5px;">
                                    <?php echo date('d M Y', strtotime($row_top['terakhir'])); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div><!-- end .content -->

    <!-- FOOTER -->
    <div class="page-footer">
        <span>© <?php echo date('Y'); ?> Praktek Mandiri drg. Azwari · Sistem Informasi Rekam Medis</span>
        <span>Data diperbarui: <?php echo date('d M Y, H:i:s'); ?> WIB</span>
    </div>

</div><!-- end .main -->

<!-- ==================== CHART.JS SCRIPT ==================== -->
<script>
// Data dari PHP
const hariLabels = <?php echo $js_labels; ?>;
const hariData   = <?php echo $js_data; ?>;

// Render Grafik Harian
const ctx = document.getElementById('grafikHarian').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: hariLabels,
        datasets: [{
            label: 'Jumlah Kunjungan',
            data: hariData,
            backgroundColor: hariData.map((_, i) =>
                i === hariData.length - 1
                    ? 'rgba(37, 99, 235, 0.9)'   // Hari ini lebih gelap
                    : 'rgba(37, 99, 235, 0.35)'
            ),
            borderColor: 'rgba(37, 99, 235, 0.8)',
            borderWidth: 1.5,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                titleColor: '#94a3b8',
                bodyColor: '#ffffff',
                titleFont: { size: 11 },
                bodyFont: { size: 14, weight: 'bold' },
                padding: 10,
                callbacks: {
                    label: (ctx) => ` ${ctx.parsed.y} kunjungan`
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { size: 11 }, color: '#94a3b8' }
            },
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false },
                ticks: {
                    font: { size: 11 }, color: '#94a3b8',
                    stepSize: 1,
                    callback: (val) => Number.isInteger(val) ? val : null
                }
            }
        }
    }
});

// Jam real-time
function updateJam() {
    const now  = new Date();
    const opts = { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' };
    document.getElementById('jamSekarang').textContent =
        now.toLocaleDateString('id-ID', opts) + ' WIB';
}
setInterval(updateJam, 60000);
</script>

</body>
</html>

<?php
session_start();
include 'koneksi.php';
include 'helper_antrean.php';

// Menangani URL jika ada akhiran path agar tidak terkunci
if (!empty($_SERVER['PATH_INFO'])) {
    $target = trim($_SERVER['PATH_INFO'], '/');
    if (file_exists(__DIR__ . '/' . $target)) {
        header("Location: /Capstone-klinik/" . $target);
        exit;
    }
}

$today = date('Y-m-d');
$pesan_aksi = "";

// Aksi Pengelolaan Antrean Langsung
if (isset($_GET['aksi'])) {
    $aksi = $_GET['aksi'];
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $now = date('Y-m-d H:i:s');

    if ($id > 0) {
        if ($aksi === 'panggil' || $aksi === 'panggil_ulang') {
            $meja = isset($_GET['meja']) ? mysqli_real_escape_string($koneksi, $_GET['meja']) : 'Ruang Poli Gigi & Mulut - Meja 1';
            mysqli_query($koneksi, "UPDATE kunjungan SET status_antrean = 'Dipanggil', waktu_panggil = '$now', loket_meja = '$meja' WHERE id_kunjungan = $id");
            $pesan_aksi = "Nomor antrean berhasil dipanggil!";
        } elseif ($aksi === 'periksa') {
            mysqli_query($koneksi, "UPDATE kunjungan SET status_antrean = 'Diperiksa' WHERE id_kunjungan = $id");
            $pesan_aksi = "Status berhasil diubah menjadi Sedang Diperiksa.";
        } elseif ($aksi === 'selesai') {
            mysqli_query($koneksi, "UPDATE kunjungan SET status_antrean = 'Selesai' WHERE id_kunjungan = $id");
            $pesan_aksi = "Status antrean berhasil diselesaikan.";
        } elseif ($aksi === 'lewati') {
            // Lewati: masukkan kembali ke status Menunggu dengan meletakkan di urutan setelahnya
            mysqli_query($koneksi, "UPDATE kunjungan SET status_antrean = 'Menunggu', waktu_panggil = NULL WHERE id_kunjungan = $id");
            $pesan_aksi = "Antrean dilewati dan dikembalikan ke antrean Menunggu.";
        } elseif ($aksi === 'batal') {
            mysqli_query($koneksi, "UPDATE kunjungan SET status_antrean = 'Batal' WHERE id_kunjungan = $id");
            $pesan_aksi = "Antrean berhasil dibatalkan.";
        }
    }
}

// Query Statistik Antrean Hari Ini
$q_stat = mysqli_query($koneksi, "SELECT 
    COUNT(*) as total_ambil,
    SUM(CASE WHEN status_antrean = 'Menunggu' THEN 1 ELSE 0 END) as menunggu,
    SUM(CASE WHEN status_antrean = 'Dipanggil' THEN 1 ELSE 0 END) as dipanggil,
    SUM(CASE WHEN status_antrean = 'Diperiksa' THEN 1 ELSE 0 END) as diperiksa,
    SUM(CASE WHEN status_antrean = 'Selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN status_antrean = 'Batal' THEN 1 ELSE 0 END) as batal
    FROM kunjungan WHERE DATE(tanggal_kunjungan) = '$today'");
$stat = mysqli_fetch_assoc($q_stat);

// Query Tabel Pengelolaan Antrean Hari Ini
$q_antrean = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.no_hp, p.status_bpjs, p.gender 
    FROM kunjungan k 
    JOIN pasien p ON k.id_pasien = p.id_pasien 
    WHERE DATE(k.tanggal_kunjungan) = '$today' 
    ORDER BY 
        CASE 
            WHEN k.status_antrean = 'Dipanggil' THEN 1
            WHEN k.status_antrean = 'Diperiksa' THEN 2
            WHEN k.status_antrean = 'Menunggu' THEN 3
            WHEN k.status_antrean = 'Selesai' THEN 4
            ELSE 5 
        END,
        k.id_kunjungan ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <base href="/Capstone-klinik/">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengumuman & Pengelolaan Antrean - Praktek Mandiri drg. Azwari</title>
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
            --teal: #0d9488;
            --teal-light: #f0fdfa;
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
        .topbar-btn-tv {
            background: linear-gradient(135deg, #0284c7, #0ea5e9);
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
            transition: all 0.2s;
        }
        .topbar-btn-tv:hover {
            transform: translateY(-1px);
        }

        /* ===== CONTENT AREA ===== */
        .content { padding: 28px; flex: 1; }

        /* ===== STAT CARDS GRID ===== */
        .stat-grid-6 {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 16px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .stat-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .stat-card-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
        }
        .stat-card-val {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-dark);
            line-height: 1;
        }
        .stat-card-label {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ===== CARD CONTAINER ===== */
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

        /* ===== TABEL PENGELOLAAN ANTREAN ===== */
        .table-responsive { width: 100%; overflow-x: auto; }
        .queue-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .queue-table thead th {
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
        .queue-table tbody tr { border-bottom: 1px solid var(--border); transition: background 0.1s; }
        .queue-table tbody tr:hover { background: #f8fafc; }
        .queue-table td { padding: 12px 14px; vertical-align: middle; }

        .nomor-badge {
            font-size: 16px;
            font-weight: 800;
            color: #0284c7;
            background: #e0f2fe;
            padding: 4px 10px;
            border-radius: 8px;
            display: inline-block;
        }

        /* Status Badge */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 700;
        }
        .badge-status.menunggu { background: #fef3c7; color: #92400e; }
        .badge-status.dipanggil { background: #e0f2fe; color: #0369a1; animation: pulse 1.5s infinite; }
        .badge-status.diperiksa { background: #dcfce7; color: #166534; }
        .badge-status.selesai { background: #f1f5f9; color: #475569; }
        .badge-status.batal { background: #fee2e2; color: #991b1b; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Action Buttons */
        .btn-action-group {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .btn-action {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-call { background: #0284c7; color: white; }
        .btn-call:hover { background: #0369a1; }
        .btn-recall { background: #f59e0b; color: white; }
        .btn-recall:hover { background: #d97706; }
        .btn-exam { background: #10b981; color: white; }
        .btn-exam:hover { background: #059669; }
        .btn-done { background: #475569; color: white; }
        .btn-done:hover { background: #334155; }
        .btn-skip { background: #e2e8f0; color: #475569; }
        .btn-skip:hover { background: #cbd5e1; }
        .btn-cancel { background: #fee2e2; color: #dc2626; }
        .btn-cancel:hover { background: #fecaca; }

        .alert-success {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ===== FOOTER ===== */
        .page-footer {
            padding: 16px 28px;
            border-top: 1px solid var(--border);
            font-size: 11.5px; color: var(--text-light);
            display: flex; justify-content: space-between; align-items: center;
            background: var(--bg-card);
        }

        @media (max-width: 1200px) {
            .stat-grid-6 { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .stat-grid-6 { grid-template-columns: repeat(2, 1fr); }
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
        <a href="pengumuman_antrean.php" class="nav-item active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            Pengumuman Antrean
        </a>
        <a href="monitor_antrean.php" target="_blank" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            Monitor Antrean TV ↗
        </a>
        <a href="rekapitulasi_antrean.php" class="nav-item">
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
            <h1>Pengumuman & Pengelolaan Antrean</h1>
            <div class="breadcrumb">Panggil nomor antrean dan perbarui status pelayanan secara real-time</div>
        </div>
        <div class="topbar-right">
            <a href="monitor_antrean.php" target="_blank" class="topbar-btn-tv">
                📺 Buka Layar Monitor TV
            </a>
            <div style="font-size: 13px; font-weight: 600; color: var(--text-mid); background: var(--bg-main); padding: 8px 14px; border-radius: 20px;">
                🕒 <span id="liveTime"><?php echo date('d M Y, H:i'); ?> WIB</span>
            </div>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content">

        <?php if (!empty($pesan_aksi)): ?>
            <div class="alert-success">
                ✅ <?php echo htmlspecialchars($pesan_aksi); ?>
            </div>
        <?php endif; ?>

        <!-- A. INFORMASI ANTREAN HARI INI (6 STAT CARDS) -->
        <div class="stat-grid-6">
            <!-- 1. Total Pasien Ambil Nomor -->
            <div class="stat-card">
                <div class="stat-card-head">
                    <span class="stat-card-label">Total Antrean</span>
                    <div class="stat-card-icon" style="background: #eff6ff; color: #2563eb;">🎫</div>
                </div>
                <div class="stat-card-val" id="statTotal"><?php echo (int)$stat['total_ambil']; ?></div>
                <div style="font-size: 11px; color: var(--text-light);">Pasien terdaftar hari ini</div>
            </div>

            <!-- 2. Menunggu -->
            <div class="stat-card">
                <div class="stat-card-head">
                    <span class="stat-card-label">Menunggu</span>
                    <div class="stat-card-icon" style="background: #fffbeb; color: #d97706;">⏳</div>
                </div>
                <div class="stat-card-val" id="statMenunggu" style="color: #d97706;"><?php echo (int)$stat['menunggu']; ?></div>
                <div style="font-size: 11px; color: var(--text-light);">Antrean di ruang tunggu</div>
            </div>

            <!-- 3. Sedang Dipanggil -->
            <div class="stat-card">
                <div class="stat-card-head">
                    <span class="stat-card-label">Dipanggil</span>
                    <div class="stat-card-icon" style="background: #e0f2fe; color: #0284c7;">📢</div>
                </div>
                <div class="stat-card-val" id="statDipanggil" style="color: #0284c7;"><?php echo (int)$stat['dipanggil']; ?></div>
                <div style="font-size: 11px; color: var(--text-light);">Tampil di monitor TV</div>
            </div>

            <!-- 4. Sedang Diperiksa -->
            <div class="stat-card">
                <div class="stat-card-head">
                    <span class="stat-card-label">Diperiksa</span>
                    <div class="stat-card-icon" style="background: #f0fdf4; color: #16a34a;">🩺</div>
                </div>
                <div class="stat-card-val" id="statDiperiksa" style="color: #16a34a;"><?php echo (int)$stat['diperiksa']; ?></div>
                <div style="font-size: 11px; color: var(--text-light);">Di ruang dokter/meja</div>
            </div>

            <!-- 5. Selesai Dilayani -->
            <div class="stat-card">
                <div class="stat-card-head">
                    <span class="stat-card-label">Selesai</span>
                    <div class="stat-card-icon" style="background: #f5f3ff; color: #7c3aed;">✅</div>
                </div>
                <div class="stat-card-val" id="statSelesai" style="color: #7c3aed;"><?php echo (int)$stat['selesai']; ?></div>
                <div style="font-size: 11px; color: var(--text-light);">Pelayanan tuntas</div>
            </div>

            <!-- 6. Dibatalkan -->
            <div class="stat-card">
                <div class="stat-card-head">
                    <span class="stat-card-label">Dibatalkan</span>
                    <div class="stat-card-icon" style="background: #fef2f2; color: #dc2626;">✕</div>
                </div>
                <div class="stat-card-val" id="statBatal" style="color: #dc2626;"><?php echo (int)$stat['batal']; ?></div>
                <div style="font-size: 11px; color: var(--text-light);">Pasien batal/terlewat</div>
            </div>
        </div>

        <!-- B. TABEL PENGELOLAAN ANTREAN HARI INI -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span class="dot"></span>
                    Daftar Antrean & Kontrol Panggilan Hari Ini
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="font-size: 12px; color: var(--text-light);">Update otomatis setiap 3 detik</span>
                    <button onclick="location.reload()" style="background:none; border:1px solid var(--border); padding:5px 10px; border-radius:6px; font-size:12px; cursor:pointer; font-weight:600;">🔄 Refresh</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="queue-table">
                    <thead>
                        <tr>
                            <th>No. Antrean</th>
                            <th>Nama Pasien</th>
                            <th>Waktu Daftar</th>
                            <th>Poli & Ruang Tujuan</th>
                            <th>Status Antrean</th>
                            <th style="text-align: right;">Tindakan / Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="queueTableBody">
                        <?php if (mysqli_num_rows($q_antrean) == 0): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-light); padding: 40px 0;">
                                    Belum ada antrean yang terdaftar hari ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($row = mysqli_fetch_assoc($q_antrean)): 
                                $status_cls = strtolower($row['status_antrean']);
                                $id_k = $row['id_kunjungan'];
                            ?>
                                <tr>
                                    <td>
                                        <span class="nomor-badge"><?php echo htmlspecialchars($row['nomor_antrean'] ?: 'A-000'); ?></span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-dark);"><?php echo htmlspecialchars($row['nama_lengkap']); ?></div>
                                        <div style="font-size: 11.5px; color: var(--text-light);"><?php echo htmlspecialchars($row['gender']); ?> • <?php echo htmlspecialchars($row['status_bpjs'] ?: 'Umum'); ?></div>
                                    </td>
                                    <td style="color: var(--text-mid); font-size: 12.5px;">
                                        <?php echo date('H:i', strtotime($row['tanggal_kunjungan'])); ?> WIB
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($row['poli_tujuan'] ?: 'Poli Umum'); ?></div>
                                        <div style="font-size: 11.5px; color: var(--text-light);"><?php echo htmlspecialchars($row['loket_meja'] ?: 'Ruang Pemeriksaan'); ?></div>
                                    </td>
                                    <td>
                                        <?php if ($row['status_antrean'] === 'Dipanggil'): ?>
                                            <span class="badge-status dipanggil">📢 DIPANGGIL</span>
                                        <?php elseif ($row['status_antrean'] === 'Diperiksa'): ?>
                                            <span class="badge-status diperiksa">🩺 SEDANG DIPERIKSA</span>
                                        <?php elseif ($row['status_antrean'] === 'Menunggu'): ?>
                                            <span class="badge-status menunggu">⏳ MENUNGGU</span>
                                        <?php elseif ($row['status_antrean'] === 'Selesai'): ?>
                                            <span class="badge-status selesai">✅ SELESAI</span>
                                        <?php else: ?>
                                            <span class="badge-status batal">✕ DIBATALKAN</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="btn-action-group" style="justify-content: flex-end;">
                                            <?php if ($row['status_antrean'] === 'Menunggu'): ?>
                                                <button onclick="actionUpdate(<?php echo $id_k; ?>, 'Dipanggil')" class="btn-action btn-call">📢 Panggil</button>
                                                <button onclick="actionUpdate(<?php echo $id_k; ?>, 'Batal')" class="btn-action btn-cancel">✕ Batal</button>
                                            <?php elseif ($row['status_antrean'] === 'Dipanggil'): ?>
                                                <button onclick="actionUpdate(<?php echo $id_k; ?>, 'Dipanggil')" class="btn-action btn-recall">🔁 Panggil Ulang</button>
                                                <button onclick="actionUpdate(<?php echo $id_k; ?>, 'Diperiksa')" class="btn-action btn-exam">🩺 Masuk Periksa</button>
                                                <button onclick="actionUpdate(<?php echo $id_k; ?>, 'Menunggu')" class="btn-action btn-skip">⏩ Lewati</button>
                                                <button onclick="actionUpdate(<?php echo $id_k; ?>, 'Batal')" class="btn-action btn-cancel">✕ Batal</button>
                                            <?php elseif ($row['status_antrean'] === 'Diperiksa'): ?>
                                                <button onclick="actionUpdate(<?php echo $id_k; ?>, 'Selesai')" class="btn-action btn-done">✅ Tandai Selesai</button>
                                                <a href="dokter.php" class="btn-action btn-exam" style="background:#0ea5e9;">📝 Rekam Medis</a>
                                            <?php elseif ($row['status_antrean'] === 'Selesai'): ?>
                                                <span style="font-size: 11.5px; color: var(--text-light); font-weight:600;">Selesai Pelayanan</span>
                                            <?php else: ?>
                                                <button onclick="actionUpdate(<?php echo $id_k; ?>, 'Menunggu')" class="btn-action btn-skip">Kembalikan ke Antrean</button>
                                            <?php endif; ?>
                                        </div>
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
        <span>© <?php echo date('Y'); ?> Praktek Mandiri drg. Azwari · Sistem Pemanggilan Antrean Real-Time</span>
        <span>Terhubung Database MySQL: rekam_medis</span>
    </div>

</div>

<script>
// Jam Realtime
function updateTime() {
    const now = new Date();
    const opts = { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
    document.getElementById('liveTime').textContent = now.toLocaleDateString('id-ID', opts) + ' WIB';
}
setInterval(updateTime, 1000);

// Aksi Update Status AJAX tanpa reload penuh
async function actionUpdate(idKunjungan, statusTarget) {
    try {
        const formData = new FormData();
        formData.append('aksi', 'update_status');
        formData.append('id_kunjungan', idKunjungan);
        formData.append('status', statusTarget);
        formData.append('loket_meja', 'Ruang Poli Gigi & Mulut - Meja 1');

        const res = await fetch('api_antrean.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.status === 'success') {
            // Reload halaman untuk memperbarui data
            window.location.reload();
        } else {
            alert('Gagal: ' + data.message);
        }
    } catch (err) {
        console.error('Error aksi:', err);
    }
}
</script>

</body>
</html>

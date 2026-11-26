<?php
include 'koneksi.php';
include 'helper_antrean.php';

$pesan = "";

// 🔍 AMBIL KATA KUNCI PENCARIAN DARI ADMIN
$search_keyword = "";
if (isset($_GET['search'])) {
    $search_keyword = mysqli_real_escape_string($koneksi, $_GET['search']);
}

// 🟢 AKSI 1: PENDAFTARAN PASIEN BARU OLEH ADMIN (FORM INPUT MANUAL LOKET / CHAT)
if (isset($_POST['tambah_pasien_baru'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $tgl_lahir = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']);
    $gender = mysqli_real_escape_string($koneksi, $_POST['gender']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $keluhan = mysqli_real_escape_string($koneksi, $_POST['keluhan']);
    $riwayat = mysqli_real_escape_string($koneksi, $_POST['riwayat_penyakit']);

    // 1. Cek duplikasi agar tidak terdaftar dua kali di data master
    $cek_pasien = mysqli_query($koneksi, "SELECT id_pasien FROM pasien WHERE nama_lengkap = '$nama' AND tanggal_lahir = '$tgl_lahir'");
    
    if (mysqli_num_rows($cek_pasien) > 0) {
        $data_p = mysqli_fetch_assoc($cek_pasien);
        $id_pasien_fix = $data_p['id_pasien'];
        $pesan_status = "Pasien lama terdeteksi otomatis. ";
    } else {
        // Masukkan data master pasien baru
        // INSERT hanya ke kolom yang ada di tabel pasien (no_rm & password tidak ada di DB saat ini)
        $query_pasien = "INSERT INTO pasien (nama_lengkap, tanggal_lahir, gender, alamat, no_hp, riwayat_penyakit) VALUES ('$nama', '$tgl_lahir', '$gender', '$alamat', '', '$riwayat')";
        mysqli_query($koneksi, $query_pasien);
        $id_pasien_fix = mysqli_insert_id($koneksi);
        $pesan_status = "Pendaftaran pasien baru sukses. ";
    }

    // 2. Masukkan ke antrean kunjungan hari ini
    $tanggal_hari_ini = date('Y-m-d');
    $cek_antrean = mysqli_query($koneksi, "SELECT id_kunjungan FROM kunjungan WHERE id_pasien = '$id_pasien_fix' AND tanggal_kunjungan = '$tanggal_hari_ini' AND status_antrean NOT IN ('Selesai','Batal')");
    
    if (mysqli_num_rows($cek_antrean) == 0) {
        $nomor_baru = generate_nomor_antrean($koneksi, 'Poli Umum', $tanggal_hari_ini);
        $query_antrean = "INSERT INTO kunjungan (id_pasien, nomor_antrean, poli_tujuan, loket_meja, tanggal_kunjungan, keluhan, status_antrean) VALUES ('$id_pasien_fix', '$nomor_baru', 'Poli Umum', 'Ruang Poli Umum - Meja 1', '$tanggal_hari_ini', '$keluhan', 'Menunggu')";
        if (mysqli_query($koneksi, $query_antrean)) {
            $pesan = $pesan_status . "Pasien berhasil dimasukkan ke antrean dengan No: <strong>$nomor_baru</strong>!";
        }
    } else {
        $pesan = "⚠️ Pasien sudah terdaftar di antrean hari ini!";
    }
}

// 🔵 AKSI 2: MASUKKAN PASIEN LAMA LANGSUNG KE ANTREAN (DARI HASIL PENCARIAN BUKU REGISTER)
if (isset($_POST['antreskan_pasien_lama'])) {
    $id_pasien_lama = mysqli_real_escape_string($koneksi, $_POST['id_pasien_lama']);
    $keluhan_lama = mysqli_real_escape_string($koneksi, $_POST['keluhan_lama']);
    $tanggal_hari_ini = date('Y-m-d');

    $cek_antrean = mysqli_query($koneksi, "SELECT id_kunjungan FROM kunjungan WHERE id_pasien = '$id_pasien_lama' AND tanggal_kunjungan = '$tanggal_hari_ini' AND status_antrean NOT IN ('Selesai','Batal')");

    if (mysqli_num_rows($cek_antrean) == 0) {
        $nomor_baru = generate_nomor_antrean($koneksi, 'Poli Umum', $tanggal_hari_ini);
        $query_antrean = "INSERT INTO kunjungan (id_pasien, nomor_antrean, poli_tujuan, loket_meja, tanggal_kunjungan, keluhan, status_antrean) VALUES ('$id_pasien_lama', '$nomor_baru', 'Poli Umum', 'Ruang Poli Umum - Meja 1', '$tanggal_hari_ini', '$keluhan_lama', 'Menunggu')";
        if (mysqli_query($koneksi, $query_antrean)) {
            $pesan = "Pasien lama berhasil dimasukkan kembali ke antrean hari ini dengan No: <strong>$nomor_baru</strong>!";
        }
    } else {
        $pesan = "⚠️ Pasien lama ini sudah berada di antrean hari ini!";
    }
}

// 📊 QUERY 1: TAMPILKAN DAFTAR ANTREAN HARI INI (Agar Admin bisa memantau kepadatan klinik)
$daftar_antrean = mysqli_query($koneksi, "SELECT k.id_kunjungan, k.nomor_antrean, k.poli_tujuan, p.nama_lengkap, k.status_antrean, k.keluhan FROM kunjungan k JOIN pasien p ON k.id_pasien = p.id_pasien WHERE DATE(k.tanggal_kunjungan) = CURDATE() ORDER BY k.id_kunjungan ASC");

// 👥 QUERY 2: BUKU REGISTRASI BESAR (Master Data Pasien + Search)
if (!empty($search_keyword)) {
    $query_master = "SELECT * FROM pasien WHERE nama_lengkap LIKE '%$search_keyword%' ORDER BY nama_lengkap ASC";
} else {
    $query_master = "SELECT * FROM pasien ORDER BY id_pasien DESC LIMIT 5"; // Default tampilkan 5 pasien pendaftar terbaru jika kosong
}
$master_pasien = mysqli_query($koneksi, $query_master);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Loket Pendaftaran Admin - Praktek Mandiri drg. Azwari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            margin: 20px; 
            background-color: #f1f5f9; 
            display: flex; 
            gap: 20px; 
            color: #0f172a;
        }

        /* KOLOM KIRI (UTAMA/FULL): LIVE STATUS ANTREAN */
        .container-left { 
            flex: 1.4; 
            display: flex; 
            flex-direction: column; 
            gap: 16px; 
        }

        /* KOLOM KANAN: PENDAFTARAN & BUKU REGISTRASI */
        .container-right { 
            flex: 1.6; 
            display: flex; 
            flex-direction: column; 
            gap: 16px; 
        }

        .box { 
            background: white; 
            padding: 20px; 
            border-radius: 14px; 
            box-shadow: 0 4px 16px rgba(0,0,0,0.04); 
            border: 1px solid #e2e8f0; 
        }

        .box-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
        }

        .box-title {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group { margin-bottom: 10px; }
        .form-group label { display: block; font-weight: 700; margin-bottom: 4px; font-size: 12px; color: #475569; }
        .form-group input, .form-group textarea, .form-group select { 
            width: 100%; 
            padding: 8px 10px; 
            box-sizing: border-box; 
            border: 1px solid #cbd5e1; 
            border-radius: 8px; 
            font-size: 13px;
            font-family: inherit;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: #0284c7;
            box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.15);
        }

        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
        table, th, td { border: 1px solid #f1f5f9; }
        th, td { padding: 9px 12px; text-align: left; vertical-align: middle; }
        th { background-color: #f8fafc; color: #475569; font-weight: 700; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px; }
        tbody tr:hover { background-color: #f8fafc; }

        .btn-submit { 
            background: #0284c7; 
            color: white; 
            padding: 10px; 
            border: none; 
            width: 100%; 
            border-radius: 8px; 
            font-weight: 800; 
            font-size: 13px; 
            cursor: pointer; 
            transition: all 0.15s;
        }
        .btn-submit:hover { background: #0369a1; }

        .btn-antre { 
            background: #10b981; 
            color: white; 
            border: none; 
            padding: 6px 10px; 
            border-radius: 6px; 
            font-weight: 700; 
            cursor: pointer; 
            font-size: 11px; 
            white-space: nowrap;
        }
        .btn-antre:hover { background: #059669; }

        .alert { 
            padding: 12px 16px; 
            background: #e0f2fe; 
            color: #0369a1; 
            border-left: 4px solid #0284c7; 
            border-radius: 8px; 
            font-weight: 700; 
            font-size: 13px;
        }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 800; display: inline-block; }
        .badge-tunggu { background-color: #fef3c7; color: #92400e; }
        .badge-dipanggil { background-color: #e0f2fe; color: #0369a1; animation: pulse 1.5s infinite; }
        .badge-periksa { background-color: #dcfce7; color: #166534; }
        .badge-selesai { background-color: #f1f5f9; color: #64748b; }
        .badge-batal { background-color: #fee2e2; color: #991b1b; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .nomor-pill {
            font-size: 13px;
            font-weight: 800;
            color: #0284c7;
            background: #e0f2fe;
            padding: 3px 8px;
            border-radius: 6px;
            display: inline-block;
        }

        .top-nav-bar {
            grid-column: 1 / -1;
            width: 100%;
            background: white;
            padding: 12px 20px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
            margin-bottom: -4px;
        }

        @media (max-width: 1024px) {
            body { flex-direction: column; }
            .container-left, .container-right { width: 100%; flex: 1; }
        }
    </style>
</head>
<body>

    <!-- TOP HEADER NAVIGASI -->
    <div class="top-nav-bar" style="width:100%; position:fixed; top:0; left:0; right:0; z-index:99; border-radius:0; margin:0; padding:12px 24px;">
        <div style="display:flex; align-items:center; gap:12px;">
            <span style="font-size:22px;">📋</span>
            <div>
                <strong style="font-size:15px; color:#0f172a;">Loket Pendaftaran Pasien</strong>
                <div style="font-size:11px; color:#64748b;">Praktek Mandiri drg. Azwari • Sistem Antrean Terpadu</div>
            </div>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <a href="dashboard_admin.php" style="background:#f1f5f9; color:#334155; text-decoration:none; padding:6px 12px; border-radius:6px; font-size:12px; font-weight:700;">🏠 Dashboard Admin</a>
            <a href="pengumuman_antrean.php" style="background:#e0f2fe; color:#0284c7; text-decoration:none; padding:6px 12px; border-radius:6px; font-size:12px; font-weight:700;">📢 Pengumuman Antrean</a>
            <a href="monitor_antrean.php" target="_blank" style="background:#0284c7; color:white; text-decoration:none; padding:6px 14px; border-radius:6px; font-size:12px; font-weight:700;">📺 Monitor TV ↗</a>
        </div>
    </div>

    <!-- WRAPPER BODY BIAR TIDAK KETUTUP TOPBAR FIXED -->
    <div style="width:100%; margin-top:55px; display:flex; gap:20px;">

        <!-- ======================================================== -->
        <!-- POSISI KIRI (FULL & JELAS): LIVE STATUS ANTREAN HARI INI -->
        <!-- ======================================================== -->
        <div class="container-left">
            
            <?php if ($pesan != ""): ?>
                <div class="alert"><?php echo $pesan; ?></div>
            <?php endif; ?>

            <div class="box" style="border-top: 4px solid #0284c7; height: 100%;">
                <div class="box-header">
                    <div>
                        <div class="box-title">
                            <span>📊</span> Live Status Antrean Hari Ini
                        </div>
                        <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
                            Pantauan langsung nomor antrean yang sedang aktif dan berjalan di klinik
                        </div>
                    </div>
                    <a href="index.php" style="font-size: 11px; font-weight: 700; color: #0284c7; text-decoration: none; background: #e0f2fe; padding: 4px 10px; border-radius: 6px;">🔄 Segarkan</a>
                </div>

                <?php if(mysqli_num_rows($daftar_antrean) == 0): ?>
                    <div style="text-align: center; color: #94a3b8; padding: 50px 20px;">
                        <div style="font-size: 36px; margin-bottom: 8px;">🎉</div>
                        <strong>Belum ada antrean berjalan hari ini.</strong>
                        <div style="font-size: 12px; margin-top: 4px;">Daftarkan pasien melalui form di sebelah kanan.</div>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No. Antrean</th>
                                <th>Nama Pasien</th>
                                <th>Keluhan / Layanan</th>
                                <th>Poli Tujuan</th>
                                <th>Status Antrean</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no_a = 1;
                            while($antre = mysqli_fetch_assoc($daftar_antrean)): 
                                $status = $antre['status_antrean'];
                            ?>
                                <tr>
                                    <td style="color:#94a3b8; font-weight:700;"><?php echo $no_a++; ?></td>
                                    <td>
                                        <span class="nomor-pill"><?php echo htmlspecialchars($antre['nomor_antrean'] ?: 'A-000'); ?></span>
                                    </td>
                                    <td>
                                        <strong style="color:#0f172a; font-size:13px;"><?php echo htmlspecialchars($antre['nama_lengkap']); ?></strong>
                                    </td>
                                    <td>
                                        <span style="color:#475569; font-size:12px;"><?php echo htmlspecialchars($antre['keluhan']); ?></span>
                                    </td>
                                    <td>
                                        <span style="font-size:11.5px; color:#0284c7; font-weight:600;"><?php echo htmlspecialchars($antre['poli_tujuan'] ?: 'Poli Umum'); ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        if($status == 'Menunggu') {
                                            echo '<span class="badge badge-tunggu">⏳ Menunggu</span>';
                                        } elseif($status == 'Dipanggil') {
                                            echo '<span class="badge badge-dipanggil">📢 Dipanggil</span>';
                                        } elseif($status == 'Diperiksa') {
                                            echo '<span class="badge badge-periksa">🩺 Diperiksa</span>';
                                        } elseif($status == 'Selesai') {
                                            echo '<span class="badge badge-selesai">✅ Selesai</span>';
                                        } else {
                                            echo '<span class="badge badge-batal">✕ Batal</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- POSISI KANAN (KOMPAK): FORM REGIS PASIEN BARU & LAMA     -->
        <!-- ======================================================== -->
        <div class="container-right">

            <!-- BOX 1: PENDAFTARAN PASIEN BARU (KOMPAK) -->
            <div class="box" style="border-top: 4px solid #10b981;">
                <div class="box-header">
                    <div class="box-title">
                        <span>➕</span> Pendaftaran Pasien Baru
                    </div>
                    <span style="font-size:11px; background:#dcfce7; color:#166534; font-weight:700; padding:2px 8px; border-radius:12px;">Form Cepat</span>
                </div>
                
                <form action="index.php" method="POST">
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Nama Lengkap:</label>
                            <input type="text" name="nama_lengkap" placeholder="Sesuai KTP" required>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Lahir:</label>
                            <input type="date" name="tanggal_lahir" required>
                        </div>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Jenis Kelamin:</label>
                            <select name="gender" required>
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Alamat Domisili:</label>
                            <input type="text" name="alamat" placeholder="Jalan, Kota" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Keluhan Utama Hari Ini:</label>
                        <input type="text" name="keluhan" placeholder="Contoh: Sakit gigi geraham bawah" required>
                    </div>

                    <div class="form-group">
                        <label>Riwayat Alergi / Penyakit (Opsional):</label>
                        <input type="text" name="riwayat_penyakit" placeholder="Contoh: Alergi Amoxicillin" value="-">
                    </div>

                    <button type="submit" name="tambah_pasien_baru" class="btn-submit" style="background:#10b981;">
                        💾 Daftarkan & Ambil No. Antrean
                    </button>
                </form>
            </div>

            <!-- BOX 2: BUKU REGISTRASI PASIEN LAMA (KOMPAK) -->
            <div class="box" style="border-top: 4px solid #f59e0b;">
                <div class="box-header">
                    <div class="box-title">
                        <span>🔍</span> Buku Registrasi & Pasien Lama
                    </div>
                </div>
                
                <form action="index.php" method="GET" style="display:flex; gap:6px; margin-bottom:12px;">
                    <input type="text" name="search" placeholder="Ketik nama pasien lama..." value="<?php echo htmlspecialchars($search_keyword); ?>" style="flex:1; padding:7px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                    <button type="submit" style="background:#475569; color:white; border:none; padding:7px 14px; border-radius:6px; cursor:pointer; font-weight:700; font-size:12px;">Cari</button>
                    <?php if(!empty($search_keyword)): ?>
                        <a href="index.php" style="background:#e2e8f0; color:#334155; padding:7px 10px; text-decoration:none; border-radius:6px; font-size:12px; font-weight:600;">Reset</a>
                    <?php endif; ?>
                </form>

                <div style="max-height: 280px; overflow-y: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Biodata Pasien</th>
                                <th style="text-align:right;">Tambah Antrean</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($master_pasien) == 0): ?>
                                <tr>
                                    <td colspan="2" style="text-align:center; color:#94a3b8; padding:16px;">Pasien tidak ditemukan.</td>
                                </tr>
                            <?php else: ?>
                                <?php while($p = mysqli_fetch_assoc($master_pasien)): ?>
                                    <tr>
                                        <td>
                                            <strong style="color:#0f172a;"><?php echo htmlspecialchars($p['nama_lengkap']); ?></strong><br>
                                            <small style="color:#64748b; font-size:11px;"><?php echo date('d M Y', strtotime($p['tanggal_lahir'])); ?> • <?php echo htmlspecialchars($p['alamat']); ?></small>
                                        </td>
                                        <td style="text-align:right;">
                                            <form action="index.php" method="POST" style="display:inline-flex; gap:4px; align-items:center;">
                                                <input type="hidden" name="id_pasien_lama" value="<?php echo $p['id_pasien']; ?>">
                                                <input type="text" name="keluhan_lama" placeholder="Keluhan..." required style="padding:4px 6px; font-size:11px; width:100px; border:1px solid #cbd5e1; border-radius:4px;">
                                                <button type="submit" name="antreskan_pasien_lama" class="btn-antre">+ Antre</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

</body>
</html>

</body>
</html>
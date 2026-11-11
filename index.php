<?php
include 'koneksi.php';

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
        $query_pasien = "INSERT INTO pasien (nama_lengkap, tanggal_lahir, gender, alamat, riwayat_penyakit) VALUES ('$nama', '$tgl_lahir', '$gender', '$alamat', '$riwayat')";
        mysqli_query($koneksi, $query_pasien);
        $id_pasien_fix = mysqli_insert_id($koneksi);
        $pesan_status = "Pendaftaran pasien baru sukses. ";
    }

    // 2. Masukkan ke antrean kunjungan hari ini
    $tanggal_hari_ini = date('Y-m-d');
    $cek_antrean = mysqli_query($koneksi, "SELECT id_kunjungan FROM kunjungan WHERE id_pasien = '$id_pasien_fix' AND tanggal_kunjungan = '$tanggal_hari_ini' AND status_antrean != 'Selesai'");
    
    if (mysqli_num_rows($cek_antrean) == 0) {
        $query_antrean = "INSERT INTO kunjungan (id_pasien, tanggal_kunjungan, keluhan, status_antrean) VALUES ('$id_pasien_fix', '$tanggal_hari_ini', '$keluhan', 'Menunggu')";
        if (mysqli_query($koneksi, $query_antrean)) {
            $pesan = $pesan_status . "Pasien berhasil dimasukkan ke antrean dokter!";
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

    $cek_antrean = mysqli_query($koneksi, "SELECT id_kunjungan FROM kunjungan WHERE id_pasien = '$id_pasien_lama' AND tanggal_kunjungan = '$tanggal_hari_ini' AND status_antrean != 'Selesai'");

    if (mysqli_num_rows($cek_antrean) == 0) {
        $query_antrean = "INSERT INTO kunjungan (id_pasien, tanggal_kunjungan, keluhan, status_antrean) VALUES ('$id_pasien_lama', '$tanggal_hari_ini', '$keluhan_lama', 'Menunggu')";
        if (mysqli_query($koneksi, $query_antrean)) {
            $pesan = "Pasien lama berhasil dimasukkan kembali ke antrean hari ini!";
        }
    } else {
        $pesan = "⚠️ Pasien lama ini sudah berada di antrean hari ini!";
    }
}

// 📊 QUERY 1: TAMPILKAN DAFTAR ANTREAN HARI INI (Agar Admin bisa memantau kepadatan klinik)
$daftar_antrean = mysqli_query($koneksi, "SELECT k.id_kunjungan, p.nama_lengkap, k.status_antrean, k.keluhan FROM kunjungan k JOIN pasien p ON k.id_pasien = p.id_pasien WHERE k.tanggal_kunjungan = CURDATE() ORDER BY k.id_kunjungan ASC");

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
    <title>Loket Pendaftaran Admin - EHR Klinik</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 25px; background-color: #f7fafc; display: flex; gap: 20px; }
        .container-main { flex: 2; display: flex; flex-direction: column; gap: 20px; }
        .sidebar-right { flex: 1; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.05); height: fit-content; }
        .box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 13px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
        table, th, td { border: 1px solid #edf2f7; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #edf2f7; color: #4a5568; }
        .btn-submit { background: #007BFF; color: white; padding: 10px; border: none; width: 100%; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .btn-antre { background: #28a745; color: white; border: none; padding: 5px 8px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 11px; }
        .alert { padding: 12px; background: #ebf8ff; color: #2b6cb0; border-left: 4px solid #3182ce; border-radius: 4px; margin-bottom: 15px; font-weight: bold; }
        .badge { padding: 3px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; color: white; }
        .badge-tunggu { background-color: #ffc107; color: #333; }
        .badge-periksa { background-color: #28a745; }
        .badge-selesai { background-color: #6c757d; }
    </style>
</head>
<body>

    <!-- KOLOM KIRI UTAMA: BUKU REGISTRASI BESAR & PENCARIAN -->
    <div class="container-main">
        
        <?php if ($pesan != ""): ?>
            <div class="alert"><?php echo $pesan; ?></div>
        <?php endif; ?>

        <!-- BOX BUKU REGISTRASI PASIEN LAMA -->
        <div class="box" style="border-top: 4px solid #4299e1;">
            <h2 style="margin-top:0;">🔍 Buku Registrasi & Pencarian Pasien Lama</h2>
            <p style="color:#718096; font-size:13px; margin-top:-5px;">Cari nama pasien lama di database untuk langsung dimasukkan ke antrean tanpa input ulang data diri.</p>
            
            <form action="index.php" method="GET" style="display:flex; gap:8px; margin-bottom:15px;">
                <input type="text" name="search" placeholder="Masukkan Nama Pasien..." value="<?php echo htmlspecialchars($search_keyword); ?>" style="flex:1; padding:8px; border:1px solid #ccc; border-radius:4px;">
                <button type="submit" style="background:#4a5568; color:white; border:none; padding:8px 16px; border-radius:4px; cursor:pointer; font-weight:bold;">Cari Data</button>
                <?php if(!empty($search_keyword)): ?>
                    <a href="index.php" style="background:#e2e8f0; color:#333; padding:8px 12px; text-decoration:none; border-radius:4px; font-size:13px;">Reset</a>
                <?php endif; ?>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Biodata Master Pasien</th>
                        <th>Alamat</th>
                        <th>Alur Cepat Tambah Antrean Hari Ini</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($master_pasien) == 0): ?>
                        <tr><td colspan="3" style="text-align:center; color:#a0aec0; padding:20px;">Pasien tidak ditemukan. Gunakan form pendaftaran pasien baru di sebelah kanan.</td></tr>
                    <?php else: ?>
                        <?php while($p = mysqli_fetch_assoc($master_pasien)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($p['nama_lengkap']); ?></strong><br>
                                    <small style="color:#718096;"><?php echo date('d M Y', strtotime($p['tanggal_lahir'])); ?> | <?php echo $p['gender']; ?></small>
                                </td>
                                <td><small><?php echo htmlspecialchars($p['alamat']); ?></small></td>
                                <td>
                                    <!-- Form Instan kirim pasien lama ke antrean -->
                                    <form action="index.php" method="POST" style="display:flex; gap:4px; align-items:center;">
                                        <input type="hidden" name="id_pasien_lama" value="<?php echo $p['id_pasien']; ?>">
                                        <input type="text" name="keluhan_lama" placeholder="Keluhan saat ini..." required style="padding:4px; font-size:11px; width:130px;">
                                        <button type="submit" name="antreskan_pasien_lama" class="btn-antre">+ Antrean</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- KOLOM KANAN: FORM PASIEN BARU & MONITORING KEPADATAN KLINIK -->
    <div class="sidebar-right">
        <!-- FORM INPUT UTK PASIEN YANG BENAR-BENAR BARU -->
        <h3 style="margin-top:0; color:#2d3748; border-bottom:2px solid #edf2f7; padding-bottom:8px;">➕ Pendaftaran Pasien Baru</h3>
        <form action="index.php" method="POST">
            <div class="form-group">
                <label>Nama Lengkap Pasien:</label>
                <input type="text" name="nama_lengkap" placeholder="Sesuai KTP" required>
            </div>
            <div class="form-group">
                <label>Tanggal Lahir:</label>
                <input type="date" name="tanggal_lahir" required>
            </div>
            <div class="form-group">
                <label>Jenis Kelamin:</label>
                <select name="gender" required>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                </select>
            </div>
            <div class="form-group">
                <label>Alamat Rumah Lengkap:</label>
                <input type="text" name="alamat" placeholder="Nama jalan, Kota" required>
            </div>
            <div class="form-group">
                <label>Keluhan Utama Hari Ini:</label>
                <textarea name="keluhan" rows="2" placeholder="Contoh: Batuk pilek sejak 2 hari lalu" required></textarea>
            </div>
            <div class="form-group">
                <label>Catatan Riwayat Penyakit/Alergi (Opsional):</label>
                <input type="text" name="riwayat_penyakit" placeholder="Contoh: Asma / Alergi Makanan" value="-">
            </div>
            <button type="submit" name="tambah_pasien_baru" class="btn-submit">Daftarkan & Kirim Ke Dokter</button>
        </form>

        <hr style="border:0; border-top:2px solid #edf2f7; margin:25px 0;">

        <!-- MONITORING LIVE REALTIME ANTREAN HARI INI -->
        <h3 style="color:#2d3748; margin-bottom:8px;">📊 Live Status Antrean Hari Ini</h3>
        <?php if(mysqli_num_rows($daftar_antrean) == 0): ?>
            <p style="color:#a0aec0; font-size:12px; font-style:italic;">Belum ada antrean berjalan hari ini.</p>
        <?php else: ?>
            <table style="font-size:12px;">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($antre = mysqli_fetch_assoc($daftar_antrean)): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($antre['nama_lengkap']); ?></strong><br>
                                <span style="font-size:10px; color:#718096;">Keluhan: <?php echo htmlspecialchars($antre['keluhan']); ?></span>
                            </td>
                            <td>
                                <?php 
                                if($antre['status_antrean'] == 'Menunggu') {
                                    echo '<span class="badge badge-tunggu">Menunggu</span>';
                                } elseif($antre['status_antrean'] == 'Diperiksa') {
                                    echo '<span class="badge badge-periksa">Diperiksa</span>';
                                } else {
                                    echo '<span class="badge badge-selesai">Selesai</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</body>
</html>
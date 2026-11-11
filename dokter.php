<?php
include 'koneksi.php';

$pesan = "";
$pasien_diperiksa = null;

// 🔍 AMBIL KATA KUNCI PENCARIAN (JIKA ADA)
$search_keyword = "";
if (isset($_GET['search'])) {
    $search_keyword = mysqli_real_escape_string($koneksi, $_GET['search']);
}

// 🟡 AKSI 1: PENDAFTARAN INSTAN (BYPASS) ATAU MEMASUKKAN PASIEN LAMA KE ANTREAN HARI INI
if (isset($_POST['daftar_bypass'])) {
    $nama_bypass = mysqli_real_escape_string($koneksi, $_POST['nama_bypass']);
    $tgl_lahir_bypass = mysqli_real_escape_string($koneksi, $_POST['tgl_lahir_bypass']);
    $keluhan_bypass = mysqli_real_escape_string($koneksi, $_POST['keluhan_bypass']);

    // Cek apakah pasien sudah ada di database
    $cek_pasien_lama = mysqli_query($koneksi, "SELECT id_pasien FROM pasien WHERE nama_lengkap = '$nama_bypass' AND tanggal_lahir = '$tgl_lahir_bypass'");
    
    if (mysqli_num_rows($cek_pasien_lama) > 0) {
        $data_p = mysqli_fetch_assoc($cek_pasien_lama);
        $id_pasien_fix = $data_p['id_pasien'];
    } else {
        $query_pasien_baru = "INSERT INTO pasien (nama_lengkap, tanggal_lahir, gender, alamat, riwayat_penyakit) VALUES ('$nama_bypass', '$tgl_lahir_bypass', '-', '-', '-')";
        mysqli_query($koneksi, $query_pasien_baru);
        $id_pasien_fix = mysqli_insert_id($koneksi);
    }

    $tanggal_hari_ini = date('Y-m-d');
    
    // Cek dulu apakah pasien ini sudah masuk antrean hari ini agar tidak duplikat antrean
    $cek_antrean_hari_ini = mysqli_query($koneksi, "SELECT id_kunjungan FROM kunjungan WHERE id_pasien = '$id_pasien_fix' AND tanggal_kunjungan = '$tanggal_hari_ini' AND status_antrean != 'Selesai'");
    
    if (mysqli_num_rows($cek_antrean_hari_ini) == 0) {
        $query_antrean = "INSERT INTO kunjungan (id_pasien, tanggal_kunjungan, keluhan, status_antrean) VALUES ('$id_pasien_fix', '$tanggal_hari_ini', '$keluhan_bypass', 'Menunggu')";
        if (mysqli_query($koneksi, $query_antrean)) {
            $pesan = "Pasien berhasil didaftarkan langsung ke dalam antrean!";
        }
    } else {
        $pesan = "⚠️ Pasien ini sudah berada di dalam antrean hari ini!";
    }
}

// 🔵 AKSI 2: MASUKKAN PASIEN DARI DAFTAR MASTER LANGSUNG KE ANTREAN (KLIK DARI HASIL SEARCH)
if (isset($_GET['aksi']) && $_GET['aksi'] == 'antreskan') {
    $id_pasien_pilih = mysqli_real_escape_string($koneksi, $_GET['id_pasien']);
    $tanggal_hari_ini = date('Y-m-d');
    
    $cek_antrean = mysqli_query($koneksi, "SELECT id_kunjungan FROM kunjungan WHERE id_pasien = '$id_pasien_pilih' AND tanggal_kunjungan = '$tanggal_hari_ini' AND status_antrean != 'Selesai'");
    
    if (mysqli_num_rows($cek_antrean) == 0) {
        mysqli_query($koneksi, "INSERT INTO kunjungan (id_pasien, tanggal_kunjungan, keluhan, status_antrean) VALUES ('$id_pasien_pilih', '$tanggal_hari_ini', 'Kunjungan Ulang / Chat Admin', 'Menunggu')");
        $pesan = "Pasien lama berhasil dimasukkan kembali ke antrean hari ini!";
    } else {
        $pesan = "⚠️ Pasien sudah ada di antrean hari ini!";
    }
}

// 🟢 AKSI 3: KETIKA DOKTER KLIK TOMBOL "PANGGIL"
if (isset($_GET['aksi']) && $_GET['aksi'] == 'periksa') {
    $id_kunjungan_pilih = mysqli_real_escape_string($koneksi, $_GET['id']); 
    mysqli_query($koneksi, "UPDATE kunjungan SET status_antrean = 'Menunggu' WHERE status_antrean = 'Diperiksa'");
    mysqli_query($koneksi, "UPDATE kunjungan SET status_antrean = 'Diperiksa' WHERE id_kunjungan = '$id_kunjungan_pilih'");
}

// 🔴 AKSI 4: KETIKA PASIEN BATAL BEROBAT
if (isset($_GET['aksi']) && $_GET['aksi'] == 'batal') {
    $id_kunjungan_batal = mysqli_real_escape_string($koneksi, $_GET['id']);
    mysqli_query($koneksi, "DELETE FROM kunjungan WHERE id_kunjungan = '$id_kunjungan_batal'");
}

// 💾 AKSI 5: SIMPAN REKAM MEDIS FINAL
if (isset($_POST['simpan_rekam_medis'])) {
    $id_kunjungan = mysqli_real_escape_string($koneksi, $_POST['id_kunjungan']);
    $id_pasien = mysqli_real_escape_string($koneksi, $_POST['id_pasien']);
    $nama_edit = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $tgl_lahir_edit = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']);
    $gender_edit = mysqli_real_escape_string($koneksi, $_POST['gender']);
    $alamat_edit = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $keluhan_edit = mysqli_real_escape_string($koneksi, $_POST['keluhan']);
    $diagnosa = mysqli_real_escape_string($koneksi, $_POST['diagnosa']);
    $terapi = mysqli_real_escape_string($koneksi, $_POST['terapi_penanganan']);
    $riwayat_baru = mysqli_real_escape_string($koneksi, $_POST['riwayat_penyakit']);

    mysqli_query($koneksi, "UPDATE pasien SET nama_lengkap = '$nama_edit', tanggal_lahir = '$tgl_lahir_edit', gender = '$gender_edit', alamat = '$alamat_edit', riwayat_penyakit = '$riwayat_baru' WHERE id_pasien = '$id_pasien'");
    mysqli_query($koneksi, "UPDATE kunjungan SET keluhan = '$keluhan_edit', diagnosa = '$diagnosa', terapi_penanganan = '$terapi', status_antrean = 'Selesai' WHERE id_kunjungan = '$id_kunjungan'");
    $pesan = "Rekam medis berhasil disimpan!";
}

// 🔍 QUERY PASIEN DI PERIKSA
$cek_diperiksa = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.tanggal_lahir, p.gender, p.alamat, p.riwayat_penyakit FROM kunjungan k JOIN pasien p ON k.id_pasien = p.id_pasien WHERE k.status_antrean = 'Diperiksa' LIMIT 1");
if (mysqli_num_rows($cek_diperiksa) > 0) {
    $pasien_diperiksa = mysqli_fetch_assoc($cek_diperiksa);
    
    // Logika Hitung Jumlah Kunjungan Selesai Sebelumnya untuk Label Baru/Lama
    $id_p_aktif = $pasien_diperiksa['id_pasien'];
    $hitung_kunjungan = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kunjungan WHERE id_pasien = '$id_p_aktif' AND status_antrean = 'Selesai'");
    $data_hitung = mysqli_fetch_assoc($hitung_kunjungan);
    $status_pasien_label = ($data_hitung['total'] > 0) ? "Pasien Lama" : "Pasien Baru";
}

// 📋 QUERY ANTREAN AKTIF
$daftar_antrean = mysqli_query($koneksi, "SELECT k.id_kunjungan, p.nama_lengkap, p.tanggal_lahir, k.keluhan FROM kunjungan k JOIN pasien p ON k.id_pasien = p.id_pasien WHERE k.status_antrean = 'Menunggu' ORDER BY k.id_kunjungan ASC");

// 👥 QUERY MASTER DATA PASIEN (Daftar Semua Pasien + Filter Search)
if (!empty($search_keyword)) {
    $query_master_pasien = "SELECT * FROM pasien WHERE nama_lengkap LIKE '%$search_keyword%' ORDER BY nama_lengkap ASC";
} else {
    $query_master_pasien = "SELECT * FROM pasien ORDER BY id_pasien DESC LIMIT 5"; // Default tampilkan 5 pasien pendaftar terbaru jika kolom search kosong
}
$master_pasien = mysqli_query($koneksi, $query_master_pasien);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard EHR Klinik - Mode Integrasi</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f9; display: flex; gap: 20px; }
        .sidebar-left { flex: 1.2; display: flex; flex-direction: column; gap: 15px; }
        .box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        .box-pemeriksaan { flex: 2; background: #fff; border-top: 5px solid #28a745; height: fit-content; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 13px; }
        table, th, td { border: 1px solid #eee; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; color: #333; }
        .btn-periksa { background: #28a745; color: white; padding: 4px 8px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .btn-batal { background: #dc3545; color: white; padding: 4px 8px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .btn-antreskan { background: #007BFF; color: white; padding: 3px 6px; text-decoration: none; border-radius: 4px; font-size: 11px; }
        .btn-simpan { background: #007BFF; color: white; padding: 12px; border: none; border-radius: 4px; width: 100%; font-weight: bold; font-size: 15px; cursor: pointer; margin-top: 15px; }
        .badge { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; color: white; display: inline-block; }
        .badge-baru { background-color: #28a745; }
        .badge-lama { background-color: #007BFF; }
        .alert { padding: 10px; background: #d4edda; color: #155724; border-radius: 4px; font-weight: bold; text-align: center; }
        .input-inline { border: 1px solid #ccc; padding: 6px; width: 100%; box-sizing: border-box; }
        .search-container { display: flex; gap: 5px; margin-bottom: 10px; }
        .search-input { flex: 1; padding: 6px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-search { background: #6c757d; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>

    <!-- CONTAINER KIRI (ANTREAN & MASTER DATA) -->
    <div class="sidebar-left">
        
        <!-- PENGUMUMAN NOTIFIKASI -->
        <?php if ($pesan != ""): ?>
            <div class="alert"><?php echo $pesan; ?></div>
        <?php endif; ?>

        <!-- BOX 1: ANTREAN AKTIF HARI INI -->
        <div class="box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h3 style="margin:0;">📋 Antrean Pasien</h3>
                <a href="laporan.php" target="_blank" style="background:#17a2b8; color:white; padding:5px 10px; text-decoration:none; border-radius:4px; font-size:12px; font-weight:bold;">📥 Excel Report</a>
            </div>
            
            <?php if (mysqli_num_rows($daftar_antrean) == 0): ?>
                <p style="color: #999; font-size:13px;">Belum ada pasien dalam antrean aktif.</p>
            <?php else: ?>
                <table>
                    <?php while($row = mysqli_fetch_assoc($daftar_antrean)): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($row['nama_lengkap']); ?></strong><br>
                                <small style="color:#666;"><?php echo date('d M Y', strtotime($row['tanggal_lahir'])); ?></small>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="dokter.php?aksi=periksa&id=<?php echo $row['id_kunjungan']; ?>" class="btn-periksa">Panggil</a>
                                <a href="dokter.php?aksi=batal&id=<?php echo $row['id_kunjungan']; ?>" class="btn-batal" onclick="return confirm('Batalkan?')">✕</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php endif; ?>
        </div>

        <!-- BOX 2: MASTER DATA REGISTER PASIEN + SEARCH -->
        <div class="box" style="background: #edf2f7;">
            <h3 style="margin-top:0; margin-bottom:10px;">🔍 Buku Registrasi & Cari Pasien</h3>
            
            <form action="dokter.php" method="GET" class="search-container">
                <input type="text" name="search" class="search-input" placeholder="Ketik nama pasien lama..." value="<?php echo htmlspecialchars($search_keyword); ?>">
                <button type="submit" class="btn-search">Cari</button>
                <?php if(!empty($search_keyword)): ?>
                    <a href="dokter.php" style="background:#e2e8f0; color:#333; padding:6px; text-decoration:none; border-radius:4px; font-size:12px;">Reset</a>
                <?php endif; ?>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Nama Pasien (Master)</th>
                        <th>Aksi Cepat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($master_pasien) == 0): ?>
                        <tr><td colspan="2" style="color:#777; text-align:center;">Pasien tidak ditemukan.</td></tr>
                    <?php else: ?>
                        <?php while($p = mysqli_fetch_assoc($master_pasien)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($p['nama_lengkap']); ?></strong><br>
                                    <small><?php echo date('d M Y', strtotime($p['tanggal_lahir'])); ?> | <?php echo $p['alamat']; ?></small>
                                </td>
                                <td>
                                    <a href="dokter.php?aksi=antreskan&id_pasien=<?php echo $p['id_pasien']; ?>" class="btn-antreskan">+ Antrean</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- BOX 3: BYPASS FORM MANUAL (UNTUK PASIEN BARU VIA CHAT/TELP) -->
        <div class="box" style="background: #fff3cd; border: 1px solid #ffeeba;">
            <h4 style="margin: 0 0 8px 0; color: #856404;">➕ Tambah Pasien Baru Via Chat/Telp</h4>
            <form action="dokter.php" method="POST">
                <input type="text" name="nama_bypass" placeholder="Nama Lengkap" required style="width:100%; padding:5px; margin-bottom:5px; box-sizing:border-box;">
                <input type="date" name="tgl_lahir_bypass" required style="width:100%; padding:5px; margin-bottom:5px; box-sizing:border-box;">
                <input type="text" name="keluhan_bypass" placeholder="Keluhan / Catatan Hubungi" required style="width:100%; padding:5px; margin-bottom:8px; box-sizing:border-box;">
                <button type="submit" name="daftar_bypass" style="width:100%; background:#ffc107; border:none; padding:6px; font-weight:bold; cursor:pointer; border-radius:4px; font-size:12px;">Daftarkan Ke Antrean</button>
            </form>
        </div>

    </div>

    <!-- CONTAINER KANAN (RUANG PERIKSA & KEPUTUSAN KLINIS) -->
    <div class="box box-pemeriksaan">
        <h3>
            🩺 Ruang Pemeriksaan 
            <?php if($pasien_diperiksa != null): ?>
                <span class="badge <?php echo ($status_pasien_label == 'Pasien Lama') ? 'badge-lama' : 'badge-baru'; ?>">
                    <?php echo $status_pasien_label; ?>
                </span>
            <?php endif; ?>
        </h3>
        
        <?php if ($pasien_diperiksa == null): ?>
            <p style="color: #777; text-align: center; margin-top: 60px; font-style: italic;">Silakan pilih dan panggil pasien dari antrean di sebelah kiri untuk melakukan pemeriksaan.</p>
        <?php else: ?>
            <form action="" method="POST">
                <input type="hidden" name="id_kunjungan" value="<?php echo $pasien_diperiksa['id_kunjungan']; ?>">
                <input type="hidden" name="id_pasien" value="<?php echo $pasien_diperiksa['id_pasien']; ?>">

                <table style="margin-bottom: 20px; background: #fafafa;">
                    <tr>
                        <td style="width: 25%;"><strong>Nama Lengkap</strong></td>
                        <td><input type="text" name="nama_lengkap" class="input-inline" value="<?php echo htmlspecialchars($pasien_diperiksa['nama_lengkap']); ?>" required></td>
                    </tr>
                    <tr>
                        <td><strong>Tanggal Lahir</strong></td>
                        <td><input type="date" name="tanggal_lahir" class="input-inline" value="<?php echo $pasien_diperiksa['tanggal_lahir']; ?>" required></td>
                    </tr>
                    <tr>
                        <td><strong>Gender</strong></td>
                        <td>
                            <select name="gender" class="input-inline" required>
                                <option value="Laki-laki" <?php if($pasien_diperiksa['gender'] == 'Laki-laki') echo 'selected'; ?>>Laki-laki</option>
                                <option value="Perempuan" <?php if($pasien_diperiksa['gender'] == 'Perempuan') echo 'selected'; ?>>Perempuan</option>
                                <option value="-" <?php if($pasien_diperiksa['gender'] == '-') echo 'selected'; ?>>-</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Alamat Tinggal</strong></td>
                        <td><input type="text" name="alamat" class="input-inline" value="<?php echo htmlspecialchars($pasien_diperiksa['alamat']); ?>" required></td>
                    </tr>
                    <tr>
                        <td><strong>Keluhan Utama</strong></td>
                        <td><textarea name="keluhan" class="input-inline" rows="2" required><?php echo htmlspecialchars($pasien_diperiksa['keluhan']); ?></textarea></td>
                    </tr>
                </table>

                <h4 style="color:#333; margin-bottom:8px;">📋 Berkas Rekam Medis Pasien</h4>
                <div style="margin-bottom: 12px;">
                    <label style="font-weight:bold; font-size:13px; display:block; margin-bottom:4px;">Riwayat Alergi & Penyakit Bawaan (Akan Tersimpan Permanen):</label>
                    <textarea name="riwayat_penyakit" class="input-inline" rows="2" style="width:100%; font-family:inherit;" placeholder="Tulis riwayat alergi jika ada..."><?php echo htmlspecialchars($pasien_diperiksa['riwayat_penyakit']); ?></textarea>
                </div>
                <div style="margin-bottom: 12px;">
                    <label style="font-weight:bold; font-size:13px; display:block; margin-bottom:4px;">Diagnosa (ICD-10 / Deskripsi Medis):</label>
                    <textarea name="diagnosa" class="input-inline" rows="2" style="width:100%; font-family:inherit;" required placeholder="Contoh: J00 - Common Cold"></textarea>
                </div>
                <div style="margin-bottom: 12px;">
                    <label style="font-weight:bold; font-size:13px; display:block; margin-bottom:4px;">Terapi Tindakan & Resep Obat:</label>
                    <textarea name="terapi_penanganan" class="input-inline" rows="3" style="width:100%; font-family:inherit;" required placeholder="Contoh: Amoxicillin 500mg 3x1, Paracetamol 500mg (k/p)"></textarea>
                </div>
                
                <button type="submit" name="simpan_rekam_medis" class="btn-simpan">✓ Simpan & Selesai Berobat</button>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>
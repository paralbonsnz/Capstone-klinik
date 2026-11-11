<?php
include 'koneksi.php';

// 🔍 AMBIL DATA: Gabungkan tabel pasien dan kunjungan yang statusnya sudah 'Selesai'
$query = "SELECT k.*, p.nama_lengkap, p.tanggal_lahir, p.gender, p.alamat, p.riwayat_penyakit 
          FROM kunjungan k 
          JOIN pasien p ON k.id_pasien = p.id_pasien 
          WHERE k.status_antrean = 'Selesai' 
          ORDER BY k.tanggal_kunjungan DESC";
$kunjungan_selesai = mysqli_query($koneksi, $query);

// ⚡ TRICK EXCEL: Mengubah halaman web biasa menjadi file Excel saat diakses
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Rekam_Medis_Klinik.xls");
?>

<!-- Halaman di bawah ini otomatis akan langsung terbuka di Excel setelah diunduh -->
<table border="1">
    <thead>
        <tr style="background-color: #007BFF; color: white; font-weight: bold;">
            <th>No</th>
            <th>Tanggal Kunjungan</th>
            <th>Nama Pasien</th>
            <th>Tanggal Lahir</th>
            <th>Jenis Kelamin</th>
            <th>Alamat</th>
            <th>Keluhan Utama</th>
            <th>Diagnosa Medis</th>
            <th>Terapi / Obat</th>
            <th>Riwayat Alergi / Penyakit</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $no = 1;
        while($row = mysqli_fetch_assoc($kunjungan_selesai)): 
            // Pengaman fail-safe jika data riwayat penyakit belum diisi/null
            $riwayat = isset($row['riwayat_penyakit']) ? $row['riwayat_penyakit'] : '-';
        ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo $row['tanggal_kunjungan']; ?></td>
                <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                <td><?php echo $row['tanggal_lahir']; ?></td>
                <td><?php echo $row['gender']; ?></td>
                <td><?php echo htmlspecialchars($row['alamat']); ?></td>
                <td><?php echo htmlspecialchars($row['keluhan']); ?></td>
                <td><?php echo htmlspecialchars($row['diagnosa']); ?></td>
                <td><?php echo htmlspecialchars($row['terapi_penanganan']); ?></td>
                <td><?php echo htmlspecialchars($riwayat); ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>
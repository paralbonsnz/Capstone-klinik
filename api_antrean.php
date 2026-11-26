<?php
header('Content-Type: application/json; charset=utf-8');
include 'koneksi.php';

$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : (isset($_POST['aksi']) ? $_POST['aksi'] : '');

// 1. Ambil data antrean publik untuk Monitor TV dan Dashboard
if ($aksi === 'get_antrean_publik' || $aksi === '') {
    $today = date('Y-m-d');

    // Antrean yang sedang dipanggil
    $q_panggil = mysqli_query($koneksi, "SELECT k.id_kunjungan, k.nomor_antrean, k.poli_tujuan, k.loket_meja, k.status_antrean, k.waktu_panggil, p.nama_lengkap, p.no_hp, p.status_bpjs FROM kunjungan k JOIN pasien p ON k.id_pasien = p.id_pasien WHERE DATE(k.tanggal_kunjungan) = '$today' AND k.status_antrean = 'Dipanggil' ORDER BY k.waktu_panggil DESC, k.id_kunjungan DESC LIMIT 1");
    
    $sedang_dipanggil = null;
    if ($r = mysqli_fetch_assoc($q_panggil)) {
        // Masking nama & no_hp untuk privasi publik
        $nama_arr = explode(' ', trim($r['nama_lengkap']));
        $nama_mask = $nama_arr[0];
        if (count($nama_arr) > 1) {
            $nama_mask .= ' ' . substr($nama_arr[1], 0, 1) . '***';
        }
        $hp_mask = !empty($r['no_hp']) ? substr($r['no_hp'], 0, 4) . '****' : '';
        
        $sedang_dipanggil = [
            'id_kunjungan'   => (int)$r['id_kunjungan'],
            'nomor_antrean'  => $r['nomor_antrean'] ?: 'A-001',
            'poli_tujuan'    => $r['poli_tujuan'] ?: 'Poli Umum',
            'loket_meja'     => $r['loket_meja'] ?: 'Ruang Pemeriksaan',
            'nama_mask'      => $nama_mask,
            'hp_mask'        => $hp_mask,
            'jenis_pasien'   => !empty($r['status_bpjs']) ? $r['status_bpjs'] : 'BPJS / Umum',
            'waktu_panggil'  => $r['waktu_panggil']
        ];
    }

    // Antrean berikutnya yang masih menunggu
    $q_berikutnya = mysqli_query($koneksi, "SELECT k.id_kunjungan, k.nomor_antrean, k.poli_tujuan, k.status_antrean FROM kunjungan k WHERE DATE(k.tanggal_kunjungan) = '$today' AND k.status_antrean = 'Menunggu' ORDER BY k.id_kunjungan ASC LIMIT 5");
    $daftar_berikutnya = [];
    $pos = 1;
    while ($rb = mysqli_fetch_assoc($q_berikutnya)) {
        $daftar_berikutnya[] = [
            'urutan'         => $pos++,
            'id_kunjungan'   => (int)$rb['id_kunjungan'],
            'nomor_antrean'  => $rb['nomor_antrean'],
            'poli_tujuan'    => $rb['poli_tujuan'] ?: 'Poli Umum',
            'status'         => 'MENUNGGU',
            'estimasi'       => ($pos == 2) ? 'SIAP-SIAP' : 'Estimasi 5-10 mnt'
        ];
    }

    // Sedang dilayani per poli
    $dilayani_umum = '-';
    $q_dilayani_umum = mysqli_query($koneksi, "SELECT nomor_antrean, loket_meja FROM kunjungan WHERE DATE(tanggal_kunjungan) = '$today' AND status_antrean IN ('Dipanggil','Diperiksa') AND (poli_tujuan = 'Poli Umum' OR poli_tujuan IS NULL) ORDER BY id_kunjungan DESC LIMIT 1");
    if ($q_dilayani_umum && mysqli_num_rows($q_dilayani_umum) > 0) {
        $ru = mysqli_fetch_assoc($q_dilayani_umum);
        $dilayani_umum = $ru['nomor_antrean'] ?: '-';
    }

    $dilayani_gigi = '-';
    $q_dilayani_gigi = mysqli_query($koneksi, "SELECT nomor_antrean, loket_meja FROM kunjungan WHERE DATE(tanggal_kunjungan) = '$today' AND status_antrean IN ('Dipanggil','Diperiksa') AND (poli_tujuan LIKE '%Gigi%' OR poli_tujuan = 'Poli Gigi & Mulut') ORDER BY id_kunjungan DESC LIMIT 1");
    if ($q_dilayani_gigi && mysqli_num_rows($q_dilayani_gigi) > 0) {
        $rg = mysqli_fetch_assoc($q_dilayani_gigi);
        $dilayani_gigi = $rg['nomor_antrean'] ?: '-';
    }

    // Statistik antrean hari ini
    $q_stat = mysqli_query($koneksi, "SELECT 
        COUNT(*) as total_ambil,
        SUM(CASE WHEN status_antrean = 'Menunggu' THEN 1 ELSE 0 END) as menunggu,
        SUM(CASE WHEN status_antrean = 'Dipanggil' THEN 1 ELSE 0 END) as dipanggil,
        SUM(CASE WHEN status_antrean = 'Diperiksa' THEN 1 ELSE 0 END) as diperiksa,
        SUM(CASE WHEN status_antrean = 'Selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN status_antrean = 'Batal' THEN 1 ELSE 0 END) as batal
        FROM kunjungan WHERE DATE(tanggal_kunjungan) = '$today'");
    $stat = mysqli_fetch_assoc($q_stat);

    echo json_encode([
        'status'            => 'success',
        'waktu_server'      => date('H:i:s'),
        'tanggal_server'    => date('d M Y'),
        'sedang_dipanggil'  => $sedang_dipanggil,
        'daftar_berikutnya' => $daftar_berikutnya,
        'dilayani_poli1'    => $dilayani_umum,
        'dilayani_poli2'    => $dilayani_gigi,
        'stat'              => [
            'total_ambil' => (int)$stat['total_ambil'],
            'menunggu'    => (int)$stat['menunggu'],
            'dipanggil'   => (int)$stat['dipanggil'],
            'diperiksa'   => (int)$stat['diperiksa'],
            'selesai'     => (int)$stat['selesai'],
            'batal'       => (int)$stat['batal']
        ]
    ]);
    exit;
}

// 2. Aksi Admin/Petugas untuk memperbarui status antrean
if ($aksi === 'update_status') {
    $id_kunjungan = isset($_POST['id_kunjungan']) ? (int)$_POST['id_kunjungan'] : 0;
    $status_baru  = isset($_POST['status']) ? mysqli_real_escape_string($koneksi, $_POST['status']) : '';
    $loket_meja   = isset($_POST['loket_meja']) ? mysqli_real_escape_string($koneksi, $_POST['loket_meja']) : '';

    if ($id_kunjungan <= 0 || empty($status_baru)) {
        echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
        exit;
    }

    $valid_statuses = ['Menunggu', 'Dipanggil', 'Diperiksa', 'Selesai', 'Batal'];
    if (!in_array($status_baru, $valid_statuses)) {
        echo json_encode(['status' => 'error', 'message' => 'Status tidak valid']);
        exit;
    }

    $waktu_sekarang = date('Y-m-d H:i:s');
    $tambahan_query = "";

    if ($status_baru === 'Dipanggil') {
        $tambahan_query = ", waktu_panggil = '$waktu_sekarang'";
        if (!empty($loket_meja)) {
            $tambahan_query .= ", loket_meja = '$loket_meja'";
        }
    } elseif ($status_baru === 'Diperiksa') {
        $tambahan_query = ", waktu_panggil = IFNULL(waktu_panggil, '$waktu_sekarang')";
    }

    $sql = "UPDATE kunjungan SET status_antrean = '$status_baru' $tambahan_query WHERE id_kunjungan = $id_kunjungan";
    if (mysqli_query($koneksi, $sql)) {
        echo json_encode([
            'status'  => 'success', 
            'message' => "Status antrean berhasil diubah menjadi $status_baru",
            'id_kunjungan' => $id_kunjungan,
            'status_baru'  => $status_baru
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($koneksi)]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenal']);
?>

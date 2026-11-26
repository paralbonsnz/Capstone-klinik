<?php
/**
 * Helper terpadu untuk nomor antrean & pengelolaan antrean klinik
 */

if (!function_exists('generate_nomor_antrean')) {
    function generate_nomor_antrean($koneksi, $poli = 'Poli Umum', $tanggal = null) {
        if (!$tanggal) {
            $tanggal = date('Y-m-d');
        }
        $prefix = ($poli === 'Poli Gigi & Mulut' || stripos($poli, 'gigi') !== false) ? 'B' : 'A';
        
        // Ambil nomor antrean tertinggi hari ini dengan prefix yang sama
        $q = mysqli_query($koneksi, "SELECT nomor_antrean FROM kunjungan WHERE DATE(tanggal_kunjungan) = '$tanggal' AND nomor_antrean LIKE '{$prefix}-%' ORDER BY id_kunjungan DESC");
        $max_num = 0;
        while ($r = mysqli_fetch_assoc($q)) {
            if (preg_match('/' . $prefix . '-(\d+)/', $r['nomor_antrean'], $matches)) {
                $val = (int)$matches[1];
                if ($val > $max_num) {
                    $max_num = $val;
                }
            }
        }
        $next_num = $max_num + 1;
        return sprintf("%s-%03d", $prefix, $next_num);
    }
}
?>

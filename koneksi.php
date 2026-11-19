<?php
// Mencegah PHP melempar Uncaught mysqli_sql_exception yang menyebabkan fatal error
mysqli_report(MYSQLI_REPORT_OFF);

// 1. Menentukan Alamat dan Identitas Database Lokal XAMPP
// Gunakan 127.0.0.1 agar lebih stabil di Windows (menghindari delay lookup IPv6 localhost)
$host     = "127.0.0.1";     
$username = "root";          
$password = "";              
$database = "rekam_medis"; 

// 2. Hubungkan PHP ke MySQL
$koneksi = @mysqli_connect($host, $username, $password, $database);

// Fallback ke "localhost" atau nama database alternatif jika belum tersambung
if (!$koneksi) {
    $koneksi = @mysqli_connect("localhost", $username, $password, $database);
}
if (!$koneksi) {
    $koneksi = @mysqli_connect($host, $username, $password, "rekam_medis_tes");
}
if (!$koneksi) {
    $koneksi = @mysqli_connect("localhost", $username, $password, "rekam_medis_tes");
}

// 3. Pengecekan: Jika MySQL di XAMPP belum aktif, berikan tampilan notifikasi yang jelas dan ramah
if (!$koneksi) {
    die("
    <div style='font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; max-width: 600px; margin: 50px auto; padding: 26px; background: #fff5f5; border: 1.5px solid #feb2b2; border-radius: 14px; color: #9b2c2c; box-shadow: 0 10px 25px rgba(0,0,0,0.06);'>
        <h3 style='margin-top: 0; color: #c53030; display: flex; align-items: center; gap: 8px;'>
            ⚠️ Koneksi ke Database MySQL Terputus
        </h3>
        <p style='font-size: 14px; line-height: 1.6; color: #4a5568;'>
            Layanan database <strong>MySQL</strong> di laptop Anda sedang mati atau belum diaktifkan.
        </p>
        <div style='background: #ffffff; border: 1px solid #fed7d7; border-radius: 8px; padding: 14px; margin: 15px 0; font-size: 13px; color: #2d3748;'>
            <strong>Langkah Mudah Mengatasinya:</strong><br>
            1. Buka aplikasi <strong>XAMPP Control Panel</strong> di laptop Anda.<br>
            2. Pada baris <strong>MySQL</strong>, klik tombol <strong>Start</strong> (hingga tombol berubah jadi <em>Stop</em> dan berwarna hijau).<br>
            3. Setelah aktif, klik tombol di bawah untuk memuat ulang halaman.
        </div>
        <button onclick='window.location.reload();' style='background: #e53e3e; color: white; border: none; padding: 10px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px;'>
            🔄 Muat Ulang Halaman (Refresh)
        </button>
    </div>");
}
?>

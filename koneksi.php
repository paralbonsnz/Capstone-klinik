<?php
// 1. Menentukan Alamat dan Identitas Database Lokal XAMPP
$host     = "localhost";     // Alamat server database (karena di laptop sendiri, namanya localhost)
$username = "root";          // Username bawaan standar XAMPP adalah "root"
$password = "";              // Password bawaan standar XAMPP adalah kosong/tanpa password
$database = "rekam_medis"; // Nama database yang kita buat di phpMyAdmin sebelumnya

// 2. Perintah untuk Menghubungkan PHP ke MySQL (Menggunakan Ekstensi mysqli)
$koneksi = mysqli_connect($host, $username, $password, $database);

// 3. Logika Pengecekan: Apakah Jembatan Berhasil Tersambung?
if (!$koneksi) {
    // Jika koneksi gagal, hentikan program dan munculkan pesan error-nya apa
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// Catatan: Jika berhasil, sengaja kita biarkan kosong/tidak memunculkan teks apa-apa 
// agar file ini bisa disisipkan dengan rapi ke file backend lainnya nanti.
?>
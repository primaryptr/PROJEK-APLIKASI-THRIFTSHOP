<?php
// config.php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // Username database Anda
define('DB_PASS', '');          // Password database (kosong jika XAMPP default)
define('DB_NAME', 'thrift'); // Nama database

// Buat koneksi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8");

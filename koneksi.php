<<<<<<< HEAD
<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "thrift";

$conn = mysqli_connect($host,$user,$password,$database);

if(!$conn){
    die("Koneksi gagal: " . mysqli_connect_error());
}

?>
=======
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
>>>>>>> b15c22a5a63a15d8874db042ef577a3404c8d4d0

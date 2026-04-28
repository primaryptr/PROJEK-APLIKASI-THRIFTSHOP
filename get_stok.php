<?php
require_once 'koneksi.php';
header('Content-Type: application/json');

$query = "SELECT id, kode_barang, stok FROM barang";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['error' => mysqli_error($conn)]);
    exit;
}

$data = mysqli_fetch_all($result, MYSQLI_ASSOC);
echo json_encode($data);
?>

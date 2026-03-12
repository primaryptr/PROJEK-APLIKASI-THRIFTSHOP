<?php
session_start();
if ($_SESSION['role'] != "owner") {
    header("Location: ../login.php");
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Owner</title>

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #e8e5db;
        }

        .header {
            background: #dcd8c8;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-weight: bold;
        }

        .container {
            padding: 20px;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .flex {
            display: flex;
            justify-content: space-between;
        }

        .yellow {
            background: #e9c46a;
            color: black;
        }

        .green {
            background: #2a9d8f;
            color: white;
        }

        .inventory {
            background: #f1efe7;
            border: 1px solid #ccc;
        }

        .btn {
            background: #2d3142;
            color: white;
            padding: 8px 15px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
        }

        .navbar {
            position: fixed;
            bottom: 0;
            width: 100%;
            background: white;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            box-shadow: 0 -3px 8px rgba(0, 0, 0, 0.1);
        }

        .navbar a {
            text-decoration: none;
            color: #333;
            font-size: 12px;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="logo">SOLO SECOND THRIFT<br><small>OWNER</small></div>
        <div><?php echo $_SESSION['nama']; ?></div>
    </div>

    <div class="container">

        <div class="flex">
            <div class="card yellow" style="width:48%;">
                <small>TRANSAKSI HARI INI</small>
                <h3>12 Transaksi</h3>
            </div>

            <div class="card green" style="width:48%;">
                <small>TOTAL OMZET</small>
                <h3>Rp. 1.250.000</h3>
            </div>
        </div>

        <div class="card inventory">
            <b>⚠ INVENTORI KRITIS</b>
            <h3>4 Item Habis Stok</h3>
            <a href="#" class="btn">Cek Sekarang</a>
        </div>

    </div>

    <div class="navbar">
        <a href="#">Katalog</a>
        <a href="#">Transaksi</a>
        <a href="#">Home</a>
        <a href="#">Laporan</a>
        <a href="#">User</a>
    </div>

</body>

</html>
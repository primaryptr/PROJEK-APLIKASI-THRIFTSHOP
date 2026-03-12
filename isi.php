<<<<<<< HEAD
<?php
session_start();
if ($_SESSION['role'] != "crew") {
    header("Location:login.php");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - Crew</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Roboto', 'Arial', sans-serif;
            background-color: #FDFCF0;
            color: #264653;
        }

        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 16px;
            padding-bottom: 70px;
            min-height: 100vh;
        }

        .header {
            background-color: #FDFCF0;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: 700;
        }

        .header a {
            color: #B23A48;
            text-decoration: none;
            font-size: 18px;
        }

        .content {
            text-align: center;
            padding: 40px 20px;
        }

        .content i {
            font-size: 48px;
            color: #E9C46A;
            margin-bottom: 20px;
        }

        .content p {
            color: #666;
            margin-bottom: 20px;
        }

        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: white;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-around;
            align-items: center;
            height: 60px;
            max-width: 500px;
            margin: 0 auto;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            text-decoration: none;
            color: #666;
            font-size: 11px;
            transition: color 0.3s;
        }

        .nav-item.active {
            color: #B23A48;
        }

        .nav-icon {
            font-size: 20px;
        }

        .cart-icon {
            width: 50px;
            height: 50px;
            background-color: #B23A48;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            position: relative;
            top: -10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Manajemen Produk</h1>
            <a href="logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
        </div>

        <div class="content">
            <i class="fas fa-boxes"></i>
            <p>Fitur Manajemen Produk sedang dikembangkan</p>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <a href="dasboard_crew.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-home"></i></div>
            <div>Dashboard</div>
        </a>
        <a href="isi.php" class="nav-item active">
            <div class="nav-icon"><i class="fas fa-box"></i></div>
            <div>Isi</div>
        </a>
        <div class="cart-icon">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <a href="laporan.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-file-alt"></i></div>
            <div>Laporan</div>
        </a>
        <a href="profil.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-user"></i></div>
            <div>Profil</div>
        </a>
    </div>
</body>

=======
<?php
session_start();
if ($_SESSION['role'] != "crew") {
    header("Location:login.php");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - Crew</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Roboto', 'Arial', sans-serif;
            background-color: #FDFCF0;
            color: #264653;
        }

        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 16px;
            padding-bottom: 70px;
            min-height: 100vh;
        }

        .header {
            background-color: #FDFCF0;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: 700;
        }

        .header a {
            color: #B23A48;
            text-decoration: none;
            font-size: 18px;
        }

        .content {
            text-align: center;
            padding: 40px 20px;
        }

        .content i {
            font-size: 48px;
            color: #E9C46A;
            margin-bottom: 20px;
        }

        .content p {
            color: #666;
            margin-bottom: 20px;
        }

        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: white;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-around;
            align-items: center;
            height: 60px;
            max-width: 500px;
            margin: 0 auto;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            text-decoration: none;
            color: #666;
            font-size: 11px;
            transition: color 0.3s;
        }

        .nav-item.active {
            color: #B23A48;
        }

        .nav-icon {
            font-size: 20px;
        }

        .cart-icon {
            width: 50px;
            height: 50px;
            background-color: #B23A48;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            position: relative;
            top: -10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Manajemen Produk</h1>
            <a href="logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
        </div>

        <div class="content">
            <i class="fas fa-boxes"></i>
            <p>Fitur Manajemen Produk sedang dikembangkan</p>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <a href="dasboard_crew.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-home"></i></div>
            <div>Dashboard</div>
        </a>
        <a href="isi.php" class="nav-item active">
            <div class="nav-icon"><i class="fas fa-box"></i></div>
            <div>Isi</div>
        </a>
        <div class="cart-icon">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <a href="laporan.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-file-alt"></i></div>
            <div>Laporan</div>
        </a>
        <a href="profil.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-user"></i></div>
            <div>Profil</div>
        </a>
    </div>
</body>

>>>>>>> b15c22a5a63a15d8874db042ef577a3404c8d4d0
</html>
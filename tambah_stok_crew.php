<?php
session_start();
require_once 'koneksi.php';

// Cek apakah user sudah login dan role adalah crew
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'crew') {
    header("Location: login.php");
    exit();
}

$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_barang = $_POST['nama_barang'];
    $kategori = $_POST['kategori'];
    $harga = (int)$_POST['harga'];
    $stok = (int)$_POST['stok'];
    $barang_rusak = (int)$_POST['barang_rusak'];
    $tipe = 'brand'; // Default tipe

    // Proses Upload Foto
    $foto = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $_FILES['foto']['name']);
        $path = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $path)) {
            $foto = $filename;
        }
    }

    $stmt = $conn->prepare("INSERT INTO barang (nama_barang, kategori, tipe, harga, stok, barang_rusak, foto) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiiss", $nama_barang, $kategori, $tipe, $harga, $stok, $barang_rusak, $foto);
    if ($stmt->execute()) {
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Solo Second Thrift - Tambah Stok</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <style>
        :root {
            --bg: #FDFCF0;
            --charcoal: #264653;
            --red: #B23A48;
            --gold: #E9C46A;
            --green: #2A9D8F;
            --radius: 16px;
            --nav-h: 70px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: #12121f;
            background-image:
                radial-gradient(ellipse at 15% 50%, rgba(38, 70, 83, 0.45) 0%, transparent 55%),
                radial-gradient(ellipse at 85% 15%, rgba(178, 58, 72, 0.25) 0%, transparent 50%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .android-device {
            position: relative;
            width: 393px;
            background: linear-gradient(160deg, #3a3a3a 0%, #1e1e1e 50%, #111 100%);
            border-radius: 54px;
            padding: 15px;
            box-shadow:
                0 0 0 1.5px #4a4a4a,
                0 0 0 3px #1a1a1a,
                6px 6px 0 4px #000,
                0 40px 100px rgba(0, 0, 0, 0.85),
                inset 0 2px 0 rgba(255, 255, 255, 0.1);
        }

        .btn-power {
            position: absolute;
            right: -5px;
            top: 140px;
            width: 5px;
            height: 55px;
            background: linear-gradient(to right, #2a2a2a, #4a4a4a, #2a2a2a);
            border-radius: 0 4px 4px 0;
        }

        .btn-vol-up {
            position: absolute;
            left: -5px;
            top: 120px;
            width: 5px;
            height: 42px;
            background: linear-gradient(to left, #2a2a2a, #4a4a4a, #2a2a2a);
            border-radius: 4px 0 0 4px;
        }

        .btn-vol-down {
            position: absolute;
            left: -5px;
            top: 172px;
            width: 5px;
            height: 42px;
            background: linear-gradient(to left, #2a2a2a, #4a4a4a, #2a2a2a);
            border-radius: 4px 0 0 4px;
        }

        .screen-bezel {
            background: #000;
            border-radius: 42px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 780px;
            position: relative;
        }

        .status-bar {
            flex-shrink: 0;
            background: #000;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 22px 0 18px;
            position: relative;
        }

        .punch-hole {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 12px;
            height: 12px;
            background: #000;
            border-radius: 50%;
            border: 2px solid #1c1c1c;
            box-shadow: 0 0 0 1px #0a0a0a;
        }

        .status-time {
            font-size: 11px;
            font-weight: 700;
            color: #fff;
        }

        .status-icons {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .status-icons svg {
            width: 13px;
            height: 13px;
        }

        .topbar {
            flex-shrink: 0;
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px 12px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-logo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--red);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
            color: var(--bg);
            box-shadow: 2px 2px 0 var(--charcoal);
        }

        .brand-text h1 {
            font-size: 13px;
            font-weight: 700;
            color: var(--charcoal);
            line-height: 1;
        }

        .brand-text span {
            font-size: 10px;
            font-weight: 500;
            color: var(--charcoal);
            opacity: 0.5;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .topbar-icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            border: 2px solid var(--charcoal);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.18s;
        }

        .topbar-icon:hover {
            background: var(--gold);
        }

        .topbar-icon svg {
            width: 16px;
            height: 16px;
            stroke: var(--charcoal);
            fill: none;
        }

        .app-screen {
            flex: 1;
            background: var(--bg);
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: none;
            position: relative;
        }

        .app-screen::-webkit-scrollbar {
            display: none;
        }

        .page-title {
            text-align: center;
            font-size: 16px;
            font-weight: 800;
            color: var(--charcoal);
            margin: 16px 0 20px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .form-group {
            padding: 0 16px 12px;
        }

        .form-label {
            font-size: 11px;
            font-weight: 800;
            color: var(--charcoal);
            margin-bottom: 6px;
            display: block;
        }

        .form-input {
            width: 100%;
            background: white;
            border: 2px solid var(--charcoal);
            border-radius: 14px;
            padding: 12px 14px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            color: var(--charcoal);
            box-shadow: 3px 3px 0 var(--charcoal);
            outline: none;
            transition: 0.2s;
        }

        .form-input:focus {
            background: #F8F9FA;
            box-shadow: 1px 1px 0 var(--charcoal);
            transform: translate(2px, 2px);
        }

        .form-input::placeholder {
            color: var(--charcoal);
            opacity: 0.4;
            font-weight: 600;
        }

        select.form-input {
            appearance: none;
        }

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }

        .photo-upload {
            background: white;
            border: 2px dashed var(--charcoal);
            border-radius: 14px;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 16px 16px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .photo-upload input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .photo-upload svg {
            width: 40px;
            height: 40px;
            stroke: var(--charcoal);
            opacity: 0.5;
            fill: none;
            stroke-width: 2;
            z-index: 1;
            pointer-events: none;
        }

        #preview-img {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
            z-index: 2;
            border-radius: 12px;
        }

        .btn-submit {
            background: var(--green);
            color: white;
            font-size: 14px;
            font-weight: 800;
            padding: 14px;
            border-radius: 14px;
            border: 2px solid var(--charcoal);
            box-shadow: 3px 3px 0 var(--charcoal);
            cursor: pointer;
            width: calc(100% - 32px);
            margin: 10px 16px 40px;
            transition: transform 0.1s, box-shadow 0.1s;
        }

        .btn-submit:active {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0 var(--charcoal);
        }

        .notification {
            position: absolute;
            top: 100px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--green);
            color: white;
            padding: 12px 24px;
            border-radius: 14px;
            border: 2px solid var(--charcoal);
            box-shadow: 4px 4px 0 var(--charcoal);
            font-weight: 800;
            font-size: 12px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideDown 0.5s ease out, fadeOut 0.5s ease 2.5s forwards;
            pointer-events: none;
        }

        @keyframes slideDown {
            from {
                top: 80px;
                opacity: 0;
            }

            to {
                top: 100px;
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                visibility: hidden;
            }
        }

        .bottom-nav {
            flex-shrink: 0;
            height: var(--nav-h);
            background: var(--bg);
            border-top: 2.5px solid var(--charcoal);
            display: flex;
            align-items: center;
            justify-content: space-around;
            padding: 0 6px;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 10px;
            text-decoration: none;
            transition: background 0.15s;
        }

        .nav-item:hover {
            background: rgba(38, 70, 83, 0.07);
        }

        .nav-item svg {
            width: 20px;
            height: 20px;
            stroke: var(--charcoal);
            fill: none;
            stroke-width: 2;
        }

        .nav-item span {
            font-size: 9px;
            font-weight: 600;
            color: var(--charcoal);
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .nav-item.active svg {
            stroke: var(--red);
        }

        .nav-item.active span {
            color: var(--red);
        }

        .nav-fab {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--red);
            border: 2.5px solid var(--charcoal);
            box-shadow: 3px 3px 0 var(--charcoal);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-top: -20px;
            flex-shrink: 0;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .nav-fab:active {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0 var(--charcoal);
        }

        .nav-fab svg {
            width: 21px;
            height: 21px;
            stroke: white;
            fill: none;
            stroke-width: 2.2;
        }

        .home-indicator {
            flex-shrink: 0;
            background: #000;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .home-bar {
            width: 90px;
            height: 4px;
            background: #3a3a3a;
            border-radius: 3px;
        }

        .device-label {
            margin-top: 18px;
            color: rgba(255, 255, 255, 0.22);
            font-size: 10px;
            letter-spacing: 2.5px;
            text-transform: uppercase;
        }

        .back-btn {
            background: none;
            border: none;
            padding: 0;
            margin-right: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .back-btn svg {
            width: 20px;
            height: 20px;
            stroke: var(--charcoal);
            stroke-width: 2.5;
        }
    </style>
</head>

<body>
    <div class="android-device">
        <div class="btn-power"></div>
        <div class="btn-vol-up"></div>
        <div class="btn-vol-down"></div>
        <div class="screen-bezel">

            <div class="status-bar">
                <div class="punch-hole"></div>
                <span class="status-time">09:41</span>
                <div class="status-icons">
                    <svg viewBox="0 0 16 12" fill="white">
                        <rect x="0" y="8" width="3" height="4" rx="0.5" />
                        <rect x="4" y="5" width="3" height="7" rx="0.5" />
                        <rect x="8" y="2" width="3" height="10" rx="0.5" />
                        <rect x="12" y="0" width="3" height="12" rx="0.5" />
                    </svg>
                    <svg viewBox="0 0 16 12" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round">
                        <path d="M1 4.5C3.8 1.9 7 .5 8 .5s4.2 1.4 7 4" />
                        <path d="M3 7C4.8 5.3 6.5 4.5 8 4.5S11.2 5.3 13 7" />
                        <path d="M5.5 9.5C6.5 8.6 7.3 8 8 8s1.5.6 2.5 1.5" />
                        <circle cx="8" cy="11.5" r="0.8" fill="white" />
                    </svg>
                    <svg viewBox="0 0 20 12" fill="none">
                        <rect x="0.5" y="0.5" width="16" height="11" rx="2" stroke="white" stroke-width="1.2" />
                        <rect x="2" y="2" width="11" height="8" rx="1" fill="white" />
                        <path d="M17.5 4v4" stroke="white" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                </div>
            </div>

            <div class="topbar">
                <div class="brand">
                    <button class="back-btn" onclick="window.location.href='stok_crew.php'">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M15 18l-6-6 6-6" />
                        </svg>
                    </button>
                    <div class="brand-logo">S²</div>
                    <div class="brand-text">
                        <h1>SOLO SECOND THRIFT</h1>
                        <span>Crew</span>
                    </div>
                </div>
                <!-- Topbar Logout / Button Placeholder -->
            </div>

            <div class="app-screen">
                <?php if ($success): ?>
                    <div class="notification">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Barang ditambahkan!
                    </div>
                <?php endif; ?>

                <div class="page-title">TAMBAH STOK BARANG</div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="photo-upload" id="photo-container">
                        <svg viewBox="0 0 24 24">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                            <circle cx="12" cy="13" r="4"></circle>
                        </svg>
                        <img id="preview-img" src="" alt="Preview">
                        <input type="file" name="foto" id="foto" accept="image/*" onchange="previewFile()">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Barang</label>
                        <input type="text" name="nama_barang" class="form-input" placeholder="Tambahkan nama barang" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <select name="kategori" class="form-input" required>
                            <option value="CELANA PANJANG">Celana Panjang</option>
                            <option value="KAOS/POLO">Kaos / Polo</option>
                            <option value="KEMEJA/FLANEL">Kemeja / Flanel</option>
                            <option value="HOODIE/CN">Hoodie / CN</option>
                            <option value="JAKET/AIRISM">Jaket / Airism</option>
                            <option value="JAS/BLAZER">Jas / Blazer</option>
                            <option value="RAJUT/CROP">Rajut / Crop</option>
                            <option value="CHINOS">Chinos</option>
                            <option value="JEANS">Jeans</option>
                            <option value="CELANA PENDEK">Celana Pendek</option>
                            <option value="TRAINING">Training</option>
                            <option value="CELANA KANTOR">Celana Kantor</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Harga Jual</label>
                        <input type="number" name="harga" class="form-input" placeholder="Tambahkan harga jual" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Stok Awal</label>
                        <input type="number" name="stok" class="form-input" placeholder="Tambahkan stok awal" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Jumlah Barang Rusak</label>
                        <input type="number" name="barang_rusak" class="form-input" placeholder="0" value="0">
                    </div>

                    <button type="submit" class="btn-submit">Tambah</button>
                </form>

            </div><!-- /app-screen -->

            <nav class="bottom-nav">
                <a href="dasboard_crew.php" class="nav-item">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7" rx="1.5" />
                        <rect x="14" y="3" width="7" height="7" rx="1.5" />
                        <rect x="3" y="14" width="7" height="7" rx="1.5" />
                        <rect x="14" y="14" width="7" height="7" rx="1.5" />
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="stok_crew.php" class="nav-item active">
                    <svg viewBox="0 0 24 24">
                        <path d="M5 8h14M5 12h14M5 16h14" stroke-linecap="round" />
                    </svg>
                    <span>Stok</span>
                </a>
                <div class="nav-fab" onclick="window.location='transaksi.php'">
                    <svg viewBox="0 0 24 24">
                        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M3 6h18M16 10a4 4 0 01-8 0" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <a href="laporan.php" class="nav-item">
                    <svg viewBox="0 0 24 24">
                        <path d="M18 20V10M12 20V4M6 20v-6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span>Laporan</span>
                </a>
                <a href="profil.php" class="nav-item">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="7" r="4" />
                        <path d="M2 21v-1a8 8 0 0116 0v1" stroke-linecap="round" />
                    </svg>
                    <span>User</span>
                </a>
            </nav>

            <div class="home-indicator">
                <div class="home-bar"></div>
            </div>
        </div>
    </div>
    <div class="device-label">Solo Second Thrift &middot; Android Preview</div>

    <script>
        function previewFile() {
            const preview = document.getElementById('preview-img');
            const svgIcon = document.querySelector('.photo-upload svg');
            const file = document.getElementById('foto').files[0];
            const reader = new FileReader();

            reader.onloadend = function() {
                preview.src = reader.result;
                preview.style.display = 'block';
                svgIcon.style.opacity = '0';
            }

            if (file) {
                reader.readAsDataURL(file);
            } else {
                preview.src = "";
                preview.style.display = 'none';
                svgIcon.style.opacity = '0.5';
            }
        }
    </script>
</body>

</html>
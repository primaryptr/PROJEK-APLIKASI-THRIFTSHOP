<?php
session_start();
require_once 'koneksi.php';

// Cek apakah user sudah login dan role adalah crew
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'crew') {
    header("Location: login.php");
    exit();
}

$success = false;
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_barang = trim($_POST['nama_barang']);
    $harga = (int)$_POST['harga'];
    $stok = (int)$_POST['stok'];

    // Auto-generate kode_barang unik
    $result_last = $conn->query("SELECT id FROM barang ORDER BY id DESC LIMIT 1");
    $last_row = $result_last ? $result_last->fetch_assoc() : null;
    $next_id = $last_row ? ((int)$last_row['id'] + 1) : 1;
    $kode_barang = 'BRG' . str_pad($next_id, 3, '0', STR_PAD_LEFT);

    if (empty($nama_barang) || $harga <= 0 || $stok < 0) {
        $error_msg = 'Semua field wajib diisi dengan benar.';
    } else {
        $stmt = $conn->prepare("INSERT INTO barang (kode_barang, nama_barang, harga, stok) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $kode_barang, $nama_barang, $harga, $stok);
        if ($stmt->execute()) {
            $success = true;
        } else {
            $error_msg = 'Gagal menyimpan data: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Solo Second Thrift - Tambah Stok</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <style>
        :root {
            --bg: #FDFCF0;
            --charcoal: #264653;
            --red: #B23A48;
            --gold: #E9C46A;
            --green: #2A9D8F;
            --nav-h: 80px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

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
            font-family: 'Inter', sans-serif;
        }

        /* ===== ANDROID FRAME ===== */
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

        .btn-power { position: absolute; right: -5px; top: 140px; width: 5px; height: 55px; background: linear-gradient(to right, #2a2a2a, #4a4a4a, #2a2a2a); border-radius: 0 4px 4px 0; }
        .btn-vol-up { position: absolute; left: -5px; top: 120px; width: 5px; height: 42px; background: linear-gradient(to left, #2a2a2a, #4a4a4a, #2a2a2a); border-radius: 4px 0 0 4px; }
        .btn-vol-down { position: absolute; left: -5px; top: 172px; width: 5px; height: 42px; background: linear-gradient(to left, #2a2a2a, #4a4a4a, #2a2a2a); border-radius: 4px 0 0 4px; }

        .screen-bezel {
            background: var(--bg);
            border-radius: 42px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 780px;
            position: relative;
        }

        /* ===== STATUS BAR ===== */
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
        .punch-hole { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 12px; height: 12px; background: #000; border-radius: 50%; border: 2px solid #1c1c1c; }
        .status-time { font-size: 11px; font-weight: 700; color: #fff; }
        .status-icons { display: flex; align-items: center; gap: 4px; }
        .status-icons svg { width: 13px; height: 13px; }

        /* ===== TOPBAR ===== */
        .topbar {
            flex-shrink: 0;
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px 12px;
            box-shadow: 0 4px 4px rgba(0,0,0,0.1);
        }

        .brand { display: flex; align-items: center; gap: 10px; }

        .back-btn {
            background: none; border: none; padding: 0; cursor: pointer;
            display: flex; align-items: center; justify-content: center; margin-right: 2px;
        }
        .back-btn svg { width: 22px; height: 22px; stroke: var(--charcoal); stroke-width: 2.5; fill: none; }

        .brand-logo {
            width: 43px; height: 43px; border-radius: 50%;
            background: var(--red); display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 800; color: var(--bg);
            border: 2px solid var(--charcoal); box-shadow: 0 4px 4px rgba(0,0,0,0.25);
        }

        .brand-text h1 { font-size: 14px; font-weight: 800; color: var(--charcoal); line-height: 1.1; }
        .brand-text span { font-size: 11px; font-weight: 400; color: var(--red); }

        .topbar-logout {
            background: white; border: 1px solid #890D0D;
            border-radius: 15px; padding: 10px 18px;
            font-size: 13px; font-weight: 800; color: #890D0D;
            cursor: pointer; box-shadow: 0 4px 4px rgba(0,0,0,0.1);
            text-decoration: none; display: flex; align-items: center; gap: 6px;
        }
        .topbar-logout:hover { background: #fff0f0; }

        /* ===== APP SCREEN ===== */
        .app-screen {
            flex: 1; background: var(--bg);
            overflow-y: auto; overflow-x: hidden;
            scrollbar-width: none; position: relative;
        }
        .app-screen::-webkit-scrollbar { display: none; }

        /* ===== PAGE TITLE ===== */
        .page-title {
            font-size: 24px; font-weight: 800; color: var(--charcoal);
            text-align: center; margin: 18px 0 16px;
            text-shadow: 0 4px 4px rgba(0,0,0,0.15);
            border: 1px solid transparent;
        }

        /* ===== PHOTO UPLOAD AREA ===== */
        .photo-upload-area {
            margin: 0 18px 16px;
            background: #fff;
            border: 1px solid #000;
            border-radius: 10px;
            height: 145px;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .photo-upload-area input[type="file"] {
            position: absolute; top: 0; left: 0;
            width: 100%; height: 100%;
            opacity: 0; cursor: pointer; z-index: 3;
        }
        .photo-upload-area svg {
            width: 46px; height: 46px;
            fill: rgba(38, 70, 83, 0.45);
            z-index: 1; pointer-events: none;
        }
        .photo-upload-area span {
            font-size: 11px; font-weight: 700;
            color: rgba(38, 70, 83, 0.5);
            z-index: 1; pointer-events: none;
        }
        #preview-photo {
            position: absolute; top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            display: none; z-index: 2; border-radius: 9px;
        }

        /* ===== FORM ===== */
        .form-group { padding: 0 18px 12px; }

        .form-label {
            font-size: 15px; font-weight: 800;
            color: #000; margin-bottom: 8px; display: block;
        }

        .form-input {
            width: 100%;
            background: #fff;
            border: 2px solid rgba(38, 70, 83, 0.3);
            border-radius: 10px;
            padding: 14px 18px;
            font-family: 'Inter', inherit;
            font-size: 14px; font-weight: 500;
            color: #000;
            box-shadow: 0 4px 4px rgba(0,0,0,0.1);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            appearance: none;
        }
        .form-input:focus { border-color: var(--charcoal); }
        .form-input::placeholder { color: rgba(0,0,0,0.3); font-weight: 400; }

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }

        .select-wrapper { position: relative; }
        .select-wrapper::after {
            content: '';
            position: absolute; right: 18px; top: 50%;
            transform: translateY(-50%);
            width: 0; height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 6px solid #000;
            pointer-events: none;
        }

        /* ===== TAMBAH BUTTON ===== */
        .btn-tambah {
            display: block;
            background: var(--green);
            color: #fff;
            font-size: 15px; font-weight: 800;
            text-align: center;
            padding: 10px 0;
            border-radius: 17px;
            border: none;
            cursor: pointer;
            width: 214px;
            margin: 16px auto 40px;
            transition: opacity 0.2s, transform 0.1s;
        }
        .btn-tambah:active { transform: scale(0.97); opacity: 0.9; }

        /* ===== NOTIFICATION ===== */
        .notification {
            position: absolute; top: 12px; left: 50%;
            transform: translateX(-50%);
            background: var(--green); color: white;
            padding: 10px 20px; border-radius: 12px;
            border: 2px solid var(--charcoal);
            box-shadow: 3px 3px 0 var(--charcoal);
            font-weight: 800; font-size: 12px; z-index: 1000;
            display: flex; align-items: center; gap: 8px;
            white-space: nowrap;
            animation: slideDown 0.4s ease, fadeOut 0.5s ease 2.5s forwards;
            pointer-events: none;
        }
        .notification.error { background: var(--red); }
        @keyframes slideDown { from { top: -20px; opacity: 0; } to { top: 12px; opacity: 1; } }
        @keyframes fadeOut { to { opacity: 0; visibility: hidden; } }

        /* ===== BOTTOM NAV ===== */
        .bottom-nav {
            flex-shrink: 0;
            height: var(--nav-h);
            background: var(--bg);
            box-shadow: inset 0 4px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-around;
            padding: 0 8px;
        }

        .nav-item {
            display: flex; flex-direction: column; align-items: center;
            gap: 4px; cursor: pointer; padding: 6px 10px;
            border-radius: 10px; text-decoration: none;
            transition: background 0.15s;
        }
        .nav-item:hover { background: rgba(38,70,83,0.07); }
        .nav-item svg { width: 35px; height: 35px; stroke: none; fill: #000; }
        .nav-item span { font-size: 10px; font-weight: 700; color: #000; letter-spacing: 0.5px; }
        .nav-item.active svg { fill: var(--red); }
        .nav-item.active span { color: var(--red); }

        /* ===== HOME INDICATOR ===== */
        .home-indicator {
            flex-shrink: 0; background: var(--bg);
            height: 26px; display: flex; align-items: center; justify-content: center;
        }
        .home-bar { width: 90px; height: 4px; background: #ccc; border-radius: 3px; }

        .device-label {
            margin-top: 18px; color: rgba(255,255,255,0.22);
            font-size: 10px; letter-spacing: 2.5px; text-transform: uppercase;
        }
    </style>
</head>

<body>
    <div class="android-device">
        <div class="btn-power"></div>
        <div class="btn-vol-up"></div>
        <div class="btn-vol-down"></div>

        <div class="screen-bezel">

            <!-- STATUS BAR -->
            <div class="status-bar">
                <div class="punch-hole"></div>
                <span class="status-time">09:41</span>
                <div class="status-icons">
                    <svg viewBox="0 0 16 12" fill="white"><rect x="0" y="8" width="3" height="4" rx="0.5"/><rect x="4" y="5" width="3" height="7" rx="0.5"/><rect x="8" y="2" width="3" height="10" rx="0.5"/><rect x="12" y="0" width="3" height="12" rx="0.5"/></svg>
                    <svg viewBox="0 0 16 12" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round"><path d="M1 4.5C3.8 1.9 7 .5 8 .5s4.2 1.4 7 4"/><path d="M3 7C4.8 5.3 6.5 4.5 8 4.5S11.2 5.3 13 7"/><path d="M5.5 9.5C6.5 8.6 7.3 8 8 8s1.5.6 2.5 1.5"/><circle cx="8" cy="11.5" r="0.8" fill="white"/></svg>
                    <svg viewBox="0 0 20 12" fill="none"><rect x="0.5" y="0.5" width="16" height="11" rx="2" stroke="white" stroke-width="1.2"/><rect x="2" y="2" width="11" height="8" rx="1" fill="white"/><path d="M17.5 4v4" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
                </div>
            </div>

            <!-- TOPBAR -->
            <div class="topbar">
                <div class="brand">
                    <button class="back-btn" onclick="window.location.href='stokcrew.php'" title="Kembali">
                        <svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <div class="brand-logo">S²</div>
                    <div class="brand-text">
                        <h1>SOLO SECOND THRIFT</h1>
                        <span>CREW</span>
                    </div>
                </div>
                <a href="logout.php" class="topbar-logout">Logout</a>
            </div>

            <!-- APP SCREEN -->
            <div class="app-screen">

                <?php if ($success): ?>
                    <div class="notification">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Barang berhasil ditambahkan!
                    </div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="notification error"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>

                <div class="page-title">TAMBAH STOK BARANG</div>

                <form method="POST" enctype="multipart/form-data">

                    <!-- FOTO UPLOAD -->
                    <div class="photo-upload-area" id="photo-area">
                        <input type="file" name="foto" id="foto-input" accept="image/*" onchange="previewFoto(this)">
                        <svg viewBox="0 0 24 24" id="camera-icon">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                            <circle cx="12" cy="13" r="4"/>
                        </svg>
                        <span id="photo-hint">Ketuk untuk tambah foto</span>
                        <img id="preview-photo" src="" alt="Preview Foto">
                    </div>

                    <!-- NAMA BARANG -->
                    <div class="form-group">
                        <label class="form-label">Nama Barang</label>
                        <input type="text" name="nama_barang" class="form-input" placeholder="Levis 501 Original" required>
                    </div>

                    <!-- KATEGORI (visual only, not saved to DB) -->
                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <div class="select-wrapper">
                            <select name="kategori" class="form-input">
                                <option value="JEANS">Jeans</option>
                                <option value="KAOS/POLO">Kaos / Polo</option>
                                <option value="KEMEJA/FLANEL">Kemeja / Flanel</option>
                                <option value="HOODIE/CN">Hoodie / CN</option>
                                <option value="JAKET/AIRISM">Jaket / Airism</option>
                                <option value="JAS/BLAZER">Jas / Blazer</option>
                                <option value="RAJUT/CROP">Rajut / Crop</option>
                                <option value="CHINOS">Chinos</option>
                                <option value="CELANA PENDEK">Celana Pendek</option>
                                <option value="TRAINING">Training</option>
                                <option value="CELANA KANTOR">Celana Kantor</option>
                                <option value="CELANA PANJANG">Celana Panjang</option>
                            </select>
                        </div>
                    </div>

                    <!-- HARGA JUAL -->
                    <div class="form-group">
                        <label class="form-label">Harga Jual</label>
                        <input type="number" name="harga" class="form-input" placeholder="150000" required>
                    </div>

                    <!-- STOK AWAL -->
                    <div class="form-group">
                        <label class="form-label">Stok Awal</label>
                        <input type="number" name="stok" class="form-input" placeholder="2" required>
                    </div>

                    <button type="submit" class="btn-tambah">Tambah</button>
                </form>

            </div><!-- /app-screen -->

            <!-- BOTTOM NAV -->
            <nav class="bottom-nav">
                <!-- Katalog -->
                <a href="stokcrew.php" class="nav-item active">
                    <svg viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 7h25M5 12h25M5 17h15" stroke="#B23A48" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                    </svg>
                    <span>Katalog</span>
                </a>
                <!-- Transaksi -->
                <a href="#" class="nav-item">
                    <svg viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 4L3.5 8v16a2 2 0 002 2h20a2 2 0 002-2V8L25 4z" stroke="#070101" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        <path d="M3.5 8h28M22 13a5 5 0 01-9 0" stroke="#070101" stroke-width="2" stroke-linecap="round" fill="none"/>
                    </svg>
                    <span>Transaksi</span>
                </a>
                <!-- Home -->
                <a href="dashboardcrew.php" class="nav-item">
                    <svg viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 14L17.5 4 31 14v17a2 2 0 01-2 2H6a2 2 0 01-2-2z" stroke="#000" stroke-width="2" fill="none"/>
                        <path d="M13 33V19h9v14" stroke="#000" stroke-width="2" fill="none"/>
                    </svg>
                    <span>Home</span>
                </a>
                <!-- User -->
                <a href="#" class="nav-item">
                    <svg viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="17.5" cy="11" r="5" stroke="#101828" stroke-width="2.5" fill="none"/>
                        <path d="M5 33v-1.5a12.5 12.5 0 0125 0V33" stroke="#101828" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                    </svg>
                    <span>User</span>
                </a>
            </nav>

            <!-- HOME INDICATOR -->
            <div class="home-indicator">
                <div class="home-bar"></div>
            </div>

        </div><!-- /screen-bezel -->
    </div><!-- /android-device -->

    <div class="device-label">Solo Second Thrift &middot; Android Preview</div>

    <script>
        function previewFoto(input) {
            const preview = document.getElementById('preview-photo');
            const icon = document.getElementById('camera-icon');
            const hint = document.getElementById('photo-hint');
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onloadend = function() {
                    preview.src = reader.result;
                    preview.style.display = 'block';
                    icon.style.display = 'none';
                    hint.style.display = 'none';
                };
                reader.readAsDataURL(file);
            } else {
                preview.src = '';
                preview.style.display = 'none';
                icon.style.display = '';
                hint.style.display = '';
            }
        }

        // Auto-redirect setelah sukses tambah barang
        <?php if ($success): ?>
        setTimeout(() => { window.location.href = 'stokcrew.php'; }, 2500);
        <?php endif; ?>
    </script>
</body>

</html>

<?php
session_start();
require_once 'koneksi.php';

// Cek login dan role crew
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'crew') {
    header("Location: login.php");
    exit();
}

// Ambil ID dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: stokcrew.php");
    exit();
}

// Ambil data barang
$stmt = $conn->prepare("SELECT * FROM barang WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$barang = $result->fetch_assoc();
$stmt->close();

if (!$barang) {
    header("Location: stokcrew.php");
    exit();
}

$success = false;
$error_msg = '';

// Handle form submit (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_barang = trim($_POST['nama_barang']);
    $harga       = (int)$_POST['harga'];
    $stok        = (int)$_POST['stok'];

    if (empty($nama_barang) || $harga <= 0 || $stok < 0) {
        $error_msg = 'Semua field wajib diisi dengan benar.';
    } else {
        $upd = $conn->prepare("UPDATE barang SET nama_barang = ?, harga = ?, stok = ? WHERE id = ?");
        $upd->bind_param("siii", $nama_barang, $harga, $stok, $id);
        if ($upd->execute()) {
            $success = true;
            // Refresh data barang dari DB setelah update
            $barang['nama_barang'] = $nama_barang;
            $barang['harga']       = $harga;
            $barang['stok']        = $stok;
        } else {
            $error_msg = 'Gagal menyimpan: ' . $upd->error;
        }
        $upd->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Edit Stok Barang - Solo Second</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <style>
        :root {
            --bg: #FDFCF0;
            --charcoal: #264653;
            --red: #B23A48;
            --gold: #E9C46A;
            --green: #2A9D8F;
            --nav-h: 70px;
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
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* ANDROID FRAME */
        .android-device {
            position: relative; width: 393px;
            background: linear-gradient(160deg, #3a3a3a 0%, #1e1e1e 50%, #111 100%);
            border-radius: 54px; padding: 15px;
            box-shadow: 0 0 0 1.5px #4a4a4a, 0 0 0 3px #1a1a1a, 6px 6px 0 4px #000,
                0 40px 100px rgba(0,0,0,0.85), inset 0 2px 0 rgba(255,255,255,0.1);
        }
        .btn-power  { position:absolute; right:-5px; top:140px; width:5px; height:55px; background:linear-gradient(to right,#2a2a2a,#4a4a4a,#2a2a2a); border-radius:0 4px 4px 0; }
        .btn-vol-up { position:absolute; left:-5px;  top:120px; width:5px; height:42px; background:linear-gradient(to left,#2a2a2a,#4a4a4a,#2a2a2a);  border-radius:4px 0 0 4px; }
        .btn-vol-down { position:absolute; left:-5px; top:172px; width:5px; height:42px; background:linear-gradient(to left,#2a2a2a,#4a4a4a,#2a2a2a); border-radius:4px 0 0 4px; }

        .screen-bezel {
            background: var(--bg); border-radius: 42px; overflow: hidden;
            display: flex; flex-direction: column; height: 780px; position: relative;
        }

        /* STATUS BAR */
        .status-bar {
            flex-shrink: 0; background: #000; height: 34px;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 22px 0 18px; position: relative;
        }
        .punch-hole { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:12px; height:12px; background:#000; border-radius:50%; border:2px solid #1c1c1c; }
        .status-time { font-size:11px; font-weight:700; color:#fff; }
        .status-icons { display:flex; align-items:center; gap:4px; }
        .status-icons svg { width:13px; height:13px; }

        /* TOPBAR */
        .topbar {
            flex-shrink: 0; background: var(--bg);
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 18px 12px;
            box-shadow: 0 4px 4px rgba(0,0,0,0.1);
        }
        .brand { display:flex; align-items:center; gap:10px; }
        .back-btn { background:none; border:none; padding:0; cursor:pointer; display:flex; align-items:center; }
        .back-btn svg { width:22px; height:22px; stroke:var(--charcoal); stroke-width:2.5; fill:none; }
        .brand-logo {
            width:40px; height:40px; border-radius:50%; background:var(--red);
            display:flex; align-items:center; justify-content:center;
            font-size:14px; font-weight:800; color:var(--bg);
            box-shadow:2px 2px 0 var(--charcoal);
        }
        .brand-text h1 { font-size:13px; font-weight:700; color:var(--charcoal); line-height:1; }
        .brand-text span { font-size:10px; font-weight:500; color:var(--charcoal); opacity:0.5; text-transform:uppercase; letter-spacing:2px; }

        /* APP SCREEN */
        .app-screen {
            flex:1; background:var(--bg);
            overflow-y:auto; overflow-x:hidden; scrollbar-width:none; position:relative;
        }
        .app-screen::-webkit-scrollbar { display:none; }

        /* PAGE TITLE */
        .page-title {
            text-align:center; font-size:16px; font-weight:800; color:var(--charcoal);
            margin:16px 0 6px; letter-spacing:0.5px; text-transform:uppercase;
        }
        .kode-badge {
            text-align:center; font-size:10px; font-weight:700;
            color:var(--charcoal); opacity:0.5; margin-bottom:16px;
            letter-spacing:1px;
        }

        /* FORM */
        .form-group { padding:0 16px 14px; }
        .form-label { font-size:11px; font-weight:800; color:var(--charcoal); margin-bottom:6px; display:block; }
        .form-input {
            width:100%; background:white; border:2px solid var(--charcoal);
            border-radius:14px; padding:12px 14px;
            font-family:inherit; font-size:13px; font-weight:600; color:var(--charcoal);
            box-shadow:3px 3px 0 var(--charcoal); outline:none; transition:0.2s;
        }
        .form-input:focus { box-shadow:1px 1px 0 var(--charcoal); transform:translate(2px,2px); }
        .form-input::placeholder { color:var(--charcoal); opacity:0.4; font-weight:500; }
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance:none; margin:0; }
        input[type=number] { -moz-appearance:textfield; }

        /* INFO CARD */
        .info-card {
            margin:0 16px 16px;
            background:white; border:1.5px solid rgba(38,70,83,0.2);
            border-radius:14px; padding:14px;
            box-shadow:2px 2px 0 rgba(38,70,83,0.12);
        }
        .info-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
        .info-row:last-child { margin-bottom:0; }
        .info-key { font-size:10px; font-weight:700; color:var(--charcoal); opacity:0.6; text-transform:uppercase; letter-spacing:0.8px; }
        .info-val { font-size:12px; font-weight:800; color:var(--charcoal); }
        .stok-badge {
            font-size:12px; font-weight:800; padding:3px 10px;
            border-radius:6px; border:1.5px solid var(--charcoal);
        }
        .stok-ok   { background:var(--green); color:#fff; border-color:var(--green); }
        .stok-low  { background:#E9A03A; color:#fff; border-color:#E9A03A; }
        .stok-zero { background:var(--red);  color:#fff; border-color:var(--red); }

        /* BUTTONS */
        .btn-row { display:flex; gap:12px; padding:0 16px 16px; }
        .btn-save {
            flex:1; background:var(--green); color:white;
            font-size:13px; font-weight:800; padding:14px;
            border-radius:14px; border:2px solid var(--charcoal);
            box-shadow:3px 3px 0 var(--charcoal); cursor:pointer;
            transition:transform 0.1s, box-shadow 0.1s;
        }
        .btn-save:active { transform:translate(2px,2px); box-shadow:1px 1px 0 var(--charcoal); }
        .btn-cancel {
            flex:1; background:white; color:var(--charcoal);
            font-size:13px; font-weight:800; padding:14px;
            border-radius:14px; border:2px solid var(--charcoal);
            box-shadow:3px 3px 0 var(--charcoal); cursor:pointer;
            text-decoration:none; display:flex; align-items:center; justify-content:center;
            transition:transform 0.1s, box-shadow 0.1s;
        }
        .btn-cancel:active { transform:translate(2px,2px); box-shadow:1px 1px 0 var(--charcoal); }

        /* NOTIFICATION */
        .notification {
            position:absolute; top:12px; left:50%; transform:translateX(-50%);
            color:white; padding:10px 20px; border-radius:12px;
            border:2px solid var(--charcoal); box-shadow:3px 3px 0 var(--charcoal);
            font-weight:800; font-size:12px; z-index:1000;
            display:flex; align-items:center; gap:8px; white-space:nowrap;
            animation:slideDown 0.4s ease, fadeOut 0.5s ease 2.5s forwards;
            pointer-events:none;
        }
        .notif-success { background:var(--green); }
        .notif-error   { background:var(--red); }
        @keyframes slideDown { from{top:-20px;opacity:0;} to{top:12px;opacity:1;} }
        @keyframes fadeOut   { to{opacity:0;visibility:hidden;} }

        /* BOTTOM NAV */
        .bottom-nav {
            flex-shrink:0; height:var(--nav-h); background:var(--bg);
            border-top:2.5px solid var(--charcoal);
            display:flex; align-items:center; justify-content:space-around; padding:0 6px;
        }
        .nav-item { display:flex; flex-direction:column; align-items:center; gap:3px; cursor:pointer; padding:6px 12px; border-radius:10px; text-decoration:none; transition:background 0.15s; }
        .nav-item:hover { background:rgba(38,70,83,0.07); }
        .nav-item svg  { width:20px; height:20px; stroke:var(--charcoal); fill:none; stroke-width:2; }
        .nav-item span { font-size:9px; font-weight:600; color:var(--charcoal); text-transform:uppercase; letter-spacing:0.8px; }
        .nav-item.active svg  { stroke:var(--red); }
        .nav-item.active span { color:var(--red); }
        .nav-fab {
            width:48px; height:48px; border-radius:50%; background:var(--red);
            border:2.5px solid var(--charcoal); box-shadow:3px 3px 0 var(--charcoal);
            display:flex; align-items:center; justify-content:center;
            cursor:pointer; margin-top:-20px; flex-shrink:0;
        }
        .nav-fab svg { width:21px; height:21px; stroke:white; fill:none; stroke-width:2.2; }

        /* HOME INDICATOR */
        .home-indicator { flex-shrink:0; background:#000; height:26px; display:flex; align-items:center; justify-content:center; }
        .home-bar { width:90px; height:4px; background:#3a3a3a; border-radius:3px; }

        .device-label { margin-top:18px; color:rgba(255,255,255,0.22); font-size:10px; letter-spacing:2.5px; text-transform:uppercase; }
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
                        <span>Crew</span>
                    </div>
                </div>
                <a href="logout.php" style="background:white;border:1px solid #890D0D;border-radius:12px;padding:8px 14px;font-size:12px;font-weight:800;color:#890D0D;text-decoration:none;">Logout</a>
            </div>

            <!-- APP SCREEN -->
            <div class="app-screen">

                <?php if ($success): ?>
                    <div class="notification notif-success">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Data berhasil diperbarui!
                    </div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="notification notif-error"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>

                <div class="page-title">EDIT STOK BARANG</div>
                <div class="kode-badge">Kode: <?php echo htmlspecialchars($barang['kode_barang']); ?></div>

                <!-- INFO STOK SAAT INI -->
                <?php
                    $stok_now = (int)$barang['stok'];
                    $badge_class = $stok_now === 0 ? 'stok-zero' : ($stok_now < 5 ? 'stok-low' : 'stok-ok');
                ?>
                <div class="info-card">
                    <div class="info-row">
                        <span class="info-key">Nama Barang</span>
                        <span class="info-val"><?php echo htmlspecialchars(strtoupper($barang['nama_barang'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-key">Harga Saat Ini</span>
                        <span class="info-val">Rp<?php echo number_format($barang['harga'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-key">Stok Saat Ini</span>
                        <span class="stok-badge <?php echo $badge_class; ?>"><?php echo $stok_now; ?> Pcs</span>
                    </div>
                </div>

                <!-- FORM EDIT -->
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Nama Barang</label>
                        <input type="text" name="nama_barang" class="form-input"
                            value="<?php echo htmlspecialchars($barang['nama_barang']); ?>"
                            placeholder="Nama barang" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Harga Jual</label>
                        <input type="number" name="harga" class="form-input"
                            value="<?php echo (int)$barang['harga']; ?>"
                            placeholder="Harga jual" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stok" class="form-input"
                            value="<?php echo (int)$barang['stok']; ?>"
                            placeholder="Jumlah stok" required>
                    </div>

                    <div class="btn-row">
                        <button type="submit" class="btn-save">💾 Simpan</button>
                        <a href="stokcrew.php" class="btn-cancel">✕ Batal</a>
                    </div>
                </form>

            </div><!-- /app-screen -->

            <!-- BOTTOM NAV -->
            <nav class="bottom-nav">
                <a href="dashboardcrew.php" class="nav-item">
                    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                    <span>Dashboard</span>
                </a>
                <a href="stokcrew.php" class="nav-item active">
                    <svg viewBox="0 0 24 24"><path d="M5 8h14M5 12h14M5 16h14" stroke-linecap="round"/></svg>
                    <span>Stok</span>
                </a>
                <div class="nav-fab" onclick="window.location='transaksicrew.php'">
                    <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 6h18M16 10a4 4 0 01-8 0" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <a href="laporan_crew.php" class="nav-item">
                    <svg viewBox="0 0 24 24"><path d="M18 20V10M12 20V4M6 20v-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>Laporan</span>
                </a>
                <a href="profil_crew.php" class="nav-item">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M2 21v-1a8 8 0 0116 0v1" stroke-linecap="round"/></svg>
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
        <?php if ($success): ?>
        // Redirect ke stokcrew.php setelah sukses edit
        setTimeout(() => { window.location.href = 'stokcrew.php'; }, 2300);
        <?php endif; ?>
    </script>
</body>

</html>

<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'content_creator') {
    header("Location: login.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'Content Creator';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_barang  = $_POST['nama_barang'];
    $kategori     = $_POST['kategori'];
    $tipe         = $_POST['tipe'];
    $harga        = $_POST['harga'];
    $stok         = 1; // Default stok
    $barang_rusak = 0; // Default rusak
    
    // Gabung notes/minus ke nama jika diisi
    $minus = $_POST['minus'] ?? '';
    if (!empty($minus)) {
        $nama_barang .= ' (Minus: ' . $minus . ')';
    }

    $foto = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = time() . '_' . basename($_FILES['foto']['name']);
        $target_file = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
            $foto = $filename;
        }
    }

    $stmt = $conn->prepare("INSERT INTO barang (nama_barang, kategori, tipe, harga, stok, barang_rusak, foto) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiiss", $nama_barang, $kategori, $tipe, $harga, $stok, $barang_rusak, $foto);
    
    if ($stmt->execute()) {
        $success = true;
    } else {
        $error = "Gagal menyimpan data: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Solo Second Thrift - Studio Konten</title>
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
                radial-gradient(ellipse at 15% 50%, rgba(38, 70, 83, .45) 0%, transparent 55%),
                radial-gradient(ellipse at 85% 15%, rgba(178, 58, 72, .25) 0%, transparent 50%);
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
                0 40px 100px rgba(0, 0, 0, .85),
                inset 0 2px 0 rgba(255, 255, 255, .1);
        }

        .btn-power {
            position: absolute; right: -5px; top: 140px; width: 5px; height: 55px; background: linear-gradient(to right, #2a2a2a, #4a4a4a, #2a2a2a); border-radius: 0 4px 4px 0;
        }
        .btn-vol-up {
            position: absolute; left: -5px; top: 120px; width: 5px; height: 42px; background: linear-gradient(to left, #2a2a2a, #4a4a4a, #2a2a2a); border-radius: 4px 0 0 4px;
        }
        .btn-vol-dn {
            position: absolute; left: -5px; top: 172px; width: 5px; height: 42px; background: linear-gradient(to left, #2a2a2a, #4a4a4a, #2a2a2a); border-radius: 4px 0 0 4px;
        }
        .screen-bezel {
            background: #000; border-radius: 42px; overflow: hidden; display: flex; flex-direction: column; height: 850px;
        }

        .status-bar {
            flex-shrink: 0; background: #000; height: 34px; display: flex; align-items: center; justify-content: space-between; padding: 0 22px 0 18px; position: relative;
        }
        .punch-hole {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 12px; height: 12px; background: #000; border-radius: 50%; border: 2px solid #1c1c1c;
        }
        .status-time { font-size: 11px; font-weight: 700; color: #fff; }
        .status-icons { display: flex; align-items: center; gap: 4px; }
        .status-icons svg { width: 13px; height: 13px; }

        .topbar {
            flex-shrink: 0; background: var(--bg); display: flex; align-items: center; justify-content: space-between; padding: 14px 18px 12px;
        }
        .brand { display: flex; align-items: center; gap: 10px; }
        .back-btn {
            width: 40px; height: 40px; border-radius: 50%; background: rgba(38, 70, 83, 0.05); display: flex; align-items: center; justify-content: center; cursor: pointer; border: none; transition: background 0.15s; text-decoration: none;
        }
        .back-btn:active { background: rgba(38, 70, 83, 0.15); }
        .back-btn svg { width: 20px; height: 20px; stroke: var(--charcoal); fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
        .brand-text h1 { font-size: 14px; font-weight: 800; color: var(--charcoal); line-height: 1; }

        .app-screen {
            flex: 1; background: var(--bg); overflow-y: auto; overflow-x: hidden; scrollbar-width: none; padding: 16px; position: relative;
        }
        .app-screen::-webkit-scrollbar { display: none; }

        .upload-frame {
            border: 2.5px dashed var(--green); border-radius: 18px; background: rgba(42, 157, 143, 0.06); cursor: pointer; transition: background 0.2s, border-color 0.2s; overflow: hidden; margin-bottom: 24px; height: 200px; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative;
        }
        .upload-frame:active { background: rgba(42, 157, 143, 0.15); }
        #uploadPreviewImg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; display: none; }
        .upload-icon {
            width: 48px; height: 48px; border-radius: 14px; background: var(--green); display: flex; align-items: center; justify-content: center; margin-bottom: 12px; z-index: 10;
        }
        .upload-icon svg { width: 24px; height: 24px; fill: none; stroke: white; stroke-width: 2; }
        .upload-hint-title { font-size: 13px; font-weight: 800; color: var(--charcoal); z-index: 10; }
        .upload-hint-sub { font-size: 10px; font-weight: 600; color: var(--charcoal); opacity: 0.5; margin-top: 4px; z-index: 10; }

        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 10px; font-weight: 800; color: var(--charcoal); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; opacity: 0.7; }
        .form-input {
            width: 100%; padding: 14px 16px; background: white; border: 2px solid var(--charcoal); border-radius: 14px; font-family: inherit; font-size: 13px; font-weight: 700; color: var(--charcoal); box-shadow: 3px 3px 0 var(--charcoal); outline: none; transition: transform 0.15s, box-shadow 0.15s;
        }
        .form-input:focus { transform: translate(2px, 2px); box-shadow: 1px 1px 0 var(--charcoal); }

        .btn-copy {
            width: 100%; padding: 16px; background: var(--green); color: white; border: 2px solid var(--charcoal); border-radius: 16px; font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; cursor: pointer; box-shadow: 4px 4px 0 var(--charcoal); transition: transform 0.15s, box-shadow 0.15s; display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 24px; margin-bottom: 24px;
        }
        .btn-copy:active { transform: translate(2px, 2px); box-shadow: 2px 2px 0 var(--charcoal); }
        .btn-copy svg { width: 20px; height: 20px; fill: none; stroke: white; stroke-width: 2.5; }

        .home-indicator { flex-shrink: 0; background: #000; height: 26px; display: flex; align-items: center; justify-content: center; }
        .home-bar { width: 90px; height: 4px; background: #3a3a3a; border-radius: 3px; }
        
        .alert-box { padding: 12px 16px; border-radius: 10px; font-size: 11px; font-weight: 700; margin-bottom: 16px; text-align: center; }
        .alert-success { background: #d4edda; color: #155724; border: 2px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border: 2px solid #dc3545; }

    </style>
</head>

<body>

    <div class="android-device">
        <div class="btn-power"></div><div class="btn-vol-up"></div><div class="btn-vol-dn"></div>

        <div class="screen-bezel">

            <div class="status-bar">
                <div class="punch-hole"></div><span class="status-time">09:41</span>
                <div class="status-icons">
                    <svg viewBox="0 0 16 12" fill="white"><rect x="0" y="8" width="3" height="4" rx=".5" /><rect x="4" y="5" width="3" height="7" rx=".5" /><rect x="8" y="2" width="3" height="10" rx=".5" /><rect x="12" y="0" width="3" height="12" rx=".5" /></svg>
                    <svg viewBox="0 0 16 12" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round"><path d="M1 4.5C3.8 1.9 7 .5 8 .5s4.2 1.4 7 4" /><path d="M3 7C4.8 5.3 6.5 4.5 8 4.5S11.2 5.3 13 7" /><path d="M5.5 9.5C6.5 8.6 7.3 8 8 8s1.5.6 2.5 1.5" /><circle cx="8" cy="11.5" r=".8" fill="white" /></svg>
                    <svg viewBox="0 0 20 12" fill="none"><rect x=".5" y=".5" width="16" height="11" rx="2" stroke="white" stroke-width="1.2" /><rect x="2" y="2" width="11" height="8" rx="1" fill="white" /><path d="M17.5 4v4" stroke="white" stroke-width="1.5" stroke-linecap="round" /></svg>
                </div>
            </div>

            <div class="topbar">
                <div class="brand">
                    <a href="dasboard_creator.php" class="back-btn">
                        <svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6" /></svg>
                    </a>
                    <div class="brand-text">
                        <h1>STUDIO KONTEN</h1>
                    </div>
                </div>
            </div>

            <div class="app-screen">
                <?php if ($success): ?>
                    <div class="alert-box alert-success">Barang berhasil disimpan & masuk Katalog!</div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert-box alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="upload-frame" onclick="document.getElementById('uploadInput').click()">
                        <img id="uploadPreviewImg" src="" alt="Preview"/>
                        <div class="upload-icon" id="upIcon">
                            <svg viewBox="0 0 24 24">
                                <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z" />
                                <circle cx="12" cy="13" r="4" />
                            </svg>
                        </div>
                        <div class="upload-hint-title" id="upTitle">Ambil Foto / Pilih Galeri</div>
                        <div class="upload-hint-sub" id="upSub">Tambahkan foto produk ke katalog</div>
                        <input type="file" name="foto" id="uploadInput" accept="image/*" style="display:none" onchange="previewUpload(event)" required />
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Barang / Merk</label>
                        <input type="text" name="nama_barang" class="form-input" placeholder="Contoh: Crewneck Nike" required />
                    </div>

                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <select name="kategori" class="form-input" required>
                            <option value="">Pilih Kategori...</option>
                            <option value="Atasan">Atasan</option>
                            <option value="Bawahan">Bawahan</option>
                            <option value="Aksesoris">Aksesoris</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tipe</label>
                        <input type="text" name="tipe" class="form-input" placeholder="Contoh: Crewneck / Hoodie" required />
                    </div>

                    <div class="form-group">
                        <label class="form-label">Harga (Rp)</label>
                        <input type="number" name="harga" class="form-input" placeholder="Contoh: 85000" required />
                    </div>

                    <div class="form-group">
                        <label class="form-label">Detail Terselubung (Minus, dsb)</label>
                        <input type="text" name="minus" class="form-input" placeholder="Contoh: Lengan bernoda" />
                    </div>

                    <button type="submit" class="btn-copy">
                        <svg viewBox="0 0 24 24">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        SIMPAN BARANG
                    </button>
                </form>

            </div>

            <div class="home-indicator">
                <div class="home-bar"></div>
            </div>

        </div>
    </div>

    <script>
        function previewUpload(event) {
            const input = event.target;
            if(input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('uploadPreviewImg');
                    img.src = e.target.result;
                    img.style.display = 'block';
                    
                    document.getElementById('upIcon').style.display = 'none';
                    document.getElementById('upTitle').style.display = 'none';
                    document.getElementById('upSub').style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>

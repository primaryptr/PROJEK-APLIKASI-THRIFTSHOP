<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'owner') {
    header('Location: login.php');
    exit;
}

// ── DB ─────────────────────────────────────────────────────────────────────
$conn = new mysqli('localhost', 'root', '', 'thrift');
if ($conn->connect_error) die('Koneksi gagal: ' . $conn->connect_error);

// ── Auto-migrate: tambah kolom kategori & foto jika belum ada ───────────────
$cols = $conn->query("SHOW COLUMNS FROM barang")->fetch_all(MYSQLI_ASSOC);
$colNames = array_column($cols, 'Field');
if (!in_array('kategori', $colNames))
    $conn->query("ALTER TABLE barang ADD COLUMN `kategori` VARCHAR(100) NOT NULL DEFAULT 'Lainnya' AFTER `nama_barang`");
if (!in_array('foto', $colNames))
    $conn->query("ALTER TABLE barang ADD COLUMN `foto` VARCHAR(255) DEFAULT NULL AFTER `stok`");

// ── Ambil data barang yang akan diedit ──────────────────────────────────────
$editId = (int)($_GET['id'] ?? 0);
$barang = $conn->query("SELECT * FROM barang WHERE id=$editId")->fetch_assoc();
if (!$barang) {
    header('Location: stokowner.php');
    exit;
}

// ── Ambil kategori unik yang sudah ada ──────────────────────────────────────
$katRows   = $conn->query("SELECT DISTINCT kategori FROM barang ORDER BY kategori")->fetch_all(MYSQLI_ASSOC);
$existKat  = array_column($katRows, 'kategori');

// ── Handle POST: update barang ───────────────────────────────────────────
$successMsg = '';
$errorMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_barang'])) {
    $nama    = trim($_POST['nama_barang'] ?? '');
    $kat     = trim($_POST['kategori_value'] ?? '');
    $harga   = (float)($_POST['harga_jual'] ?? 0);
    $stok    = (int)($_POST['stok_awal'] ?? 0);

    if ($nama === '' || $kat === '' || $harga <= 0 || $stok < 0) {
        $errorMsg = 'Semua field wajib diisi dengan benar.';
    } else {
        $fotoSql = "";
        
        if (!empty($_FILES['foto_barang']['name'])) {
            $ext      = strtolower(pathinfo($_FILES['foto_barang']['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg','jpeg','png','webp','gif'];
            if (!in_array($ext, $allowed)) {
                $errorMsg = 'Format foto tidak didukung. Gunakan JPG, PNG, atau WEBP.';
            } elseif ($_FILES['foto_barang']['size'] > 5 * 1024 * 1024) {
                $errorMsg = 'Ukuran foto maksimal 5 MB.';
            } else {
                $filename = $barang['kode_barang'] . '_' . time() . '.' . $ext;
                $dest     = __DIR__ . '/uploads/' . $filename;
                if (move_uploaded_file($_FILES['foto_barang']['tmp_name'], $dest)) {
                    $fotoPath = 'uploads/' . $filename;
                    $fotoSql  = ", foto='" . $conn->real_escape_string($fotoPath) . "'";
                    
                    // Hapus foto lama jika ada
                    if ($barang['foto'] && file_exists(__DIR__ . '/' . $barang['foto'])) {
                        unlink(__DIR__ . '/' . $barang['foto']);
                    }
                } else {
                    $errorMsg = 'Gagal menyimpan foto baru. Periksa izin folder uploads/.';
                }
            }
        }

        if ($errorMsg === '') {
            $n  = $conn->real_escape_string($nama);
            $k  = $conn->real_escape_string($kat);

            $sql = "UPDATE barang SET nama_barang='$n', kategori='$k', harga=$harga, stok=$stok $fotoSql WHERE id=$editId";
            if ($conn->query($sql)) {
                $successMsg = "Data barang <strong>$nama</strong> berhasil diperbarui!";
                // Refresh data
                $barang = $conn->query("SELECT * FROM barang WHERE id=$editId")->fetch_assoc();
                $katRows  = $conn->query("SELECT DISTINCT kategori FROM barang ORDER BY kategori")->fetch_all(MYSQLI_ASSOC);
                $existKat = array_column($katRows, 'kategori');
            } else {
                $errorMsg = 'Gagal menyimpan: ' . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Edit Barang - Solo Second Thrift</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Secular+One&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #d7d5ca;
        }

        .phone-mockup {
            width: 430px;
            height: 932px;
            background-color: #FDFCF0;
            border: 18px solid #3A3A3A;
            border-radius: 55px;
            position: relative;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            overflow: hidden;
            font-family: 'Inter', sans-serif;
            display: flex;
            flex-direction: column;
        }

        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* ── Photo upload zone ── */
        .upload-zone {
            width: 100%;
            height: 150px;
            background: #F2F4F7;
            border: 2px dashed #D0D5DD;
            border-radius: 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: border-color .2s, background .2s;
            position: relative;
            overflow: hidden;
        }
        .upload-zone:hover { border-color: #388035; background: #f0fdf4; }
        .upload-zone.has-image { border-style: solid; border-color: #388035; }
        .upload-zone img.preview {
            width: 100%; height: 100%;
            object-fit: cover;
            position: absolute; inset: 0;
        }
        .upload-zone .overlay {
            position: absolute; inset: 0;
            background: rgba(0,0,0,0.4);
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            opacity: 0; transition: opacity .2s;
        }
        .upload-zone:hover .overlay { opacity: 1; }

        /* ── Input ── */
        .input-label {
            font-size: 12px; font-weight: 700;
            color: #374151; letter-spacing: .05em;
            margin-bottom: 6px; display: block;
        }
        .input-field {
            width: 100%; height: 48px;
            background: #FFFFFF;
            border: 1.5px solid #D0D5DD;
            border-radius: 10px;
            padding: 0 14px;
            font-family: 'Inter', sans-serif;
            font-size: 15px; color: #101828;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .input-field:focus { border-color: #388035; box-shadow: 0 0 0 3px rgba(56,128,53,.1); }

        /* ── Kategori chips ── */
        .kat-chip {
            display: inline-flex; align-items: center;
            padding: 5px 12px;
            border-radius: 20px;
            border: 1.5px solid #E5E7EB;
            font-size: 12px; font-weight: 700;
            cursor: pointer;
            background: #fff; color: #374151;
            transition: all .15s;
            white-space: nowrap;
        }
        .kat-chip:hover  { border-color: #388035; color: #388035; }
        .kat-chip.active { background: #388035; border-color: #388035; color: #fff; }

        @media (max-width: 480px) {
            .phone-mockup { width: 100%; height: 100vh; border: none; border-radius: 0; }
        }
    </style>
</head>
<body>

<div class="phone-mockup mx-auto">

    <!-- Status Bar -->
    <div class="w-full h-[44px] flex-none bg-[#FDFCF0] z-20 flex justify-between items-center px-6">
        <span class="text-[13px] font-bold" id="clockDisplay">09:41</span>
        <div class="flex gap-1.5 items-center">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M5 12.55a11 11 0 0114.08 0M1.42 9a16 16 0 0121.16 0M8.53 16.11a6 6 0 016.95 0M12 20h.01"/>
            </svg>
            <svg class="w-5 h-3.5" viewBox="0 0 24 14" fill="none">
                <rect x=".5" y=".5" width="20" height="13" rx="3.5" stroke="currentColor" stroke-width="1.2"/>
                <rect x="2" y="2" width="15" height="10" rx="2" fill="currentColor"/>
                <path d="M21.5 4.5v5c1-.5 1.5-1.2 1.5-2.5s-.5-2-1.5-2.5z" fill="currentColor"/>
            </svg>
        </div>
    </div>

    <!-- Header -->
    <div class="w-full h-[76px] flex-none bg-[#FDFCF0] shadow-[0px_3px_10px_rgba(0,0,0,0.1)] z-20 flex items-center justify-between px-[22px]">
        <div class="flex items-center gap-3">
            <a href="stokowner.php" class="w-9 h-9 rounded-full flex items-center justify-center hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5 text-[#264653]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="flex flex-col">
                <span class="font-extrabold text-[14px] text-[#264653] leading-tight">Edit Barang</span>
                <span class="font-['Secular_One'] text-[11px] text-[#B23A48] leading-tight">Katalog Stok</span>
            </div>
        </div>
        <a href="logout.php"
           class="h-[38px] px-4 bg-white border border-[#890D0D] rounded-[12px] flex items-center gap-1.5 text-[#890D0D] font-bold text-[13px] hover:bg-[#890D0D] hover:text-white transition-all duration-300 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Logout
        </a>
    </div>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto hide-scrollbar bg-[#F3F4F6]">
        <div class="px-4 pt-5 pb-4 flex flex-col gap-4">

            <!-- Alert Success -->
            <?php if ($successMsg): ?>
            <div class="bg-green-50 border border-green-200 rounded-2xl px-4 py-3 flex items-start gap-3">
                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
                </div>
                <div>
                    <p class="font-bold text-[13px] text-green-800">Berhasil Disimpan!</p>
                    <p class="text-[12px] text-green-600 mt-0.5"><?= $successMsg ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Alert Error -->
            <?php if ($errorMsg): ?>
            <div class="bg-red-50 border border-red-200 rounded-2xl px-4 py-3 flex items-start gap-3">
                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                </div>
                <div>
                    <p class="font-bold text-[13px] text-red-800">Terjadi Kesalahan</p>
                    <p class="text-[12px] text-red-600 mt-0.5"><?= htmlspecialchars($errorMsg) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Form Card -->
            <div class="bg-white rounded-[20px] p-5 shadow-[0_2px_12px_rgba(0,0,0,0.08)] flex flex-col gap-5">

                <form id="formBarang" method="POST" action="editbarangowner.php?id=<?= $editId ?>" enctype="multipart/form-data">

                    <!-- ── Foto Barang ── -->
                    <div>
                        <label class="input-label">FOTO BARANG</label>
                        <div class="upload-zone <?= $barang['foto'] ? 'has-image' : '' ?>" id="uploadZone" onclick="document.getElementById('fotoInput').click()">
                            <img id="imgPreview" class="preview <?= $barang['foto'] ? '' : 'hidden' ?>" src="<?= htmlspecialchars($barang['foto'] ?? '') ?>" alt="preview">
                            <div class="overlay" id="overlayChange">
                                <svg class="w-6 h-6 text-white mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="text-white text-[11px] font-bold">Ganti Foto</span>
                            </div>
                            <!-- Default state (no image) -->
                            <div id="uploadPlaceholder" class="flex flex-col items-center gap-2 <?= $barang['foto'] ? 'hidden' : '' ?>">
                                <div class="w-12 h-12 bg-[#DCFCE7] rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-[#16A34A]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5-5 5 5M12 5v11"/>
                                    </svg>
                                </div>
                                <p class="text-[12px] font-bold text-[#374151]">Tap untuk pilih foto</p>
                                <p class="text-[10px] text-gray-400">JPG, PNG, WEBP · maks 5 MB</p>
                            </div>
                        </div>
                        <input type="file" id="fotoInput" name="foto_barang"
                               accept="image/jpeg,image/png,image/webp,image/gif"
                               class="hidden" onchange="previewFoto(this)">
                        <!-- Nama file terpilih -->
                        <p id="namaFile" class="text-[11px] text-gray-400 mt-1.5 hidden"></p>
                    </div>

                    <!-- ── Nama Barang ── -->
                    <div>
                        <label for="nama_barang" class="input-label">NAMA BARANG</label>
                        <input type="text" id="nama_barang" name="nama_barang"
                               class="input-field" placeholder="Contoh: Levis 501 Original"
                               value="<?= htmlspecialchars($_POST['nama_barang'] ?? $barang['nama_barang']) ?>" required>
                    </div>

                    <!-- ── Kategori ── -->
                    <div>
                        <label class="input-label">KATEGORI</label>

                        <?php if (!empty($existKat)): ?>
                        <!-- Chip pilihan cepat dari kategori yang sudah ada -->
                        <div class="flex flex-wrap gap-2 mb-3" id="katChips">
                            <?php foreach ($existKat as $k): ?>
                            <button type="button" class="kat-chip"
                                    onclick="pilihKategori('<?= htmlspecialchars($k, ENT_QUOTES) ?>', this)">
                                <?= htmlspecialchars($k) ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <div class="relative">
                            <input type="text" id="kategori_input" name="kategori_value"
                                   class="input-field pr-10"
                                   placeholder="Ketik kategori (misal: Atasan, Celana...)"
                                   value="<?= htmlspecialchars($_POST['kategori_value'] ?? $barang['kategori']) ?>"
                                   required oninput="clearChipActive()">
                            <!-- Clear btn -->
                            <button type="button" id="btnClearKat"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 hover:text-red-400 transition-colors hidden"
                                    onclick="clearKategori()">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1.5">Pilih dari chip di atas atau ketik kategori baru</p>
                    </div>

                    <!-- ── Harga Jual ── -->
                    <div>
                        <label for="harga_jual" class="input-label">HARGA JUAL (Rp)</label>
                        <input type="number" id="harga_jual" name="harga_jual"
                               class="input-field" placeholder="150000" min="0"
                               value="<?= htmlspecialchars($_POST['harga_jual'] ?? $barang['harga']) ?>" required>
                    </div>

                    <!-- ── Stok Awal ── -->
                    <div>
                        <label for="stok_awal" class="input-label">STOK AWAL</label>
                        <input type="number" id="stok_awal" name="stok_awal"
                               class="input-field" placeholder="1" min="0"
                               value="<?= htmlspecialchars($_POST['stok_awal'] ?? $barang['stok']) ?>" required>
                    </div>

                    <input type="hidden" name="update_barang" value="1">

                </form>
            </div>

            <div class="h-2"></div>
        </div>
    </div><!-- /scrollable -->

    <!-- Sticky Save Button -->
    <div class="flex-none px-4 pb-3 pt-3 bg-[#F3F4F6] border-t border-gray-200/80">
        <button type="submit" form="formBarang"
                class="w-full h-[54px] bg-gradient-to-r from-[#388035] to-[#2d6b2b] rounded-[14px] flex items-center justify-center gap-2.5 hover:from-[#2c6529] hover:to-[#245222] active:scale-[0.98] transition-all shadow-[0_4px_16px_rgba(56,128,53,0.4)] font-bold text-white text-[15px] tracking-wide uppercase">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            Simpan Perubahan
        </button>
    </div>

    <!-- Bottom Nav -->
    <div class="w-full h-[64px] flex-none bg-[#FDFCF0] shadow-[inset_0px_4px_4px_rgba(0,0,0,0.08)] z-20 flex flex-row items-center justify-between px-6">

        <!-- Katalog (Active) -->
        <a href="stokowner.php" class="flex flex-col items-center justify-center w-14 gap-1 group">
            <div class="relative w-8 h-8 rounded-lg bg-[#B23A48] flex items-center justify-center shadow-sm">
                <svg class="w-[18px] h-[18px] text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <span class="font-bold text-[10px] text-[#B23A48]">Katalog</span>
        </a>

        <!-- Transaksi -->
        <a href="transaksiowner.php" class="flex flex-col items-center justify-center w-14 gap-1 group hover:-translate-y-0.5 transition-transform">
            <div class="w-8 h-8 flex items-center justify-center">
                <svg class="w-5 h-5 text-[#6B7280] group-hover:text-[#B23A48] transition-colors" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <span class="font-bold text-[10px] text-[#6B7280] group-hover:text-[#B23A48] transition-colors">Transaksi</span>
        </a>

        <!-- Home -->
        <a href="owner_dashboard.php" class="flex flex-col items-center justify-center w-14 gap-1 group hover:-translate-y-0.5 transition-transform">
            <div class="w-8 h-8 flex items-center justify-center">
                <svg class="w-6 h-6 text-[#6B7280] group-hover:text-[#B23A48] transition-colors" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </div>
            <span class="font-bold text-[10px] text-[#6B7280] group-hover:text-[#B23A48] transition-colors">Home</span>
        </a>

        <!-- Laporan -->
        <a href="laporanowner.php" class="flex flex-col items-center justify-center w-14 gap-1 group hover:-translate-y-0.5 transition-transform">
            <div class="w-8 h-8 flex items-center justify-center">
                <svg class="w-[22px] h-[22px] text-[#6B7280] group-hover:text-[#B23A48] transition-colors" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                </svg>
            </div>
            <span class="font-bold text-[10px] text-[#6B7280] group-hover:text-[#B23A48] transition-colors">Laporan</span>
        </a>

        <!-- User -->
        <a href="userowner.php" class="flex flex-col items-center justify-center w-14 gap-1 group hover:-translate-y-0.5 transition-transform">
            <div class="w-8 h-8 flex items-center justify-center">
                <svg class="w-6 h-6 text-[#6B7280] group-hover:text-[#B23A48] transition-colors" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <span class="font-bold text-[10px] text-[#6B7280] group-hover:text-[#B23A48] transition-colors">User</span>
        </a>

    </div>

    <!-- Bottom indicator -->
    <div class="w-full h-[24px] flex-none bg-[#FDFCF0] flex justify-center items-center">
        <div class="w-[120px] h-[5px] bg-[#101828] opacity-15 rounded-full"></div>
    </div>

</div><!-- /phone-mockup -->

<script>
// ── Clock ─────────────────────────────────────────────────────────────────
(function tick() {
    const el = document.getElementById('clockDisplay');
    if (el) {
        const n = new Date();
        el.textContent = String(n.getHours()).padStart(2,'0') + ':' + String(n.getMinutes()).padStart(2,'0');
    }
    setTimeout(tick, 15000);
})();

// ── Preview foto ──────────────────────────────────────────────────────────
function previewFoto(input) {
    const zone    = document.getElementById('uploadZone');
    const preview = document.getElementById('imgPreview');
    const ph      = document.getElementById('uploadPlaceholder');
    const namaEl  = document.getElementById('namaFile');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            ph.classList.add('hidden');
            zone.classList.add('has-image');
        };
        reader.readAsDataURL(input.files[0]);

        namaEl.textContent = '📎 ' + input.files[0].name;
        namaEl.classList.remove('hidden');
    }
}

// ── Pilih kategori dari chip ──────────────────────────────────────────────
function pilihKategori(nama, el) {
    document.getElementById('kategori_input').value = nama;
    document.getElementById('btnClearKat').classList.remove('hidden');
    // update chip active
    document.querySelectorAll('.kat-chip').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
}

function clearChipActive() {
    const val = document.getElementById('kategori_input').value;
    document.getElementById('btnClearKat').classList.toggle('hidden', val === '');
    document.querySelectorAll('.kat-chip').forEach(c => c.classList.remove('active'));
}

function clearKategori() {
    document.getElementById('kategori_input').value = '';
    document.getElementById('btnClearKat').classList.add('hidden');
    document.querySelectorAll('.kat-chip').forEach(c => c.classList.remove('active'));
}

// Inisialisasi: cek nilai input kategori saat load (misal setelah POST error)
document.addEventListener('DOMContentLoaded', () => {
    const val = document.getElementById('kategori_input').value;
    if (val) {
        document.getElementById('btnClearKat').classList.remove('hidden');
        document.querySelectorAll('.kat-chip').forEach(c => {
            if (c.textContent.trim() === val) c.classList.add('active');
        });
    }

    <?php if ($successMsg): ?>
    // Auto scroll to top after success
    document.querySelector('.flex-1.overflow-y-auto').scrollTop = 0;
    <?php endif; ?>
});
</script>
</body>
</html>

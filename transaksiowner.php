<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'owner') {
    header('Location: login.php');
    exit;
}

// ── Database connection ──────────────────────────────────────────────────────
$conn = new mysqli('localhost', 'root', '', 'thrift');
if ($conn->connect_error) die('Koneksi gagal: ' . $conn->connect_error);

// ── Auto-create tables ───────────────────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS `barang` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_barang` VARCHAR(50) NOT NULL UNIQUE,
    `nama_barang` VARCHAR(150) NOT NULL,
    `harga` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `stok` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS `transaksi` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_transaksi` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `total_bayar` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `fk_trx_user` (`user_id`),
    CONSTRAINT `fk_trx_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS `transaksi_detail` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `transaksi_id` INT NOT NULL,
    `kode_barang` VARCHAR(50) NOT NULL,
    `nama_barang` VARCHAR(150) NOT NULL,
    `harga_satuan` DECIMAL(12,2) NOT NULL,
    `qty` INT NOT NULL DEFAULT 1,
    `subtotal` DECIMAL(12,2) NOT NULL,
    KEY `fk_detail_trx` (`transaksi_id`),
    CONSTRAINT `fk_detail_trx` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed demo barang
$cnt = $conn->query("SELECT COUNT(*) c FROM barang")->fetch_assoc()['c'];
if ($cnt == 0) {
    $conn->query("INSERT INTO barang (kode_barang,nama_barang,harga,stok) VALUES
        ('BRG001','Kaos Vintage Polo',125000,10),
        ('BRG002','Celana Jeans Retro',200000,5),
        ('BRG003','Jaket Denim Classic',350000,3),
        ('BRG004','Kemeja Flanel Kotak',175000,8)");
}

// ── AJAX: cari barang ────────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'cari_barang') {
    header('Content-Type: application/json');
    $kode = $conn->real_escape_string(trim($_GET['kode'] ?? ''));
    $row  = $conn->query("SELECT * FROM barang WHERE kode_barang='$kode'")->fetch_assoc();
    echo $row ? json_encode(['ok'=>true,'data'=>$row])
              : json_encode(['ok'=>false,'msg'=>'Barang tidak ditemukan']);
    exit;
}

// ── POST: simpan transaksi ───────────────────────────────────────────────────
$successMsg = ''; $errorMsg = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_transaksi'])) {
    $kode = $conn->real_escape_string(trim($_POST['kode_barang'] ?? ''));
    $qty  = (int)($_POST['qty'] ?? 1);
    if ($kode==='' || $qty<1) {
        $errorMsg = 'Kode barang dan jumlah harus diisi dengan benar.';
    } else {
        $brg = $conn->query("SELECT * FROM barang WHERE kode_barang='$kode'")->fetch_assoc();
        if (!$brg)             $errorMsg = "Barang kode '$kode' tidak ditemukan.";
        elseif ($brg['stok']<$qty) $errorMsg = "Stok tidak cukup. Tersedia: {$brg['stok']}";
        else {
            $kode_trx = 'TRX'.date('YmdHis').rand(10,99);
            $uid      = (int)$_SESSION['user_id'];
            $subtotal = $brg['harga'] * $qty;
            $conn->begin_transaction();
            try {
                $conn->query("INSERT INTO transaksi(kode_transaksi,user_id,total_bayar) VALUES('$kode_trx',$uid,$subtotal)");
                $tid  = $conn->insert_id;
                $nama = $conn->real_escape_string($brg['nama_barang']);
                $harga= $brg['harga'];
                $conn->query("INSERT INTO transaksi_detail(transaksi_id,kode_barang,nama_barang,harga_satuan,qty,subtotal)
                              VALUES($tid,'$kode','$nama',$harga,$qty,$subtotal)");
                $conn->query("UPDATE barang SET stok=stok-$qty WHERE kode_barang='$kode'");
                $conn->commit();
                $successMsg = "Transaksi <strong>$kode_trx</strong> berhasil! Total: Rp ".number_format($subtotal,0,',','.');
            } catch(Exception $e) {
                $conn->rollback();
                $errorMsg = 'Gagal: '.$e->getMessage();
            }
        }
    }
}

// ── Ambil 5 transaksi terakhir ───────────────────────────────────────────────
$riwayat = $conn->query("
    SELECT t.kode_transaksi, t.total_bayar, t.created_at,
           GROUP_CONCAT(d.nama_barang SEPARATOR ', ') AS items
    FROM transaksi t
    JOIN transaksi_detail d ON d.transaksi_id=t.id
    WHERE t.user_id=".((int)$_SESSION['user_id'])."
    GROUP BY t.id
    ORDER BY t.created_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Transaksi - Solo Second Thrift</title>
    <meta name="description" content="Halaman transaksi penjualan owner Solo Second Thrift">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Secular+One&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #d7d5ca;
            font-family: 'Inter', sans-serif;
        }

        /* ── Phone shell ── */
        .phone-mockup {
            width: 430px;
            height: 932px;
            background-color: #FDFCF0;
            border: 18px solid #3A3A3A;
            border-radius: 55px;
            position: relative;
            box-shadow: 0 30px 80px rgba(0,0,0,0.18);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* ── Scrollbar hidden ── */
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* ══ View system ══ */
        .view { display: none; flex-direction: column; flex: 1; min-height: 0; overflow: hidden; }
        .view.active { display: flex; }

        /* ── Form inputs ── */
        .form-label {
            font-size: 11px; font-weight: 700;
            letter-spacing: .08em; color: #6B7280;
            margin-bottom: 8px; display: block;
        }
        .form-input {
            width: 100%;
            border: 1.5px solid #E5E7EB;
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            color: #111827;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            background: #fff;
        }
        .form-input:focus { border-color: #388035; box-shadow: 0 0 0 3px rgba(56,128,53,.12); }
        .form-input::placeholder { color: #9CA3AF; }

        /* ── Action cards (menu) ── */
        .action-card {
            border-radius: 18px;
            transition: transform .2s, box-shadow .2s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .action-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.12); }
        .action-card:active { transform: scale(.97); }

        /* ── Riwayat badge ── */
        .badge-success { background: #DCFCE7; color: #166534; }
        .badge-warning { background: #FEF9C3; color: #854D0E; }

        /* ── Toast ── */
        #toast {
            position: absolute;
            bottom: 110px; left: 50%;
            transform: translateX(-50%) translateY(16px);
            opacity: 0; transition: opacity .4s, transform .4s;
            z-index: 200; min-width: 260px; max-width: 340px;
            pointer-events: none; text-align: center;
        }
        #toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

        /* ── Slide animations ── */
        @keyframes slideInRight {
            from { transform: translateX(105%); opacity: 0; }
            to   { transform: translateX(0);    opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0);    opacity: 1; }
            to   { transform: translateX(105%); opacity: 0; }
        }
        @keyframes slideInLeft {
            from { transform: translateX(-105%); opacity: 0; }
            to   { transform: translateX(0);     opacity: 1; }
        }
        .slide-in-right { animation: slideInRight .3s cubic-bezier(.4,0,.2,1) forwards; }
        .slide-out-right { animation: slideOutRight .3s cubic-bezier(.4,0,.2,1) forwards; }
        .slide-in-left  { animation: slideInLeft  .3s cubic-bezier(.4,0,.2,1) forwards; }

        /* ── Qty stepper ── */
        .qty-btn {
            width: 36px; height: 36px;
            border-radius: 10px;
            border: 1.5px solid #E5E7EB;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; font-weight: 700; color: #374151;
            cursor: pointer; background: #fff;
            transition: background .15s, border-color .15s;
            flex-shrink: 0;
        }
        .qty-btn:hover { background: #F3F4F6; border-color: #388035; color: #388035; }

        /* ── Riwayat row ── */
        .riwayat-row {
            background: #fff;
            border-radius: 14px;
            padding: 12px 14px;
            display: flex; align-items: center;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            gap: 12px;
        }

        @media (max-width: 480px) {
            .phone-mockup { width: 100%; height: 100vh; border: none; border-radius: 0; }
        }
    </style>
</head>
<body>

<div class="phone-mockup mx-auto" id="phoneMockup">

<!-- ══════════════════ STATUS BAR (shared) ══════════════════════════════════════ -->
<div class="w-full h-[44px] flex-none bg-[#FDFCF0] z-30 flex justify-between items-center px-6">
    <span id="clockDisplay" class="text-[13px] font-bold">09:41</span>
    <div class="flex gap-2 items-center">
        <!-- WiFi icon -->
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M5 12.55a11 11 0 0114.08 0M1.42 9a16 16 0 0121.16 0M8.53 16.11a6 6 0 016.95 0M12 20h.01"/>
        </svg>
        <!-- Battery icon -->
        <svg class="w-5 h-3.5" viewBox="0 0 24 14" fill="none">
            <rect x="0.5" y="0.5" width="20" height="13" rx="3.5" stroke="currentColor" stroke-width="1.2"/>
            <rect x="2" y="2" width="15" height="10" rx="2" fill="currentColor"/>
            <path d="M21.5 4.5v5c1-.5 1.5-1.2 1.5-2.5s-.5-2-1.5-2.5z" fill="currentColor"/>
        </svg>
    </div>
</div>

<!-- ══════════════════ VIEW 1 — Menu Utama ══════════════════════════════════════ -->
<div id="viewMenu" class="view active">

    <!-- Header -->
    <div class="w-full h-[76px] flex-none bg-[#FDFCF0] shadow-[0px_3px_10px_rgba(0,0,0,0.1)] z-20 flex items-center justify-between px-5">
        <div class="flex items-center gap-3">
            <div class="w-[42px] h-[42px] bg-gradient-to-br from-[#B23A48] to-[#8B2635] border-2 border-[#264653] shadow-md rounded-full flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <div class="flex flex-col">
                <span class="font-extrabold text-[14px] text-[#264653] leading-tight">SOLO SECOND THRIFT</span>
                <span class="font-['Secular_One'] text-[11px] text-[#B23A48] leading-tight">OWNER</span>
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

            <!-- Greeting -->
            <div>
                <p class="text-[12px] text-gray-500 font-medium">Selamat datang 👋</p>
                <h2 class="text-[18px] font-extrabold text-[#111827]">Kelola Transaksi</h2>
            </div>

            <!-- ── CARD: Transaksi Baru ── -->
            <button id="btnTransaksiBaru"
                    class="action-card w-full bg-gradient-to-br from-[#388035] to-[#2a6128] text-white p-5 flex items-center justify-between group shadow-[0_6px_20px_rgba(56,128,53,0.35)]">
                <div class="flex flex-col items-start gap-1">
                    <span class="text-[11px] font-semibold uppercase tracking-widest text-green-200">Penjualan Baru</span>
                    <span class="text-[22px] font-extrabold leading-tight">Transaksi Baru</span>
                    <span class="text-[12px] text-green-200 mt-1">Tap untuk mulai input barang</span>
                </div>
                <div class="w-[60px] h-[60px] bg-white/15 rounded-2xl flex items-center justify-center group-hover:bg-white/25 transition-colors">
                    <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
            </button>

            <!-- ── ROW: Cek Stok + Lapor Rusak ── -->
            <div class="grid grid-cols-2 gap-3">

                <!-- Cek Stok -->
                <a href="stokowner.php"
                   class="action-card bg-white p-4 flex flex-col gap-3 shadow-[0_2px_8px_rgba(0,0,0,0.07)]">
                    <div class="w-[44px] h-[44px] bg-[#DCFCE7] rounded-[14px] flex items-center justify-center">
                        <svg class="w-6 h-6 text-[#16A34A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold text-[14px] text-[#111827]">Cek Stok</p>
                        <p class="text-[11px] text-gray-400 mt-0.5 leading-tight">Lihat ketersediaan barang</p>
                    </div>
                    <div class="flex items-center gap-1 text-[11px] text-[#16A34A] font-semibold">
                        <span>Buka Katalog</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>

                <!-- Lapor Rusak -->
                <button class="action-card bg-white p-4 flex flex-col gap-3 shadow-[0_2px_8px_rgba(0,0,0,0.07)] text-left">
                    <div class="w-[44px] h-[44px] bg-[#FEE2E2] rounded-[14px] flex items-center justify-center">
                        <svg class="w-6 h-6 text-[#DC2626]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold text-[14px] text-[#111827]">Lapor Rusak</p>
                        <p class="text-[11px] text-gray-400 mt-0.5 leading-tight">Laporkan barang cacat/rusak</p>
                    </div>
                    <div class="flex items-center gap-1 text-[11px] text-[#DC2626] font-semibold">
                        <span>Buat Laporan</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </button>

            </div>

            <!-- ── Stats row ── -->
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white rounded-[16px] p-4 shadow-[0_2px_8px_rgba(0,0,0,0.06)]">
                    <p class="text-[11px] text-gray-400 font-medium">Transaksi Hari Ini</p>
                    <?php
                    $todayCount = $conn->query("SELECT COUNT(*) c FROM transaksi WHERE DATE(created_at)=CURDATE() AND user_id=".((int)$_SESSION['user_id']))->fetch_assoc()['c'];
                    ?>
                    <p class="text-[24px] font-extrabold text-[#388035] mt-1"><?= $todayCount ?></p>
                    <p class="text-[11px] text-gray-400">transaksi selesai</p>
                </div>
                <div class="bg-white rounded-[16px] p-4 shadow-[0_2px_8px_rgba(0,0,0,0.06)]">
                    <p class="text-[11px] text-gray-400 font-medium">Total Pendapatan</p>
                    <?php
                    $todayTotal = $conn->query("SELECT COALESCE(SUM(total_bayar),0) s FROM transaksi WHERE DATE(created_at)=CURDATE() AND user_id=".((int)$_SESSION['user_id']))->fetch_assoc()['s'];
                    ?>
                    <p class="text-[18px] font-extrabold text-[#264653] mt-1 leading-tight">Rp <?= number_format($todayTotal,0,',','.') ?></p>
                    <p class="text-[11px] text-gray-400">hari ini</p>
                </div>
            </div>

            <!-- ── Riwayat Transaksi ── -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-[14px] text-[#111827]">Riwayat Terakhir</h3>
                    <span class="text-[11px] text-[#388035] font-semibold">5 terakhir</span>
                </div>

                <?php if (empty($riwayat)): ?>
                <div class="bg-white rounded-[16px] p-6 text-center shadow-[0_2px_8px_rgba(0,0,0,0.06)]">
                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-[13px] text-gray-400 font-medium">Belum ada transaksi</p>
                    <p class="text-[11px] text-gray-300 mt-1">Tap "Transaksi Baru" untuk memulai</p>
                </div>
                <?php else: ?>
                <div class="flex flex-col gap-2">
                    <?php foreach ($riwayat as $r): ?>
                    <div class="riwayat-row">
                        <div class="w-9 h-9 bg-[#DCFCE7] rounded-[11px] flex items-center justify-center flex-shrink-0">
                            <svg class="w-[18px] h-[18px] text-[#16A34A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-[12px] text-[#111827] truncate"><?= htmlspecialchars($r['kode_transaksi']) ?></p>
                            <p class="text-[11px] text-gray-400 truncate mt-0.5"><?= htmlspecialchars($r['items']) ?></p>
                        </div>
                        <div class="flex flex-col items-end flex-shrink-0">
                            <p class="font-extrabold text-[13px] text-[#388035]">Rp <?= number_format($r['total_bayar'],0,',','.') ?></p>
                            <p class="text-[10px] text-gray-400 mt-0.5"><?= date('d/m H:i', strtotime($r['created_at'])) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- bottom spacer -->
            <div class="h-2"></div>

        </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="w-full h-[72px] flex-none bg-[#FDFCF0] shadow-[0px_-2px_12px_rgba(0,0,0,0.08)] z-20 flex items-center justify-around px-2">

        <!-- Katalog -->
        <a href="stokowner.php" class="flex flex-col items-center gap-1 w-14 group">
            <div class="w-8 h-8 rounded-[10px] border-2 border-[#9CA3AF] group-hover:border-[#388035] flex items-center justify-center transition-colors">
                <svg class="w-[18px] h-[18px] text-[#6B7280] group-hover:text-[#388035] transition-colors" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <span class="text-[10px] font-bold text-[#6B7280] group-hover:text-[#388035] transition-colors">Katalog</span>
        </a>

        <!-- Transaksi (Active) -->
        <a href="transaksiowner.php" class="flex flex-col items-center gap-1 w-14">
            <div class="w-8 h-8 rounded-[10px] bg-[#B23A48] flex items-center justify-center shadow-[0_3px_8px_rgba(178,58,72,0.4)]">
                <svg class="w-[18px] h-[18px] text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <span class="text-[10px] font-bold text-[#B23A48]">Transaksi</span>
        </a>

        <!-- Home -->
        <a href="owner_dashboard.php" class="flex flex-col items-center gap-1 w-14 group">
            <div class="w-8 h-8 flex items-center justify-center">
                <svg class="w-6 h-6 text-[#6B7280] group-hover:text-[#388035] transition-colors" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </div>
            <span class="text-[10px] font-bold text-[#6B7280] group-hover:text-[#388035] transition-colors">Home</span>
        </a>

        <!-- Laporan -->
        <a href="laporanowner.php" class="flex flex-col items-center gap-1 w-14 group">
            <div class="w-8 h-8 flex items-center justify-center">
                <svg class="w-[22px] h-[22px] text-[#6B7280] group-hover:text-[#388035] transition-colors" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                </svg>
            </div>
            <span class="text-[10px] font-bold text-[#6B7280] group-hover:text-[#388035] transition-colors">Laporan</span>
        </a>

        <!-- User -->
        <a href="userowner.php" class="flex flex-col items-center gap-1 w-14 group">
            <div class="w-8 h-8 flex items-center justify-center">
                <svg class="w-6 h-6 text-[#6B7280] group-hover:text-[#388035] transition-colors" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <span class="text-[10px] font-bold text-[#6B7280] group-hover:text-[#388035] transition-colors">User</span>
        </a>

    </div>

    <!-- Bottom bar indicator -->
    <div class="w-full h-[28px] flex-none bg-[#FDFCF0] flex justify-center items-center">
        <div class="w-[120px] h-[5px] bg-[#101828] opacity-15 rounded-full"></div>
    </div>

</div><!-- /viewMenu -->


<!-- ══════════════════ VIEW 2 — Input Transaksi ═════════════════════════════════ -->
<div id="viewTransaksi" class="view">

    <!-- Header green -->
    <div class="w-full h-[64px] flex-none bg-gradient-to-r from-[#388035] to-[#2d6b2b] z-20 flex items-center justify-between px-4 shadow-[0_3px_12px_rgba(56,128,53,0.3)]">
        <div class="flex items-center gap-2">
            <button id="btnBack"
                    class="w-9 h-9 rounded-full flex items-center justify-center hover:bg-white/20 transition-colors"
                    aria-label="Kembali">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <div>
                <h1 class="font-extrabold text-[18px] text-white leading-tight">Penjualan</h1>
                <p class="text-[11px] text-green-200 leading-none">Input transaksi baru</p>
            </div>
        </div>
        <!-- Receipt icon button -->
        <button class="w-10 h-10 bg-white/15 rounded-[12px] flex items-center justify-center hover:bg-white/25 transition-colors">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </button>
    </div>

    <!-- Scrollable form area -->
    <div class="flex-1 overflow-y-auto hide-scrollbar bg-[#F3F4F6]">
        <div class="px-4 pt-5 pb-4 flex flex-col gap-4">

            <!-- Section title -->
            <div class="flex items-center gap-2">
                <div class="w-1 h-5 bg-[#388035] rounded-full"></div>
                <h2 class="font-bold text-[16px] text-[#111827]">Input Transaksi</h2>
            </div>

            <!-- Alert messages -->
            <?php if ($successMsg): ?>
            <div class="bg-green-50 border border-green-200 rounded-2xl px-4 py-3 flex items-start gap-3">
                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
                </div>
                <div>
                    <p class="font-bold text-[12px] text-green-800">Transaksi Berhasil!</p>
                    <p class="text-[11px] text-green-600 mt-0.5"><?= $successMsg ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($errorMsg): ?>
            <div class="bg-red-50 border border-red-200 rounded-2xl px-4 py-3 flex items-start gap-3">
                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                </div>
                <div>
                    <p class="font-bold text-[12px] text-red-800">Terjadi Kesalahan</p>
                    <p class="text-[11px] text-red-600 mt-0.5"><?= htmlspecialchars($errorMsg) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Main form card ── -->
            <div class="bg-white rounded-[20px] p-5 shadow-[0_2px_12px_rgba(0,0,0,0.08)] flex flex-col gap-5">

                <form id="formTransaksi" method="POST" action="transaksiowner.php?view=transaksi">

                    <!-- Kode Barang -->
                    <div>
                        <label for="kode_barang" class="form-label">SCAN / KODE BARANG</label>
                        <div class="relative">
                            <input type="text" id="kode_barang" name="kode_barang"
                                   class="form-input pr-12"
                                   placeholder="Contoh: BRG001"
                                   value="<?= htmlspecialchars($_POST['kode_barang'] ?? '') ?>"
                                   autocomplete="off" spellcheck="false">
                            <button type="button" id="btnCari"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 flex items-center justify-center text-[#388035] hover:bg-green-50 rounded-lg transition-colors"
                                    title="Cari barang">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m0 0A7 7 0 1 0 6.65 6.65a7 7 0 0 0 10 10z"/>
                                </svg>
                            </button>
                        </div>
                        <!-- Info/Error barang -->
                        <div id="barangInfo" class="mt-2 hidden">
                            <div class="bg-green-50 border border-green-200 rounded-[12px] px-3 py-2.5 flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
                                <span id="barangInfoText" class="text-[12px] text-green-800 font-semibold"></span>
                            </div>
                        </div>
                        <div id="barangError" class="mt-2 hidden">
                            <div class="bg-red-50 border border-red-200 rounded-[12px] px-3 py-2.5 flex items-center gap-2">
                                <svg class="w-4 h-4 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                                <span id="barangErrorText" class="text-[12px] text-red-700 font-semibold"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Qty dengan stepper -->
                    <div>
                        <label class="form-label">JUMLAH (QTY)</label>
                        <div class="flex items-center gap-3">
                            <button type="button" id="btnQtyMinus" class="qty-btn">−</button>
                            <input type="number" id="qty" name="qty"
                                   class="form-input text-center font-bold text-[18px]"
                                   value="<?= htmlspecialchars($_POST['qty'] ?? '1') ?>"
                                   min="1" max="999" readonly
                                   style="flex:1;">
                            <button type="button" id="btnQtyPlus" class="qty-btn">+</button>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-dashed border-gray-200"></div>

                    <!-- Total section -->
                    <div class="flex flex-col gap-2.5">
                        <div class="flex justify-between items-center">
                            <span class="text-[13px] text-gray-400 font-medium">Harga Satuan</span>
                            <span id="hargaDisplay" class="text-[13px] font-semibold text-gray-600">Rp 0</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[13px] text-gray-400 font-medium">Subtotal</span>
                            <span id="subtotalDisplay" class="text-[13px] font-semibold text-gray-700">Rp 0</span>
                        </div>
                        <div class="flex justify-between items-center bg-[#F0FDF4] rounded-[12px] px-3 py-3 mt-1">
                            <span class="font-bold text-[14px] text-[#166534]">TOTAL BAYAR</span>
                            <span id="totalDisplay" class="font-extrabold text-[22px] text-[#388035]">Rp 0</span>
                        </div>
                    </div>

                    <input type="hidden" id="hargaSatuan" name="_harga_satuan" value="0">
                    <input type="hidden" name="save_transaksi" value="1">

                </form>
            </div><!-- /form card -->

            <!-- ── Kode barang tersedia (hint) ── -->
            <div class="bg-white rounded-[16px] p-4 shadow-[0_2px_8px_rgba(0,0,0,0.06)]">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-3">Barang Tersedia</p>
                <div class="flex flex-col gap-2">
                    <?php
                    $listBarang = $conn->query("SELECT kode_barang,nama_barang,harga,stok FROM barang ORDER BY kode_barang LIMIT 6")->fetch_all(MYSQLI_ASSOC);
                    foreach ($listBarang as $b):
                        $lowStock = $b['stok'] <= 2;
                    ?>
                    <button type="button"
                            class="flex items-center justify-between p-2.5 rounded-[10px] hover:bg-[#F3F4F6] transition-colors text-left"
                            onclick="piliBarang('<?= htmlspecialchars($b['kode_barang']) ?>')">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 <?= $lowStock ? 'bg-red-50' : 'bg-green-50' ?> rounded-[9px] flex items-center justify-center">
                                <svg class="w-4 h-4 <?= $lowStock ? 'text-red-400' : 'text-green-500' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 12h10M7 17h5"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-[12px] font-bold text-[#111827]"><?= htmlspecialchars($b['nama_barang']) ?></p>
                                <p class="text-[10px] text-gray-400"><?= $b['kode_barang'] ?> &middot; Rp <?= number_format($b['harga'],0,',','.') ?></p>
                            </div>
                        </div>
                        <span id="stok-hint-<?= $b['kode_barang'] ?>" class="text-[11px] font-bold px-2 py-1 rounded-lg <?= $lowStock ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-700' ?>">
                            Stok <?= $b['stok'] ?>
                        </span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="h-2"></div>

        </div>
    </div><!-- /scrollable -->

    <!-- Sticky CTA Button -->
    <div class="flex-none px-4 pb-3 pt-3 bg-[#F3F4F6] border-t border-gray-200/80">
        <button id="btnSimpan"
                type="submit" form="formTransaksi"
                class="w-full h-[54px] bg-gradient-to-r from-[#388035] to-[#2d6b2b] rounded-[14px] flex items-center justify-center gap-2.5 hover:from-[#2c6529] hover:to-[#245222] active:scale-[0.98] transition-all shadow-[0_4px_16px_rgba(56,128,53,0.4)] font-bold text-white text-[15px] tracking-wide uppercase">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Simpan &amp; Cetak Nota
        </button>
    </div>

    <!-- Bottom indicator -->
    <div class="w-full h-[28px] flex-none bg-[#F3F4F6] flex justify-center items-center">
        <div class="w-[120px] h-[5px] bg-[#101828] opacity-15 rounded-full"></div>
    </div>

</div><!-- /viewTransaksi -->

<!-- ── Toast ── -->
<div id="toast" role="alert" aria-live="polite"></div>

</div><!-- /phone-mockup -->

<script>
// ── Clock ────────────────────────────────────────────────────────────────────
(function tick() {
    const el = document.getElementById('clockDisplay');
    if (el) {
        const n = new Date();
        el.textContent = String(n.getHours()).padStart(2,'0') + ':' + String(n.getMinutes()).padStart(2,'0');
    }
    setTimeout(tick, 15000);
})();

// ── View Switch ──────────────────────────────────────────────────────────────
const viewMenu      = document.getElementById('viewMenu');
const viewTransaksi = document.getElementById('viewTransaksi');

function showTransaksi() {
    viewMenu.classList.remove('active');
    viewMenu.style.display  = 'none';
    viewTransaksi.style.display = 'flex';
    viewTransaksi.classList.add('active','slide-in-right');
    viewTransaksi.addEventListener('animationend', () => viewTransaksi.classList.remove('slide-in-right'), { once:true });
}

function showMenu() {
    viewTransaksi.classList.add('slide-out-right');
    viewTransaksi.addEventListener('animationend', () => {
        viewTransaksi.classList.remove('active','slide-out-right');
        viewTransaksi.style.display = 'none';
        viewMenu.style.display = 'flex';
        viewMenu.classList.add('active','slide-in-left');
        viewMenu.addEventListener('animationend', () => viewMenu.classList.remove('slide-in-left'), { once:true });
    }, { once:true });
}

document.getElementById('btnTransaksiBaru').addEventListener('click', showTransaksi);
document.getElementById('btnBack').addEventListener('click', showMenu);

<?php if ($successMsg || $errorMsg || (isset($_GET['view']) && $_GET['view']==='transaksi')): ?>
window.addEventListener('DOMContentLoaded', () => showTransaksi());
<?php endif; ?>

// ── Qty stepper ──────────────────────────────────────────────────────────────
const qtyInput = document.getElementById('qty');

document.getElementById('btnQtyPlus').addEventListener('click', () => {
    const v = parseInt(qtyInput.value) || 1;
    if (v < 999) { qtyInput.value = v + 1; hitungTotal(); }
});
document.getElementById('btnQtyMinus').addEventListener('click', () => {
    const v = parseInt(qtyInput.value) || 1;
    if (v > 1) { qtyInput.value = v - 1; hitungTotal(); }
});

// ── Pilih barang dari list ────────────────────────────────────────────────────
function piliBarang(kode) {
    document.getElementById('kode_barang').value = kode;
    cariBarang();
}

// ── Hitung total ─────────────────────────────────────────────────────────────
let hargaSatuan = 0;

function formatRp(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }

function hitungTotal() {
    const qty = parseInt(qtyInput.value) || 0;
    const sub = hargaSatuan * qty;
    document.getElementById('hargaDisplay').textContent    = formatRp(hargaSatuan);
    document.getElementById('subtotalDisplay').textContent = formatRp(sub);
    document.getElementById('totalDisplay').textContent    = formatRp(sub);
    document.getElementById('hargaSatuan').value = hargaSatuan;
}

// ── AJAX: cari barang ─────────────────────────────────────────────────────────
async function cariBarang() {
    const kode    = document.getElementById('kode_barang').value.trim();
    const infoEl  = document.getElementById('barangInfo');
    const errorEl = document.getElementById('barangError');
    infoEl.classList.add('hidden');
    errorEl.classList.add('hidden');
    if (!kode) return;

    try {
        const res  = await fetch(`transaksiowner.php?action=cari_barang&kode=${encodeURIComponent(kode)}`);
        const data = await res.json();
        if (data.ok) {
            hargaSatuan = parseFloat(data.data.harga);
            document.getElementById('barangInfoText').textContent =
                `✓ ${data.data.nama_barang} — ${formatRp(hargaSatuan)} | Stok: ${data.data.stok}`;
            infoEl.classList.remove('hidden');
        } else {
            hargaSatuan = 0;
            document.getElementById('barangErrorText').textContent = data.msg;
            errorEl.classList.remove('hidden');
        }
    } catch {
        hargaSatuan = 0;
        document.getElementById('barangErrorText').textContent = 'Gagal menghubungi server.';
        errorEl.classList.remove('hidden');
    }
    hitungTotal();
}

document.getElementById('btnCari').addEventListener('click', cariBarang);
document.getElementById('kode_barang').addEventListener('keydown', e => {
    if (e.key==='Enter') { e.preventDefault(); cariBarang(); }
});

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(msg, type='success') {
    const toast = document.getElementById('toast');
    const bg    = type==='success' ? '#388035' : '#B23A48';
    const icon  = type==='success'
        ? '<path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/>'
        : '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>';
    toast.innerHTML = `<div style="background:${bg};color:#fff;font-size:13px;font-weight:600;padding:12px 18px;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,0.2);display:inline-flex;align-items:center;gap:8px;">
        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">${icon}</svg>
        <span>${msg}</span></div>`;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3500);
}

<?php if ($successMsg): ?>
window.addEventListener('DOMContentLoaded', () => showToast('Transaksi berhasil disimpan!', 'success'));
<?php elseif ($errorMsg): ?>
window.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode(strip_tags($errorMsg)) ?>, 'error'));
<?php endif; ?>

// Auto-update stok live
setInterval(() => {
    fetch('get_stok.php')
        .then(res => res.json())
        .then(data => {
            if(!data.error) {
                data.forEach(item => {
                    const el = document.getElementById('stok-hint-' + item.kode_barang);
                    if(el) {
                        el.innerText = 'Stok ' + item.stok;
                        if(parseInt(item.stok) <= 2) {
                            el.className = 'text-[11px] font-bold px-2 py-1 rounded-lg bg-red-100 text-red-600';
                        } else {
                            el.className = 'text-[11px] font-bold px-2 py-1 rounded-lg bg-green-100 text-green-700';
                        }
                    }
                });
            }
        })
        .catch(err => console.error('Error fetching stok:', err));
}, 3000);
</script>
</body>
</html>

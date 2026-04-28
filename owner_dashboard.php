<?php
session_start();
// hanya izinkan akses untuk user dengan role 'owner'
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'owner') {
    header('Location: login.php');
    exit;
}

// ── DB ─────────────────────────────────────────────────────────────────────
$conn = new mysqli('localhost', 'root', '', 'thrift');
if ($conn->connect_error) die('Koneksi gagal: ' . $conn->connect_error);

$uid = (int)$_SESSION['user_id'];

// ── Stats ──────────────────────────────────────────────────────────────────
// Transaksi hari ini
$trxRes = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(total_bayar),0) s FROM transaksi WHERE DATE(created_at)=CURDATE() AND user_id=$uid")->fetch_assoc();
$transaksiHariIni = $trxRes['c'] . " Transaksi";
$totalOmzet = "Rp " . number_format($trxRes['s'], 0, ',', '.');

// Stok & Kategori
$totalStok = (int)($conn->query("SELECT COALESCE(SUM(stok),0) s FROM barang")->fetch_assoc()['s']) . " Pcs";
$totalKategori = (int)($conn->query("SELECT COUNT(DISTINCT kategori) c FROM barang")->fetch_assoc()['c']) . " Jenis";

// Stok Kritis
$stokKritis = $conn->query("SELECT * FROM barang WHERE stok <= 3 ORDER BY stok ASC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Owner Dashboard - Solo Second Thrift</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800;900&family=Secular+One&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

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

        /* ── Phone shell ── */
        .phone {
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
    background: #FDFCF0;
    border-radius: 42px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 780px;
    position: relative;
}

        /* ── Status bar ── */
        .status-bar {
            width: 100%;
            height: 51px;
            background: #FDFCF0;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            z-index: 20;
        }
        .status-bar .time { font-size: 13px; font-weight: 700; color: #101828; }
        .status-icons { display: flex; gap: 6px; align-items: center; }

        /* ── Header (Frame 9) ── */
        .header {
            width: 100%;
            height: 80px;
            background: #FDFCF0;
            box-shadow: 0px 4px 4px rgba(0,0,0,0.25);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 22px;
            z-index: 20;
        }
        .header-left { display: flex; align-items: center; gap: 10px; }
        .header-avatar {
            width: 43px; height: 43px;
            background: #B23A48;
            border: 2px solid #264653;
            box-shadow: 0px 4px 4px rgba(0,0,0,0.25);
            border-radius: 50%;
            flex-shrink: 0;
        }
        .header-name { font-weight: 800; font-size: 14px; color: #264653; line-height: 1.2; }
        .header-role { font-family: 'Secular One', sans-serif; font-size: 11px; color: #B23A48; line-height: 1.4; }

        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 121px; height: 43px;
            background: #FFFFFF;
            border: 1px solid #890D0D;
            box-shadow: 0px 4px 4px rgba(0,0,0,0.25);
            border-radius: 15px;
            font-weight: 800; font-size: 15px;
            color: #890D0D;
            text-decoration: none;
            transition: background .2s, color .2s;
        }
        .logout-btn:hover { background: #890D0D; color: #fff; }

        /* ── Scrollable content ── */
        .content {
            flex: 1;
            overflow-y: auto;
            background: #FDFCF0;
            padding: 28px 17px 0;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .content::-webkit-scrollbar { display: none; }

        /* ── Stat grid ── */
        .stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            border-radius: 27px;
            padding: 20px 23px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            min-height: 97px;
            cursor: pointer;
            transition: transform .18s, box-shadow .18s;
        }
        .stat-card:hover { transform: scale(1.03); box-shadow: 0 6px 18px rgba(0,0,0,0.15); }
        .stat-label { font-weight: 700; font-size: 12px; line-height: 15px; }
        .stat-value { font-weight: 900; font-size: 20px; line-height: 24px; }

        .card-transaksi  { background: #E9C46A; }
        .card-transaksi .stat-label { color: #364153; }
        .card-transaksi .stat-value { color: #0F2241; }

        .card-omzet      { background: #2A9D8F; }
        .card-omzet .stat-label { color: #DBFDF8; }
        .card-omzet .stat-value { color: #FFFFFF; }

        .card-stok       { background: #101828; }
        .card-stok .stat-label { color: #FFFFFF; }
        .card-stok .stat-value { color: #FFFFFF; }

        .card-kategori   { background: #B23A48; }
        .card-kategori .stat-label { color: #FFFFFF; }
        .card-kategori .stat-value { color: #FFFFFF; }

        /* ── Warning heading ── */
        .warning-heading {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        .warning-heading svg { flex-shrink: 0; }
        .warning-heading span {
            font-weight: 700; font-size: 20px; line-height: 24px; color: #364153;
        }

        /* ── Stock warning cards ── */
        .stock-list { display: flex; flex-direction: column; gap: 14px; padding-bottom: 110px; }
        .item-card {
            width: 100%;
            background: #fff;
            border: 2px solid #101828;
            border-radius: 30px;
            height: 86px;
            display: flex; align-items: center;
            padding: 0 16px; gap: 14px;
            transition: box-shadow .2s;
        }
        .item-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.1); }

        .item-thumb {
            width: 55px; height: 48px;
            background: #6A7282;
            border: 2px solid #101828;
            border-radius: 10px;
            flex-shrink: 0;
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
        }
        .item-thumb img { width: 100%; height: 100%; object-fit: cover; }

        .item-info { flex: 1; min-width: 0; }
        .item-name {
            font-weight: 900; font-size: 13px; color: #364153;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .item-meta {
            display: flex; align-items: center; gap: 8px; margin-top: 5px;
            flex-wrap: wrap;
        }
        .item-price-stok {
            font-weight: 900; font-size: 12px; color: #2A9D8F;
        }
        .item-price-stok span.kritis { color: #B23A48; }
        .item-id {
            background: #FFF7ED;
            border: 1px solid #101828;
            border-radius: 5px;
            padding: 2px 7px;
            font-weight: 900; font-size: 10px; color: #6A7282;
        }

        /* ── Bottom Nav (Frame 15) ── */
        .bottom-nav {
            width: 100%;
            height: 80px;
            background: #FDFCF0;
            box-shadow: inset 0px 4px 4px rgba(0,0,0,0.25);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: space-around;
            padding: 0 10px;
            z-index: 20;
            position: relative;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            text-decoration: none;
            cursor: pointer;
            transition: transform .2s;
            width: 70px;
        }
        .nav-item:hover { transform: translateY(-3px); }
        .nav-icon {
            width: 35px; height: 35px;
            display: flex; align-items: center; justify-content: center;
        }
        .nav-label { font-weight: 700; font-size: 10px; }

        /* Active – Dashboard */
        .nav-active .nav-label { color: #B23A48; }
        .nav-active .nav-icon-bg {
            background: #B23A48;
            border-radius: 10px;
            padding: 5px;
        }
        /* Inactive */
        .nav-inactive .nav-label { color: #101828; }
        .nav-inactive:hover .nav-label { color: #B23A48; }

        /* ── Floating Cart FAB (Frame 26) ── */
        .fab-wrap {
            position: absolute;
            /* horizontally center */
            left: 50%;
            transform: translateX(-50%);
            /* sit on top of the nav bar */
            bottom: 50px;
            z-index: 30;
        }
        .fab {
            width: 70px; height: 70px;
            background: #B23A48;
            border: 3px solid #101828;
            box-shadow: 0px 4px 4px rgba(0,0,0,0.25);
            border-radius: 40px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            text-decoration: none;
            transition: background .2s, transform .2s;
        }
        .fab:hover { background: #902A38; transform: scale(1.08); }

        /* ── Bottom indicator (Frame 10) ── */
        .bottom-indicator {
            width: 100%;
            height: 34px;
            background: #FFF7ED;
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .bottom-pill {
            width: 130px; height: 6px;
            background: #101828;
            opacity: 0.2;
            border-radius: 99px;
        }

        @media (max-width: 480px) {
            .phone {
                width: 100%;
                height: 100vh;
                border: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>

<div class="phone">
    <!-- Physical Buttons -->
    <div class="btn-power"></div>
    <div class="btn-vol-up"></div>
    <div class="btn-vol-down"></div>
    <div class="screen-bezel">

    <!-- Status Bar -->
    <div class="status-bar">
        <span class="time">09:41</span>
        <div class="status-icons">
            <svg width="16" height="16" fill="none" stroke="#101828" stroke-width="2" viewBox="0 0 24 24">
                <path d="M2 8.82C5.52 5.61 9.02 4 12 4s6.48 1.61 10 4.82M5 12.05C7.44 9.97 9.82 8.95 12 8.95s4.56 1.02 7 3.1M8.4 15.1A5.88 5.88 0 0112 14c1.4 0 2.72.38 3.6 1.1"/>
                <circle cx="12" cy="18" r="1.3" fill="#101828" stroke="none"/>
            </svg>
            <svg width="20" height="12" fill="none" viewBox="0 0 24 14">
                <rect x="1" y="1" width="18" height="12" rx="2" stroke="#101828" stroke-width="2"/>
                <rect x="3" y="3" width="12" height="8" rx="1" fill="#101828"/>
                <path d="M20 4v6" stroke="#101828" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
    </div>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="header-avatar"></div>
            <div>
                <div class="header-name">SOLO SECOND THRIFT</div>
                <div class="header-role">OWNER</div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <!-- Scrollable Content -->
    <div class="content">

        <!-- Stat Cards -->
        <div class="stat-grid">
            <!-- TRANSAKSI -->
            <div class="stat-card card-transaksi">
                <div class="stat-label">TRANSAKSI</div>
                <div class="stat-value"><?php echo $transaksiHariIni; ?></div>
            </div>
            <!-- TOTAL OMZET -->
            <div class="stat-card card-omzet">
                <div class="stat-label">TOTAL OMZET</div>
                <div class="stat-value"><?php echo $totalOmzet; ?></div>
            </div>
            <!-- TOTAL STOK -->
            <div class="stat-card card-stok">
                <div class="stat-label">TOTAL STOK</div>
                <div class="stat-value"><?php echo $totalStok; ?></div>
            </div>
            <!-- KATEGORI -->
            <div class="stat-card card-kategori">
                <div class="stat-label">KATEGORI</div>
                <div class="stat-value"><?php echo $totalKategori; ?></div>
            </div>
        </div>

        <!-- Peringatan Stok Heading -->
        <div class="warning-heading">
            <!-- Warning / noto:warning icon -->
            <svg width="31" height="31" viewBox="0 0 32 32" fill="none">
                <path d="M16 2L30 28H2L16 2Z" fill="#FFCC32" stroke="#F2A600" stroke-width="2" stroke-linejoin="round"/>
                <path d="M16 11V20" stroke="#212121" stroke-width="2.5" stroke-linecap="round"/>
                <circle cx="16" cy="24" r="1.6" fill="#212121"/>
            </svg>
            <span>PERINGATAN STOK</span>
        </div>

        <!-- Stock Warning List -->
        <div class="stock-list">
            <?php if (empty($stokKritis)): ?>
                <div style="text-align: center; color: #6A7282; font-weight: bold; font-size: 13px; margin-top:20px;">Stok aman!</div>
            <?php else: ?>
            <?php foreach ($stokKritis as $item): ?>
            <a href="stokowner.php?q=<?= htmlspecialchars($item['kode_barang']) ?>" style="text-decoration:none;" class="item-card">
                <div class="item-thumb">
                    <?php if (!empty($item['foto']) && file_exists(__DIR__ . '/' . $item['foto'])): ?>
                    <img src="<?= htmlspecialchars($item['foto']) ?>" alt="">
                    <?php endif; ?>
                </div>
                <div class="item-info">
                    <div class="item-name"><?= htmlspecialchars($item['nama_barang']) ?></div>
                    <div class="item-meta">
                        <span class="item-price-stok">
                            Rp.<?= number_format($item['harga'],0,',','.') ?> | <span class="kritis">STOK : <?= $item['stok'] ?></span>
                        </span>
                        <span class="item-id">ID : <?= htmlspecialchars(str_replace('BRG','', $item['kode_barang'])) ?></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div><!-- /content -->

    <!-- Bottom Navigation -->
    <div class="bottom-nav">

        <!-- Dashboard (Active) -->
        <a href="owner_dashboard.php" class="nav-item nav-active">
            <div class="nav-icon nav-icon-bg">
                <svg width="22" height="22" fill="none" stroke="#FFFFFF" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M3 10.2L12 3l9 7.2V21a1 1 0 01-1 1h-5v-7h-6v7H4a1 1 0 01-1-1z"/>
                </svg>
            </div>
            <span class="nav-label">Dashboard</span>
        </a>

        <!-- Stok -->
        <a href="stokowner.php" class="nav-item nav-inactive">
            <div class="nav-icon">
                <!-- gravity-ui:box icon -->
                <svg width="28" height="28" fill="none" stroke="#101828" stroke-width="1.8" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M21 8l-9 5.25L3 8l9-5.25z"/>
                    <path d="M21 8v8l-9 5.25M3 8v8l9 5.25M12 13.25V24"/>
                </svg>
            </div>
            <span class="nav-label">Stok</span>
        </a>

        <!-- FAB spacer -->
        <div style="width:70px; flex-shrink:0;"></div>

        <!-- Laporan -->
        <a href="laporanowner.php" class="nav-item nav-inactive">
            <div class="nav-icon">
                <!-- fluent:data-area-24-filled -->
                <svg width="28" height="28" fill="#101828" viewBox="0 0 24 24">
                    <path d="M3 21h18v-2H3v2zm0-4h2v-7H3v7zm4 0h2V9H7v8zm4 0h2V5h-2v12zm4 0h2v-5h-2v5zm4 0h2v-9h-2v9z"/>
                </svg>
            </div>
            <span class="nav-label">Laporan</span>
        </a>

        <!-- User -->
        <a href="userowner.php" class="nav-item nav-inactive">
            <div class="nav-icon">
                <!-- iconamoon:profile-bold -->
                <svg width="28" height="28" fill="none" stroke="#101828" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 21a8 8 0 0116 0"/>
                </svg>
            </div>
            <span class="nav-label">User</span>
        </a>

        <!-- Floating Cart Button -->
        <div class="fab-wrap">
            <a href="transaksiowner.php" class="fab">
                <!-- ic:outline-local-grocery-store -->
                <svg width="35" height="35" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 01-8 0"/>
                </svg>
            </a>
        </div>

    </div><!-- /bottom-nav -->

    <!-- Bottom Indicator -->
    <div class="bottom-indicator">
        <div class="bottom-pill"></div>
    </div>

</div>
    </div><!-- /phone -->

</body>
</html>

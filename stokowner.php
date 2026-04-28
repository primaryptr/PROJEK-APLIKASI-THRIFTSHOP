<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'owner') {
    header('Location: login.php');
    exit;
}

// ── DB ─────────────────────────────────────────────────────────────────────
$conn = new mysqli('localhost', 'root', '', 'thrift');
if ($conn->connect_error) die('Koneksi gagal: ' . $conn->connect_error);

// ── Auto-migrate ─────────────────────────────────────────────────────────────
$cols = $conn->query("SHOW COLUMNS FROM barang")->fetch_all(MYSQLI_ASSOC);
$colNames = array_column($cols, 'Field');
if (!in_array('kategori', $colNames))
    $conn->query("ALTER TABLE barang ADD COLUMN `kategori` VARCHAR(100) NOT NULL DEFAULT 'Lainnya' AFTER `nama_barang`");
if (!in_array('foto', $colNames))
    $conn->query("ALTER TABLE barang ADD COLUMN `foto` VARCHAR(255) DEFAULT NULL AFTER `stok`");

// ── Handle DELETE ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delId = (int)$_POST['delete_id'];
    $row   = $conn->query("SELECT foto FROM barang WHERE id=$delId")->fetch_assoc();
    if ($row && $row['foto'] && file_exists(__DIR__ . '/' . $row['foto']))
        unlink(__DIR__ . '/' . $row['foto']);
    $conn->query("DELETE FROM barang WHERE id=$delId");
    $redir = 'stokowner.php';
    if (isset($_GET['kat'])) $redir .= '?kat=' . urlencode($_GET['kat']);
    if (isset($_GET['q']))   $redir .= (strpos($redir,'?')!==false ? '&' : '?') . 'q=' . urlencode($_GET['q']);
    header('Location: ' . $redir);
    exit;
}

// ── Kategori dari DB (hanya yang sudah ada) ───────────────────────────────────
$katRows  = $conn->query("SELECT DISTINCT kategori FROM barang ORDER BY kategori")->fetch_all(MYSQLI_ASSOC);
$allKat   = array_column($katRows, 'kategori');

// ── Filter ────────────────────────────────────────────────────────────────────
$activeKat = $_GET['kat'] ?? 'Semua';
$search    = trim($_GET['q'] ?? '');

$where = [];
if ($activeKat !== 'Semua' && $activeKat !== '')
    $where[] = "kategori='" . $conn->real_escape_string($activeKat) . "'";
if ($search !== '')
    $where[] = "(nama_barang LIKE '%" . $conn->real_escape_string($search) . "%' OR kode_barang LIKE '%" . $conn->real_escape_string($search) . "%')";

$whereSql   = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$barangList = $conn->query("SELECT * FROM barang $whereSql ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$totalAll   = (int)$conn->query("SELECT COUNT(*) c FROM barang")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Stok Barang - Solo Second Thrift</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Secular+One&display=swap" rel="stylesheet">
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
            width: 100%; height: 44px;
            background: #FDFCF0;
            flex-shrink: 0;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 24px; z-index: 30;
        }
        .status-bar .time { font-size: 13px; font-weight: 700; color: #101828; }

        /* ── Header ── */
        .header {
            width: 100%;
            background: #FDFCF0;
            flex-shrink: 0;
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 20px 16px;
            border-bottom: 1px solid rgba(0,0,0,0.08);
        }
        .header-left  { display: flex; align-items: center; gap: 10px; }
        .avatar {
            width: 46px; height: 46px;
            background: linear-gradient(135deg,#B23A48,#8B2635);
            border: 2px solid #264653;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .header-name { font-weight: 800; font-size: 14px; color: #264653; line-height: 1.2; }
        .header-role { font-family: 'Secular One'; font-size: 11px; color: #B23A48; }
        .logout-btn {
            height: 40px; padding: 0 18px;
            background: transparent;
            border: 2px solid #B23A48;
            border-radius: 14px;
            font-weight: 800; font-size: 14px; color: #B23A48;
            text-decoration: none;
            display: flex; align-items: center;
            transition: background .2s, color .2s;
        }
        .logout-btn:hover { background: #B23A48; color: #fff; }

        /* ── Scrollable content ── */
        .content {
            flex: 1; min-height: 0;
            overflow-y: auto;
            -ms-overflow-style: none; scrollbar-width: none;
            background: #FDFCF0;
        }
        .content::-webkit-scrollbar { display: none; }
        .content-inner { padding: 16px 18px 110px; display: flex; flex-direction: column; gap: 14px; }

        /* ── Tabs ── */
        .tabs {
            display: flex; gap: 14px;
            justify-content: center;
        }
        .tab-btn {
            flex: 1; max-width: 160px; height: 46px;
            border-radius: 24px;
            font-weight: 800; font-size: 14px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; text-decoration: none;
            border: none;
            transition: opacity .2s, transform .15s;
        }
        .tab-btn:active { transform: scale(.97); }
        .tab-active   { background: #101828; color: #fff; }
        .tab-inactive { background: #B23A48; color: #fff; }

        /* ── Search bar ── */
        .search-wrap {
            width: 100%;
            background: #fff;
            border: 2px solid #101828;
            border-radius: 30px;
            height: 50px;
            display: flex; align-items: center;
            padding: 0 16px; gap: 10px;
        }
        .search-wrap input {
            flex: 1; background: transparent; border: none; outline: none;
            font-family: 'Inter'; font-weight: 700; font-size: 13px;
            color: #264653;
        }
        .search-wrap input::placeholder { color: #9CA3AF; font-weight: 600; }

        /* ── Category chips ── */
        .chips-row {
            display: flex; gap: 10px;
            overflow-x: auto;
            -ms-overflow-style: none; scrollbar-width: none;
            padding-bottom: 4px;
        }
        .chips-row::-webkit-scrollbar { display: none; }
        .kat-chip {
            display: inline-flex; align-items: center;
            padding: 7px 16px;
            border-radius: 30px;
            background: #E9C46A;
            border: 1.5px solid #C9A84C;
            font-size: 11px; font-weight: 800;
            color: #264653;
            cursor: pointer;
            white-space: nowrap;
            text-decoration: none;
            flex-shrink: 0;
            transition: opacity .15s, transform .15s;
        }
        .kat-chip:hover   { opacity: .85; transform: translateY(-1px); }
        .kat-chip.active  { background: #C9A84C; border-color: #A07C30; }

        /* ── Item cards ── */
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
        .item-id {
            background: #FFF7ED;
            border: 1px solid #101828;
            border-radius: 5px;
            padding: 2px 7px;
            font-weight: 900; font-size: 10px; color: #6A7282;
        }

        .item-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .btn-del, .btn-edit {
            background: none; border: none; cursor: pointer; padding: 4px;
            display: flex; align-items: center; justify-content: center;
            transition: transform .15s;
        }
        .btn-del:hover, .btn-edit:hover { transform: scale(1.15); }

        /* ── Empty state ── */
        .empty-state {
            text-align: center; padding: 40px 20px;
            background: #fff; border: 2px solid #101828; border-radius: 30px;
        }
        .empty-state p { color: #6A7282; font-weight: 700; font-size: 13px; margin-top: 10px; }
        .empty-state small { color: #9CA3AF; font-size: 11px; }

        /* ── + Tambah FAB ── */
        .fab-tambah {
            position: absolute;
            right: 22px;
            bottom: 88px;
            z-index: 30;
            height: 46px; padding: 0 22px;
            background: #2A9D8F;
            border: 1.5px solid #101828;
            border-radius: 30px;
            font-weight: 800; font-size: 14px; color: #fff;
            text-decoration: none;
            display: flex; align-items: center; gap: 6px;
            box-shadow: 0 4px 12px rgba(42,157,143,0.4);
            transition: background .2s, transform .2s;
        }
        .fab-tambah:hover { background: #218075; transform: translateY(-2px); }

        /* ── Bottom nav ── */
        .bottom-nav {
            width: 100%; height: 72px;
            background: #FDFCF0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.08);
            flex-shrink: 0;
            display: flex; align-items: center; justify-content: space-around;
            padding: 0 8px; z-index: 20;
        }
        .nav-item {
            display: flex; flex-direction: column; align-items: center;
            gap: 3px; text-decoration: none; cursor: pointer;
            transition: transform .2s;
        }
        .nav-item:hover { transform: translateY(-2px); }
        .nav-icon { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; }
        .nav-label { font-size: 10px; font-weight: 800; }
        .nav-label-active   { color: #B23A48; }
        .nav-label-inactive { color: #101828; }

        /* Bottom pill */
        .bottom-pill-wrap {
            width: 100%; height: 24px;
            background: #FDFCF0;
            flex-shrink: 0;
            display: flex; justify-content: center; align-items: center;
        }
        .bottom-pill { width: 130px; height: 5px; background: #101828; opacity: .15; border-radius: 99px; }

        @media (max-width: 480px) {
            .phone { width: 100%; height: 100vh; border: none; border-radius: 0; }
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
        <span class="time" id="clockDisplay">09:41</span>
        <div style="display:flex;gap:6px;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M5 12.55a11 11 0 0114.08 0M1.42 9a16 16 0 0121.16 0M8.53 16.11a6 6 0 016.95 0M12 20h.01"/>
            </svg>
            <svg width="22" height="13" viewBox="0 0 24 14" fill="none">
                <rect x=".5" y=".5" width="20" height="13" rx="3.5" stroke="currentColor" stroke-width="1.2"/>
                <rect x="2" y="2" width="15" height="10" rx="2" fill="currentColor"/>
                <path d="M21.5 4.5v5c1-.5 1.5-1.2 1.5-2.5s-.5-2-1.5-2.5z" fill="currentColor"/>
            </svg>
        </div>
    </div>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="avatar">
                <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <div>
                <div class="header-name">SOLO SECOND THRIFT</div>
                <div class="header-role">OWNER</div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <!-- Scrollable Content -->
    <div class="content">
    <div class="content-inner">

        <!-- Tabs -->
        <div class="tabs">
            <span class="tab-btn tab-active">Daftar Barang</span>
            <a href="datasupplier.php" class="tab-btn tab-inactive">Data Supplier</a>
        </div>

        <!-- Search -->
        <form method="GET" action="stokowner.php">
            <input type="hidden" name="kat" value="<?= htmlspecialchars($activeKat) ?>">
            <div class="search-wrap">
                <svg width="18" height="18" fill="none" stroke="#9CA3AF" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m0 0A7 7 0 1 0 6.65 6.65a7 7 0 0 0 10 10z"/>
                </svg>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                       placeholder="CARI BARANG / RAK...">
                <?php if ($search): ?>
                <a href="stokowner.php?kat=<?= urlencode($activeKat) ?>" style="color:#9CA3AF;display:flex;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Category Chips — hanya dari DB, muncul setelah ditambahkan via tambahbarang -->
        <?php if (!empty($allKat)): ?>
        <div class="chips-row">
            <a href="stokowner.php<?= $search ? '?q='.urlencode($search) : '' ?>"
               class="kat-chip <?= ($activeKat === 'Semua') ? 'active' : '' ?>">
                SEMUA
            </a>
            <?php foreach ($allKat as $k): ?>
            <a href="stokowner.php?kat=<?= urlencode($k) ?><?= $search ? '&q='.urlencode($search) : '' ?>"
               class="kat-chip <?= ($activeKat === $k) ? 'active' : '' ?>">
                <?= strtoupper(htmlspecialchars($k)) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Item List -->
        <div style="display:flex;flex-direction:column;gap:12px;">

            <?php if (empty($barangList)): ?>
            <div class="empty-state">
                <svg width="44" height="44" fill="none" stroke="#9CA3AF" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <p><?= $search ? 'Tidak ada barang untuk "'.htmlspecialchars($search).'"' : 'Belum ada barang' ?></p>
                <small>Tap <strong>+ Tambah</strong> untuk menambahkan barang baru</small>
            </div>

            <?php else: ?>
            <?php foreach ($barangList as $item): ?>
            <div class="item-card">

                <!-- Foto/Thumb -->
                <div class="item-thumb">
                    <?php if (!empty($item['foto']) && file_exists(__DIR__ . '/' . $item['foto'])): ?>
                    <img src="<?= htmlspecialchars($item['foto']) ?>" alt="">
                    <?php endif; ?>
                </div>
                <!-- Info -->
                <div class="item-info">
                    <div class="item-name"><?= htmlspecialchars($item['nama_barang']) ?></div>
                    <div class="item-meta">
                        <span class="item-price-stok">
                            Rp.<?= number_format($item['harga'],0,',','.') ?> | STOK : <span id="stok-val-<?= $item['id'] ?>"><?= $item['stok'] ?></span>
                        </span>
                        <span class="item-id">ID : <?= htmlspecialchars(str_replace('BRG','', $item['kode_barang'])) ?></span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="item-actions">
                    <!-- Delete -->
                    <form method="POST"
                          action="stokowner.php?kat=<?= urlencode($activeKat) ?><?= $search ? '&q='.urlencode($search) : '' ?>"
                          onsubmit="return confirm('Hapus barang ini?')">
                        <input type="hidden" name="delete_id" value="<?= $item['id'] ?>">
                        <button type="submit" class="btn-del" title="Hapus">
                            <svg width="22" height="22" fill="none" stroke="#B23A48" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                    <!-- Edit -->
                    <a href="editbarangowner.php?id=<?= $item['id'] ?>" class="btn-edit" title="Edit">
                        <svg width="20" height="20" fill="none" stroke="#364153" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                    </a>
                </div>

            </div>
            <?php endforeach; ?>
            <?php endif; ?>

        </div>

    </div>
    </div><!-- /content -->

    <!-- FAB + Tambah -->
    <a href="tambahbarangowner.php" class="fab-tambah">
        + Tambah
    </a>

    <!-- Bottom Nav -->
    <div class="bottom-nav">

        <!-- Katalog (Active) -->
        <a href="stokowner.php" class="nav-item">
            <div class="nav-icon">
                <svg width="28" height="28" fill="none" stroke="#B23A48" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <span class="nav-label nav-label-active">Katalog</span>
        </a>

        <!-- Transaksi -->
        <a href="transaksiowner.php" class="nav-item">
            <div class="nav-icon">
                <svg width="26" height="26" fill="none" stroke="#101828" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <span class="nav-label nav-label-inactive">Transaksi</span>
        </a>

        <!-- Home -->
        <a href="owner_dashboard.php" class="nav-item">
            <div class="nav-icon">
                <svg width="26" height="26" fill="none" stroke="#101828" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </div>
            <span class="nav-label nav-label-inactive">Home</span>
        </a>

        <!-- Laporan -->
        <a href="laporanowner.php" class="nav-item">
            <div class="nav-icon">
                <svg width="26" height="26" fill="#101828" viewBox="0 0 24 24">
                    <path d="M3 21h18v-2H3v2zm0-4h2v-7H3v7zm4 0h2V9H7v8zm4 0h2V5h-2v12zm4 0h2v-5h-2v5zm4 0h2v-9h-2v9z"/>
                </svg>
            </div>
            <span class="nav-label nav-label-inactive">Laporan</span>
        </a>

        <!-- User -->
        <a href="userowner.php" class="nav-item">
            <div class="nav-icon">
                <svg width="26" height="26" fill="none" stroke="#101828" stroke-width="2.2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <span class="nav-label nav-label-inactive">User</span>
        </a>

    </div>

    <div class="bottom-pill-wrap">
        <div class="bottom-pill"></div>
    </div>

</div>
    </div><!-- /phone -->

<script>
(function tick() {
    const el = document.getElementById('clockDisplay');
    if (el) {
        const n = new Date();
        el.textContent = String(n.getHours()).padStart(2,'0') + ':' + String(n.getMinutes()).padStart(2,'0');
    }
    setTimeout(tick, 15000);
})();

// Auto-update stok live
setInterval(() => {
    fetch('get_stok.php')
        .then(res => res.json())
        .then(data => {
            if(!data.error) {
                data.forEach(item => {
                    const el = document.getElementById('stok-val-' + item.id);
                    if(el && el.innerText !== item.stok) {
                        el.innerText = item.stok;
                    }
                });
            }
        })
        .catch(err => console.error('Error fetching stok:', err));
}, 3000);
</script>
</body>
</html>

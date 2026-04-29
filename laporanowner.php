<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'owner') {
    header('Location: login.php');
    exit;
}

// ── DB ────────────────────────────────────────────────────────────────────────
$conn = new mysqli('localhost', 'root', '', 'thrift');
if ($conn->connect_error) die('Koneksi gagal: ' . $conn->connect_error);

$uid = (int)$_SESSION['user_id'];

// ── Filter periode (default: harian) ─────────────────────────────────────────
$filter = $_GET['filter'] ?? 'harian';
$filter = in_array($filter, ['harian','mingguan','tahunan']) ? $filter : 'harian';

switch ($filter) {
    case 'mingguan': $dateCond = "YEARWEEK(t.created_at, 1) = YEARWEEK(CURDATE(), 1)"; break;
    case 'tahunan':  $dateCond = "YEAR(t.created_at) = YEAR(CURDATE())"; break;
    default:         $dateCond = "DATE(t.created_at) = CURDATE()"; break;
}

// ── Total omzet periode ───────────────────────────────────────────────────────
$omzet = $conn->query("
    SELECT COALESCE(SUM(total_bayar),0) s, COUNT(*) c
    FROM transaksi t WHERE $dateCond AND t.user_id=$uid
")->fetch_assoc();
$totalOmzet  = (float)$omzet['s'];
$totalTrx    = (int)$omzet['c'];

// ── Avg transaksi ─────────────────────────────────────────────────────────────
$avgTrx = $totalTrx > 0 ? $totalOmzet / $totalTrx : 0;

// ── Omzet kemarin (untuk % growth) ───────────────────────────────────────────
$prevOmzet = (float)$conn->query("
    SELECT COALESCE(SUM(total_bayar),0) s FROM transaksi
    WHERE DATE(created_at)=DATE_SUB(CURDATE(),INTERVAL 1 DAY) AND user_id=$uid
")->fetch_assoc()['s'];
$growth = $prevOmzet > 0 ? round((($totalOmzet - $prevOmzet) / $prevOmzet) * 100) : 0;

// ── Margin profit simulasi (55% dari omzet sebagai HPP) ──────────────────────
$hpp    = $totalOmzet * 0.575;
$margin = $totalOmzet > 0 ? round((($totalOmzet - $hpp) / $totalOmzet) * 100, 1) : 0;

// ── Transaksi terbaru ─────────────────────────────────────────────────────────
$latestTrx = $conn->query("
    SELECT t.kode_transaksi, t.total_bayar, t.created_at,
           GROUP_CONCAT(d.nama_barang SEPARATOR ', ') AS items,
           COUNT(d.id) AS item_count,
           SUM(d.qty) AS total_qty
    FROM transaksi t
    JOIN transaksi_detail d ON d.transaksi_id = t.id
    WHERE t.user_id = $uid
    GROUP BY t.id
    ORDER BY t.created_at DESC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// ── Top produk terlaris ───────────────────────────────────────────────────────
$topProduk = $conn->query("
    SELECT d.nama_barang, SUM(d.qty) AS total_qty, SUM(d.subtotal) AS total_rev
    FROM transaksi_detail d
    JOIN transaksi t ON t.id = d.transaksi_id
    WHERE t.user_id = $uid
    GROUP BY d.nama_barang
    ORDER BY total_qty DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
$maxQty = !empty($topProduk) ? (int)$topProduk[0]['total_qty'] : 1;

// ── Data grafik per jam (harian) / per hari (mingguan) / per bulan (tahunan) ─
$chartData = [];
if ($filter === 'harian') {
    $rows = $conn->query("
        SELECT HOUR(created_at) h, COALESCE(SUM(total_bayar),0) s
        FROM transaksi WHERE DATE(created_at)=CURDATE() AND user_id=$uid
        GROUP BY h ORDER BY h
    ")->fetch_all(MYSQLI_ASSOC);
    $map = []; foreach ($rows as $r) $map[$r['h']] = $r['s'];
    foreach ([8,10,12,14,16,18,20] as $h)
        $chartData[] = ['label'=>$h.'h', 'val'=>(float)($map[$h]??0)];
} elseif ($filter === 'mingguan') {
    $rows = $conn->query("
        SELECT DAYOFWEEK(created_at) d, COALESCE(SUM(total_bayar),0) s
        FROM transaksi WHERE YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1) AND user_id=$uid
        GROUP BY d ORDER BY d
    ")->fetch_all(MYSQLI_ASSOC);
    $days = [2=>'Sen',3=>'Sel',4=>'Rab',5=>'Kam',6=>'Jum',7=>'Sab',1=>'Min'];
    $map  = []; foreach ($rows as $r) $map[$r['d']] = $r['s'];
    foreach ($days as $k=>$v) $chartData[] = ['label'=>$v,'val'=>(float)($map[$k]??0)];
} else {
    $rows = $conn->query("
        SELECT MONTH(created_at) m, COALESCE(SUM(total_bayar),0) s
        FROM transaksi WHERE YEAR(created_at)=YEAR(CURDATE()) AND user_id=$uid
        GROUP BY m ORDER BY m
    ")->fetch_all(MYSQLI_ASSOC);
    $months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $map=[]; foreach ($rows as $r) $map[$r['m']] = $r['s'];
    for ($i=1;$i<=12;$i++) $chartData[] = ['label'=>$months[$i-1],'val'=>(float)($map[$i]??0)];
}
$maxChart = max(array_column($chartData,'val') ?: [1]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Laporan - Solo Second Thrift</title>
    <meta name="description" content="Halaman laporan keuangan dan analisa penjualan owner Solo Second Thrift">
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
            width: 100%; height: 76px;
            background: #FDFCF0;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            flex-shrink: 0;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 20px; z-index: 20;
        }
        .header-left { display: flex; align-items: center; gap: 10px; }
        .avatar {
            width: 42px; height: 42px;
            background: linear-gradient(135deg,#B23A48,#8B2635);
            border: 2px solid #264653;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .header-name { font-weight: 800; font-size: 14px; color: #264653; line-height: 1.2; }
        .header-role { font-family: 'Secular One'; font-size: 11px; color: #B23A48; }
        .logout-btn {
            height: 38px; padding: 0 14px;
            background: #fff; border: 1px solid #890D0D;
            border-radius: 12px;
            font-weight: 800; font-size: 13px; color: #890D0D;
            text-decoration: none; display: flex; align-items: center; gap: 5px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            transition: background .2s, color .2s;
        }
        .logout-btn:hover { background: #890D0D; color: #fff; }

        /* ── Scrollable main ── */
        .content {
            flex: 1; min-height: 0;
            overflow-y: auto;
            -ms-overflow-style: none; scrollbar-width: none;
            background: #F3F4F6;
        }
        .content::-webkit-scrollbar { display: none; }
        .content-inner { padding: 16px 14px 16px; display: flex; flex-direction: column; gap: 14px; }

        /* ── Dark analytics card ── */
        .analytics-card {
            background: #0F1825;
            border-radius: 22px;
            overflow: hidden;
        }

        /* Filter tabs */
        .filter-tabs {
            display: flex; gap: 0;
            background: #1C2A3A;
            margin: 14px 14px 0;
            border-radius: 10px;
            overflow: hidden;
        }
        .filter-tab {
            flex: 1; padding: 8px 0;
            text-align: center;
            font-size: 12px; font-weight: 700;
            color: #6B8099;
            cursor: pointer;
            text-decoration: none;
            transition: background .2s, color .2s;
            border-radius: 10px;
        }
        .filter-tab.active { background: #2563EB; color: #fff; }
        .filter-tab:hover:not(.active) { color: #CBD5E1; }

        /* Omzet row */
        .omzet-section { padding: 16px 14px 4px; }
        .omzet-label { font-size: 10px; font-weight: 700; letter-spacing: .1em; color: #6B8099; text-transform: uppercase; }
        .omzet-row { display: flex; align-items: center; justify-content: space-between; margin-top: 4px; }
        .omzet-value { font-size: 28px; font-weight: 900; color: #fff; }
        .growth-badge {
            display: inline-flex; align-items: center; gap: 3px;
            font-size: 11px; font-weight: 700;
            padding: 4px 8px; border-radius: 20px;
        }
        .growth-up   { background: #14532D; color: #4ADE80; }
        .growth-down { background: #7F1D1D; color: #F87171; }
        .growth-flat { background: #1e293b; color: #94a3b8; }

        /* Mini chart */
        .chart-wrap {
            padding: 8px 14px 14px;
            display: flex; align-items: flex-end;
            gap: 4px; height: 68px;
        }
        .chart-col { display: flex; flex-direction: column; align-items: center; flex: 1; gap: 3px; }
        .chart-bar-bg { width: 100%; flex: 1; background: #1C2A3A; border-radius: 4px; display: flex; align-items: flex-end; overflow: hidden; }
        .chart-bar { width: 100%; background: linear-gradient(180deg,#3B82F6,#1D4ED8); border-radius: 4px; transition: height .4s; }
        .chart-bar.max-bar { background: linear-gradient(180deg,#60A5FA,#3B82F6); }
        .chart-label { font-size: 8px; color: #475569; font-weight: 600; }

        /* Stats mini row */
        .stats-mini-row {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 8px; padding: 0 14px 14px;
        }
        .stat-mini {
            background: #1C2A3A; border-radius: 14px;
            padding: 12px;
        }
        .stat-mini-icon { font-size: 16px; margin-bottom: 4px; }
        .stat-mini-label { font-size: 9px; font-weight: 700; letter-spacing: .08em; color: #6B8099; text-transform: uppercase; margin-bottom: 2px; }
        .stat-mini-value { font-size: 15px; font-weight: 800; color: #fff; }
        .stat-mini-sub   { font-size: 9px; color: #475569; margin-top: 1px; }

        /* ── Transaksi Terbaru panel ── */
        .section-card {
            background: #0F1825;
            border-radius: 18px;
            overflow: hidden;
        }
        .section-header {
            padding: 14px 14px 10px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .section-title { font-size: 12px; font-weight: 800; letter-spacing: .1em; color: #94A3B8; text-transform: uppercase; }
        .section-link  { font-size: 11px; font-weight: 700; color: #3B82F6; text-decoration: none; }
        .section-link:hover { text-decoration: underline; }

        .trx-row {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px;
            border-top: 1px solid #1C2A3A;
            cursor: default;
            transition: background .15s;
        }
        .trx-row:hover { background: #152033; }
        .trx-icon {
            width: 34px; height: 34px; border-radius: 10px;
            background: #1C2A3A;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .trx-info { flex: 1; min-width: 0; }
        .trx-code  { font-size: 12px; font-weight: 700; color: #F1F5F9; }
        .trx-meta  { font-size: 10px; color: #475569; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .trx-right { text-align: right; flex-shrink: 0; }
        .trx-amount{ font-size: 13px; font-weight: 800; color: #fff; white-space: nowrap; }
        .trx-profit { font-size: 10px; font-weight: 700; color: #4ADE80; margin-top: 1px; }

        /* ── Top produk ── */
        .produk-row { padding: 8px 14px; border-top: 1px solid #1C2A3A; }
        .produk-name { font-size: 12px; font-weight: 600; color: #E2E8F0; margin-bottom: 5px; }
        .produk-bar-bg { height: 7px; background: #1C2A3A; border-radius: 99px; overflow: hidden; }
        .produk-bar    { height: 100%; background: linear-gradient(90deg,#16A34A,#4ADE80); border-radius: 99px; }
        .produk-meta   { display: flex; justify-content: space-between; margin-top: 3px; }
        .produk-pct    { font-size: 10px; font-weight: 700; color: #4ADE80; }
        .produk-qty    { font-size: 10px; color: #475569; }

        /* ── Unduh PDF button ── */
        .download-btn {
            display: flex; align-items: center; justify-content: center;
            gap: 8px;
            width: 100%; height: 50px;
            background: #fff; border: 2px solid #388035;
            border-radius: 14px; cursor: pointer;
            font-size: 13px; font-weight: 800; letter-spacing: .1em;
            color: #388035; text-transform: uppercase;
            text-decoration: none;
            transition: background .2s, color .2s;
        }
        .download-btn:hover { background: #388035; color: #fff; }

        /* ── Bottom Nav ── */
        .bottom-nav {
            width: 100%; height: 72px;
            background: #FDFCF0;
            box-shadow: inset 0 4px 8px rgba(0,0,0,0.08);
            flex-shrink: 0;
            display: flex; align-items: center; justify-content: space-around;
            padding: 0 6px; z-index: 20;
            position: relative;
        }
        .nav-item {
            display: flex; flex-direction: column; align-items: center;
            gap: 3px; text-decoration: none;
            width: 60px; cursor: pointer;
            transition: transform .2s;
        }
        .nav-item:hover { transform: translateY(-2px); }
        .nav-icon-wrap { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; }
        .nav-label { font-size: 10px; font-weight: 700; }
        .nav-active-bg { background: #B23A48; border-radius: 10px; padding: 4px; }
        .nav-label-active { color: #B23A48; }
        .nav-label-inactive { color: #6B7280; }
        .nav-inactive:hover .nav-label { color: #388035; }

        /* FAB */
        .fab-wrap { position: absolute; left: 50%; transform: translateX(-50%); bottom: 38px; z-index: 30; }
        .fab {
            width: 64px; height: 64px;
            background: #B23A48; border: 3px solid #101828;
            border-radius: 36px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; text-decoration: none;
            box-shadow: 0 4px 14px rgba(178,58,72,0.4);
            transition: background .2s, transform .2s;
        }
        .fab:hover { background: #902A38; transform: scale(1.08); }

        /* Bottom indicator */
        .bottom-indicator {
            width: 100%; height: 28px;
            background: #FDFCF0;
            flex-shrink: 0;
            display: flex; justify-content: center; align-items: center;
        }
        .bottom-pill { width: 120px; height: 5px; background: #101828; opacity: .15; border-radius: 99px; }

        /* ── Empty state ── */
        .empty-state { padding: 24px 14px; text-align: center; }
        .empty-state p { color: #475569; font-size: 12px; margin-top: 8px; }

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
                <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <div>
                <div class="header-name">SOLO SECOND THRIFT</div>
                <div class="header-role">OWNER</div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Logout
        </a>
    </div>

    <!-- ═══════════ SCROLLABLE CONTENT ═══════════ -->
    <div class="content" id="mainContent">
    <div class="content-inner">

        <!-- ═══ ANALISA KEUANGAN CARD (dark) ═══ -->
        <div class="analytics-card">

            <!-- Title row -->
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 14px 0;">
                <span style="font-size:15px;font-weight:800;color:#fff;">Analisa Keuangan</span>
                <!-- Download icon -->
                <a href="?filter=<?= $filter ?>&export=pdf" title="Unduh"
                   style="width:30px;height:30px;background:#1C2A3A;border-radius:8px;display:flex;align-items:center;justify-content:center;text-decoration:none;">
                    <svg width="15" height="15" fill="none" stroke="#94A3B8" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                    </svg>
                </a>
            </div>

            <!-- Filter tabs -->
            <div class="filter-tabs">
                <a href="?filter=harian"   class="filter-tab <?= $filter==='harian'   ? 'active' : '' ?>">HARIAN</a>
                <a href="?filter=mingguan" class="filter-tab <?= $filter==='mingguan' ? 'active' : '' ?>">MINGGUAN</a>
                <a href="?filter=tahunan"  class="filter-tab <?= $filter==='tahunan'  ? 'active' : '' ?>">TAHUNAN</a>
            </div>

            <!-- Omzet -->
            <div class="omzet-section">
                <div class="omzet-label">TOTAL OMZET <?= strtoupper($filter) ?></div>
                <div class="omzet-row">
                    <div class="omzet-value">Rp <?= number_format($totalOmzet,0,',','.') ?></div>
                    <?php
                    $growthClass = $growth > 0 ? 'growth-up' : ($growth < 0 ? 'growth-down' : 'growth-flat');
                    $growthSign  = $growth > 0 ? '+' : '';
                    $growthIcon  = $growth > 0 ? '▲' : ($growth < 0 ? '▼' : '—');
                    ?>
                    <span class="growth-badge <?= $growthClass ?>"><?= $growthIcon ?> <?= $growthSign.$growth ?>%</span>
                </div>
            </div>

            <!-- Mini Chart -->
            <div class="chart-wrap">
                <?php
                $maxVal = max($maxChart, 1);
                foreach ($chartData as $cd):
                    $pct    = min(100, round(($cd['val']/$maxVal)*100));
                    $isMax  = $cd['val'] === $maxChart && $maxChart > 0;
                ?>
                <div class="chart-col">
                    <div class="chart-bar-bg">
                        <div class="chart-bar <?= $isMax ? 'max-bar' : '' ?>"
                             style="height:<?= $pct ?>%;min-height:<?= $pct>0?'3':'0' ?>px;"></div>
                    </div>
                    <div class="chart-label"><?= $cd['label'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Mini stats -->
            <div class="stats-mini-row">
                <div class="stat-mini">
                    <div class="stat-mini-icon">⏱️</div>
                    <div class="stat-mini-label">Margin Profit</div>
                    <div class="stat-mini-value"><?= $margin ?>%</div>
                    <div class="stat-mini-sub">estimasi HPP 57.5%</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-icon">📅</div>
                    <div class="stat-mini-label">Avg Transaksi</div>
                    <div class="stat-mini-value">Rp <?= $avgTrx>=1000 ? round($avgTrx/1000).'k' : '0' ?></div>
                    <div class="stat-mini-sub"><?= $totalTrx ?> transaksi</div>
                </div>
            </div>

        </div><!-- /analytics-card -->

        <!-- ═══ TRANSAKSI TERBARU ═══ -->
        <div class="section-card">
            <div class="section-header">
                <span class="section-title">Transaksi Terbaru</span>
                <a href="transaksiowner.php" class="section-link">+ Buat Baru</a>
            </div>

            <?php if (empty($latestTrx)): ?>
            <div class="empty-state">
                <svg width="36" height="36" fill="none" stroke="#475569" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p>Belum ada transaksi.<br>
                   <a href="transaksiowner.php" style="color:#3B82F6;font-weight:700;">Buat transaksi baru →</a>
                </p>
            </div>
            <?php else: ?>
            <?php foreach ($latestTrx as $i => $trx):
                $profit = round($trx['total_bayar'] * 0.425);
                $warna  = ['#3B82F6','#8B5CF6','#EC4899','#F59E0B','#10B981','#06B6D4','#EF4444','#A3E635'];
                $col    = $warna[$i % count($warna)];
            ?>
            <div class="trx-row">
                <div class="trx-icon" style="background:<?= $col ?>22;border:1px solid <?= $col ?>44;">
                    <svg width="16" height="16" fill="none" stroke="<?= $col ?>" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="trx-info">
                    <div class="trx-code"><?= htmlspecialchars($trx['kode_transaksi']) ?></div>
                    <div class="trx-meta"><?= htmlspecialchars(mb_substr($trx['items'],0,32)) ?> &bull; <?= date('d/m/Y H:i', strtotime($trx['created_at'])) ?></div>
                </div>
                <div class="trx-right">
                    <div class="trx-amount">Rp <?= number_format($trx['total_bayar'],0,',','.') ?></div>
                    <div class="trx-profit">PROFIT +Rp <?= number_format($profit,0,',','.') ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ═══ TOP PRODUK ═══ -->
        <div class="section-card">
            <div class="section-header">
                <span class="section-title">Top Produk</span>
                <a href="stokowner.php" class="section-link">Lihat Stok</a>
            </div>

            <?php if (empty($topProduk)): ?>
            <div class="empty-state">
                <p>Belum ada data produk terjual.</p>
            </div>
            <?php else: ?>
            <?php
            $barColors = ['#4ADE80','#60A5FA','#F472B6','#FBBF24','#A78BFA'];
            foreach ($topProduk as $pi => $prod):
                $pct = $maxQty > 0 ? round(($prod['total_qty']/$maxQty)*100) : 0;
                $bc  = $barColors[$pi % count($barColors)];
            ?>
            <div class="produk-row">
                <div class="produk-name"><?= htmlspecialchars($prod['nama_barang']) ?></div>
                <div class="produk-bar-bg">
                    <div class="produk-bar" style="width:<?= $pct ?>%;background:linear-gradient(90deg,<?= $bc ?>99,<?= $bc ?>);"></div>
                </div>
                <div class="produk-meta">
                    <span class="produk-pct" style="color:<?= $bc ?>;"><?= $pct ?>%</span>
                    <span class="produk-qty"><?= $prod['total_qty'] ?> terjual</span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <!-- spacer bottom -->
            <div style="height:8px;"></div>
        </div>

        <!-- ═══ UNDUH LAPORAN ═══ -->
        <a href="javascript:void(0)" onclick="cetakLaporan()" class="download-btn">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
            </svg>
            UNDUH LAPORAN PDF
        </a>

        <div style="height:8px;"></div>

    </div><!-- /content-inner -->
    </div><!-- /content -->

    <!-- ═══ BOTTOM NAV ═══ -->
    <div class="bottom-nav">

        <!-- Dashboard -->
        <a href="owner_dashboard.php" class="nav-item nav-inactive">
            <div class="nav-icon-wrap">
                <svg width="24" height="24" fill="none" stroke="#6B7280" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </div>
            <span class="nav-label nav-label-inactive">Dashboard</span>
        </a>

        <!-- Stok -->
        <a href="stokowner.php" class="nav-item nav-inactive">
            <div class="nav-icon-wrap">
                <svg width="23" height="23" fill="none" stroke="#6B7280" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <span class="nav-label nav-label-inactive">Stok</span>
        </a>

        <!-- FAB spacer -->
        <div style="width:64px;flex-shrink:0;"></div>

        <!-- Laporan (Active) -->
        <a href="laporanowner.php" class="nav-item">
            <div class="nav-icon-wrap nav-active-bg">
                <svg width="20" height="20" fill="#fff" viewBox="0 0 24 24">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                </svg>
            </div>
            <span class="nav-label nav-label-active">Laporan</span>
        </a>

        <!-- User -->
        <a href="userowner.php" class="nav-item nav-inactive">
            <div class="nav-icon-wrap">
                <svg width="24" height="24" fill="none" stroke="#6B7280" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <span class="nav-label nav-label-inactive">User</span>
        </a>

        <!-- FAB: ke transaksi -->
        <div class="fab-wrap">
            <a href="transaksiowner.php" class="fab" title="Transaksi Baru">
                <svg width="28" height="28" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </a>
        </div>

    </div><!-- /bottom-nav -->

    <!-- Bottom indicator -->
    <div class="bottom-indicator">
        <div class="bottom-pill"></div>
    </div>

</div><!-- /phone -->

<script>
// ── Clock ────────────────────────────────────────────────────────────────────
(function tick() {
    const el = document.getElementById('clockDisplay');
    if (el) {
        const n = new Date();
        el.textContent = String(n.getHours()).padStart(2,'0') + ':' + String(n.getMinutes()).padStart(2,'0');
    }
    setTimeout(tick, 30000);
})();

// ── Animate chart bars on load ────────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.chart-bar').forEach(bar => {
        const target = bar.style.height;
        bar.style.height = '0%';
        requestAnimationFrame(() => {
            setTimeout(() => { bar.style.height = target; }, 120);
        });
    });
});

// ── Cetak Laporan (print window) ─────────────────────────────────────────────
function cetakLaporan() {
    const filter   = '<?= $filter ?>';
    const omzet    = 'Rp <?= number_format($totalOmzet,0,",",".") ?>';
    const trxCount = <?= $totalTrx ?>;
    const margin   = '<?= $margin ?>%';

    const win = window.open('', '_blank', 'width=700,height=900');
    win.document.write(`
    <!DOCTYPE html><html lang="id"><head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan - Solo Second Thrift</title>
    <style>
        body{font-family:Arial,sans-serif;padding:30px;color:#111;}
        h1{color:#388035;border-bottom:3px solid #388035;padding-bottom:8px;}
        .meta{color:#555;font-size:13px;margin-bottom:20px;}
        table{width:100%;border-collapse:collapse;margin-top:16px;}
        th{background:#388035;color:#fff;padding:8px 12px;text-align:left;font-size:13px;}
        td{padding:8px 12px;border-bottom:1px solid #eee;font-size:13px;}
        tr:nth-child(even){background:#f9f9f9;}
        .total-row{font-weight:800;background:#f0fdf4;}
        .footer{margin-top:30px;text-align:center;color:#888;font-size:12px;}
        @media print{body{padding:10px;}}
    </style>
    </head><body>
    <h1>Laporan Keuangan — Solo Second Thrift</h1>
    <div class="meta">Periode: <strong>${filter.toUpperCase()}</strong> &nbsp;|&nbsp;
        Dicetak: <strong>${new Date().toLocaleString('id-ID')}</strong></div>

    <table>
        <tr><th colspan="2">Ringkasan</th></tr>
        <tr><td>Total Omzet</td><td><strong>${omzet}</strong></td></tr>
        <tr><td>Jumlah Transaksi</td><td>${trxCount}</td></tr>
        <tr><td>Margin Profit (est.)</td><td>${margin}</td></tr>
    </table>

    <?php if (!empty($latestTrx)): ?>
    <table style="margin-top:24px;">
        <tr><th>Kode Transaksi</th><th>Item</th><th>Total</th><th>Waktu</th></tr>
        <?php foreach ($latestTrx as $trx): ?>
        <tr>
            <td><?= htmlspecialchars($trx['kode_transaksi']) ?></td>
            <td><?= htmlspecialchars(mb_substr($trx['items'],0,40)) ?></td>
            <td>Rp <?= number_format($trx['total_bayar'],0,',','.') ?></td>
            <td><?= date('d/m/Y H:i', strtotime($trx['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row">
            <td colspan="2">TOTAL</td>
            <td>Rp <?= number_format($totalOmzet,0,',','.') ?></td>
            <td><?= $totalTrx ?> transaksi</td>
        </tr>
    </table>
    <?php endif; ?>

    <div class="footer">Solo Second Thrift &copy; <?= date('Y') ?> &mdash; Laporan dibuat otomatis</div>
    </div>
    </body></html>`);
    win.document.close();
    setTimeout(() => { win.focus(); win.print(); }, 400);
}
</script>
</body>
</html>

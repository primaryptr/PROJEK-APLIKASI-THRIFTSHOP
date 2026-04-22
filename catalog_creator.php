<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'content_creator') {
    header("Location: login.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'Content Creator';

// Ambil total barang tersedia dari database
$queryTotal = "SELECT COUNT(*) as total FROM barang WHERE stok > 0";
$resTotal   = mysqli_query($conn, $queryTotal);
$rowTotal   = mysqli_fetch_assoc($resTotal);
$totalBarang = $rowTotal['total'] ?? 0;

// Ambil jumlah barang stok menipis (stok < 5, stok > 0) → kandidat highlight promosi
$queryMenipis = "SELECT COUNT(*) as total FROM barang WHERE stok > 0 AND stok < 5";
$resMenipis   = mysqli_query($conn, $queryMenipis);
$rowMenipis   = mysqli_fetch_assoc($resMenipis);
$stokMenipis  = $rowMenipis['total'] ?? 0;

// Ambil jumlah kategori tersedia
$queryKat = "SELECT COUNT(DISTINCT kategori) as total FROM barang WHERE stok > 0";
$resKat   = mysqli_query($conn, $queryKat);
$rowKat   = mysqli_fetch_assoc($resKat);
$totalKat = $rowKat['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Solo Second Thrift - Dashboard Creator</title>
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

        /* ===== PAGE BACKGROUND ===== */
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

        /* Physical buttons */
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

        /* ===== SCREEN BEZEL ===== */
        .screen-bezel {
            background: #000;
            border-radius: 42px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 850px;
        }

        /* ===== 1. STATUS BAR ===== */
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

        /* ===== 2. TOPBAR ===== */
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

        /* ===== 3. APP SCREEN ===== */
        .app-screen {
            flex: 1;
            background: var(--bg);
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: none;
        }

        .app-screen::-webkit-scrollbar {
            display: none;
        }

        .app-screen::-webkit-scrollbar {
            display: none;
        }

        /* ── GREETING BANNER ── */
        .greeting-banner {
            background: linear-gradient(135deg, var(--green) 0%, #1f7a70 100%);
            margin: 16px 16px 0;
            border-radius: 20px;
            padding: 18px 20px 20px;
            border: 2px solid var(--charcoal);
            box-shadow: 3px 3px 0 var(--charcoal);
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .banner-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, .15);
            border-radius: 14px;
            border: 2px solid rgba(255, 255, 255, .3);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .banner-icon svg {
            width: 24px;
            height: 24px;
            stroke: white;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .banner-text {
            flex: 1;
        }

        .banner-sup {
            font-size: 9px;
            font-weight: 700;
            color: rgba(255, 255, 255, .7);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 4px;
        }

        .banner-title {
            font-size: 15px;
            font-weight: 800;
            color: white;
            line-height: 1.2;
        }

        .banner-sub {
            font-size: 10px;
            font-weight: 600;
            color: rgba(255, 255, 255, .75);
            margin-top: 2px;
        }

        /* ── STAT CARDS ── */
        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            padding: 14px 16px 0;
        }

        .stat-card {
            border-radius: 14px;
            padding: 20px 10px;
            border: 2px solid var(--charcoal);
            box-shadow: 3px 3px 0 var(--charcoal);
            cursor: pointer;
            transition: transform .15s, box-shadow .15s;
            text-align: center;
        }

        .stat-card:active {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0 var(--charcoal);
        }

        .stat-card.gold {
            background: var(--gold);
        }

        .stat-card.green {
            background: var(--green);
        }

        .stat-card.dark {
            background: var(--charcoal);
        }

        .stat-icon {
            font-size: 18px;
            margin-bottom: 6px;
        }

        .stat-label {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: .7;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 18px;
            font-weight: 800;
            line-height: 1;
        }

        .stat-unit {
            font-size: 8px;
            font-weight: 700;
            opacity: .65;
            margin-top: 2px;
        }

        .stat-card.gold .stat-label,
        .stat-card.gold .stat-value,
        .stat-card.gold .stat-unit {
            color: var(--charcoal);
        }

        .stat-card.green .stat-label,
        .stat-card.green .stat-value,
        .stat-card.green .stat-unit {
            color: var(--bg);
        }

        .stat-card.dark .stat-label,
        .stat-card.dark .stat-value,
        .stat-card.dark .stat-unit {
            color: var(--bg);
        }

        /* ── SECTION HEADER ── */
        .section-divider {
            height: 1px;
            background: var(--charcoal);
            opacity: .1;
            margin: 16px 16px 0;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 14px 16px 10px;
        }

        .section-icon {
            width: 26px;
            height: 26px;
            border-radius: 7px;
            font-size: 13px;
            border: 2px solid var(--charcoal);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .section-icon.green-ic {
            background: var(--green);
        }

        .section-icon.gold-ic {
            background: var(--gold);
        }

        .section-header h2 {
            font-size: 14px;
            font-weight: 700;
            color: var(--charcoal);
        }

        .badge-count {
            margin-left: auto;
            background: var(--red);
            color: var(--bg);
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
            border: 1.5px solid var(--charcoal);
        }

        /* ── MENU GRID (aksi utama) ── */
        .menu-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 0 16px;
        }

        .menu-card {
            background: white;
            border: 2px solid var(--charcoal);
            border-radius: 16px;
            padding: 24px 16px;
            box-shadow: 3px 3px 0 var(--charcoal);
            cursor: pointer;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: transform .15s, box-shadow .15s;
        }

        .menu-card:active {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0 var(--charcoal);
        }

        .menu-card-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            border: 2px solid var(--charcoal);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .menu-card-icon.ic-green {
            background: var(--green);
        }

        .menu-card-icon.ic-gold {
            background: var(--gold);
        }

        .menu-card-icon.ic-red {
            background: var(--red);
        }

        .menu-card-icon.ic-dark {
            background: var(--charcoal);
        }

        .menu-card-icon svg {
            width: 18px;
            height: 18px;
            stroke: white;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .menu-card-icon.ic-gold svg {
            stroke: var(--charcoal);
        }

        .menu-card-title {
            font-size: 12px;
            font-weight: 800;
            color: var(--charcoal);
            line-height: 1.3;
        }

        .menu-card-desc {
            font-size: 9px;
            font-weight: 600;
            color: var(--charcoal);
            opacity: .5;
            margin-top: -4px;
        }

        .menu-card-arrow {
            align-self: flex-end;
            width: 22px;
            height: 22px;
            border-radius: 6px;
            background: var(--charcoal);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .menu-card-arrow svg {
            width: 12px;
            height: 12px;
            stroke: var(--bg);
            fill: none;
            stroke-width: 2.5;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* ── TIPS CARD ── */
        .tips-card {
            margin: 12px 16px 0;
            background: #fff8e6;
            border: 2px solid var(--gold);
            border-radius: 16px;
            padding: 14px 16px;
            box-shadow: 3px 3px 0 var(--gold);
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .tips-emoji {
            font-size: 22px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .tips-title {
            font-size: 11px;
            font-weight: 800;
            color: var(--charcoal);
            margin-bottom: 3px;
        }

        .tips-text {
            font-size: 9px;
            font-weight: 600;
            color: var(--charcoal);
            opacity: .65;
            line-height: 1.5;
        }

        /* ── BOTTOM NAV ── */
                /* ===== 4. BOTTOM NAV ===== */
        .bottom-nav {
            flex-shrink: 0;
            height: var(--nav-h);
            background: var(--bg);
            border-top: 2.5px solid var(--charcoal);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 4px;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            cursor: pointer;
            flex: 1;
            padding: 6px 0;
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

        /* ===== 5. HOME INDICATOR ===== */
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

        /* ===== LABEL ===== */
        .device-label {
            margin-top: 18px;
            color: rgba(255, 255, 255, 0.22);
            font-size: 10px;
            letter-spacing: 2.5px;
            text-transform: uppercase;
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
                    <svg viewBox="0 0 16 12" fill="white">
                        <rect x="0" y="8" width="3" height="4" rx=".5" />
                        <rect x="4" y="5" width="3" height="7" rx=".5" />
                        <rect x="8" y="2" width="3" height="10" rx=".5" />
                        <rect x="12" y="0" width="3" height="12" rx=".5" />
                    </svg>
                    <svg viewBox="0 0 16 12" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round">
                        <path d="M1 4.5C3.8 1.9 7 .5 8 .5s4.2 1.4 7 4" />
                        <path d="M3 7C4.8 5.3 6.5 4.5 8 4.5S11.2 5.3 13 7" />
                        <path d="M5.5 9.5C6.5 8.6 7.3 8 8 8s1.5.6 2.5 1.5" />
                        <circle cx="8" cy="11.5" r=".8" fill="white" />
                    </svg>
                    <svg viewBox="0 0 20 12" fill="none">
                        <rect x=".5" y=".5" width="16" height="11" rx="2" stroke="white" stroke-width="1.2" />
                        <rect x="2" y="2" width="11" height="8" rx="1" fill="white" />
                        <path d="M17.5 4v4" stroke="white" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                </div>
            </div>

            <!-- TOPBAR -->
            <div class="topbar">
                <div class="brand">
                    <div class="brand-logo">CC</div>
                    <div class="brand-text">
                        <h1>SOLO SECOND THRIFT</h1>
                        <span>Content Creator</span>
                    </div>
                </div>
                </div>
            </div>

            <!-- APP SCREEN -->
            <div class="app-screen">

                <!-- Greeting Banner -->
                <div class="greeting-banner">
                    <div class="banner-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z" />
                            <circle cx="12" cy="13" r="4" />
                        </svg>
                    </div>
                    <div class="banner-text">
                        <div class="banner-sup">Studio Konten</div>
                        <div class="banner-title">Halo, <?= htmlspecialchars(explode(' ', $nama)[0]) ?>! 👋</div>
                        <div class="banner-sub">Ambil foto &amp; buat katalog promosi</div>
                    </div>
                </div>

                <!-- Stat Cards -->
                <div class="stats">
                    <div class="stat-card green">
                        <div class="stat-icon">👕</div>
                        <div class="stat-label">Tersedia</div>
                        <div class="stat-value"><?= $totalBarang ?></div>
                        <div class="stat-unit">Produk</div>
                    </div>
                    <div class="stat-card gold">
                        <div class="stat-icon">⚡</div>
                        <div class="stat-label">Stok Tipis</div>
                        <div class="stat-value"><?= $stokMenipis ?></div>
                        <div class="stat-unit">Item</div>
                    </div>
                    <div class="stat-card dark">
                        <div class="stat-icon">🏷️</div>
                        <div class="stat-label">Kategori</div>
                        <div class="stat-value"><?= $totalKat ?></div>
                        <div class="stat-unit">Jenis</div>
                    </div>
                </div>

                <!-- Divider + Section Header -->
                <div class="section-divider"></div>
                <div class="section-header">
                    <div class="section-icon green-ic">📋</div>
                    <h2>Menu Utama</h2>
                </div>

                <!-- Menu Grid -->
                <div class="menu-grid">

                    <!-- Katalog Produk -->
                    <a href="catalog_creator.php" class="menu-card">
                        <div class="menu-card-icon ic-green">
                            <svg viewBox="0 0 24 24">
                                <rect x="3" y="3" width="7" height="7" rx="1" />
                                <rect x="14" y="3" width="7" height="7" rx="1" />
                                <rect x="3" y="14" width="7" height="7" rx="1" />
                                <rect x="14" y="14" width="7" height="7" rx="1" />
                            </svg>
                        </div>
                        <div>
                            <div class="menu-card-title">Katalog Produk</div>
                            <div class="menu-card-desc">Lihat semua barang</div>
                        </div>
                        <div class="menu-card-arrow">
                            <svg viewBox="0 0 24 24">
                                <path d="M5 12h14M13 6l6 6-6 6" />
                            </svg>
                        </div>
                    </a>

                    <!-- Ekspor Katalog -->
                    <a href="catalog_creator.php?filter=menipis" class="menu-card">
                        <div class="menu-card-icon ic-gold">
                            <svg viewBox="0 0 24 24">
                                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" y1="15" x2="12" y2="3" />
                            </svg>
                        </div>
                        <div>
                            <div class="menu-card-title">Ekspor Katalog</div>
                            <div class="menu-card-desc">Stok menipis &amp; spesial</div>
                        </div>
                        <div class="menu-card-arrow">
                            <svg viewBox="0 0 24 24">
                                <path d="M5 12h14M13 6l6 6-6 6" />
                            </svg>
                        </div>
                    </a>

                    <!-- Stok Menipis -->
                    <a href="catalog_creator.php?filter=menipis" class="menu-card">
                        <div class="menu-card-icon ic-red">
                            <svg viewBox="0 0 24 24">
                                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                                <line x1="12" y1="9" x2="12" y2="13" />
                                <line x1="12" y1="17" x2="12.01" y2="17" />
                            </svg>
                        </div>
                        <div>
                            <div class="menu-card-title">Stok Menipis</div>
                            <div class="menu-card-desc">Prioritas promosi</div>
                        </div>
                        <div class="menu-card-arrow">
                            <svg viewBox="0 0 24 24">
                                <path d="M5 12h14M13 6l6 6-6 6" />
                            </svg>
                        </div>
                    </a>

                    <!-- Profil -->
                    <a href="usercc.php" class="menu-card">
                        <div class="menu-card-icon ic-dark">
                            <svg viewBox="0 0 24 24">
                                <circle cx="12" cy="8" r="4" />
                                <path d="M4 20v-1a8 8 0 0116 0v1" />
                            </svg>
                        </div>
                        <div>
                            <div class="menu-card-title">Profil Saya</div>
                            <div class="menu-card-desc">Data akun &amp; logout</div>
                        </div>
                        <div class="menu-card-arrow">
                            <svg viewBox="0 0 24 24">
                                <path d="M5 12h14M13 6l6 6-6 6" />
                            </svg>
                        </div>
                    </a>

                </div><!-- /menu-grid -->

                <!-- Tips Card -->
                <div class="tips-card">
                    <div class="tips-emoji">💡</div>
                    <div>
                        <div class="tips-title">Tips Konten</div>
                        <div class="tips-text">Gunakan fitur <strong>Ekspor Katalog</strong> untuk mendapatkan info produk yang siap di-posting ke media sosial. Prioritaskan barang dengan stok menipis agar terjual lebih cepat!</div>
                    </div>
                </div>

            </div><!-- /app-screen -->

            <!-- BOTTOM NAV -->
                        <nav class="bottom-nav">
                <a href="dasboard_creator.php" class="nav-item active">
                    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5" /><rect x="14" y="3" width="7" height="7" rx="1.5" /><rect x="3" y="14" width="7" height="7" rx="1.5" /><rect x="14" y="14" width="7" height="7" rx="1.5" /></svg>
                    <span>Katalog</span>
                </a>
                <a href="usercc.php" class="nav-item">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4" /><path d="M2 21v-1a8 8 0 0116 0v1" stroke-linecap="round" /></svg>
                    <span>User</span>
                </a>
            </nav>

            <!-- HOME INDICATOR -->
            <div class="home-indicator">
                <div class="home-bar"></div>
            </div>

        </div><!-- /screen-bezel -->
    </div><!-- /android-device -->

    <div class="device-label">Solo Second Thrift &middot; Content Creator</div>

</body>

</html>

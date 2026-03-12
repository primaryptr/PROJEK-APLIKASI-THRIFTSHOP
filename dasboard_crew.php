<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Solo Second Thrift - Dashboard</title>
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

        /* ===== SCREEN BEZEL =====
           Flex column:
           status-bar → topbar → app-screen (scroll) → bottom-nav → home-indicator
        ===== */
        .screen-bezel {
            background: #000;
            border-radius: 42px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 780px;
        }

        /* ===== 1. STATUS BAR — diam ===== */
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

        /* ===== 2. TOPBAR — diam, tidak ikut scroll ===== */
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

        /* ===== 3. APP SCREEN — satu-satunya bagian yang scroll ===== */
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

        /* ===== STATS GRID ===== */
        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 14px 16px 0;
        }

        .stat-card {
            border-radius: 14px;
            padding: 14px 13px;
            border: 2px solid var(--charcoal);
            box-shadow: 3px 3px 0 var(--charcoal);
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .stat-card:active {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0 var(--charcoal);
        }

        .stat-card.gold {
            background: var(--gold);
        }

        .stat-card.teal {
            background: var(--green);
        }

        .stat-card.dark {
            background: var(--charcoal);
        }

        .stat-card.red {
            background: var(--red);
        }

        .stat-label {
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            opacity: 0.75;
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 17px;
            font-weight: 800;
            line-height: 1.1;
        }

        .stat-card.gold .stat-label,
        .stat-card.gold .stat-value {
            color: var(--charcoal);
        }

        .stat-card.teal .stat-label,
        .stat-card.teal .stat-value {
            color: var(--bg);
        }

        .stat-card.dark .stat-label,
        .stat-card.dark .stat-value {
            color: var(--bg);
        }

        .stat-card.red .stat-label,
        .stat-card.red .stat-value {
            color: var(--bg);
        }

        /* ===== DIVIDER ===== */
        .section-divider {
            height: 1px;
            background: var(--charcoal);
            opacity: 0.1;
            margin: 16px 16px 0;
        }

        /* ===== SECTION HEADER ===== */
        .section-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 14px 16px 10px;
        }

        .warning-icon {
            width: 26px;
            height: 26px;
            background: var(--gold);
            border-radius: 7px;
            font-size: 13px;
            border: 2px solid var(--charcoal);
            display: flex;
            align-items: center;
            justify-content: center;
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

        /* ===== STOCK LIST ===== */
        .stock-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 0 16px 16px;
        }

        .stock-item {
            background: white;
            border: 2px solid var(--charcoal);
            border-radius: 14px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 3px 3px 0 var(--charcoal);
            animation: slideIn 0.3s ease both;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stock-item:nth-child(1) {
            animation-delay: 0.05s;
        }

        .stock-item:nth-child(2) {
            animation-delay: 0.12s;
        }

        .stock-item:nth-child(3) {
            animation-delay: 0.19s;
        }

        .stock-item:active {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0 var(--charcoal);
        }

        .item-thumb {
            width: 46px;
            height: 46px;
            border-radius: 9px;
            background: #eee;
            border: 1.5px solid var(--charcoal);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .item-thumb svg {
            width: 24px;
            height: 24px;
            opacity: 0.3;
        }

        .item-info {
            flex: 1;
            min-width: 0;
        }

        .item-name {
            font-size: 12px;
            font-weight: 700;
            color: var(--charcoal);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .item-meta {
            display: flex;
            gap: 6px;
            margin-top: 4px;
            justify-content: space-between;
            align-items: center;
        }

        .meta-tag {
            font-size: 9px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 5px;
            border: 1.5px solid var(--charcoal);
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .meta-tag.rack {
            background: var(--gold);
            color: var(--charcoal);
            margin-right: auto;
        }

        .meta-tag.stok-empty {
            background: var(--red);
            color: white;
        }

        .meta-tag.stok-low {
            background: var(--gold);
            color: var(--charcoal);
        }

        .item-actions {
            display: flex;
            gap: 5px;
            flex-shrink: 0;
        }

        .btn-qty {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            border: 2px solid var(--charcoal);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            color: white;
            line-height: 1;
            transition: transform 0.1s;
        }

        .btn-qty:active {
            transform: scale(0.9);
        }

        .btn-qty.minus {
            background: var(--red);
        }

        .btn-qty.plus {
            background: var(--green);
        }

        /* ===== 4. BOTTOM NAV — diam, tidak ikut scroll ===== */
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

        /* ===== 5. HOME INDICATOR — diam ===== */
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

        <!-- Physical Buttons -->
        <div class="btn-power"></div>
        <div class="btn-vol-up"></div>
        <div class="btn-vol-down"></div>

        <div class="screen-bezel">

            <!-- ① STATUS BAR — diam -->
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

            <!-- ② TOPBAR — diam, logo & role selalu keliatan -->
            <div class="topbar">
                <div class="brand">
                    <div class="brand-logo">S²</div>
                    <div class="brand-text">
                        <h1>SOLO SECOND THRIFT</h1>
                        <span>Crew</span>
                    </div>
                </div>
                <div class="topbar-icon">
                    <svg viewBox="0 0 24 24" stroke-width="2.2">
                        <rect x="3" y="3" width="7" height="7" rx="1.5" />
                        <rect x="14" y="3" width="7" height="7" rx="1.5" />
                        <rect x="3" y="14" width="7" height="7" rx="1.5" />
                        <rect x="14" y="14" width="7" height="7" rx="1.5" />
                    </svg>
                </div>
            </div>

            <!-- ③ APP SCREEN — satu-satunya bagian yang scroll -->
            <div class="app-screen">

                <!-- STATS GRID -->
                <div class="stats">
                    <div class="stat-card gold">
                        <div class="stat-label">Transaksi</div>
                        <div class="stat-value">12 Transaksi</div>
                    </div>
                    <div class="stat-card teal">
                        <div class="stat-label">Total Omzet</div>
                        <div class="stat-value">Rp 1.250.000</div>
                    </div>
                    <div class="stat-card dark">
                        <div class="stat-label">Total Stok</div>
                        <div class="stat-value">146 Pcs</div>
                    </div>
                    <div class="stat-card red">
                        <div class="stat-label">Kategori</div>
                        <div class="stat-value">8 Jenis</div>
                    </div>
                </div>

                <div class="section-divider"></div>

                <div class="section-header">
                    <div class="warning-icon">⚠️</div>
                    <h2>Peringatan Stok</h2>
                    <div class="badge-count">3</div>
                </div>

                <div class="stock-list">
                    <?php
                    $stok_kritis = [
                        ['nama' => 'Vintage Tee Nirvana', 'harga' => 'Rp 129.000', 'stok' => 0],
                        ['nama' => 'Vintage Tee Nirvana', 'harga' => 'Rp 85.000', 'stok' => 0],
                        ['nama' => 'Vintage Tee Nirvana', 'harga' => 'Rp 195.000', 'stok' => 0],
                        ['nama' => 'Vintage Tee Nirvana', 'harga' => 'Rp 15.000', 'stok' => 0],
                        ['nama' => 'Vintage Tee Nirvana', 'harga' => 'Rp 125.000', 'stok' => 2],
                        ['nama' => 'Vintage Tee Nirvana', 'harga' => 'Rp 175.000', 'stok' => 3],
                    ];
                    foreach ($stok_kritis as $item):
                        $tag_class = 'stok-empty';
                        $tag_label = $item['stok'] == 0 ? 'Stok: 0' : 'Stok: ' . $item['stok'];
                    ?>
                        <div class="stock-item">
                            <div class="item-thumb">
                                <svg viewBox="0 0 24 24" stroke="#264653" fill="none" stroke-width="1.5">
                                    <path d="M20.38 3.46L16 2a4 4 0 01-8 0L3.62 3.46a2 2 0 00-1.34 2.23l.58 3.57a1 1 0 00.99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 002-2V10h2.15a1 1 0 00.99-.84l.58-3.57a2 2 0 00-1.34-2.23z" />
                                </svg>
                            </div>
                            <div class="item-info">
                                <div class="item-name"><?= htmlspecialchars($item['nama']) ?></div>
                                <div class="item-meta">
                                    <span class="meta-tag rack">Harga: <?= htmlspecialchars($item['harga']) ?></span>
                                    <span class="meta-tag <?= $tag_class ?>"><?= $tag_label ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div><!-- /app-screen ③ -->

            <!-- ④ BOTTOM NAV — diam -->
            <nav class="bottom-nav">
                <a href="dashboard.php" class="nav-item active">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7" rx="1.5" />
                        <rect x="14" y="3" width="7" height="7" rx="1.5" />
                        <rect x="3" y="14" width="7" height="7" rx="1.5" />
                        <rect x="14" y="14" width="7" height="7" rx="1.5" />
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="stok_crew.php" class="nav-item">
                    <svg viewBox="0 0 24 24">
                        <path d="M5 8h14M5 12h14M5 16h14" stroke-linecap="round" />
                    </svg>
                    <span>Stok</span>
                </a>
                <div class="nav-fab" onclick="window.location='transaksi_crew.php'">
                    <svg viewBox="0 0 24 24">
                        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M3 6h18M16 10a4 4 0 01-8 0" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <a href="laporan_crew.php" class="nav-item">
                    <svg viewBox="0 0 24 24">
                        <path d="M18 20V10M12 20V4M6 20v-6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span>Laporan</span>
                </a>
                <a href="profil_crew.php" class="nav-item">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="7" r="4" />
                        <path d="M2 21v-1a8 8 0 0116 0v1" stroke-linecap="round" />
                    </svg>
                    <span>User</span>
                </a>
            </nav>

            <!-- ⑤ HOME INDICATOR — diam -->
            <div class="home-indicator">
                <div class="home-bar"></div>
            </div>

        </div><!-- /screen-bezel -->
    </div><!-- /android-device -->

    <div class="device-label">Solo Second Thrift &middot; Android Preview</div>

</body>

</html>
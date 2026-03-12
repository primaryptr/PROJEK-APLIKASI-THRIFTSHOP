<?php
session_start();
require_once 'koneksi.php';

// Cek apakah user sudah login dan role adalah crew
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'crew') {
    header("Location: login.php");
    exit();
}

$productsData = [];
$query = "SELECT * FROM barang ORDER BY id_barang DESC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Konversi tipe (dari database bisa 'brand'/'nonbrand' jadi 'Brand'/'Non-Brand')
        $tipe = strtolower($row['tipe']);
        if ($tipe == 'brand') {
            $typeFormatted = 'Brand';
        } else {
            // Asumsi apapun selain brand adalah Non-Brand
            $typeFormatted = 'Non-Brand';
        }

        $productsData[] = [
            'id' => str_pad($row['id_barang'], 3, '0', STR_PAD_LEFT),
            'title' => strtoupper($row['nama_barang']),
            'price' => 'Rp.' . number_format($row['harga'], 0, ',', '.'),
            'stock' => (int)$row['stok'],
            'category' => strtoupper($row['kategori']),
            'type' => $typeFormatted
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Solo Second Thrift - Stok Crew</title>
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
            height: 780px;
            position: relative;
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
            position: relative;
        }

        .app-screen::-webkit-scrollbar {
            display: none;
        }

        /* ===== STOK TABS (TOGGLE) ===== */
        .title-stok {
            text-align: center;
            font-size: 16px;
            font-weight: 800;
            color: var(--charcoal);
            margin: 16px 0 10px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .stok-tabs {
            display: flex;
            padding: 0 16px 16px;
            justify-content: center;
        }

        .tab-group {
            display: flex;
            width: 100%;
            border: 2px solid var(--charcoal);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 2px 2px 0 var(--charcoal);
        }

        .btn-tab {
            flex: 1;
            padding: 10px 0;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            border: none;
            background: white;
            color: var(--charcoal);
            text-align: center;
        }

        .btn-tab.active {
            background: var(--green);
            color: white;
            border-right: 2px solid var(--charcoal);
        }

        .btn-tab:not(.active) {
            border-left: 2px solid var(--charcoal);
            margin-left: -2px;
            /* Overlap border untuk tab non-active */
        }

        /* ===== SEARCH BAR ===== */
        .search-container {
            padding: 0 16px 12px;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: white;
            border: 2px solid var(--charcoal);
            border-radius: 14px;
            padding: 10px 14px;
            gap: 10px;
            box-shadow: 3px 3px 0 var(--charcoal);
        }

        .search-box svg {
            width: 18px;
            height: 18px;
            stroke: var(--charcoal);
            stroke-width: 2.5;
            fill: none;
        }

        .search-box input {
            flex: 1;
            border: none;
            outline: none;
            font-family: inherit;
            font-size: 11px;
            font-weight: 700;
            color: var(--charcoal);
            background: transparent;
        }

        .search-box input::placeholder {
            color: var(--charcoal);
            opacity: 0.4;
        }

        /* ===== FILTER CHIPS ===== */
        .filter-container {
            padding: 0 16px 16px;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .filter-container::-webkit-scrollbar {
            display: none;
        }

        .filter-chip {
            background: var(--gold);
            color: var(--charcoal);
            font-size: 9px;
            font-weight: 800;
            padding: 6px 12px;
            border-radius: 12px;
            border: 2px solid var(--charcoal);
            box-shadow: 2px 2px 0 var(--charcoal);
            cursor: pointer;
            white-space: nowrap;
        }

        /* ===== PRODUCT LIST ===== */
        .product-list {
            padding: 0 16px 80px;
            /* space for FAB */
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .product-card {
            background: white;
            border: 2px solid var(--charcoal);
            border-radius: 20px;
            padding: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 4px 4px 0 var(--charcoal);
        }

        .product-img {
            width: 45px;
            height: 45px;
            background: #6C757D;
            border: 2px solid var(--charcoal);
            border-radius: 10px;
            flex-shrink: 0;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .product-info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .product-title {
            font-size: 11px;
            font-weight: 800;
            color: var(--charcoal);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-transform: uppercase;
        }

        .product-meta {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .product-price-stock {
            color: var(--green);
            font-size: 10px;
            font-weight: 800;
        }

        .product-id-badge {
            border: 1.5px solid var(--charcoal);
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 8px;
            font-weight: 800;
            color: var(--charcoal);
            background: white;
            letter-spacing: 0.5px;
        }

        .product-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
            align-items: center;
            margin-right: 4px;
        }

        .product-actions svg {
            width: 24px;
            height: 24px;
            cursor: pointer;
            transition: transform 0.1s;
        }

        .product-actions svg:active {
            transform: scale(0.9);
        }

        .icon-trash {
            stroke: var(--red);
            stroke-width: 2;
            fill: none;
        }

        .icon-edit {
            stroke: var(--red);
            stroke-width: 2;
            fill: none;
        }

        /* ===== FAB TRANSAKSI MODIFIED ===== */
        .fab-container {
            position: absolute;
            bottom: 110px;
            /* Nilai nav-h(70px) + home-indicator(26px) + margin(14px) */
            right: 20px;
            z-index: 50;
        }

        .btn-tambah-fab {
            background: var(--green);
            color: white;
            font-size: 12px;
            font-weight: 800;
            padding: 12px 20px;
            border-radius: 12px;
            border: 2px solid var(--charcoal);
            box-shadow: 3px 3px 0 var(--charcoal);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: transform 0.1s, box-shadow 0.1s;
        }

        .btn-tambah-fab:active {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0 var(--charcoal);
        }

        /* ===== 4. BOTTOM NAV ===== */
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

        <!-- Physical Buttons -->
        <div class="btn-power"></div>
        <div class="btn-vol-up"></div>
        <div class="btn-vol-down"></div>

        <div class="screen-bezel">

            <!-- ① STATUS BAR -->
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

            <!-- ② TOPBAR -->
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

            <!-- ③ APP SCREEN -->
            <div class="app-screen">

                <div class="title-stok">KELOLA STOK BARANG</div>

                <div class="stok-tabs">
                    <div class="tab-group">
                        <button class="btn-tab active">Brand</button>
                        <button class="btn-tab">Non-Brand</button>
                    </div>
                </div>

                <div class="search-container">
                    <div class="search-box">
                        <svg viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8" />
                            <path d="M21 21l-4.35-4.35" stroke-linecap="round" />
                        </svg>
                        <input type="text" id="search-input" placeholder="CARI BARANG / RAK..." />
                    </div>
                </div>

                <div class="filter-container" id="filter-container">
                    <button class="filter-chip active" data-filter="SEMUA">SEMUA</button>
                    <button class="filter-chip" data-filter="KAOS/POLO">KAOS/POLO</button>
                    <button class="filter-chip" data-filter="KEMEJA/FLANEL">KEMEJA/FLANEL</button>
                    <button class="filter-chip" data-filter="HOODIE/CN">HOODIE/CN</button>
                    <button class="filter-chip" data-filter="JAKET/AIRISM">JAKET/AIRISM</button>
                    <button class="filter-chip" data-filter="JAS/BLAZER">JAS/BLAZER</button>
                    <button class="filter-chip" data-filter="RAJUT/CROP">RAJUT/CROP</button>
                    <button class="filter-chip" data-filter="CHINOS">CHINOS</button>
                    <button class="filter-chip" data-filter="JEANS">JEANS</button>
                    <button class="filter-chip" data-filter="CELANA PENDEK">CELANA PENDEK</button>
                    <button class="filter-chip" data-filter="TRAINING">TRAINING</button>
                    <button class="filter-chip" data-filter="CELANA KANTOR">CELANA KANTOR</button>
                </div>

                <div class="product-list" id="product-list-container">
                    <!-- Products will be injected by JavaScript -->
                </div>

            </div><!-- /app-screen ③ -->

            <!-- Floating Added Tag Button ditaruh di luar scroll -->
            <div class="fab-container">
                <button class="btn-tambah-fab">+ Tambah</button>
            </div>

            <!-- ④ BOTTOM NAV -->
            <nav class="bottom-nav">
                <a href="dasboard_crew.php" class="nav-item">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7" rx="1.5" />
                        <rect x="14" y="3" width="7" height="7" rx="1.5" />
                        <rect x="3" y="14" width="7" height="7" rx="1.5" />
                        <rect x="14" y="14" width="7" height="7" rx="1.5" />
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="stok_crew.php" class="nav-item active">
                    <svg viewBox="0 0 24 24">
                        <path d="M5 8h14M5 12h14M5 16h14" stroke-linecap="round" />
                    </svg>
                    <span>Stok</span>
                </a>
                <div class="nav-fab" onclick="window.location='transaksi.php'">
                    <svg viewBox="0 0 24 24">
                        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M3 6h18M16 10a4 4 0 01-8 0" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <a href="laporan.php" class="nav-item">
                    <svg viewBox="0 0 24 24">
                        <path d="M18 20V10M12 20V4M6 20v-6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span>Laporan</span>
                </a>
                <a href="profil.php" class="nav-item">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="7" r="4" />
                        <path d="M2 21v-1a8 8 0 0116 0v1" stroke-linecap="round" />
                    </svg>
                    <span>User</span>
                </a>
            </nav>

            <!-- ⑤ HOME INDICATOR -->
            <div class="home-indicator">
                <div class="home-bar"></div>
            </div>

        </div><!-- /screen-bezel -->
    </div><!-- /android-device -->

    <div class="device-label">Solo Second Thrift &middot; Android Preview</div>

    <script>
        // Data dari Database (di-inject via PHP)
        const dbProducts = <?php echo json_encode($productsData); ?>;

        let activeCategory = 'SEMUA';
        let activeType = 'Brand';
        let searchTerm = '';

        // Render Function
        function renderProducts() {
            const container = document.getElementById('product-list-container');
            container.innerHTML = '';

            const filtered = dbProducts.filter(p => {
                const matchCategory = activeCategory === 'SEMUA' || p.category === activeCategory;
                const matchType = p.type === activeType;
                const matchSearch = searchTerm === '' || p.title.toLowerCase().includes(searchTerm.toLowerCase());
                return matchCategory && matchType && matchSearch;
            });

            if (filtered.length === 0) {
                container.innerHTML = `<div style="text-align:center; padding: 20px; color:gray; font-weight:bold; font-size:12px;">TIDAK ADA BARANG DITEMUKAN</div>`;
                return;
            }

            filtered.forEach(p => {
                const card = `
                    <div class="product-card">
                        <div class="product-img"></div>
                        <div class="product-info">
                            <div class="product-title">${p.title}</div>
                            <div class="product-meta">
                                <span class="product-price-stock">${p.price} | STOK : ${p.stock}</span>
                                <span class="product-id-badge">ID : ${p.id}</span>
                            </div>
                        </div>
                        <div class="product-actions">
                            <svg class="icon-edit" viewBox="0 0 24 24">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                    </div>
                `;
                container.innerHTML += card;
            });
        }

        // Event Label Type (Brand/Non-Brand)
        document.querySelectorAll('.tab-group .btn-tab').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.tab-group .btn-tab').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                activeType = e.target.innerText;
                renderProducts();
            });
        });

        // Event Label Kategori (Chips)
        document.querySelectorAll('.filter-chip').forEach(chip => {
            chip.addEventListener('click', (e) => {
                // Reset styling for all chips
                document.querySelectorAll('.filter-chip').forEach(c => {
                    c.style.background = 'var(--gold)';
                    c.classList.remove('active');
                    c.style.color = 'var(--charcoal)';
                });

                // Style Active Chip
                e.target.style.background = 'var(--charcoal)';
                e.target.style.color = 'white';
                e.target.classList.add('active');

                activeCategory = e.target.dataset.filter;
                renderProducts();
            });
        });

        // Init
        // Style 'SEMUA' chip on load
        document.querySelector('.filter-chip[data-filter="SEMUA"]').style.background = 'var(--charcoal)';
        document.querySelector('.filter-chip[data-filter="SEMUA"]').style.color = 'white';
        renderProducts();

        // Search Functionality
        const searchInput = document.getElementById('search-input');

        function performSearch() {
            searchTerm = searchInput.value.trim();
            renderProducts();
        }

        searchInput.addEventListener('keyup', performSearch);
    </script>
</body>

</html>
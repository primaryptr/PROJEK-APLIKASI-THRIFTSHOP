<?php
session_start();
require_once 'koneksi.php';

// Cek apakah user sudah login dan role adalah crew
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'crew') {
    header("Location: login.php");
    exit();
}

$success = false;
$errorMsg = '';

// Load data barang untuk JS autocalculate
$productsData = [];
$mapToDbId = [];

$query = "SELECT * FROM barang ORDER BY id_barang DESC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tipe = strtolower($row['tipe']);
        if ($tipe == 'brand') {
            $kode_tipe = 'B';
        } else {
            $kode_tipe = 'NB';
        }

        $kategori_kodes = [
            'KAOS/POLO' => 'KOS',
            'KEMEJA/FLANEL' => 'KMJ',
            'HOODIE/CN' => 'HOD',
            'JAKET/AIRISM' => 'JKT',
            'JAS/BLAZER' => 'JAS',
            'RAJUT/CROP' => 'RJT',
            'CHINOS' => 'CHN',
            'JEANS' => 'JNS',
            'CELANA PENDEK' => 'CPD',
            'TRAINING' => 'TRN',
            'CELANA KANTOR' => 'CKN'
        ];
        $kat = strtoupper($row['kategori']);
        $kode_kat = isset($kategori_kodes[$kat]) ? $kategori_kodes[$kat] : 'BRG';
        $kode_sku = $kode_tipe . '-' . $kode_kat . '-' . str_pad($row['id_barang'], 3, '0', STR_PAD_LEFT);

        $cleanSku = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($kode_sku));

        $productsData[] = [
            'sku' => $kode_sku,
            'cleanSku' => $cleanSku,
            'title' => strtoupper($row['nama_barang']),
            'price' => (int)$row['harga'],
            'stock' => (int)$row['stok'],
            'db_id' => $row['id_barang']
        ];
        $mapToDbId[$cleanSku] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_input = $_POST['kode_barang'];
    $qty = (int)$_POST['qty'];

    $clean_input = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($kode_input));

    if (isset($mapToDbId[$clean_input])) {
        $barang = $mapToDbId[$clean_input];
        if ($barang['stok'] >= $qty) {
            $id_barang = $barang['id_barang'];
            $total_harga = $barang['harga'] * $qty;

            // Insert transaksi
            $stmt = $conn->prepare("INSERT INTO transaksi (id_barang, qty, total_harga) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $id_barang, $qty, $total_harga);
            if ($stmt->execute()) {
                // Update stok
                $new_stok = $barang['stok'] - $qty;
                $stmt2 = $conn->prepare("UPDATE barang SET stok = ? WHERE id_barang = ?");
                $stmt2->bind_param("ii", $new_stok, $id_barang);
                if ($stmt2->execute()) {
                    $success = true;
                    // Update fresh data js
                    $mapToDbId[$clean_input]['stok'] = $new_stok;
                    foreach ($productsData as &$pd) {
                        if ($pd['cleanSku'] == $clean_input) {
                            $pd['stock'] = $new_stok;
                        }
                    }
                }
            } else {
                $errorMsg = 'Gagal menyimpan transaksi!';
            }
        } else {
            $errorMsg = 'Stok tidak mencukupi untuk jumlah transaksi ini!';
        }
    } else {
        $errorMsg = 'Kode barang tidak ditemukan di sistem!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Solo Second Thrift - Transaksi</title>
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

        .screen-bezel {
            background: #000;
            border-radius: 42px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 780px;
            position: relative;
        }

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

        .page-title {
            text-align: center;
            font-size: 20px;
            font-weight: 800;
            color: var(--charcoal);
            margin: 16px 0 20px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            text-shadow: 2px 2px 0px rgba(0, 0, 0, 0.1);
        }

        .transaksi-card {
            background: white;
            margin: 0 16px 40px;
            border-radius: 20px;
            padding: 20px 16px;
            box-shadow: 4px 4px 0px var(--charcoal);
            border: 2px solid var(--charcoal);
        }

        .form-group {
            padding: 0 0 16px;
        }

        .form-label {
            font-size: 11px;
            font-weight: 800;
            color: var(--charcoal);
            margin-bottom: 8px;
            display: block;
            text-transform: uppercase;
            opacity: 0.7;
        }

        .form-input {
            width: 100%;
            background: white;
            border: 2px solid var(--charcoal);
            border-radius: 14px;
            padding: 12px 14px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            color: var(--charcoal);
            box-shadow: 3px 3px 0 var(--charcoal);
            outline: none;
            transition: 0.2s;
        }

        .form-input:focus {
            background: #F8F9FA;
            box-shadow: 1px 1px 0 var(--charcoal);
            transform: translate(2px, 2px);
        }

        .form-input::placeholder {
            color: var(--charcoal);
            opacity: 0.4;
            font-weight: 600;
        }

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }

        .totals-section {
            margin-top: 10px;
            padding-top: 16px;
            border-top: 2px dashed rgba(38, 70, 83, 0.2);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            font-weight: 600;
            color: var(--charcoal);
            opacity: 0.8;
        }

        .totals-row.total-bayar {
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            opacity: 1;
        }

        .btn-submit {
            background: var(--green);
            color: white;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.5px;
            padding: 14px;
            border-radius: 14px;
            border: 2px solid var(--charcoal);
            box-shadow: 3px 3px 0 var(--charcoal);
            cursor: pointer;
            width: 100%;
            margin: 24px 0 0;
            transition: transform 0.1s, box-shadow 0.1s;
            display: block;
        }

        .btn-submit:active {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0 var(--charcoal);
        }

        .notification {
            position: absolute;
            top: 100px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--green);
            color: white;
            padding: 12px 24px;
            border-radius: 14px;
            border: 2px solid var(--charcoal);
            box-shadow: 4px 4px 0 var(--charcoal);
            font-weight: 800;
            font-size: 12px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideDown 0.5s ease out, fadeOut 0.5s ease 2.5s forwards;
            pointer-events: none;
        }

        @keyframes slideDown {
            from {
                top: 80px;
                opacity: 0;
            }

            to {
                top: 100px;
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                visibility: hidden;
            }
        }

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

        .nav-fab.active {
            box-shadow: 0 0 10px var(--red);
        }

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

        .device-label {
            margin-top: 18px;
            color: rgba(255, 255, 255, 0.22);
            font-size: 10px;
            letter-spacing: 2.5px;
            text-transform: uppercase;
        }

        .back-btn {
            background: none;
            border: none;
            padding: 0;
            margin-right: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .back-btn svg {
            width: 20px;
            height: 20px;
            stroke: var(--charcoal);
            stroke-width: 2.5;
        }
    </style>
</head>

<body>
    <div class="android-device">
        <div class="btn-power"></div>
        <div class="btn-vol-up"></div>
        <div class="btn-vol-down"></div>
        <div class="screen-bezel">

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

            <div class="topbar">
                <div class="brand">
                    <div class="brand-logo">S²</div>
                    <div class="brand-text">
                        <h1>SOLO SECOND THRIFT</h1>
                        <span>Crew</span>
                    </div>
                </div>
            </div>

            <div class="app-screen">
                <?php if ($success): ?>
                    <div class="notification">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Transaksi tersimpan!
                    </div>
                <?php endif; ?>
                <?php if (!empty($errorMsg)): ?>
                    <div class="notification" style="background: var(--red);">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        <?= $errorMsg ?>
                    </div>
                <?php endif; ?>

                <div class="page-title">PENJUALAN</div>
                <div style="padding: 0 16px; font-weight: 800; font-size: 14px; margin-bottom: 12px; color: var(--charcoal);">Input Transaksi</div>

                <div class="transaksi-card">
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Scan/ Kode Barang</label>
                            <input type="text" id="kode_barang" name="kode_barang" class="form-input" placeholder="Contoh : NB-KOS-012" required autocomplete="off">
                            <div id="product-hint" style="font-size:10px; font-weight:700; color:var(--green); margin-top:6px; margin-left:4px; height:12px;"></div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Jumlah (Qty)</label>
                            <input type="number" id="qty" name="qty" class="form-input" placeholder="Contoh : 1" value="1" required min="1">
                        </div>

                        <div class="totals-section">
                            <div class="totals-row">
                                <span>Subtotal</span>
                                <span id="subtotal_text" style="color: var(--charcoal); font-weight: 800;">Rp. 0</span>
                            </div>
                            <div class="totals-row total-bayar">
                                <span>TOTAL BAYAR</span>
                                <span id="total_bayar_text" style="color: var(--red);">Rp. 0</span>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">SIMPAN & CETAK NOTA</button>
                    </form>
                </div>

            </div><!-- /app-screen -->

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
                <a href="stok_crew.php" class="nav-item">
                    <svg viewBox="0 0 24 24">
                        <path d="M5 8h14M5 12h14M5 16h14" stroke-linecap="round" />
                    </svg>
                    <span>Stok</span>
                </a>
                <!-- Middle Fab di Transaksi -->
                <div class="nav-fab active" onclick="window.location='transaksi.php'" style="background:var(--red);">
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

            <div class="home-indicator">
                <div class="home-bar"></div>
            </div>
        </div>
    </div>
    <div class="device-label">Solo Second Thrift &middot; Android Preview</div>

    <script>
        const dbProducts = <?php echo json_encode($productsData); ?>;

        const inputKode = document.getElementById('kode_barang');
        const inputQty = document.getElementById('qty');
        const textSubtotal = document.getElementById('subtotal_text');
        const textTotal = document.getElementById('total_bayar_text');
        const textHint = document.getElementById('product-hint');

        function formatRupiah(angka) {
            return 'Rp. ' + angka.toLocaleString('id-ID');
        }

        function calculateTotal() {
            const rawSku = inputKode.value.toLowerCase().replace(/[^a-z0-9]/g, '');
            const qty = parseInt(inputQty.value) || 0;

            // Find product using smart search (exact SKU without dash, OR just the ID number!)
            const product = dbProducts.find(p => p.cleanSku === rawSku || p.db_id == parseInt(rawSku));

            if (product) {
                textHint.textContent = `🛒 ${product.title} (STOK: ${product.stock})`;
                if (qty > product.stock) {
                    textHint.style.color = 'var(--red)';
                    textHint.textContent += ' - STOK TIDAK CUKUP!';
                } else {
                    textHint.style.color = 'var(--green)';
                }

                const total = product.price * qty;
                textSubtotal.textContent = formatRupiah(total);
                textTotal.textContent = formatRupiah(total);
            } else {
                if (rawSku.length > 3) {
                    textHint.textContent = `Kode barang tidak ditemukan!`;
                    textHint.style.color = 'var(--red)';
                } else {
                    textHint.textContent = ``;
                }
                textSubtotal.textContent = 'Rp. 0';
                textTotal.textContent = 'Rp. 0';
            }
        }

        inputKode.addEventListener('input', calculateTotal);
        inputQty.addEventListener('input', calculateTotal);

        // Hide popup when tapping anywhere
        document.body.addEventListener('click', () => {
            document.querySelectorAll('.notification').forEach(n => {
                n.style.display = 'none';
            });
        });
    </script>
</body>

</html>
<?php
session_start();
require_once 'koneksi.php';

// Cek login dan role crew
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'crew') {
    header("Location: login.php");
    exit();
}

$user_id  = (int)$_SESSION['user_id'];
$success  = false;
$errorMsg = '';

// Load data barang dari DB (hanya field yang ada)
$productsData = [];
$mapByKode    = [];

$result = mysqli_query($conn, "SELECT id, kode_barang, nama_barang, harga, stok FROM barang ORDER BY id DESC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $clean = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $row['kode_barang']));
        $productsData[] = [
            'db_id'    => (int)$row['id'],
            'sku'      => $row['kode_barang'],
            'cleanSku' => $clean,
            'title'    => strtoupper($row['nama_barang']),
            'price'    => (int)$row['harga'],
            'stock'    => (int)$row['stok'],
        ];
        $mapByKode[$clean] = $row;
    }
}

// Handle Transaksi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_input = trim($_POST['kode_barang']);
    $qty        = (int)$_POST['qty'];
    $clean_in   = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $kode_input));

    if (isset($mapByKode[$clean_in])) {
        $brg = $mapByKode[$clean_in];

        if ($qty <= 0) {
            $errorMsg = 'Jumlah tidak valid!';
        } elseif ($brg['stok'] < $qty) {
            $errorMsg = 'Stok tidak mencukupi! (Tersisa: ' . $brg['stok'] . ')';
        } else {
            $total_bayar   = (int)$brg['harga'] * $qty;
            $kode_transaksi = 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            $conn->begin_transaction();
            try {
                // 1. Insert header transaksi
                $s1 = $conn->prepare("INSERT INTO transaksi (kode_transaksi, user_id, total_bayar) VALUES (?, ?, ?)");
                $s1->bind_param("sii", $kode_transaksi, $user_id, $total_bayar);
                $s1->execute();
                $transaksi_id = $conn->insert_id;
                $s1->close();

                // 2. Insert detail transaksi
                $harga_satuan = (int)$brg['harga'];
                $subtotal     = $total_bayar;
                $s2 = $conn->prepare("INSERT INTO transaksi_detail (transaksi_id, kode_barang, nama_barang, harga_satuan, qty, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                $s2->bind_param("issiid", $transaksi_id, $brg['kode_barang'], $brg['nama_barang'], $harga_satuan, $qty, $subtotal);
                $s2->execute();
                $s2->close();

                // 3. Kurangi stok
                $new_stok = $brg['stok'] - $qty;
                $s3 = $conn->prepare("UPDATE barang SET stok = ? WHERE id = ?");
                $s3->bind_param("ii", $new_stok, $brg['id']);
                $s3->execute();
                $s3->close();

                $conn->commit();
                $success = true;

                // Update data JS agar stok tampil terbaru
                foreach ($productsData as &$pd) {
                    if ($pd['cleanSku'] === $clean_in) {
                        $pd['stock'] = $new_stok;
                    }
                }
                unset($pd);

            } catch (Exception $e) {
                $conn->rollback();
                $errorMsg = 'Transaksi gagal: ' . $e->getMessage();
            }
        }
    } else {
        $errorMsg = 'Kode barang tidak ditemukan!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Solo Second Thrift - Transaksi Crew</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <style>
        :root {
            --bg: #FDFCF0;
            --charcoal: #264653;
            --red: #B23A48;
            --gold: #E9C46A;
            --green: #2A9D8F;
            --nav-h: 70px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #12121f;
            background-image:
                radial-gradient(ellipse at 15% 50%, rgba(38,70,83,0.45) 0%, transparent 55%),
                radial-gradient(ellipse at 85% 15%, rgba(178,58,72,0.25) 0%, transparent 50%);
            min-height: 100vh;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 40px 20px;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* ANDROID FRAME */
        .android-device {
            position: relative; width: 393px;
            background: linear-gradient(160deg, #3a3a3a 0%, #1e1e1e 50%, #111 100%);
            border-radius: 54px; padding: 15px;
            box-shadow: 0 0 0 1.5px #4a4a4a, 0 0 0 3px #1a1a1a, 6px 6px 0 4px #000,
                0 40px 100px rgba(0,0,0,0.85), inset 0 2px 0 rgba(255,255,255,0.1);
        }
        .btn-power   { position:absolute; right:-5px; top:140px; width:5px; height:55px; background:linear-gradient(to right,#2a2a2a,#4a4a4a,#2a2a2a); border-radius:0 4px 4px 0; }
        .btn-vol-up  { position:absolute; left:-5px;  top:120px; width:5px; height:42px; background:linear-gradient(to left,#2a2a2a,#4a4a4a,#2a2a2a); border-radius:4px 0 0 4px; }
        .btn-vol-down{ position:absolute; left:-5px;  top:172px; width:5px; height:42px; background:linear-gradient(to left,#2a2a2a,#4a4a4a,#2a2a2a); border-radius:4px 0 0 4px; }

        .screen-bezel {
            background: #000; border-radius: 42px; overflow: hidden;
            display: flex; flex-direction: column; height: 780px; position: relative;
        }

        /* STATUS BAR */
        .status-bar {
            flex-shrink: 0; background: #000; height: 34px;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 22px 0 18px; position: relative;
        }
        .punch-hole { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:12px; height:12px; background:#000; border-radius:50%; border:2px solid #1c1c1c; }
        .status-time { font-size:11px; font-weight:700; color:#fff; }
        .status-icons { display:flex; align-items:center; gap:4px; }
        .status-icons svg { width:13px; height:13px; }

        /* TOPBAR */
        .topbar {
            flex-shrink: 0; background: var(--bg);
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 18px 12px;
        }
        .brand { display:flex; align-items:center; gap:10px; }
        .brand-logo {
            width:40px; height:40px; border-radius:50%; background:var(--red);
            display:flex; align-items:center; justify-content:center;
            font-size:14px; font-weight:800; color:var(--bg); box-shadow:2px 2px 0 var(--charcoal);
        }
        .brand-text h1 { font-size:13px; font-weight:700; color:var(--charcoal); line-height:1; }
        .brand-text span { font-size:10px; font-weight:500; color:var(--charcoal); opacity:0.5; text-transform:uppercase; letter-spacing:2px; }

        /* APP SCREEN */
        .app-screen {
            flex:1; background:var(--bg);
            overflow-y:auto; overflow-x:hidden; scrollbar-width:none; position:relative;
        }
        .app-screen::-webkit-scrollbar { display:none; }

        /* PAGE TITLE */
        .page-title {
            text-align:center; font-size:20px; font-weight:800; color:var(--charcoal);
            margin:16px 0 6px; letter-spacing:0.5px; text-transform:uppercase;
            text-shadow:2px 2px 0 rgba(0,0,0,0.1);
        }
        .page-sub { text-align:center; font-size:11px; font-weight:600; color:var(--charcoal); opacity:0.5; margin-bottom:18px; }

        /* TRANSAKSI CARD */
        .transaksi-card {
            background: white; margin: 0 16px 16px;
            border-radius: 20px; padding: 20px 16px;
            box-shadow: 4px 4px 0 var(--charcoal); border: 2px solid var(--charcoal);
        }

        /* FORM */
        .form-group { padding: 0 0 14px; }
        .form-label { font-size:11px; font-weight:800; color:var(--charcoal); margin-bottom:8px; display:block; text-transform:uppercase; opacity:0.7; }
        .form-input {
            width:100%; background:white; border:2px solid var(--charcoal); border-radius:14px;
            padding:12px 14px; font-family:inherit; font-size:12px; font-weight:700; color:var(--charcoal);
            box-shadow:3px 3px 0 var(--charcoal); outline:none; transition:0.2s;
        }
        .form-input:focus { background:#F8F9FA; box-shadow:1px 1px 0 var(--charcoal); transform:translate(2px,2px); }
        .form-input::placeholder { color:var(--charcoal); opacity:0.4; font-weight:600; }
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance:none; margin:0; }
        input[type=number] { -moz-appearance:textfield; }

        /* STOK HINT */
        .hint-box {
            font-size:10px; font-weight:700; margin-top:6px; margin-left:2px;
            height:14px; transition:color 0.2s;
        }

        /* TOTALS */
        .totals-section {
            margin-top:10px; padding-top:16px;
            border-top:2px dashed rgba(38,70,83,0.2);
            display:flex; flex-direction:column; gap:10px;
        }
        .totals-row { display:flex; justify-content:space-between; align-items:center; font-size:11px; font-weight:600; color:var(--charcoal); opacity:0.8; }
        .totals-row.total-bayar { font-size:15px; font-weight:800; opacity:1; text-transform:uppercase; }

        /* SUBMIT */
        .btn-submit {
            background:var(--green); color:white; font-size:13px; font-weight:800;
            letter-spacing:0.5px; padding:14px; border-radius:14px;
            border:2px solid var(--charcoal); box-shadow:3px 3px 0 var(--charcoal);
            cursor:pointer; width:100%; margin:20px 0 0; transition:transform 0.1s, box-shadow 0.1s;
        }
        .btn-submit:active { transform:translate(2px,2px); box-shadow:1px 1px 0 var(--charcoal); }

        /* QUICK LINK TAMBAH */
        .quick-link {
            display:block; text-align:center; font-size:11px; font-weight:700;
            color:var(--charcoal); opacity:0.6; text-decoration:none;
            margin:0 16px 30px; padding:10px;
            border:2px dashed rgba(38,70,83,0.3); border-radius:12px;
            transition:opacity 0.2s, background 0.2s;
        }
        .quick-link:hover { opacity:1; background:rgba(38,70,83,0.05); }

        /* NOTIFICATION */
        .notification {
            position:absolute; top:100px; left:50%; transform:translateX(-50%);
            background:var(--green); color:white;
            padding:12px 24px; border-radius:14px;
            border:2px solid var(--charcoal); box-shadow:4px 4px 0 var(--charcoal);
            font-weight:800; font-size:12px; z-index:1000;
            display:flex; align-items:center; gap:8px;
            animation:slideDown 0.5s ease, fadeOut 0.5s ease 2.5s forwards;
            pointer-events:none; white-space:nowrap;
        }
        @keyframes slideDown { from{top:80px;opacity:0;} to{top:100px;opacity:1;} }
        @keyframes fadeOut   { to{opacity:0;visibility:hidden;} }

        /* BOTTOM NAV */
        .bottom-nav {
            flex-shrink:0; height:var(--nav-h); background:var(--bg);
            border-top:2.5px solid var(--charcoal);
            display:flex; align-items:center; justify-content:space-around; padding:0 6px;
        }
        .nav-item { display:flex; flex-direction:column; align-items:center; gap:3px; cursor:pointer; padding:6px 12px; border-radius:10px; text-decoration:none; transition:background 0.15s; }
        .nav-item:hover { background:rgba(38,70,83,0.07); }
        .nav-item svg  { width:20px; height:20px; stroke:var(--charcoal); fill:none; stroke-width:2; }
        .nav-item span { font-size:9px; font-weight:600; color:var(--charcoal); text-transform:uppercase; letter-spacing:0.8px; }
        .nav-item.active svg  { stroke:var(--red); }
        .nav-item.active span { color:var(--red); }
        .nav-fab {
            width:48px; height:48px; border-radius:50%; background:var(--red);
            border:2.5px solid var(--charcoal); box-shadow:3px 3px 0 var(--charcoal);
            display:flex; align-items:center; justify-content:center;
            cursor:pointer; margin-top:-20px; flex-shrink:0;
        }
        .nav-fab svg { width:21px; height:21px; stroke:white; fill:none; stroke-width:2.2; }

        .home-indicator { flex-shrink:0; background:#000; height:26px; display:flex; align-items:center; justify-content:center; }
        .home-bar { width:90px; height:4px; background:#3a3a3a; border-radius:3px; }
        .device-label { margin-top:18px; color:rgba(255,255,255,0.22); font-size:10px; letter-spacing:2.5px; text-transform:uppercase; }
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
                    <svg viewBox="0 0 16 12" fill="white"><rect x="0" y="8" width="3" height="4" rx="0.5"/><rect x="4" y="5" width="3" height="7" rx="0.5"/><rect x="8" y="2" width="3" height="10" rx="0.5"/><rect x="12" y="0" width="3" height="12" rx="0.5"/></svg>
                    <svg viewBox="0 0 16 12" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round"><path d="M1 4.5C3.8 1.9 7 .5 8 .5s4.2 1.4 7 4"/><path d="M3 7C4.8 5.3 6.5 4.5 8 4.5S11.2 5.3 13 7"/><path d="M5.5 9.5C6.5 8.6 7.3 8 8 8s1.5.6 2.5 1.5"/><circle cx="8" cy="11.5" r="0.8" fill="white"/></svg>
                    <svg viewBox="0 0 20 12" fill="none"><rect x="0.5" y="0.5" width="16" height="11" rx="2" stroke="white" stroke-width="1.2"/><rect x="2" y="2" width="11" height="8" rx="1" fill="white"/><path d="M17.5 4v4" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
                </div>
            </div>

            <!-- TOPBAR -->
            <div class="topbar">
                <div class="brand">
                    <div class="brand-logo">S²</div>
                    <div class="brand-text">
                        <h1>SOLO SECOND THRIFT</h1>
                        <span>Crew</span>
                    </div>
                </div>
            </div>

            <!-- APP SCREEN -->
            <div class="app-screen">

                <?php if ($success): ?>
                    <div class="notification">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Transaksi tersimpan!
                    </div>
                <?php endif; ?>
                <?php if (!empty($errorMsg)): ?>
                    <div class="notification" style="background:var(--red);">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        <?= htmlspecialchars($errorMsg) ?>
                    </div>
                <?php endif; ?>

                <div class="page-title">PENJUALAN</div>
                <div class="page-sub">Input Transaksi Crew</div>

                <div class="transaksi-card">
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Kode Barang</label>
                            <input type="text" id="kode_barang" name="kode_barang"
                                class="form-input" placeholder="Contoh: BRG001" required autocomplete="off">
                            <div id="product-hint" class="hint-box"></div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Jumlah (Qty)</label>
                            <input type="number" id="qty" name="qty"
                                class="form-input" placeholder="Contoh: 1" value="1" required min="1">
                        </div>

                        <div class="totals-section">
                            <div class="totals-row">
                                <span>Subtotal</span>
                                <span id="subtotal-text" style="font-weight:800;">Rp 0</span>
                            </div>
                            <div class="totals-row total-bayar">
                                <span>Total Bayar</span>
                                <span id="total-text" style="color:var(--red);">Rp 0</span>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">SIMPAN TRANSAKSI</button>
                    </form>
                </div>

                <!-- Quick link ke Tambah Stok -->
                <a href="tambahstokcrew.php" class="quick-link">
                    + Tambah Barang Baru ke Stok →
                </a>

            </div><!-- /app-screen -->

            <!-- BOTTOM NAV -->
            <nav class="bottom-nav">
                <a href="dashboardcrew.php" class="nav-item">
                    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                    <span>Dashboard</span>
                </a>
                <a href="stokcrew.php" class="nav-item">
                    <svg viewBox="0 0 24 24"><path d="M5 8h14M5 12h14M5 16h14" stroke-linecap="round"/></svg>
                    <span>Stok</span>
                </a>
                <div class="nav-fab" style="box-shadow:0 0 14px var(--red), 3px 3px 0 var(--charcoal);">
                    <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 6h18M16 10a4 4 0 01-8 0" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <a href="laporancrew.php" class="nav-item">
                    <svg viewBox="0 0 24 24"><path d="M18 20V10M12 20V4M6 20v-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>Laporan</span>
                </a>
                <a href="profilcrew.php" class="nav-item">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M2 21v-1a8 8 0 0116 0v1" stroke-linecap="round"/></svg>
                    <span>User</span>
                </a>
            </nav>

            <div class="home-indicator"><div class="home-bar"></div></div>

        </div><!-- /screen-bezel -->
    </div><!-- /android-device -->

    <div class="device-label">Solo Second Thrift &middot; Android Preview</div>

    <script>
        const dbProducts = <?php echo json_encode($productsData); ?>;

        const inputKode    = document.getElementById('kode_barang');
        const inputQty     = document.getElementById('qty');
        const textSubtotal = document.getElementById('subtotal-text');
        const textTotal    = document.getElementById('total-text');
        const textHint     = document.getElementById('product-hint');

        function fmt(n) {
            return 'Rp ' + n.toLocaleString('id-ID');
        }

        function recalc() {
            const raw = inputKode.value.toLowerCase().replace(/[^a-z0-9]/g, '');
            const qty = parseInt(inputQty.value) || 0;
            const p = dbProducts.find(x => x.cleanSku === raw || String(x.db_id) === raw);

            if (p) {
                const overStock = qty > p.stock;
                textHint.textContent = `🛒 ${p.title} — Stok: ${p.stock}` + (overStock ? '  ⚠️ Stok tidak cukup!' : '');
                textHint.style.color = overStock ? 'var(--red)' : 'var(--green)';
                const total = p.price * qty;
                textSubtotal.textContent = fmt(total);
                textTotal.textContent    = fmt(total);
            } else {
                textHint.textContent = raw.length > 2 ? '❌ Kode tidak ditemukan' : '';
                textHint.style.color = 'var(--red)';
                textSubtotal.textContent = 'Rp 0';
                textTotal.textContent    = 'Rp 0';
            }
        }

        inputKode.addEventListener('input', recalc);
        inputQty.addEventListener('input', recalc);

        // Auto-update stok live
        setInterval(() => {
            fetch('get_stok.php')
                .then(res => res.json())
                .then(data => {
                    if(!data.error) {
                        data.forEach(item => {
                            const pd = dbProducts.find(p => p.db_id == item.id);
                            if(pd && pd.stock !== parseInt(item.stok)) {
                                pd.stock = parseInt(item.stok);
                                // Panggil recalc untuk update hint/info jika barang tersebut sedang discan
                                recalc(); 
                            }
                        });
                    }
                })
                .catch(err => console.error('Error fetching stok:', err));
        }, 3000);
    </script>
</body>

</html>
<?php
session_start();
require_once 'koneksi.php';

// Memastikan hanya role content_creator yang bisa masuk
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'content_creator') {
    header("Location: login.php");
    exit();
}

// Nama didapatkan dari session saat login
$nama = $_SESSION['user_name'] ?? 'Content Creator';

$queryBarang = "SELECT * FROM barang ORDER BY id DESC";
$resultBarang = mysqli_query($conn, $queryBarang);
$barang_list = [];
if ($resultBarang) {
    while ($row = mysqli_fetch_assoc($resultBarang)) {
        $barang_list[] = $row;
    }
}
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

        body {
            background: #12121f;
            background-image:
                radial-gradient(ellipse at 15% 50%, rgba(38, 70, 83, .45) 0%, transparent 55%),
                radial-gradient(ellipse at 85% 15%, rgba(178, 58, 72, .25) 0%, transparent 50%);
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
                0 40px 100px rgba(0, 0, 0, .85),
                inset 0 2px 0 rgba(255, 255, 255, .1);
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

        .btn-vol-dn {
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
            height: 850px;
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
            font-weight: 600;
            color: var(--red);
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
            text-decoration: none;
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
            padding: 16px;
        }

        .app-screen::-webkit-scrollbar {
            display: none;
        }

        /* ── STUDIO KONTEN BUTTON (BANNER) ── */
        .studio-btn {
            background: linear-gradient(135deg, var(--green) 0%, #1f7a70 100%);
            border-radius: 20px;
            padding: 20px 24px;
            border: 2px solid var(--charcoal);
            box-shadow: 4px 4px 0 var(--charcoal);
            display: flex;
            flex-direction: column;
            gap: 8px;
            text-decoration: none;
            color: white;
            transition: transform 0.15s, box-shadow 0.15s;
            margin-bottom: 24px;
        }

        .studio-btn:active {
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0 var(--charcoal);
        }

        .studio-btn svg {
            width: 28px;
            height: 28px;
            fill: none;
            stroke: white;
            stroke-width: 2;
            margin-bottom: 4px;
        }

        .studio-btn-title {
            font-size: 16px;
            font-weight: 800;
            font-style: italic;
            letter-spacing: 0.5px;
        }

        .studio-btn-sub {
            font-size: 10px;
            font-weight: 600;
            opacity: 0.85;
            font-style: italic;
        }

        /* ── CATALOG GRID ── */
        .catalog-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .katalog-card {
            background: white;
            border-radius: 12px;
            border: 2px solid var(--charcoal);
            box-shadow: 3px 3px 0 var(--charcoal);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .katalog-card:active {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0 var(--charcoal);
        }

        .katalog-img {
            width: 100%;
            height: 120px;
            background: #eee;
            object-fit: cover;
            border-bottom: 2px solid var(--charcoal);
        }

        .katalog-body {
            padding: 12px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .katalog-nama {
            font-size: 12px;
            font-weight: 800;
            color: var(--charcoal);
            line-height: 1.3;
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .katalog-harga {
            font-size: 11px;
            font-weight: 700;
            color: var(--green);
            margin-bottom: 12px;
            margin-top: auto;
        }

        .btn-copy {
            background: #318239;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px;
            font-size: 10px;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            width: 100%;
            transition: background 0.1s;
        }

        .btn-copy:active {
            background: #25612b;
        }

        /* ── TOAST NOTIFICATION ── */
        #toast {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(-20px);
            background: var(--green);
            color: white;
            padding: 12px 24px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            opacity: 0;
            pointer-events: none;
            transition: transform 0.3s ease, opacity 0.3s ease;
            z-index: 100;
            white-space: nowrap;
        }

        #toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* ── BOTTOM NAV ── */
        .bottom-nav {
            flex-shrink: 0;
            background: white;
            height: var(--nav-h);
            display: flex;
            align-items: center;
            justify-content: space-around;
            padding: 0 8px;
            border-top: 1px solid #eee;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            text-decoration: none;
            color: #aaa;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
            flex: 1;
        }

        .nav-item svg {
            width: 22px;
            height: 22px;
            fill: none;
            stroke: #aaa;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .nav-item.active {
            color: var(--red);
        }

        .nav-item.active svg {
            stroke: var(--red);
        }

        /* HOME INDICATOR */
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
            color: rgba(255, 255, 255, .22);
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
        <div class="btn-vol-dn"></div>

        <div class="screen-bezel">

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

            <div class="topbar">
                <div class="brand">
                    <div class="brand-logo">S²</div>
                    <div class="brand-text">
                        <h1>SOLO SECOND THRIFT</h1>
                        <span>Content Creator</span>
                    </div>
                </div>
                <a href="logout.php" class="topbar-icon" title="Logout">
                    <svg viewBox="0 0 24 24" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </a>
            </div>

            <div class="app-screen" style="position: relative;">
                <div id="toast">Info Katalog Disalin!</div>

                <!-- TOMBOL STUDIO KONTEN -->
                <a href="studio_konten.php" class="studio-btn">
                    <svg viewBox="0 0 24 24">
                        <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z" />
                        <circle cx="12" cy="13" r="4" />
                    </svg>
                    <div class="studio-btn-title">STUDIO KONTEN</div>
                    <div class="studio-btn-sub">Ambil foto &amp; buat katalog</div>
                </a>

                <!-- KATALOG GRID -->
                <div class="catalog-grid">
                    <?php if (count($barang_list) > 0): ?>
                        <?php foreach($barang_list as $item): ?>
                            <?php
                                $img_src = !empty($item['foto']) ? 'uploads/'.$item['foto'] : 'https://images.unsplash.com/photo-1542272604-787c3835535d?auto=format&fit=crop&q=80&w=300';
                                $nama_brg = htmlspecialchars($item['nama_barang']);
                                $harga_brg = 'Rp ' . number_format($item['harga'], 0, ',', '.');
                                $kat_brg = htmlspecialchars($item['kategori'] ?? '-');
                                $stok_brg = (int)$item['stok'];
                            ?>
                            <div class="katalog-card">
                                <img src="<?= $img_src ?>" class="katalog-img" alt="Foto Produk">
                                <div class="katalog-body">
                                    <div class="katalog-nama"><?= $nama_brg ?></div>
                                    <div class="katalog-harga"><?= $harga_brg ?> <span style="float:right; font-size:10px; color:var(--charcoal); opacity:0.8;" id="stok-<?= $item['id'] ?>">Stok: <?= $stok_brg ?></span></div>
                                    <button class="btn-copy" onclick="copyInfo('<?= addslashes($nama_brg) ?>', '<?= addslashes($harga_brg) ?>', '<?= addslashes($kat_brg) ?>', document.getElementById('stok-<?= $item['id'] ?>').innerText.replace('Stok: ',''))">Copy Info</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="grid-column:1/-1; text-align:center; padding: 20px; font-size:12px; color:var(--charcoal); opacity:0.6;">Belum ada barang di database.</div>
                    <?php endif; ?>
                </div>

            </div>

            <nav class="bottom-nav">
                <a href="dashboardcc.php" class="nav-item active">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span>Katalog</span>
                </a>
                <a href="usercc.php" class="nav-item">
                    <svg viewBox="0 0 24 24">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
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
        function copyInfo(nama, harga, kategori, stok) {
            const caption = 
`✨ ${nama} ✨

🏷️ Harga: ${harga}
📌 Kategori: ${kategori}
📦 Stok: ${stok}

Yuks buruan DM sebelum keduluan yang lain! 🔥
#SoloSecondThrift #ThriftSolo #Preloved`;

            navigator.clipboard.writeText(caption).then(() => {
                const toast = document.getElementById('toast');
                toast.classList.add('show');
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 2000);
            }).catch(err => {
                alert('Gagal copy clipboard!');
            });
        }

        // Auto-update stok live
        setInterval(() => {
            fetch('get_stok.php')
                .then(res => res.json())
                .then(data => {
                    if(!data.error) {
                        data.forEach(item => {
                            const el = document.getElementById('stok-' + item.id);
                            if(el) {
                                el.innerText = 'Stok: ' + item.stok;
                                if(parseInt(item.stok) === 0) {
                                    el.style.color = 'var(--red)';
                                    el.style.fontWeight = 'bold';
                                } else if(parseInt(item.stok) <= 3) {
                                    el.style.color = '#E9A03A';
                                    el.style.fontWeight = 'bold';
                                } else {
                                    el.style.color = 'var(--charcoal)';
                                    el.style.fontWeight = 'normal';
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

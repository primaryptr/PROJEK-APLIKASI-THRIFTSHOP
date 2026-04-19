<?php
session_start();
// hanya izinkan akses untuk user dengan role 'owner'
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'owner') {
    header('Location: login.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>User - Solo Second Thrift</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800;900&family=Secular+One&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: #d7d5ca;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* ── Phone shell ── */
        .phone {
            width: 430px;
            height: 932px;
            background: #FDFCF0;
            border: 18px solid #3A3A3A;
            border-radius: 55px;
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
            box-shadow: 0 24px 60px rgba(0,0,0,0.35);
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

        /* ── Header ── */
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
            width: 100px; height: 43px;
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
            padding: 40px 24px 0;
            -ms-overflow-style: none;
            scrollbar-width: none;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .content::-webkit-scrollbar { display: none; }

        /* ── Role Buttons ── */
        .role-btn {
            display: flex;
            align-items: center;
            width: 100%;
            height: 75px;
            background: #FFFFFF;
            border: 2.5px solid #101828;
            box-shadow: 0px 6px 0px rgba(16, 24, 40, 0.1);
            border-radius: 20px;
            padding: 0 16px;
            text-decoration: none;
            color: #101828;
            transition: transform .1s, box-shadow .1s;
            margin-bottom: 12px;
        }
        .role-btn:active {
            transform: translateY(4px);
            box-shadow: 0px 2px 0px rgba(16, 24, 40, 0.1);
        }

        .role-icon-wrap {
            width: 45px; height: 45px;
            border-radius: 12px;
            display: flex; justify-content: center; align-items: center;
            flex-shrink: 0; margin-right: 14px;
        }
        .icon-red { background: #B23A48; }
        .icon-yellow { background: #E9C46A; }
        .icon-green { background: #2A9D8F; }

        .role-text { flex: 1; }
        .role-title { font-weight: 900; font-size: 16px; margin-bottom: 2px; font-style: italic; }
        .role-subtitle { font-weight: 800; font-size: 10px; color: #6A7282; font-style: italic;}

        .role-arrow { color: #9CA3AF; margin-left: 10px; }

        .btn-tutup {
            width: 100%;
            height: 55px;
            background: #264653;
            border: none;
            border-radius: 20px;
            font-weight: 900;
            font-size: 14px;
            color: #FFFFFF;
            font-style: italic;
            cursor: pointer;
            margin-top: 10px;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
            transition: background .2s, transform .1s;
        }
        .btn-tutup:active { transform: translateY(2px); }

        /* ── Pengaturan Section ── */
        .pengaturan-section {
            background: #F4F5F7;
            border-radius: 20px;
            padding: 20px;
            margin-top: 20px;
            margin-bottom: 110px; /* space for bottom nav */
        }
        .pengaturan-title {
            font-weight: 900;
            font-size: 18px;
            color: #364153;
            margin-bottom: 16px;
        }
        .pengaturan-card {
            display: flex;
            align-items: center;
            background: #FFFFFF;
            border-radius: 16px;
            padding: 16px;
            text-decoration: none;
            color: #101828;
            box-shadow: 0px 2px 8px rgba(0,0,0,0.05);
            transition: transform .2s;
        }
        .pengaturan-card:hover { transform: translateY(-2px); }
        .pengaturan-icon {
            width: 32px; height: 32px;
            margin-right: 12px;
            display: flex; justify-content: center; align-items: center;
        }
        .pengaturan-text { font-weight: 600; font-size: 14px; }

        /* ── Bottom Nav ── */
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
            position: absolute;
            bottom: 34px;
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

        /* Active – User */
        .nav-active .nav-label { color: #B23A48; }
        .nav-active .nav-icon-bg {
            background: rgba(178, 58, 72, 0.1);
            border-radius: 10px;
            padding: 5px;
            border: 1px solid #B23A48;
        }
        
        .nav-active svg { stroke: #B23A48; }

        /* Inactive */
        .nav-inactive .nav-label { color: #101828; }
        .nav-inactive:hover .nav-label { color: #B23A48; }

        /* ── Floating Cart FAB ── */
        .fab-wrap {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 80px; /* 50px + bottom nav relative diff */
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

        /* ── Bottom indicator ── */
        .bottom-indicator {
            width: 100%;
            height: 34px;
            background: #FFF7ED;
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            position: absolute;
            bottom: 0;
            z-index: 20;
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
            .bottom-nav { bottom: 34px; }
            .fab-wrap { bottom: 84px; }
        }
    </style>
</head>
<body>

<div class="phone">

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

        <!-- Role Action Buttons -->
        <div>
            <!-- OWNER -->
            <a href="#" class="role-btn">
                <div class="role-icon-wrap icon-red">
                    <svg width="22" height="22" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                        <polyline points="17 6 23 6 23 12"></polyline>
                    </svg>
                </div>
                <div class="role-text">
                    <div class="role-title">OWNER</div>
                    <div class="role-subtitle">LAPORAN LABA & ATUR TIM</div>
                </div>
                <div class="role-arrow">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </div>
            </a>

            <!-- CREW -->
            <a href="#" class="role-btn">
                <div class="role-icon-wrap icon-yellow">
                    <svg width="22" height="22" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                </div>
                <div class="role-text">
                    <div class="role-title">CREW</div>
                    <div class="role-subtitle">KASIR & UPDATE STOK FISIK</div>
                </div>
                <div class="role-arrow">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </div>
            </a>

            <!-- CREATOR -->
            <a href="#" class="role-btn">
                <div class="role-icon-wrap icon-green">
                    <svg width="22" height="22" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                        <circle cx="12" cy="13" r="4"></circle>
                    </svg>
                </div>
                <div class="role-text">
                    <div class="role-title">CREATOR</div>
                    <div class="role-subtitle">FOTO PRODUK & KATALOG SOSMED</div>
                </div>
                <div class="role-arrow">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </div>
            </a>

            <button class="btn-tutup">TUTUP</button>
        </div>

        <!-- Pengaturan Section -->
        <div class="pengaturan-section">
            <div class="pengaturan-title">Pengaturan</div>
            <a href="kelolacrew.php" class="pengaturan-card">
                <div class="pengaturan-icon">
                    <svg width="24" height="24" fill="none" stroke="#2A9D8F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="pengaturan-text">Kelola Akun Crew</div>
            </a>
        </div>

    </div><!-- /content -->

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <!-- Dashboard -->
        <a href="owner_dashboard.php" class="nav-item nav-inactive">
            <div class="nav-icon">
                <svg width="24" height="24" fill="none" stroke="#101828" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
            <span class="nav-label">Dashboard</span>
        </a>

        <!-- Stok -->
        <a href="stokowner.php" class="nav-item nav-inactive">
            <div class="nav-icon">
                <svg width="26" height="26" fill="none" stroke="#101828" stroke-width="2" stroke-linejoin="round" viewBox="0 0 24 24">
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
                <svg width="26" height="26" fill="none" stroke="#101828" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
            </div>
            <span class="nav-label">Laporan</span>
        </a>

        <!-- User (Active) -->
        <a href="userowner.php" class="nav-item nav-active">
            <div class="nav-icon">
                <svg width="26" height="26" fill="none" stroke="#B23A48" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <span class="nav-label">User</span>
        </a>
    </div>

    <!-- Floating Cart Button -->
    <div class="fab-wrap">
        <a href="transaksiowner.php" class="fab">
            <svg width="32" height="32" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
        </a>
    </div>

    <!-- Bottom Indicator -->
    <div class="bottom-indicator">
        <div class="bottom-pill"></div>
    </div>

</div><!-- /phone -->

</body>
</html>

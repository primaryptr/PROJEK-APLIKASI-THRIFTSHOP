<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'crew') {
    header("Location: login.php");
    exit();
}
$userName = $_SESSION['user_name'] ?? 'Crew Member';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Solo Second Thrift - Profil Crew</title>
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
            position: absolute; right: -5px; top: 140px; width: 5px; height: 55px; background: linear-gradient(to right, #2a2a2a, #4a4a4a, #2a2a2a); border-radius: 0 4px 4px 0;
        }

        .btn-vol-up {
            position: absolute; left: -5px; top: 120px; width: 5px; height: 42px; background: linear-gradient(to left, #2a2a2a, #4a4a4a, #2a2a2a); border-radius: 4px 0 0 4px;
        }

        .btn-vol-down {
            position: absolute; left: -5px; top: 172px; width: 5px; height: 42px; background: linear-gradient(to left, #2a2a2a, #4a4a4a, #2a2a2a); border-radius: 4px 0 0 4px;
        }

        .screen-bezel {
            background: #000; border-radius: 42px; overflow: hidden; display: flex; flex-direction: column; height: 780px; position: relative;
        }

        /* ===== 1. STATUS BAR ===== */
        .status-bar {
            flex-shrink: 0; background: #000; height: 34px; display: flex; align-items: center; justify-content: space-between; padding: 0 22px 0 18px; position: relative;
        }
        .punch-hole {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 12px; height: 12px; background: #000; border-radius: 50%; border: 2px solid #1c1c1c; box-shadow: 0 0 0 1px #0a0a0a;
        }
        .status-time { font-size: 11px; font-weight: 700; color: #fff; }
        .status-icons { display: flex; align-items: center; gap: 4px; }
        .status-icons svg { width: 13px; height: 13px; }

        /* ===== 2. TOPBAR ===== */
        .topbar {
            flex-shrink: 0; background: var(--bg); display: flex; align-items: center; justify-content: space-between; padding: 14px 18px 12px;
        }
        .brand { display: flex; align-items: center; gap: 10px; }
        .brand-logo {
            width: 40px; height: 40px; border-radius: 50%; background: var(--red); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; color: var(--bg); box-shadow: 2px 2px 0 var(--charcoal);
        }
        .brand-text h1 { font-size: 13px; font-weight: 700; color: var(--charcoal); line-height: 1; }
        .brand-text span { font-size: 10px; font-weight: 500; color: var(--charcoal); opacity: 0.5; text-transform: uppercase; letter-spacing: 2px; }

        /* ===== 3. APP SCREEN ===== */
        .app-screen {
            flex: 1; background: var(--bg); overflow-y: auto; overflow-x: hidden; scrollbar-width: none;
        }
        .app-screen::-webkit-scrollbar { display: none; }

        /* ===== PROFIL HEADER ===== */
        .profile-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px 20px 20px;
            margin: 20px 16px;
            background: white;
            border: 2px solid var(--charcoal);
            border-radius: 20px;
            box-shadow: 4px 4px 0 var(--charcoal);
            position: relative;
        }

        .avatar-lg {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--gold);
            border: 2px solid var(--charcoal);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            box-shadow: 2px 2px 0 var(--charcoal);
        }

        .avatar-lg svg {
            width: 40px;
            height: 40px;
            stroke: var(--charcoal);
            fill: none;
            stroke-width: 1.5;
        }

        .profile-name {
            font-size: 18px;
            font-weight: 800;
            color: var(--charcoal);
            text-align: center;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .profile-role {
            font-size: 11px;
            font-weight: 700;
            color: white;
            background: var(--red);
            padding: 4px 12px;
            border-radius: 12px;
            border: 1.5px solid var(--charcoal);
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* ===== ACTION MENU ===== */
        .action-menu {
            padding: 10px 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 15px;
            background: white;
            padding: 16px;
            border: 2px solid var(--charcoal);
            border-radius: 14px;
            text-decoration: none;
            box-shadow: 3px 3px 0 var(--charcoal);
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .menu-item:active {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0 var(--charcoal);
        }

        .menu-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--bg);
            border: 1.5px solid var(--charcoal);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .menu-icon svg {
            width: 18px;
            height: 18px;
            stroke: var(--charcoal);
            fill: none;
            stroke-width: 2;
        }

        .menu-text {
            flex: 1;
            font-size: 13px;
            font-weight: 700;
            color: var(--charcoal);
        }

        .menu-item.logout {
            background: var(--red);
        }

        .menu-item.logout .menu-text {
            color: white;
        }

        .menu-item.logout .menu-icon {
            border: none;
            background: rgba(255, 255, 255, 0.2);
        }

        .menu-item.logout .menu-icon svg {
            stroke: white;
        }

        .dev-badge {
            font-size: 10px;
            font-weight: 700;
            color: var(--charcoal);
            opacity: 0.5;
            text-align: center;
            margin-top: 20px;
        }

        /* ===== 4. BOTTOM NAV ===== */
        .bottom-nav {
            flex-shrink: 0; height: var(--nav-h); background: var(--bg); border-top: 2.5px solid var(--charcoal); display: flex; align-items: center; justify-content: space-around; padding: 0 6px;
        }
        .nav-item {
            display: flex; flex-direction: column; align-items: center; gap: 3px; cursor: pointer; padding: 6px 12px; border-radius: 10px; text-decoration: none; transition: background 0.15s;
        }
        .nav-item:hover { background: rgba(38, 70, 83, 0.07); }
        .nav-item svg { width: 20px; height: 20px; stroke: var(--charcoal); fill: none; stroke-width: 2; }
        .nav-item span { font-size: 9px; font-weight: 600; color: var(--charcoal); text-transform: uppercase; letter-spacing: 0.8px; }
        .nav-item.active svg { stroke: var(--red); }
        .nav-item.active span { color: var(--red); }

        .nav-fab {
            width: 48px; height: 48px; border-radius: 50%; background: var(--red); border: 2.5px solid var(--charcoal); box-shadow: 3px 3px 0 var(--charcoal); display: flex; align-items: center; justify-content: center; cursor: pointer; margin-top: -20px; flex-shrink: 0; transition: transform 0.15s, box-shadow 0.15s;
        }
        .nav-fab:active { transform: translate(2px, 2px); box-shadow: 1px 1px 0 var(--charcoal); }
        .nav-fab svg { width: 21px; height: 21px; stroke: white; fill: none; stroke-width: 2.2; }

        /* ===== 5. HOME INDICATOR ===== */
        .home-indicator {
            flex-shrink: 0; background: #000; height: 26px; display: flex; align-items: center; justify-content: center;
        }
        .home-bar { width: 90px; height: 4px; background: #3a3a3a; border-radius: 3px; }

        /* ===== LABEL ===== */
        .device-label { margin-top: 18px; color: rgba(255, 255, 255, 0.22); font-size: 10px; letter-spacing: 2.5px; text-transform: uppercase; }
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
            </div>

            <!-- ③ APP SCREEN -->
            <div class="app-screen">

                <div class="profile-header">
                    <div class="avatar-lg">
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="7" r="4" />
                            <path d="M2 21v-1a8 8 0 0116 0v1" stroke-linecap="round" />
                        </svg>
                    </div>
                    <div class="profile-name"><?= htmlspecialchars($userName) ?></div>
                    <div class="profile-role">CREW</div>
                </div>

                <div class="action-menu">
                    <!-- Placeholder Menu -->
                    <div class="menu-item">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <span class="menu-text">Ganti Password</span>
                    </div>

                    <a href="logout.php" class="menu-item logout">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        </div>
                        <span class="menu-text">Sign Out</span>
                    </a>
                </div>

                <div class="dev-badge">Fitur lengkap sedang dikembangkan...</div>

            </div><!-- /app-screen ③ -->

            <!-- ④ BOTTOM NAV -->
            <nav class="bottom-nav">
                <a href="dashboardcrew.php" class="nav-item">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7" rx="1.5" />
                        <rect x="14" y="3" width="7" height="7" rx="1.5" />
                        <rect x="3" y="14" width="7" height="7" rx="1.5" />
                        <rect x="14" y="14" width="7" height="7" rx="1.5" />
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="stokcrew.php" class="nav-item">
                    <svg viewBox="0 0 24 24">
                        <path d="M5 8h14M5 12h14M5 16h14" stroke-linecap="round" />
                    </svg>
                    <span>Stok</span>
                </a>
                <div class="nav-fab" onclick="window.location='transaksicrew.php'">
                    <svg viewBox="0 0 24 24">
                        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M3 6h18M16 10a4 4 0 01-8 0" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <a href="laporancrew.php" class="nav-item">
                    <svg viewBox="0 0 24 24">
                        <path d="M18 20V10M12 20V4M6 20v-6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span>Laporan</span>
                </a>
                <a href="profilcrew.php" class="nav-item active">
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

</body>
</html>
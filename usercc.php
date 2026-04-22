<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'content_creator') {
    header("Location: login.php");
    exit();
}

// Ambil data user dari database berdasarkan session
$user_id = $_SESSION['user_id'] ?? 0;

$stmt  = $conn->prepare("SELECT nama, email, status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();
$stmt->close();

$nama   = $user['nama']   ?? $_SESSION['nama'] ?? '-';
$email  = $user['email']  ?? '-';
$status = $user['status'] ?? 'aktif';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Solo Second Thrift - Profil Content Creator</title>
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
            background: var(--green);
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
            color: var(--green);
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

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

        .profile-hero {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px 0 20px;
        }

        .avatar-ring {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--green), #1f7a70);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 20px rgba(42, 157, 143, 0.35), 0 0 0 4px rgba(42, 157, 143, 0.15);
            margin-bottom: 14px;
        }

        .avatar-ring svg {
            width: 42px;
            height: 42px;
            stroke: white;
            fill: none;
            stroke-width: 1.5;
            stroke-linecap: round;
        }

        .profile-name {
            font-size: 18px;
            font-weight: 800;
            color: var(--charcoal);
            letter-spacing: 0.3px;
            margin-bottom: 4px;
        }

        .profile-role-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--green);
            color: white;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 999px;
        }

        .info-card {
            background: white;
            border-radius: 20px;
            padding: 6px 0;
            box-shadow: 0 2px 12px rgba(38, 70, 83, 0.08);
            margin: 0 16px 16px;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 22px 18px;
            border-bottom: 1px solid #f0ede3;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .info-icon svg {
            width: 18px;
            height: 18px;
            stroke: var(--charcoal);
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .info-text {
            flex: 1;
        }

        .info-label {
            font-size: 10px;
            font-weight: 700;
            color: var(--charcoal);
            opacity: 0.45;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 13px;
            font-weight: 700;
            color: var(--charcoal);
        }

        .status-aktif {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #d4edda;
            color: #155724;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 999px;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #28a745;
            display: inline-block;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: calc(100% - 32px);
            margin: 4px 16px 16px;
            padding: 15px;
            background: linear-gradient(135deg, var(--green), #1f7a70);
            color: white;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            border: none;
            border-radius: 16px;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 4px 16px rgba(42, 157, 143, 0.35);
            transition: opacity 0.2s, transform 0.1s;
        }

        .logout-btn:active {
            opacity: 0.85;
            transform: scale(0.98);
        }

        .logout-btn svg {
            width: 18px;
            height: 18px;
            stroke: white;
            fill: none;
            stroke-width: 2.2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

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
            stroke: var(--green);
        }

        .nav-item.active span {
            color: var(--green);
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
                    <div class="brand-logo">CC</div>
                    <div class="brand-text">
                        <h1>SOLO SECOND THRIFT</h1>
                        <span>Content Creator</span>
                    </div>
                </div>
            </div>

            <div class="app-screen">

                <div class="profile-hero">
                    <div class="avatar-ring">
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="8" r="4" />
                            <path d="M4 20v-1a8 8 0 0116 0v1" />
                        </svg>
                    </div>
                    <div class="profile-name"><?= htmlspecialchars(strtoupper($nama)) ?></div>
                    <div class="profile-role-badge">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        CONTENT CREATOR
                    </div>
                </div>

                <div class="info-card">

                    <div class="info-row">
                        <div class="info-icon">
                            <svg viewBox="0 0 24 24">
                                <circle cx="12" cy="8" r="4" />
                                <path d="M4 20v-1a8 8 0 0116 0v1" />
                            </svg>
                        </div>
                        <div class="info-text">
                            <div class="info-label">Nama Lengkap</div>
                            <div class="info-value"><?= htmlspecialchars($nama) ?></div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon">
                            <svg viewBox="0 0 24 24">
                                <rect x="2" y="4" width="20" height="16" rx="2" />
                                <path d="M2 7l10 7 10-7" />
                            </svg>
                        </div>
                        <div class="info-text">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?= htmlspecialchars($email) ?></div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4" />
                                <circle cx="12" cy="12" r="9" />
                            </svg>
                        </div>
                        <div class="info-text">
                            <div class="info-label">Status</div>
                            <div class="status-aktif">
                                <span class="status-dot"></span>
                                <?= ucfirst(htmlspecialchars($status)) ?>
                            </div>
                        </div>
                    </div>

                </div>

                <a href="logout.php" class="logout-btn"
                    onclick="return confirm('Yakin ingin logout?')">
                    <svg viewBox="0 0 24 24">
                        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    LOGOUT
                </a>

            </div>

            <nav class="bottom-nav">
                <a href="dasboard_creator.php" class="nav-item">
                    <svg viewBox="0 0 24 24" stroke="currentColor">
                        <rect x="3" y="3" width="7" height="7" rx="1.5" />
                        <rect x="14" y="3" width="7" height="7" rx="1.5" />
                        <rect x="3" y="14" width="7" height="7" rx="1.5" />
                        <rect x="14" y="14" width="7" height="7" rx="1.5" />
                    </svg>
                    <span>Katalog</span>
                </a>
                <a href="usercc.php" class="nav-item active">
                    <svg viewBox="0 0 24 24" stroke="currentColor">
                        <circle cx="12" cy="7" r="4" />
                        <path d="M2 21v-1a8 8 0 0116 0v1" />
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

</body>

</html>

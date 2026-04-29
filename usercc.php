<?php
session_start();
require_once 'koneksi.php';

// Proteksi halaman: Harus role content_creator
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'content_creator') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$successMsg = '';
$errorMsg   = '';

// ── Ambil Data User ──────────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// ── Handle Update Password ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];
    $conf_pass = $_POST['confirm_password'];

    if ($old_pass !== $user['password']) {
        $errorMsg = "Password lama salah!";
    } elseif ($new_pass !== $conf_pass) {
        $errorMsg = "Konfirmasi password baru tidak cocok!";
    } elseif (strlen($new_pass) < 4) {
        $errorMsg = "Password baru minimal 4 karakter!";
    } else {
        $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->bind_param("si", $new_pass, $user_id);
        if ($upd->execute()) {
            $successMsg = "Password berhasil diperbarui!";
            $user['password'] = $new_pass; // Update local state
        } else {
            $errorMsg = "Gagal memperbarui password.";
        }
    }
}

$initials = strtoupper(substr($user['nama'], 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Profil Creator - Solo Second Thrift</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #FDFCF0;
            --charcoal: #264653;
            --red: #B23A48;
            --gold: #E9C46A;
            --green: #2A9D8F;
            --soft-bg: #F8F9FA;
            --radius: 20px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

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
            color: var(--charcoal);
        }

        /* Android Frame Mockup */
        .phone-frame {
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

        .screen {
            background: var(--bg);
            border-radius: 42px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 780px;
            position: relative;
        }

        /* Status Bar */
        .status-bar {
            height: 44px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 700;
            z-index: 10;
        }

        /* Content Area */
        .content {
            flex: 1;
            overflow-y: auto;
            padding: 0 20px 100px;
            scrollbar-width: none;
        }
        .content::-webkit-scrollbar { display: none; }

        /* Profile Header */
        .profile-card {
            background: white;
            border-radius: var(--radius);
            padding: 30px 20px;
            margin-top: 20px;
            border: 2.5px solid var(--charcoal);
            box-shadow: 6px 6px 0 var(--charcoal);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .avatar-circle {
            width: 84px;
            height: 84px;
            background: var(--gold);
            border: 2.5px solid var(--charcoal);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 16px;
            box-shadow: 4px 4px 0 var(--charcoal);
        }

        .user-name {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .user-role {
            font-size: 11px;
            font-weight: 700;
            background: var(--charcoal);
            color: white;
            padding: 4px 14px;
            border-radius: 12px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* Form Styling */
        .section-title {
            font-size: 14px;
            font-weight: 800;
            margin: 30px 0 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-box {
            background: white;
            border: 2px solid var(--charcoal);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: 4px 4px 0 rgba(0,0,0,0.05);
        }

        .input-group {
            margin-bottom: 15px;
        }

        .input-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #6B7280;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .input-field {
            width: 100%;
            height: 48px;
            background: var(--soft-bg);
            border: 2px solid #E5E7EB;
            border-radius: 14px;
            padding: 0 16px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            outline: none;
            transition: all 0.2s;
        }

        .input-field:focus {
            border-color: var(--charcoal);
            background: white;
        }

        .btn-update {
            width: 100%;
            height: 50px;
            background: var(--green);
            color: white;
            border: 2.5px solid var(--charcoal);
            border-radius: 14px;
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            box-shadow: 4px 4px 0 var(--charcoal);
            margin-top: 10px;
            transition: all 0.15s;
        }

        .btn-update:active {
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0 var(--charcoal);
        }

        .btn-logout {
            width: 100%;
            height: 50px;
            background: var(--red);
            color: white;
            border: 2.5px solid var(--charcoal);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            box-shadow: 4px 4px 0 var(--charcoal);
            margin-top: 20px;
            transition: all 0.15s;
        }

        .btn-logout:active {
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0 var(--charcoal);
        }

        /* Message Bubbles */
        .alert {
            padding: 12px 16px;
            border-radius: 14px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #DCFCE7; color: #166534; border: 1.5px solid #BBF7D0; }
        .alert-error { background: #FEE2E2; color: #991B1B; border: 1.5px solid #FECACA; }

        /* Bottom Nav */
        .bottom-nav {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 80px;
            background: white;
            border-top: 2.5px solid var(--charcoal);
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 0 20px 20px;
            z-index: 10;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            color: var(--charcoal);
            opacity: 0.4;
            transition: all 0.2s;
        }

        .nav-item.active {
            opacity: 1;
            color: var(--red);
        }

        .nav-item svg { width: 24px; height: 24px; stroke-width: 2.5; }
        .nav-item span { font-size: 10px; font-weight: 800; text-transform: uppercase; }

        .home-indicator {
            position: absolute;
            bottom: 8px;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 5px;
            background: var(--charcoal);
            opacity: 0.15;
            border-radius: 10px;
        }

        /* Physical Buttons */
        .btn-power { position: absolute; right: -5px; top: 140px; width: 5px; height: 55px; background: #333; border-radius: 0 4px 4px 0; }
        .btn-vol { position: absolute; left: -5px; width: 5px; height: 42px; background: #333; border-radius: 4px 0 0 4px; }
        .vol-up { top: 120px; }
        .vol-down { top: 172px; }

    </style>
</head>
<body>

    <div class="phone-frame">
        <div class="btn-power"></div>
        <div class="btn-vol vol-up"></div>
        <div class="btn-vol vol-down"></div>

        <div class="screen">
            <!-- Status Bar -->
            <div class="status-bar">
                <span id="time">19:15</span>
                <div style="display:flex; gap:6px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12.55a11 11 0 0114.08 0M1.42 9a16 16 0 0121.16 0M8.53 16.11a6 6 0 016.95 0M12 20h.01"/></svg>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="7" width="16" height="10" rx="2"/><path d="M22 11v2"/></svg>
                </div>
            </div>

            <div class="content">
                <!-- User Profile -->
                <div class="profile-card">
                    <div class="avatar-circle"><?= $initials ?></div>
                    <h1 class="user-name"><?= htmlspecialchars($user['nama']) ?></h1>
                    <span class="user-role">Content Creator</span>
                </div>

                <!-- Messages -->
                <?php if ($successMsg): ?>
                    <div class="alert alert-success" style="margin-top:20px;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                        <?= $successMsg ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMsg): ?>
                    <div class="alert alert-error" style="margin-top:20px;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                        <?= $errorMsg ?>
                    </div>
                <?php endif; ?>

                <!-- Settings Section -->
                <h2 class="section-title">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Keamanan Akun
                </h2>

                <div class="form-box">
                    <form method="POST">
                        <div class="input-group">
                            <label>Password Lama</label>
                            <input type="password" name="old_password" class="input-field" placeholder="••••••••" required>
                        </div>
                        <div class="input-group">
                            <label>Password Baru</label>
                            <input type="password" name="new_password" class="input-field" placeholder="Min. 4 Karakter" required>
                        </div>
                        <div class="input-group">
                            <label>Konfirmasi Password</label>
                            <input type="password" name="confirm_password" class="input-field" placeholder="Ulangi Password Baru" required>
                        </div>
                        <button type="submit" name="update_password" class="btn-update">Update Password</button>
                    </form>
                </div>

                <!-- Additional Info -->
                <h2 class="section-title">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Informasi Akun
                </h2>
                <div class="form-box" style="margin-bottom: 20px;">
                    <div class="input-group" style="margin-bottom: 10px;">
                        <label>Email Terdaftar</label>
                        <div style="font-weight: 700; font-size: 14px;"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                    <div class="input-group" style="margin-bottom: 0;">
                        <label>Nomor Telepon</label>
                        <div style="font-weight: 700; font-size: 14px;"><?= htmlspecialchars($user['no_telp'] ?? '-') ?></div>
                    </div>
                </div>

                <!-- Logout -->
                <a href="logout.php" class="btn-logout" onclick="return confirm('Yakin ingin keluar?')">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Keluar Sekarang
                </a>

                <div style="text-align:center; margin-top: 30px; font-size: 10px; opacity: 0.3; font-weight: 700;">
                    SOLO SECOND THRIFT v1.0
                </div>
            </div>

            <!-- Bottom Nav -->
            <nav class="bottom-nav">
                <a href="dashboardcc.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    <span>Katalog</span>
                </a>
                <a href="usercc.php" class="nav-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span>User</span>
                </a>
            </nav>

            <div class="home-indicator"></div>
        </div>
    </div>

    <script>
        // Update Time
        function updateTime() {
            const now = new Date();
            const timeStr = now.getHours().toString().padStart(2, '0') + ':' + 
                          now.getMinutes().toString().padStart(2, '0');
            document.getElementById('time').textContent = timeStr;
        }
        setInterval(updateTime, 1000);
        updateTime();
    </script>
</body>
</html>

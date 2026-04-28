<?php
session_start();
$message = "";

define('DB_HOST','localhost');
define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','thrift');

// handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_otp') {
        $phone = trim($_POST['phone'] ?? '');
        if ($phone === '') {
            $message = 'Masukkan nomor telepon.';
        } else {
            // generate OTP and pretend to send via WhatsApp
            $otp = rand(100000, 999999);
            $_SESSION['otp'] = (string)$otp;
            $_SESSION['otp_phone'] = $phone;
            $message = "Kode OTP ($otp) telah dikirim ke $phone via WhatsApp.";
        }
    } elseif ($action === 'verify_otp') {
        $otp_input = trim($_POST['otp'] ?? '');
        if (isset($_SESSION['otp']) && $otp_input === $_SESSION['otp']) {
            $_SESSION['otp_verified'] = true;
            $message = 'OTP terverifikasi, silakan isi email dan password baru.';
        } else {
            $message = 'OTP salah.';
        }
    } elseif ($action === 'reset') {
        if (empty($_SESSION['otp_verified'])) {
            $message = 'OTP belum diverifikasi.';
        } else {
            $email = trim($_POST['email'] ?? '');
            $newpass = trim($_POST['newpass'] ?? '');
            if ($email === '' || $newpass === '') {
                $message = 'Email dan password baru harus diisi.';
            } else {
                $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                if ($mysqli->connect_errno) {
                    die('Koneksi database gagal: ' . $mysqli->connect_error);
                }
                $stmt = $mysqli->prepare('UPDATE users SET password = ? WHERE email = ?');
                $stmt->bind_param('ss', $newpass, $email);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $message = 'Password berhasil direset. Silakan login kembali.';
                    session_unset();
                } else {
                    $message = 'Email tidak ditemukan.';
                }
                $stmt->close();
                $mysqli->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Solo Second</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
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
            font-family: 'Inter', sans-serif;
        }
        .phone-mockup {
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
        .btn-power { position: absolute; right: -5px; top: 140px; width: 5px; height: 55px; background: linear-gradient(to right, #2a2a2a, #4a4a4a, #2a2a2a); border-radius: 0 4px 4px 0; }
        .btn-vol-up { position: absolute; left: -5px; top: 120px; width: 5px; height: 42px; background: linear-gradient(to left, #2a2a2a, #4a4a4a, #2a2a2a); border-radius: 4px 0 0 4px; }
        .btn-vol-down { position: absolute; left: -5px; top: 172px; width: 5px; height: 42px; background: linear-gradient(to left, #2a2a2a, #4a4a4a, #2a2a2a); border-radius: 4px 0 0 4px; }
        .screen-bezel { background: #FDFCF0; border-radius: 42px; overflow: hidden; display: flex; flex-direction: column; height: 780px; position: relative; } 
        .status-bar { flex-shrink: 0; background: #000; height: 34px; display: flex; align-items: center; justify-content: space-between; padding: 0 22px 0 18px; position: relative; } 
        .punch-hole { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 12px; height: 12px; background: #000; border-radius: 50%; border: 2px solid #1c1c1c; box-shadow: 0 0 0 1px #0a0a0a; } 
        .status-time { font-size: 11px; font-weight: 700; color: #fff; } 
        .status-icons { display: flex; align-items: center; gap: 4px; } 
        .status-icons svg { width: 13px; height: 13px; } 
        .home-indicator { flex-shrink: 0; background: #000; height: 26px; display: flex; align-items: center; justify-content: center; } 
        .home-bar { width: 90px; height: 4px; background: #3a3a3a; border-radius: 3px; } 
        .device-label { margin-top: 18px; color: rgba(255, 255, 255, 0.22); font-size: 10px; letter-spacing: 2.5px; text-transform: uppercase; }
        .screen-content { flex: 1; padding: 100px 40px 40px 40px; display: flex; flex-direction: column; align-items: center; overflow-y: auto; }
        
        .header-title {
            color: #388035;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-container { width: 100%; }
        .input-group { margin-bottom: 20px; display: flex; flex-direction: column; }
        .input-group label { color: #7A7067; font-size: 14px; margin-bottom: 8px; font-weight: 500; }
        .input-group input { width: 100%; padding: 14px 18px; border: 1.5px solid #EBE7E1; border-radius: 12px; font-size: 14px; color: #333; outline: none; transition: all 0.3s ease; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .input-group input:focus { border-color: #388035; }
        .btn-submit { width: 100%; padding: 14px; background-color: #388035; color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 10px; transition: background-color 0.3s ease; }
        .btn-submit:hover { background-color: #2c6529; }
        .back-login { display: block; text-align: center; color: #7A7067; text-decoration: none; font-size: 14px; font-weight: 600; margin-top: 20px; }
        .back-login:hover { color: #388035; }

        @media (max-width: 480px) {
            .phone-mockup { width: 100%; height: 100vh; border: none; border-radius: 0; padding-top: 80px; }
        }
    </style>
</head>
<body>
    <div class="phone-mockup">
        <!-- Physical Buttons -->
        <div class="btn-power"></div>
        <div class="btn-vol-up"></div>
        <div class="btn-vol-down"></div>
        <div class="screen-bezel">
            <div class="status-bar"><div class="punch-hole"></div><span class="status-time">09:41</span><div class="status-icons"><svg viewBox="0 0 16 12" fill="white"><rect x="0" y="8" width="3" height="4" rx="0.5" /><rect x="4" y="5" width="3" height="7" rx="0.5" /><rect x="8" y="2" width="3" height="10" rx="0.5" /><rect x="12" y="0" width="3" height="12" rx="0.5" /></svg><svg viewBox="0 0 16 12" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round"><path d="M1 4.5C3.8 1.9 7 .5 8 .5s4.2 1.4 7 4" /><path d="M3 7C4.8 5.3 6.5 4.5 8 4.5S11.2 5.3 13 7" /><path d="M5.5 9.5C6.5 8.6 7.3 8 8 8s1.5.6 2.5 1.5" /><circle cx="8" cy="11.5" r="0.8" fill="white" /></svg><svg viewBox="0 0 20 12" fill="none"><rect x="0.5" y="0.5" width="16" height="11" rx="2" stroke="white" stroke-width="1.2" /><rect x="2" y="2" width="11" height="8" rx="1" fill="white" /><path d="M17.5 4v4" stroke="white" stroke-width="1.5" stroke-linecap="round" /></svg></div></div>
            
            <div class="screen-content">
                <h2 class="header-title">Reset Password</h2>
                
                <?php if ($message): ?>
                    <div style="color: #b91c1c; background-color: #fee2e2; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; font-weight: 500; width: 100%;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($_SESSION['otp']) || (!empty($_SESSION['otp']) && empty($_SESSION['otp_verified']))): ?>
                    <?php if (empty($_SESSION['otp'])): ?>
                        <form class="form-container" method="post">
                            <input type="hidden" name="action" value="send_otp">
                            <div class="input-group">
                                <label>Nomor telepon</label>
                                <input name="phone" placeholder="0812xxxx" required>
                            </div>
                            <button type="submit" class="btn-submit">Kirim OTP</button>
                        </form>
                    <?php else: ?>
                        <form class="form-container" method="post">
                            <input type="hidden" name="action" value="verify_otp">
                            <div class="input-group">
                                <label>Masukkan kode OTP</label>
                                <input name="otp" placeholder="123456" required>
                            </div>
                            <button type="submit" class="btn-submit">Verifikasi OTP</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <form class="form-container" method="post">
                        <input type="hidden" name="action" value="reset">
                        <div class="input-group">
                            <label>Email</label>
                            <input name="email" type="email" placeholder="email@contoh.com" required>
                        </div>
                        <div class="input-group">
                            <label>Password baru</label>
                            <input name="newpass" type="password" placeholder="********" required>
                        </div>
                        <button type="submit" class="btn-submit">Reset Password</button>
                    </form>
                <?php endif; ?>

                <a href="login.php" class="back-login">Kembali ke login</a>
            </div>
            
            <div class="home-indicator">
                <div class="home-bar"></div>
            </div>
        </div>
    </div>
    <div class="device-label">Solo Second Thrift &middot; Android Preview</div>
</body>
</html>
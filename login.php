<?php
session_start();
/**
 * Logika Sederhana PHP untuk menangani Form Login
 */
$message = "";

// koneksi database
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'thrift';

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    die('Koneksi database gagal: ' . $mysqli->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $message = "Silakan isi email dan password.";
    } else {
        $stmt = $mysqli->prepare('SELECT id, password, nama, role FROM users WHERE email = ? AND status = "aktif"');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $message = "Email tidak terdaftar.";
        } else {
            $stmt->bind_result($dbId, $dbPassword, $name, $role);
            $stmt->fetch();

            if ($password === $dbPassword || password_verify($password, $dbPassword)) {
                // set session
                $_SESSION['user_id'] = $dbId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $role;

                // jika owner, arahkan ke dashboard owner
                if ($role === 'owner') {
                    header('Location: owner_dashboard.php');
                    exit;
                }

                $message = "Login berhasil untuk: " . htmlspecialchars($name);
            } else {
                $message = "Password salah.";
            }
        }
        $stmt->close();
    }
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login Android - Scrollable</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #121212; /* Dark Mode Android */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Roboto', sans-serif;
            overflow: hidden; /* Mengunci scroll body utama */
        }

        /* Container Frame Android */
        .android-device {
            width: 393px;
            height: 852px;
            background: #FFFFFF;
            border-radius: 40px;
            position: relative;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            overflow: hidden;
            border: 8px solid #1a1a1a;
        }

        /* Status Bar Mockup (Fixed at top) */
        .status-bar {
            height: 44px;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            font-weight: 500;
            color: #000;
            flex-shrink: 0;
            z-index: 10;
            background: #FFFFFF;
        }

        /* Scrollable Content Area */
        .scroll-content {
            flex: 1;
            overflow-y: auto; /* Aktifkan scroll vertikal */
            padding: 0 32px;
            scrollbar-width: none; /* Sembunyikan scrollbar Firefox */
        }

        .scroll-content::-webkit-scrollbar {
            display: none; /* Sembunyikan scrollbar Chrome/Safari */
        }

        /* Material Input Style */
        .material-input-group {
            position: relative;
            width: 100%;
            margin-bottom: 32px;
        }

        .material-input {
            width: 100%;
            border: none;
            border-bottom: 2px solid #e0e0e0;
            padding: 12px 0;
            font-size: 16px;
            background: transparent;
            transition: border-color 0.3s;
            outline: none;
        }

        .material-input:focus {
            border-bottom-color: #6200EE;
        }

        .material-label {
            position: absolute;
            top: 12px;
            left: 0;
            color: #757575;
            pointer-events: none;
            transition: 0.3s ease all;
        }

        .material-input:focus ~ .material-label,
        .material-input:not(:placeholder-shown) ~ .material-label {
            top: -12px;
            font-size: 12px;
            color: #6200EE;
        }

        /* Button Android Style */
        .btn-android {
            width: 100%;
            background: #6200EE;
            color: white;
            padding: 16px;
            border-radius: 28px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1.25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: background 0.2s, box-shadow 0.2s;
            cursor: pointer;
            border: none;
            margin-top: 20px;
        }

        /* Navigation Bar Mockup (Fixed at bottom) */
        .nav-bar {
            height: 48px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding-bottom: 8px;
            flex-shrink: 0;
            background: #FFFFFF;
            border-top: 1px solid #f0f0f0;
        }

        @media (max-width: 400px) {
            .android-device {
                width: 100vw;
                height: 100vh;
                border-radius: 0;
                border: none;
            }
        }
    </style>
</head>
<body>

    <div class="android-device">
        <!-- Status Bar -->
        <div class="status-bar">
            <span>09:41</span>
            <div class="flex gap-2">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 20.67L2 10.67a10 10 0 1120 0l-10 10z"/></svg>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M15.67 4H14V2h-4v2H8.33C7.6 4 7 4.6 7 5.33v15.33C7 21.4 7.6 22 8.33 22h7.33c.74 0 1.34-.6 1.34-1.33V5.33C17 4.6 16.4 4 15.67 4z"/></svg>
            </div>
        </div>

        <!-- Scrollable Content -->
        <div class="scroll-content">
            <!-- Header Section -->
            <div class="mt-12 mb-10">
                <div class="w-16 h-16 bg-purple-100 rounded-2xl flex items-center justify-center mb-6">
                    <svg class="text-purple-700 w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"></path>
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 tracking-tight">Login</h1>
                <p class="text-gray-500 mt-2 text-lg">Gunakan akun Google Anda</p>
            </div>

            <?php if ($message): ?>
                <div class="p-4 mb-6 text-sm text-purple-700 bg-purple-50 rounded-xl border border-purple-100">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form action="" method="POST" class="w-full pb-10">
                <div class="material-input-group">
                    <input type="email" name="email" class="material-input" placeholder=" " required>
                    <label class="material-label">Email atau ponsel</label>
                </div>

                <div class="material-input-group">
                    <input type="password" name="password" class="material-input" placeholder=" " required>
                    <label class="material-label">Masukkan sandi</label>
                </div>

                <div class="mt-4 mb-8">
                    <a href="forgot.php" class="text-purple-700 font-medium text-sm">Lupa email?</a>
                </div>

                <!-- Konten Tambahan untuk mendemonstrasikan scroll jika perlu -->
                <div class="text-xs text-gray-400 leading-relaxed mb-8">
                    Dengan melanjutkan, Anda menyetujui Ketentuan Layanan kami. Pelajari bagaimana kami memproses data Anda di Kebijakan Privasi kami. Google akan membagikan nama, alamat email, preferensi bahasa, dan gambar profil Anda dengan aplikasi ini.
                </div>

                <div class="flex flex-col gap-2">
                    <button type="submit" class="btn-android">
                        Berikutnya
                    </button>
                    <button type="button" class="w-full py-4 text-purple-700 font-medium">
                        Buat akun
                    </button>
                </div>
            </form>
        </div>

        <!-- Android Navigation Bar Mockup -->
        <div class="nav-bar">
            <div class="w-4 h-4 border-2 border-gray-400 rotate-45" style="border-top:none; border-right:none;"></div>
            <div class="w-4 h-4 border-2 border-gray-400 rounded-full"></div>
            <div class="w-4 h-4 border-2 border-gray-400 rounded-sm"></div>
        </div>
    </div>

</body>
</html>
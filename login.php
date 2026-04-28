<?php
// login.php
session_start();

// Redirect based on role if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'owner') {
        header("Location: owner_dashboard.php");
        exit;
    } else if ($_SESSION['user_role'] === 'crew') {
        header("Location: dashboardcrew.php");
        exit;
    } else if ($_SESSION['user_role'] === 'content_creator') {
        header("Location: dashboardcc.php");
        exit;
    } else {
        header("Location: dashboard.php"); // Fallback for other roles
        exit;
    }
}

$error_message = "";

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Database connection using mysqli
    $host = 'localhost';
    $db   = 'thrift';
    $user = 'root';
    $pass = '';

    $conn = new mysqli($host, $user, $pass, $db);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']); 

    // Query to check user by email (used as username here) or name
    // Adjusting based on thrift.sql `users` table which has `nama`, `email`, `password`, `role`
    $sql = "SELECT id, nama, email, password, role, status FROM users WHERE (email = '$username' OR nama = '$username') AND status = 'aktif'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        
        // Direct string comparison as the dummy data password '1111' is not hashed
        if ($password === $user_data['password']) {
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['user_name'] = $user_data['nama'];
            $_SESSION['user_role'] = $user_data['role'];

            // Redirect based on role
            if ($user_data['role'] === 'owner') {
                header("Location: owner_dashboard.php");
            } else if ($user_data['role'] === 'crew') {
                header("Location: dashboardcrew.php");
            } else if ($user_data['role'] === 'content_creator') {
                header("Location: dashboardcc.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $error_message = "Password salah!";
        }
    } else {
        $error_message = "Username tidak ditemukan atau akun tidak aktif!";
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Solo Second</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
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

        .screen-content { flex: 1; padding: 130px 40px 40px 40px; display: flex; flex-direction: column; align-items: center; }
.logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 60px;
        }
        
        .logo-icon {
            width: 80px;
            height: 80px;
            margin-bottom: 15px;
            color: #388035; 
        }
        
        .logo-text {
            color: #388035;
            font-size: 34px;
            font-weight: 700;
        }

        .form-container {
            width: 100%;
        }

        .input-group {
            margin-bottom: 25px;
            display: flex;
            flex-direction: column;
        }

        .input-group label {
            color: #7A7067;
            font-size: 16px;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .input-group input {
            width: 100%;
            padding: 16px 20px;
            border: 1.5px solid #EBE7E1;
            border-radius: 12px;
            font-size: 16px;
            color: #333;
            outline: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }

        .input-group input:focus {
            border-color: #388035;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background-color: #388035;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            transition: background-color 0.3s ease;
        }

        .btn-login:hover {
            background-color: #2c6529;
        }

        .forgot-password {
            display: block;
            text-align: center;
            color: #388035;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            margin-top: 25px;
        }

        @media (max-width: 480px) {
            .phone-mockup {
                width: 100%;
                height: 100vh;
                border: none;
                border-radius: 0;
                padding-top: 80px;
            }
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
        <div class="status-bar"><div class="punch-hole"></div><span class="status-time">09:41</span><div class="status-icons"><svg viewBox="0 0 16 12" fill="white"><rect x="0" y="8" width="3" height="4" rx="0.5" /><rect x="4" y="5" width="3" height="7" rx="0.5" /><rect x="8" y="2" width="3" height="10" rx="0.5" /><rect x="12" y="0" width="3" height="12" rx="0.5" /></svg><svg viewBox="0 0 16 12" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round"><path d="M1 4.5C3.8 1.9 7 .5 8 .5s4.2 1.4 7 4" /><path d="M3 7C4.8 5.3 6.5 4.5 8 4.5S11.2 5.3 13 7" /><path d="M5.5 9.5C6.5 8.6 7.3 8 8 8s1.5.6 2.5 1.5" /><circle cx="8" cy="11.5" r="0.8" fill="white" /></svg><svg viewBox="0 0 20 12" fill="none"><rect x="0.5" y="0.5" width="16" height="11" rx="2" stroke="white" stroke-width="1.2" /><rect x="2" y="2" width="11" height="8" rx="1" fill="white" /><path d="M17.5 4v4" stroke="white" stroke-width="1.5" stroke-linecap="round" /></svg></div></div><div class="screen-content">
            <div class="logo-container">
                <div class="logo-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20.7 5.4l-4.2-2c-.3-.1-.6-.2-.9-.2H15c-.4 1.7-2 3-3.9 3S7.5 4.9 7.1 3.2H6.4c-.3 0-.6.1-.9.2l-4.2 2c-.6.3-.9 1-.7 1.6l1.2 3.6c.2.5.7.8 1.2.8h1v10.3c0 .8.6 1.4 1.4 1.4h11c.8 0 1.4-.6 1.4-1.4V11.5h1c.5 0 1-.3 1.2-.8l1.2-3.6c.2-.6-.1-1.3-.7-1.6z"/>
                    </svg>
                </div>
                <h1 class="logo-text">Solo Second</h1>
            </div>

            <?php if (!empty($error_message)): ?>
                <div style="color: #b91c1c; background-color: #fee2e2; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; font-weight: 500;">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form class="form-container" action="login.php" method="POST">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="admin_solo" required>
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="........" required>
                </div>

                <button type="submit" class="btn-login" name="login">Masuk</button>
                
                <a href="forgot.php" class="forgot-password">Lupa Password?</a>
            </form>
        </div>
        <div class="home-indicator">
            <div class="home-bar"></div>
        </div>
    </div>
    </div>

    <div class="device-label">Solo Second Thrift &middot; Android Preview</div>
    <div class="device-label">Solo Second Thrift &middot; Android Preview</div>
</body>
</html>

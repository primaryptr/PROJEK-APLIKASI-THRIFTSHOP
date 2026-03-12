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
    <title>Lupa Email / Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex justify-center items-center h-screen">
    <div class="bg-white p-6 rounded shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-4">Lupa Email / Reset Password</h2>
        <?php if ($message): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (empty($_SESSION['otp']) || (!empty($_SESSION['otp']) && empty($_SESSION['otp_verified']))): ?>
            <?php if (empty($_SESSION['otp'])): ?>
                <form method="post">
                    <input type="hidden" name="action" value="send_otp">
                    <label class="block mb-2">Nomor telepon</label>
                    <input name="phone" class="w-full mb-4 p-2 border rounded" placeholder="0812xxxx" required>
                    <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded">Kirim OTP</button>
                </form>
            <?php else: ?>
                <form method="post">
                    <input type="hidden" name="action" value="verify_otp">
                    <label class="block mb-2">Masukkan kode OTP</label>
                    <input name="otp" class="w-full mb-4 p-2 border rounded" placeholder="123456" required>
                    <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded">Verifikasi OTP</button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="action" value="reset">
                <label class="block mb-2">Email</label>
                <input name="email" type="email" class="w-full mb-4 p-2 border rounded" required>
                <label class="block mb-2">Password baru</label>
                <input name="newpass" type="password" class="w-full mb-4 p-2 border rounded" required>
                <button type="submit" class="w-full bg-green-500 text-white p-2 rounded">Reset Password</button>
            </form>
        <?php endif; ?>

        <p class="mt-4 text-sm"><a href="login.php" class="text-blue-500">Kembali ke login</a></p>
    </div>
</body>
</html>
<?php
session_start();
// Harap login sebagai owner
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'owner') {
    header('Location: login.php');
    exit;
}

// ── Koneksi DB ─────────────────────────────────────────────────────────────
$conn = new mysqli('localhost', 'root', '', 'thrift');
if ($conn->connect_error) die('Koneksi gagal: ' . $conn->connect_error);

// ── Auto-migrate: Tambah kolom no_telp jika belum ada ───────────────────────
$cols = $conn->query("SHOW COLUMNS FROM users")->fetch_all(MYSQLI_ASSOC);
if (!in_array('no_telp', array_column($cols, 'Field'))) {
    $conn->query("ALTER TABLE users ADD COLUMN `no_telp` VARCHAR(20) DEFAULT NULL AFTER `nama`");
}

$successMsg = '';
$errorMsg   = '';

// ── Handle Request POST (Tambah / Hapus) ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['tambah_crew'])) {
        $nama     = trim($_POST['nama']);
        $no_telp  = trim($_POST['no_telp']);
        $email    = trim($_POST['email']);
        $jabatan  = trim($_POST['jabatan']);
        $password = trim($_POST['password']);
        
        if ($nama === '' || $no_telp === '' || $email === '' || $jabatan === '' || $password === '') {
            $errorMsg = "Semua field harus diisi.";
        } elseif (!in_array($jabatan, ['crew', 'content_creator'])) {
            $errorMsg = "Jabatan tidak valid.";
        } else {
            // Cek duplikasi Nama / Email
            $n = $conn->real_escape_string($nama);
            $e = $conn->real_escape_string($email);
            $check = $conn->query("SELECT id FROM users WHERE email='$e' OR nama='$n'");
            
            if ($check->num_rows > 0) {
                $errorMsg = "Nama atau Email tersebut sudah terdaftar!";
            } else {
                $t = $conn->real_escape_string($no_telp);
                $p = $conn->real_escape_string($password);
                $j = $conn->real_escape_string($jabatan);
                
                $sql = "INSERT INTO users (nama, no_telp, email, password, role, status) VALUES ('$n', '$t', '$e', '$p', '$j', 'aktif')";
                if ($conn->query($sql)) {
                    $successMsg = "Akun crew <strong>$nama</strong> berhasil dibuat!";
                } else {
                    $errorMsg = "Gagal menambah data: " . $conn->error;
                }
            }
        }
    } elseif (isset($_POST['hapus_crew'])) {
        $id = (int)$_POST['hapus_id'];
        // Pastikan tidak bisa menghapus owner
        if ($conn->query("DELETE FROM users WHERE id=$id AND role IN ('crew', 'content_creator')")) {
            $successMsg = "Akun berhasil dihapus.";
        } else {
            $errorMsg = "Gagal menghapus data: " . $conn->error;
        }
    }
}

// ── Ambil data crew ──────────────────────────────────────────────────────────
$crews = $conn->query("SELECT * FROM users WHERE role IN ('crew', 'content_creator') ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kelola Akun Crew - Solo Second Thrift</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Secular+One&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; min-height: 100vh; display: flex; justify-content: center; align-items: center; background: #d7d5ca; font-family: 'Inter', sans-serif; }
        .phone-mockup { width: 430px; height: 932px; background-color: #FDFCF0; border: 18px solid #3A3A3A; border-radius: 55px; position: relative; box-shadow: 0 30px 80px rgba(0,0,0,0.18); overflow: hidden; display: flex; flex-direction: column; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        .input-label { font-size: 11px; font-weight: 700; color: #4B5563; letter-spacing: .05em; margin-bottom: 6px; display: block; text-transform: uppercase; }
        .input-field { width: 100%; height: 46px; background: #F9FAFB; border: 1.5px solid #E5E7EB; border-radius: 12px; padding: 0 14px; font-size: 14px; color: #111827; outline: none; transition: all .2s; }
        .input-field:focus { border-color: #2A9D8F; box-shadow: 0 0 0 3px rgba(42,157,143,.15); background: #FFF; }
        select.input-field { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; background-size: 16px; padding-right: 40px; }

        .btn-tambah { background: #2A9D8F; color: white; transition: all .2s; }
        .btn-tambah:active { transform: scale(0.98); }
        .btn-tambah:hover { background: #21867a; }

        .crew-card { background: white; border: 1.5px solid #E5E7EB; border-radius: 16px; padding: 14px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.03); }
        .role-badge { font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 20px; text-transform: uppercase; letter-spacing: .05em; }
        .badge-crew { background: #FEF3C7; color: #D97706; }
        .badge-cc { background: #D1FAE5; color: #059669; }

        @media (max-width: 480px) { .phone-mockup { width: 100%; height: 100vh; border: none; border-radius: 0; } }
    </style>
</head>
<body>

<div class="phone-mockup">
    <!-- Status Bar -->
    <div class="w-full h-[48px] flex-none z-20 flex justify-between items-center px-6">
        <span class="text-[13px] font-bold text-gray-800" id="clockDisplay">09:41</span>
        <div class="flex gap-1.5 items-center text-gray-800">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12.55a11 11 0 0114.08 0M1.42 9a16 16 0 0121.16 0M8.53 16.11a6 6 0 016.95 0M12 20h.01"/></svg>
            <svg class="w-5 h-3.5" viewBox="0 0 24 14" fill="none"><rect x=".5" y=".5" width="20" height="13" rx="3.5" stroke="currentColor" stroke-width="1.5"/><rect x="2" y="2" width="15" height="10" rx="2" fill="currentColor"/><path d="M21.5 4.5v5c1-.5 1.5-1.2 1.5-2.5s-.5-2-1.5-2.5z" fill="currentColor"/></svg>
        </div>
    </div>

    <!-- Header -->
    <div class="w-full h-[64px] flex-none bg-[#FDFCF0] shadow-[0px_2px_10px_rgba(0,0,0,0.06)] z-20 flex items-center justify-between px-5">
        <div class="flex items-center gap-3">
            <a href="userowner.php" class="w-9 h-9 border border-gray-200 bg-white rounded-[12px] flex items-center justify-center hover:bg-gray-50 transition-colors shadow-sm">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="font-extrabold text-[15px] text-[#264653] leading-tight">Kelola Akun Crew</h1>
                <p class="font-['Secular_One'] text-[11px] text-[#2A9D8F] leading-tight">MANAGEMENT</p>
            </div>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto hide-scrollbar bg-[#F8F9FA]">
        <div class="px-5 pt-5 pb-8 flex flex-col gap-6">

            <!-- Alerts -->
            <?php if ($successMsg): ?>
            <div class="bg-green-50 border border-green-200 rounded-[14px] p-3 flex items-start gap-2.5 shadow-sm">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm-1.177-7.86l-2.765-2.767L7 12.431l3.118 3.121a1 1 0 001.414 0l5.952-5.95-1.062-1.062-5.6 5.6z"/></svg>
                <div>
                    <p class="text-[12px] font-bold text-green-800">Berhasil!</p>
                    <p class="text-[11px] text-green-600 leading-tight mt-0.5"><?= $successMsg ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($errorMsg): ?>
            <div class="bg-red-50 border border-red-200 rounded-[14px] p-3 flex items-start gap-2.5 shadow-sm">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm-1-7v2h2v-2h-2zm0-8v6h2V7h-2z"/></svg>
                <div>
                    <p class="text-[12px] font-bold text-red-800">Gagal!</p>
                    <p class="text-[11px] text-red-600 leading-tight mt-0.5"><?= htmlspecialchars($errorMsg) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Form Tambah Crew -->
            <div>
                <h2 class="font-extrabold text-[14px] text-gray-800 mb-3 flex items-center gap-2">
                    <div class="w-6 h-6 rounded-md bg-[#2A9D8F]/10 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-[#2A9D8F]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    Tambah Anggota Tim
                </h2>
                <div class="bg-white p-4 rounded-[18px] shadow-[0_2px_12px_rgba(0,0,0,0.04)] border border-gray-100">
                    <form method="POST" action="kelolacrew.php" class="flex flex-col gap-3.5">
                        <input type="hidden" name="tambah_crew" value="1">
                        
                        <div>
                            <label class="input-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="input-field" placeholder="Cth: Budi Santoso" required>
                        </div>
                        
                        <div>
                            <label class="input-label">Nomor Telepon</label>
                            <input type="text" name="no_telp" class="input-field" placeholder="Cth: 0812345678" required>
                        </div>
                        
                        <div>
                            <label class="input-label">Email (Untuk Login)</label>
                            <input type="email" name="email" class="input-field" placeholder="budi@solosecond.com" required>
                        </div>
                        
                        <div>
                            <label class="input-label">Jabatan (Role)</label>
                            <select name="jabatan" class="input-field font-semibold" required>
                                <option value="crew">Crew Kasir / Gudang</option>
                                <option value="content_creator">Content Creator</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="input-label">Password Sementara</label>
                            <input type="text" name="password" class="input-field" placeholder="123456" required>
                            <p class="text-[9px] text-gray-400 mt-1">* Crew bisa mengubah password nanti.</p>
                        </div>
                        
                        <button type="submit" class="btn-tambah w-full h-[46px] rounded-[12px] font-extrabold text-[13px] tracking-wide uppercase mt-1 shadow-md shadow-[#2A9D8F]/30">
                            Simpan Anggota
                        </button>
                    </form>
                </div>
            </div>

            <div class="h-px w-full bg-gray-200"></div>

            <!-- Daftar Crew -->
            <div>
                <h2 class="font-extrabold text-[14px] text-gray-800 mb-3 flex items-center gap-2">
                    <div class="w-6 h-6 rounded-md bg-gray-100 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    Anggota Tim Saat Ini
                </h2>
                
                <?php if (empty($crews)): ?>
                    <div class="text-center py-6">
                        <p class="text-[12px] text-gray-400 font-medium">Belum ada anggota tim selain Owner.</p>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col">
                        <?php foreach ($crews as $c): 
                            $isCrew = $c['role'] === 'crew';
                            $roleLbl = $isCrew ? 'Crew Kasir' : 'Creator';
                            $badgeCls = $isCrew ? 'badge-crew' : 'badge-cc';
                            $initials = strtoupper(substr($c['nama'], 0, 1));
                        ?>
                        <div class="crew-card">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full <?= $isCrew ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600' ?> flex items-center justify-center font-extrabold text-[15px]">
                                    <?= $initials ?>
                                </div>
                                <div class="flex flex-col shadow-none">
                                    <span class="text-[13px] font-extrabold text-[#111827]"><?= htmlspecialchars($c['nama']) ?></span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="role-badge <?= $badgeCls ?>"><?= $roleLbl ?></span>
                                        <span class="text-[10px] text-gray-400 font-medium"><?= htmlspecialchars($c['no_telp'] ?? '-') ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hapus Form -->
                            <form method="POST" action="kelolacrew.php" class="shadow-none m-0" onsubmit="return confirm('Apakah Anda yakin ingin menghapus <?= htmlspecialchars($c['nama']) ?>?');">
                                <input type="hidden" name="hapus_crew" value="1">
                                <input type="hidden" name="hapus_id" value="<?= $c['id'] ?>">
                                <button type="submit" class="w-8 h-8 rounded-full bg-red-50 text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition-colors border-none cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bottom Spacer -->
            <div class="h-6"></div>
        </div>
    </div>
    
    <!-- Home Indicator -->
    <div class="w-full h-[24px] bg-[#F8F9FA] flex-none flex justify-center items-center rounded-b-[55px]">
        <div class="w-[120px] h-[5px] bg-[#101828] opacity-15 rounded-full"></div>
    </div>
</div>

<script>
    setInterval(() => {
        const d = new Date();
        const el = document.getElementById('clockDisplay');
        if(el) el.textContent = d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
    }, 1000);
</script>
</body>
</html>

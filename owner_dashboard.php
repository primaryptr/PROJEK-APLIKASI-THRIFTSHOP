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
    <title>Solo Second Thrift - Android View</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #F9F9F4;
            /* Center the android-device in the viewport */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            min-height: 100vh;
            box-shadow: 0 0 50px rgba(0,0,0,0.1);
        }
        
        /* Menghilangkan scrollbar agar terlihat bersih seperti App */
        ::-webkit-scrollbar {
            display: none;
        }

        .android-status-bar {
            height: 32px;
            background-color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            font-size: 12px;
            font-weight: 600;
            color: #333;
        }

        .nav-active {
            color: #B3404A;
        }

        /* Android device wrapper (sama seperti layout login.php) */
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
            margin: 0;
        }

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

        .scroll-content {
            flex: 1;
            overflow-y: auto;
            padding: 16px 24px 100px 24px;
            scrollbar-width: none;
        }

        .scroll-content::-webkit-scrollbar {
            display: none;
        }

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
    </style>
</head>
<body class="flex flex-col">

    <div class="android-device">

    <?php
    // Data Dinamis Simulasi
    $namaToko = "SOLO SECOND THRIFT";
    $role = "OWNER";
    $transaksiHariIni = 12;
    $totalOmzet = 1250000;
    $itemHabisStok = 4;
    ?>

    <!-- Status Bar Android -->
    <div class="status-bar android-status-bar">
        <div>09:41</div>
        <div class="flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M15.67 4H14V2h-4v2H8.33C7.6 4 7 4.6 7 5.33v15.33C7 21.4 7.6 22 8.33 22h7.33c.74 0 1.34-.6 1.34-1.33V5.33C17 4.6 16.4 4 15.67 4z"/></svg>
        </div>
    </div>

    <!-- App Bar -->
    <header class="bg-white px-6 py-4 flex justify-between items-center border-b border-gray-100">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-[#B3404A] rounded-full shadow-md border-2 border-white overflow-hidden">
                <!-- User Profile Image Placeholder -->
            </div>
            <div>
                <h1 class="font-bold text-[#1E3A4C] text-sm uppercase leading-tight"><?php echo $namaToko; ?></h1>
                <p class="text-[#B3404A] text-[10px] font-bold tracking-widest uppercase"><?php echo $role; ?></p>
            </div>
        </div>
        <div class="w-10 h-10 border border-gray-200 rounded-xl flex items-center justify-center bg-gray-50 active:bg-gray-100 transition">
            <svg class="w-5 h-5 text-[#1E3A4C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
        </div>
    </header>

    <main class="scroll-content p-6 space-y-6">

        <!-- Statistik Ringkas -->
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-[#E9C46A] p-5 rounded-[2rem] flex flex-col justify-center min-h-[130px] shadow-sm">
                <span class="text-[10px] font-bold text-[#1E3A4C]/60 uppercase mb-1">Transaksi Hari Ini</span>
                <p class="text-xl font-bold text-[#1E3A4C] leading-none"><?php echo $transaksiHariIni; ?> <br><span class="text-base">Transaksi</span></p>
            </div>
            <div class="bg-[#2A9D8F] p-5 rounded-[2rem] flex flex-col justify-center min-h-[130px] shadow-sm text-white">
                <span class="text-[10px] font-bold text-white/70 uppercase mb-1">Total Omzet</span>
                <p class="text-xl font-bold leading-none">Rp <br><?php echo number_format($totalOmzet, 0, ',', '.'); ?></p>
            </div>
        </div>

        <!-- Kartu Stok Kritis -->
        <div class="bg-white border-2 border-gray-100 rounded-[2.5rem] p-7 relative overflow-hidden shadow-sm active:bg-gray-50 transition">
            <div class="relative z-10">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-yellow-500">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"></path></svg>
                    </span>
                    <h2 class="text-[11px] font-bold text-[#1E3A4C] uppercase tracking-widest">Inventori Kritis</h2>
                </div>
                <h3 class="text-2xl font-bold text-[#1E3A4C] mb-5"><?php echo $itemHabisStok; ?> Item Habis Stok</h3>
                
                <button class="bg-[#1E3A4C] text-white px-8 py-3 rounded-full text-xs font-bold uppercase tracking-wider shadow-lg active:scale-95 transition">
                    Cek Sekarang
                </button>
            </div>

            <!-- Background Icon Decoration -->
            <div class="absolute -right-4 -bottom-4 opacity-[0.05] rotate-12">
                <svg width="120" height="120" fill="currentColor" viewBox="0 0 24 24"><path d="M21 8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16Z m-9-6.3L19.3 7 12 11.2 4.7 7Z m-1 18.2L4.3 16V8.2l6.7 3.8Z m2 0l6.7-3.8V8.2l-6.7 3.8Z"/></svg>
            </div>
        </div>

        <!-- Banner Merah / Iklan -->
        <div class="bg-[#B3404A] w-full h-36 rounded-[2.5rem] shadow-lg shadow-[#B3404A]/20"></div>

    </main>

    <!-- Bottom Navigation Android -->
    <nav class="nav-bar px-4 pt-4 pb-8">
        <a href="#" class="flex flex-col items-center gap-1 opacity-40 hover:opacity-100">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg>
            <span class="text-[9px] font-bold uppercase tracking-tighter">Katalog</span>
        </a>
        <a href="#" class="flex flex-col items-center gap-1 opacity-40 hover:opacity-100">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
            <span class="text-[9px] font-bold uppercase tracking-tighter">Transaksi</span>
        </a>
        <a href="#" class="flex flex-col items-center gap-1 nav-active">
            <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"></path></svg>
            <span class="text-[9px] font-black uppercase tracking-tighter">Home</span>
        </a>
        <a href="#" class="flex flex-col items-center gap-1 opacity-40 hover:opacity-100">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
            <span class="text-[9px] font-bold uppercase tracking-tighter">Laporan</span>
        </a>
        <a href="#" class="flex flex-col items-center gap-1 opacity-40 hover:opacity-100">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            <span class="text-[9px] font-bold uppercase tracking-tighter">User</span>
        </a>
    </nav>

    <!-- Gesture Bar Android -->
    <div class="fixed bottom-2 left-1/2 -translate-x-1/2 w-28 h-1 bg-gray-200 rounded-full"></div>

    </div> <!-- end android-device -->

</body>
</html>
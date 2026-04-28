<?php
session_start();
// hanya izinkan akses untuk user dengan role 'owner'
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'owner') {
    header('Location: login.php');
    exit;
}

// Data Simulasi Supplier Umum
$supplierBarang = [
    ["nama" => "Bandung Thrift Center", "alamat" => "Jl. Gede", "kontak" => "08xx-xxxx-xxxx"],
    ["nama" => "Bandung Thrift Center", "alamat" => "Jl. Gede", "kontak" => "08xx-xxxx-xxxx"],
];

// Data Simulasi Mitra Supplier Khusus
$mitraSupplier = [
    ["nama" => "Bandung Thrift Center", "kontak" => "0812-3456-7890", "status" => "Lunas", "status_color" => "bg-[#E6F4EA] text-[#388035]"],
    ["nama" => "Lokal Solo Ball", "kontak" => "0877-1122-3344", "status" => "Hutang", "status_color" => "bg-[#FFF0E6] text-[#D97706]"],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Data Supplier - Solo Second Thrift</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800;900&family=Secular+One&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #d7d5ca; 
        }

        .phone-mockup {
            width: 430px;
            height: 932px;
            background-color: #FDFCF0;
            border: 18px solid #3A3A3A;
            border-radius: 55px;
            position: relative;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            overflow: hidden;
            font-family: 'Inter', sans-serif;
            display: flex;
            flex-direction: column;
        }

        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        @media (max-width: 480px) {
            .phone-mockup {
                width: 100%;
                height: 100vh;
                border: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body class="">

  <div class="phone-mockup mx-auto">
    
    <!-- Status Bar -->
    <div class="w-full h-[51px] flex-none bg-[#FDFCF0] z-20 flex justify-between items-center px-6">
        <span class="text-[13px] font-bold">09:41</span>
        <div class="flex gap-1.5 items-center">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2 8.82C5.52 5.61 9.02 4 12 4s6.48 1.61 10 4.82M5 12.05C7.44 9.97 9.82 8.95 12 8.95s4.56 1.02 7 3.1M8.4 15.1A5.88 5.88 0 0112 14c1.4 0 2.72.38 3.6 1.1"></path><circle cx="12" cy="18" r="1.3" fill="currentColor" stroke="none"></circle></svg>
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M20 10v4"></path><rect x="3" y="7" width="15" height="10" rx="2" stroke="currentColor" stroke-width="2" fill="none"></rect></svg>
        </div>
    </div>

    <!-- Header -->
    <div class="w-full h-[80px] flex-none bg-[#FDFCF0] shadow-[0px_2px_4px_rgba(0,0,0,0.1)] z-20 relative flex items-center justify-between px-[22px]">
        <div class="flex items-center gap-3">
            <div class="w-[43px] h-[43px] bg-[#B23A48] border-[2px] border-[#264653] shadow-[0px_4px_4px_rgba(0,0,0,0.25)] rounded-full hover:bg-[#902A38] hover:scale-105 transition-all cursor-pointer"></div>
            <div class="flex flex-col">
                <span class="font-extrabold text-[14px] leading-[17px] text-[#264653]">SOLO SECOND THRIFT</span>
                <span class="font-['Secular_One'] font-normal text-[11px] leading-[16px] text-[#B23A48]">OWNER</span>
            </div>
        </div>
        
        <!-- Logout Button -->
        <a href="logout.php" class="w-[121px] h-[43px] bg-[#FFFFFF] border-[1px] border-[#890D0D] shadow-[0px_4px_4px_rgba(0,0,0,0.25)] rounded-[15px] flex items-center justify-center group hover:bg-[#890D0D] transition-colors duration-300">
            <span class="font-extrabold text-[15px] leading-[18px] text-[#890D0D] group-hover:text-white transition-colors duration-300">Logout</span>
        </a>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 overflow-y-auto hide-scrollbar z-10 scroll-smooth px-[16px] pt-[25px] pb-[80px] relative bg-[#FDFCF0]">
        
        <!-- Top Tabs (Daftar Barang & Data Supplier) INVERTED COLORS -->
        <div class="flex gap-[25px] justify-center mb-6">
            <!-- Daftar Barang (Inactive) -->
            <a href="stokowner.php" class="w-[121px] h-[43px] bg-[#101828] border border-black shadow-[0px_4px_4px_rgba(0,0,0,0.25)] rounded-[15px] flex items-center justify-center cursor-pointer hover:bg-[#1a2538] transform hover:-translate-y-0.5 transition-all duration-200">
                <span class="font-extrabold text-[15px] text-white">Daftar Barang</span>
            </a>
            <!-- Data Supplier (Active) -->
            <div class="w-[121px] h-[43px] bg-[#B23A48] border border-black shadow-[0px_4px_4px_rgba(0,0,0,0.25)] rounded-[15px] flex items-center justify-center cursor-default transform hover:-translate-y-0.5 transition-transform duration-200">
                <span class="font-extrabold text-[15px] text-white">Data Supplier</span>
            </div>
        </div>

        <!-- Section Title -->
        <h2 class="font-bold text-[20px] leading-[24px] text-[#364153] uppercase tracking-wide mb-[15px] pl-1">DATA SUPPLIER</h2>

        <!-- General Supplier List (Frame 13 / 27 equivalents) -->
        <div class="flex flex-col gap-[20px] mb-[30px]">
            <?php foreach ($supplierBarang as $supplier): ?>
            <div class="w-full bg-[#FFFFFF] border-[2px] border-[#101828] rounded-[30px] p-[16px] flex items-center shadow-sm hover:shadow-md transition-shadow cursor-pointer">
                <!-- Image Placeholder -->
                <div class="w-[55px] h-[48px] bg-[#6A7282] border-[2px] border-black rounded-[10px] shrink-0"></div>
                
                <div class="ml-4 flex-1">
                    <div class="font-black text-[14px] text-[#364153] mb-1">
                        <?php echo $supplier['nama']; ?>
                    </div>
                    <div class="font-black text-[12px] text-[#2A9D8F]">
                        <?php echo $supplier['alamat'] . " | " . $supplier['kontak']; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Mitra Supplier Block Area (Nested List) -->
        <div class="w-full bg-[#FFFFFF] shadow-sm flex flex-col items-center mt-2 relative">
            <div class="w-full h-[54px] bg-[#388035] flex items-center justify-center shadow-md">
                <span class="font-bold text-[16px] text-white">Mitra Supplier</span>
            </div>

            <div class="w-full flex flex-col p-4 gap-4 bg-white min-h-[180px]">
                
                <?php foreach ($mitraSupplier as $index => $mitra): ?>
                <?php 
                  // Border coloring simulation
                  $borderColor = ($index % 2 == 0) ? "border-l-[#388035]" : "border-l-[#D97706]"; 
                ?>
                <div class="w-full p-4 border-l-[4px] border-y-[1px] border-r-[1px] border-y-[#f0f0f0] border-r-[#f0f0f0] <?php echo $borderColor; ?> rounded-r-[12px] shadow-sm hover:shadow-md transition-shadow">
                    <div class="font-black text-[13px] text-[#111827] mb-1"><?php echo $mitra['nama']; ?></div>
                    <div class="font-normal text-[11px] text-[#6B7280] mb-3">Kontak: <?php echo $mitra['kontak']; ?></div>
                    <div class="inline-flex px-2 py-0.5 rounded-[4px] font-bold text-[9px] <?php echo $mitra['status_color']; ?>">
                        Status <?php echo $mitra['status']; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
            </div>
        </div>
        
    </div>

    <!-- Bottom Nav (Frame 15) matching Stok Owner & Dashboard -->
    <div class="w-full h-[80px] flex-none bg-[#FDFCF0] shadow-[inset_0px_4px_4px_rgba(0,0,0,0.15)] z-20 flex flex-row items-center justify-between px-6">
        
        <!-- Katalog (Inactive) -->
        <a href="stokowner.php" class="flex flex-col items-center justify-center w-14 gap-1 cursor-pointer group hover:-translate-y-1 transition-transform duration-200">
            <div class="relative w-8 h-8 rounded-lg border-[2.5px] border-[#101828] flex items-center justify-center group-hover:border-[#B23A48] transition-colors duration-200">
                <svg class="w-5 h-5 text-[#101828] group-hover:text-[#B23A48] transition-colors duration-200" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
            </div>
            <span class="font-bold text-[10px] text-[#000000] group-hover:text-[#B23A48] transition-colors duration-200">Katalog</span>
        </a>

        <!-- Transaksi (Inactive) -->
        <a href="transaksiowner.php" class="flex flex-col items-center justify-center w-14 gap-1 cursor-pointer group hover:-translate-y-1 transition-transform duration-200">
            <div class="relative w-8 h-8 rounded-lg border-[2.5px] border-[#101828] flex items-center justify-center group-hover:border-[#B23A48] transition-colors duration-200">
                <svg class="w-5 h-5 text-[#101828] group-hover:text-[#B23A48] transition-colors duration-200" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
            <span class="font-bold text-[10px] text-[#000000] group-hover:text-[#B23A48] transition-colors duration-200">Transaksi</span>
        </a>
        
        <!-- Home (Inactive) -->
        <a href="owner_dashboard.php" class="flex flex-col items-center justify-center w-14 gap-1 cursor-pointer group hover:-translate-y-1 transition-transform duration-200">
            <div class="w-8 h-8 flex items-center justify-center">
                <svg class="w-7 h-7 text-[#000000] group-hover:text-[#B23A48] transition-colors duration-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
            </div>
            <span class="font-bold text-[10px] text-[#000000] group-hover:text-[#B23A48] transition-colors duration-200">Home</span>
        </a>
        
        <!-- Laporan (Inactive) -->
        <a href="#" class="flex flex-col items-center justify-center w-14 gap-1 cursor-pointer group hover:-translate-y-1 transition-transform duration-200">
            <div class="w-8 h-8 flex items-center justify-center">
                <svg class="w-7 h-7 text-[#101828] group-hover:text-[#B23A48] transition-colors duration-200" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"></path></svg>
            </div>
            <span class="font-bold text-[10px] text-[#000000] group-hover:text-[#B23A48] transition-colors duration-200">Laporan</span>
        </a>
        
        <!-- User (Inactive) -->
        <a href="#" class="flex flex-col items-center justify-center w-14 gap-1 cursor-pointer group hover:-translate-y-1 transition-transform duration-200">
            <div class="w-8 h-8 flex items-center justify-center">
                <svg class="w-7 h-7 text-[#000000] group-hover:text-[#B23A48] transition-colors duration-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            </div>
            <span class="font-bold text-[10px] text-[#000000] group-hover:text-[#B23A48] transition-colors duration-200">User</span>
        </a>
        
    </div>

    <!-- Bottom Indicator -->
    <div class="w-full h-[34px] flex-none bg-[#FDFCF0] z-20 flex justify-center items-center rounded-b-[37px]">
      <div class="w-[130px] h-[5px] bg-[#101828] opacity-20 rounded-full"></div>
    </div>

  </div> <!-- end wrapper -->

</body>
</html>

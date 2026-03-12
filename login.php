<?php
session_start();
require_once 'koneksi.php';

// Logic verifikasi login ke database akan ditaruh di sini
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thrift Solo Second - Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        /* Simulasi Frame Android */
        .phone-container {
            width: 100%;
            max-width: 412px;
            height: 844px;
            background: #FDFCF0;
            position: relative;
            overflow: hidden;
            border: 12px solid #202124;
            border-radius: 3rem;
            box-shadow: 0 50px 100px -20px rgba(0, 0, 0, 0.2);
        }

        .content-area {
            height: calc(100% - 140px);
            overflow-y: auto;
            padding: 24px;
            padding-bottom: 100px;
        }

        .content-area::-webkit-scrollbar {
            display: none;
        }

        /* Bottom Nav Floating */
        .bottom-nav {
            position: absolute;
            bottom: 24px;
            left: 20px;
            right: 20px;
            height: 72px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border: 1px solid #F1F3F4;
            border-radius: 2rem;
            display: flex;
            justify-content: space-around;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            z-index: 50;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #BDC1C6;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .nav-item.active {
            color: #1A73E8;
        }

        .nav-item.active .icon-bg {
            background-color: #E8F0FE;
            border-radius: 12px;
            padding: 6px 12px;
        }

        .nav-item span {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 4px;
            letter-spacing: 0.05em;
        }

        /* Status Bar Simulation */
        .status-bar {
            height: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px 0;
            font-size: 13px;
            font-weight: 700;
            color: #202124;
        }

        /* Animations */
        .page-transition {
            animation: slideUp 0.4s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Modal Custom */
        #modal-overlay {
            background: rgba(32, 33, 36, 0.4);
            backdrop-filter: blur(4px);
        }
    </style>
</head>

<body>

    <div class="phone-container" id="app">
        <!-- Login Screen -->
        <div id="login-page" class="h-full flex flex-col justify-center px-10">
            <div class="text-center mb-10">
                <div class="w-20 h-20 bg-[#1A73E8] rounded-[2rem] mx-auto flex items-center justify-center mb-6 shadow-xl shadow-blue-100">
                    <i data-lucide="shopping-bag" class="text-white w-10 h-10"></i>
                </div>
                <h1 class="text-3xl font-bold text-[#202124]">Solo Second</h1>
                <p class="text-[#5F6368] text-sm mt-1">Management System</p>
            </div>
            <form id="login-form" class="space-y-4">
                <div class="relative">
                    <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#5F6368]"></i>
                    <input type="text" id="username" placeholder="Username (owner/crew)" class="w-full pl-12 pr-6 py-4 bg-[#F1F3F4] rounded-2xl outline-none focus:ring-2 ring-[#1A73E8] font-medium" required>
                </div>
                <div class="relative">
                    <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#5F6368]"></i>
                    <input type="password" id="password" placeholder="Password" class="w-full pl-12 pr-6 py-4 bg-[#F1F3F4] rounded-2xl outline-none focus:ring-2 ring-[#1A73E8] font-medium" required>
                </div>
                <button type="submit" class="w-full bg-[#1A73E8] text-white py-4 rounded-2xl font-bold shadow-lg shadow-blue-100 active:scale-95 transition-all">Masuk</button>
            </form>
        </div>

        <!-- Main App Layout (Hidden Initially) -->
        <div id="main-layout" class="h-full hidden">
            <div class="status-bar">
                <span>10:45</span>
                <div class="flex gap-2">
                    <i data-lucide="signal" class="w-4 h-4"></i>
                    <i data-lucide="battery" class="w-4 h-4"></i>
                </div>
            </div>

            <div class="content-area" id="content-container">
                <!-- Content will be injected here via JS -->
            </div>

            <nav class="bottom-nav">
                <div class="nav-item active" onclick="navigate('home')">
                    <div class="icon-bg"><i data-lucide="layout-dashboard" class="w-6 h-6"></i></div>
                    <span>Home</span>
                </div>
                <div class="nav-item" onclick="navigate('stok')">
                    <div class="icon-bg"><i data-lucide="package" class="w-6 h-6"></i></div>
                    <span>Stok</span>
                </div>
                <div class="nav-item" onclick="navigate('transaksi')">
                    <div class="icon-bg"><i data-lucide="shopping-cart" class="w-6 h-6"></i></div>
                    <span>Trans</span>
                </div>
                <div class="nav-item" onclick="navigate('user')">
                    <div class="icon-bg"><i data-lucide="users" class="w-6 h-6"></i></div>
                    <span>User</span>
                </div>
            </nav>

            <!-- Floating Action Button -->
            <button id="fab" class="hidden absolute bottom-28 right-8 w-14 h-14 bg-[#1A73E8] text-white rounded-2xl flex items-center justify-center shadow-2xl border-4 border-white active:scale-90 transition-all z-40" onclick="openModal()">
                <i data-lucide="plus" class="w-8 h-8"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <div id="modal-overlay" class="hidden absolute inset-0 z-[100] flex items-end">
            <div class="w-full bg-white rounded-t-[3rem] p-8 page-transition shadow-2xl">
                <div class="w-12 h-1.5 bg-[#F1F3F4] rounded-full mx-auto mb-6"></div>
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-[#202124]">Input Data Baru</h3>
                    <button onclick="closeModal()" class="p-2 bg-[#F8F9FA] rounded-full"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>
                <div class="space-y-4">
                    <input type="text" id="item-name" placeholder="Nama Produk / Staff" class="w-full p-4 bg-[#F1F3F4] rounded-2xl outline-none font-medium">
                    <div class="grid grid-cols-2 gap-3">
                        <input type="text" id="item-cat" placeholder="Kategori" class="p-4 bg-[#F1F3F4] rounded-2xl outline-none font-medium">
                        <input type="number" id="item-stock" placeholder="Jumlah" class="p-4 bg-[#F1F3F4] rounded-2xl outline-none font-medium">
                    </div>
                    <button onclick="saveData()" class="w-full bg-[#1A73E8] text-white py-4 rounded-2xl font-bold shadow-lg shadow-blue-50 mt-4">Simpan Data</button>
                </div>
                <div class="h-8"></div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // App State
        let currentUser = null;
        let products = JSON.parse(localStorage.getItem('thrift_products')) || [{
                id: 'TFT-01',
                name: 'Crewneck Vintage',
                cat: 'Atasan',
                stock: 12,
                price: '150k'
            },
            {
                id: 'TFT-02',
                name: 'Denim Pants Levis',
                cat: 'Bawahan',
                stock: 4,
                price: '250k'
            }
        ];

        // Login Logic
        document.getElementById('login-form').addEventListener('submit', (e) => {
            e.preventDefault();
            const user = document.getElementById('username').value.toLowerCase();
            if (['owner', 'crew', 'creator'].includes(user)) {
                currentUser = user;
                document.getElementById('login-page').classList.add('hidden');
                document.getElementById('main-layout').classList.remove('hidden');
                navigate('home');
            } else {
                alert('Gunakan username: owner, crew, atau creator');
            }
        });

        // Navigation Logic
        function navigate(page) {
            const container = document.getElementById('content-container');
            const fab = document.getElementById('fab');

            // Update Nav UI
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
                if (item.innerText.toLowerCase().includes(page.slice(0, 3))) item.classList.add('active');
            });

            // Show/Hide FAB
            if (page === 'stok' || page === 'user') fab.classList.remove('hidden');
            else fab.classList.add('hidden');

            // Render Page
            let html = `<div class="page-transition">`;

            if (page === 'home') {
                html += `
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h2 class="text-2xl font-bold text-[#202124]">Dashboard</h2>
                        <p class="text-[#5F6368] text-sm">Halo, ${currentUser}!</p>
                    </div>
                    <div class="w-12 h-12 bg-white rounded-2xl border flex items-center justify-center"><i data-lucide="bell" class="w-5 h-5"></i></div>
                </div>
                <div class="bg-[#1A73E8] p-7 rounded-[2.5rem] text-white shadow-xl mb-6">
                    <p class="text-[10px] font-bold uppercase tracking-widest opacity-80">Total Omzet Hari Ini</p>
                    <h3 class="text-3xl font-bold mt-1">Rp 1.450.000</h3>
                    <div class="mt-4 inline-block bg-white/20 px-3 py-1 rounded-full text-[10px] font-bold">+5% target</div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white p-5 rounded-[2rem] border">
                        <div class="w-10 h-10 bg-[#E8F0FE] rounded-xl flex items-center justify-center text-[#1A73E8] mb-4"><i data-lucide="shopping-cart" class="w-5 h-5"></i></div>
                        <p class="text-[10px] font-bold text-[#5F6368] uppercase">Terjual</p>
                        <h4 class="text-xl font-bold">24 Item</h4>
                    </div>
                    <div class="bg-white p-5 rounded-[2rem] border">
                        <div class="w-10 h-10 bg-[#FEF7E0] rounded-xl flex items-center justify-center text-[#F9AB00] mb-4"><i data-lucide="alert-triangle" class="w-5 h-5"></i></div>
                        <p class="text-[10px] font-bold text-[#5F6368] uppercase">Stok Habis</p>
                        <h4 class="text-xl font-bold">3 Produk</h4>
                    </div>
                </div>
            `;
            } else if (page === 'stok') {
                html += `
                <h2 class="text-2xl font-bold mb-6">Inventaris Barang</h2>
                <div class="space-y-3">
                    ${products.map(p => `
                        <div class="bg-white p-4 rounded-3xl border flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center ${p.stock < 5 ? 'bg-red-50 text-red-500' : 'bg-blue-50 text-blue-500'}"><i data-lucide="package" class="w-6 h-6"></i></div>
                            <div class="flex-1">
                                <h4 class="font-bold text-sm">${p.name}</h4>
                                <p class="text-[10px] text-gray-500 uppercase">${p.cat} • ${p.price}</p>
                            </div>
                            <div class="text-right"><p class="font-bold">${p.stock}</p><p class="text-[9px] text-gray-400">UNIT</p></div>
                        </div>
                    `).join('')}
                </div>
            `;
            } else if (page === 'transaksi') {
                html += `
                <h2 class="text-2xl font-bold mb-6">Transaksi</h2>
                <div class="bg-white p-6 rounded-[2rem] border text-center py-12">
                    <i data-lucide="receipt" class="w-12 h-12 mx-auto text-gray-300 mb-4"></i>
                    <p class="text-gray-500 font-medium">Belum ada transaksi hari ini</p>
                </div>
            `;
            } else if (page === 'user') {
                html += `
                <h2 class="text-2xl font-bold mb-6">Tim & Staff</h2>
                <div class="space-y-3">
                    <div class="bg-white p-4 rounded-3xl border flex items-center gap-4">
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center"><i data-lucide="user" class="w-6 h-6"></i></div>
                        <div><h4 class="font-bold">Nabiel</h4><p class="text-xs text-blue-500 font-bold uppercase">Owner</p></div>
                    </div>
                    <div class="bg-white p-4 rounded-3xl border flex items-center gap-4">
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center"><i data-lucide="user" class="w-6 h-6"></i></div>
                        <div><h4 class="font-bold">Siti</h4><p class="text-xs text-green-500 font-bold uppercase">Crew / Kasir</p></div>
                    </div>
                </div>
            `;
            }

            html += `</div>`;
            container.innerHTML = html;
            lucide.createIcons();
        }

        // Modal Operations
        function openModal() {
            document.getElementById('modal-overlay').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('modal-overlay').classList.add('hidden');
        }

        function saveData() {
            const name = document.getElementById('item-name').value;
            const cat = document.getElementById('item-cat').value;
            const stock = document.getElementById('item-stock').value;

            if (name && stock) {
                products.unshift({
                    id: 'TFT-' + Math.floor(Math.random() * 100),
                    name,
                    cat: cat || 'Umum',
                    stock: parseInt(stock),
                    price: 'N/A'
                });
                localStorage.setItem('thrift_products', JSON.stringify(products));
                closeModal();
                navigate('stok');

                // Clear inputs
                document.getElementById('item-name').value = '';
                document.getElementById('item-cat').value = '';
                document.getElementById('item-stock').value = '';
            }
        }
    </script>

</body>

</html>
<?php
session_start();
require_once 'config/koneksi.php';

// Cek apakah user sudah login dan role-nya admin
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$nama_admin = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Admin SiJumpa';
$role_admin = isset($_SESSION['role']) ? $_SESSION['role'] : 'admin';
$role_display = ($role_admin === 'admin') ? 'Super Admin' : (($role_admin === 'bendahara') ? 'Bendahara' : 'Petugas');

// Proses Hapus Warga
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    try {
        // Hapus data transaksi terkait warga terlebih dahulu (Cascade Delete Manual)
        mysqli_query($conn, "DELETE FROM transaksi_jumpitan WHERE warga_id = $id_hapus");
        
        // Hapus data warga
        mysqli_query($conn, "DELETE FROM warga WHERE id = $id_hapus");
        $_SESSION['swal'] = ['title' => 'Berhasil!', 'text' => 'Data warga beserta riwayat transaksinya berhasil dihapus.', 'icon' => 'success'];
    } catch (mysqli_sql_exception $e) {
        $_SESSION['swal'] = ['title' => 'Error Sistem!', 'text' => 'Gagal menghapus data warga. Error: ' . $e->getMessage(), 'icon' => 'error'];
    }
    header("Location: warga.php");
    exit;
}

// Proses Tambah Warga
if (isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $kode_rumah = mysqli_real_escape_string($conn, $_POST['kode_rumah']);
    $gang = mysqli_real_escape_string($conn, $_POST['gang']);
        
    // Generate QR Code String (SJ-XXXX)
    $q_max = mysqli_query($conn, "SELECT MAX(id) as max_id FROM warga");
    $row_max = mysqli_fetch_assoc($q_max);
    $next_id = $row_max['max_id'] + 1;
    $qr_code = "SJ-" . str_pad($next_id, 4, "0", STR_PAD_LEFT);

    mysqli_query($conn, "INSERT INTO warga (nama, alamat, kode_rumah, qr_code, gang) VALUES ('$nama', '$alamat', '$kode_rumah', '$qr_code', '$gang')");
    $_SESSION['swal'] = ['title' => 'Berhasil!', 'text' => 'Data warga baru berhasil ditambahkan.', 'icon' => 'success'];
    header("Location: warga.php");
    exit;
}

// Proses Edit Warga
if (isset($_POST['edit'])) {
    $id_edit = (int)$_POST['id_edit'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $kode_rumah = mysqli_real_escape_string($conn, $_POST['kode_rumah']);
    $gang = mysqli_real_escape_string($conn, $_POST['gang']);

    mysqli_query($conn, "UPDATE warga SET nama='$nama', alamat='$alamat', kode_rumah='$kode_rumah', gang='$gang' WHERE id=$id_edit");
    $_SESSION['swal'] = ['title' => 'Berhasil!', 'text' => 'Data warga berhasil diperbarui.', 'icon' => 'success'];
    header("Location: warga.php");
    exit;
}

// Ambil Data Warga
$query_warga = mysqli_query($conn, "SELECT * FROM warga ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Warga | SiJumpa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
        .glass-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .nav-item.active { background: linear-gradient(to right, rgba(59, 130, 246, 0.2), transparent); border-left: 4px solid #3b82f6; color: white; }

        /* CSS Khusus Mode Cetak (window.print()) */
        @media print {
            body {
                background: white !important;
                color: black !important;
            }
            body * {
                visibility: hidden;
            }
            #modalQR, #modalQR * {
                visibility: visible;
            }
            #modalQR {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                display: flex !important;
                justify-content: center;
                align-items: center;
                background: white !important;
                border: none !important;
            }
            /* Menghilangkan tombol close dan cetak */
            #modalQR button {
                display: none !important;
            }
            /* Menghilangkan background overlay modal */
            #modalQR > div:first-child {
                display: none !important;
            }
            
            /* Box Container Utama Modal dibentuk menjadi KARTU FISIK */
            #modalQR .bg-slate-900 {
                background: white !important;
                color: black !important;
                border: 3px double #0f172a !important; /* Elegant double border */
                border-radius: 20px !important;
                box-shadow: none !important;
                padding: 30px !important;
                width: 380px !important;
                max-width: 380px !important;
                height: 520px !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                align-items: center !important;
                box-sizing: border-box !important;
                position: relative !important;
            }
            
            /* Banner Header di dalam Kartu */
            #modalQR h3 {
                color: #0f172a !important;
                font-size: 22px !important;
                font-weight: 800 !important;
                text-transform: uppercase !important;
                letter-spacing: 1px !important;
                margin: 0 !important;
                border-bottom: 2px solid #0f172a !important;
                width: 100% !important;
                padding-bottom: 8px !important;
                text-align: center !important;
            }
            
            /* Hilangkan deskripsi scan saat diprint agar lebih bersih */
            #modalQR p.text-sm.text-slate-400.mb-6 {
                display: none !important;
            }
            
            /* Kotak luar QR Code */
            #modalQR .bg-white {
                border: 1px solid #cbd5e1 !important;
                border-radius: 12px !important;
                padding: 12px !important;
                box-shadow: none !important;
                margin: 15px 0 !important;
                display: inline-block !important;
            }
            
            #modalQR #qrImage {
                width: 170px !important;
                height: 170px !important;
            }
            
            /* Container Detail Warga */
            #modalQR .bg-slate-800\/50 {
                background: #f8fafc !important;
                border: 1px dashed #94a3b8 !important;
                border-radius: 12px !important;
                padding: 15px !important;
                width: 100% !important;
                box-sizing: border-box !important;
                text-align: center !important;
            }
            
            #modalQR #qrWargaName {
                color: #000000 !important;
                font-size: 22px !important;
                font-weight: 800 !important;
                line-height: 1.2 !important;
                margin-bottom: 8px !important;
            }
            
            /* Tombol / Label Kode Unik (SJ-xxxx) */
            #modalQR .bg-blue-500\/20 {
                background: #0f172a !important; /* Warna gelap kontras untuk print */
                border: none !important;
                border-radius: 6px !important;
                padding: 4px 12px !important;
                display: inline-block !important;
            }
            
            #modalQR #qrDataString {
                color: #ffffff !important; /* Text putih di dalam background gelap */
                font-family: monospace !important;
                font-size: 14px !important;
                font-weight: bold !important;
            }
            
            /* Tampilkan Waktu Cetak di bagian paling bawah */
            #qrPrintTime {
                display: block !important;
                color: #64748b !important;
                font-size: 10px !important;
                margin-top: 10px !important;
                text-align: center !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body class="text-slate-300 flex h-screen overflow-hidden selection:bg-blue-500/30">

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col hidden md:flex transition-all duration-300">
        <div class="h-20 flex items-center px-6 border-b border-slate-800">
            <div class="flex items-center gap-3">
                <img src="assets/logo.png" alt="Logo SiJumpa" class="w-10 h-10 object-contain drop-shadow-[0_0_10px_rgba(59,130,246,0.3)] hover:scale-105 transition-transform">
                <div>
                    <h1 class="text-white font-bold text-lg leading-tight">SiJumpa</h1>
                    <p class="text-xs text-slate-400">Sistem Jumpitan Desa </p>
                </div>
            </div>
        </div>
        <!-- Menu -->
        <div class="flex-1 overflow-y-auto py-6 px-3 space-y-1">
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Menu Utama</p>
            
            <a href="dashboard.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?> flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? '' : 'text-slate-400'; ?>">
                <svg class="w-5 h-5 <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'text-blue-500' : ''; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                Dashboard
            </a>
            
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="warga.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'warga.php') ? 'active' : ''; ?> flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 <?php echo (basename($_SERVER['PHP_SELF']) == 'warga.php') ? '' : 'text-slate-400'; ?>">
                <svg class="w-5 h-5 <?php echo (basename($_SERVER['PHP_SELF']) == 'warga.php') ? 'text-blue-500' : ''; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Manajemen Warga
            </a>

            <a href="petugas.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'petugas.php') ? 'active' : ''; ?> flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 <?php echo (basename($_SERVER['PHP_SELF']) == 'petugas.php') ? '' : 'text-slate-400'; ?>">
                <svg class="w-5 h-5 <?php echo (basename($_SERVER['PHP_SELF']) == 'petugas.php') ? 'text-blue-500' : ''; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Manajemen Petugas
            </a>

            <a href="gang.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'gang.php') ? 'active' : ''; ?> flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 <?php echo (basename($_SERVER['PHP_SELF']) == 'gang.php') ? '' : 'text-slate-400'; ?>">
                <svg class="w-5 h-5 <?php echo (basename($_SERVER['PHP_SELF']) == 'gang.php') ? 'text-blue-500' : ''; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
                Manajemen Gang
            </a>

            <a href="jadwal.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'jadwal.php') ? 'active' : ''; ?> flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 <?php echo (basename($_SERVER['PHP_SELF']) == 'jadwal.php') ? '' : 'text-slate-400'; ?>">
                <svg class="w-5 h-5 <?php echo (basename($_SERVER['PHP_SELF']) == 'jadwal.php') ? 'text-blue-500' : ''; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Jadwal & Rute
            </a>
            <?php endif; ?>

            <a href="laporan.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'laporan.php') ? 'active' : ''; ?> flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 <?php echo (basename($_SERVER['PHP_SELF']) == 'laporan.php') ? '' : 'text-slate-400'; ?>">
                <svg class="w-5 h-5 <?php echo (basename($_SERVER['PHP_SELF']) == 'laporan.php') ? 'text-blue-500' : ''; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Laporan Transaksi
            </a>

            <a href="pengeluaran.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'pengeluaran.php') ? 'active' : ''; ?> flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 <?php echo (basename($_SERVER['PHP_SELF']) == 'pengeluaran.php') ? '' : 'text-slate-400'; ?>">
                <svg class="w-5 h-5 <?php echo (basename($_SERVER['PHP_SELF']) == 'pengeluaran.php') ? 'text-blue-500' : ''; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Pengeluaran Kas
            </a>

            <?php if ($_SESSION['role'] === 'admin'): ?>
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-6 mb-2">Pengaturan</p>
            
            <a href="audit.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'audit.php') ? 'active' : ''; ?> flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 <?php echo (basename($_SERVER['PHP_SELF']) == 'audit.php') ? '' : 'text-slate-400'; ?>">
                <svg class="w-5 h-5 <?php echo (basename($_SERVER['PHP_SELF']) == 'audit.php') ? 'text-blue-500' : ''; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Sistem & Audit
            </a>
            <?php endif; ?>
        </div>
        <div class="p-4 border-t border-slate-800">
            <a href="logout.php" onclick="event.preventDefault(); Swal.fire({title: 'Konfirmasi Keluar', text: 'Apakah Anda yakin ingin keluar dari sistem?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#334155', confirmButtonText: 'Ya, Keluar!', cancelButtonText: 'Batal', background: '#1e293b', color: '#fff'}).then((result) => { if(result.isConfirmed) { window.location.href = 'logout.php'; } })" class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>Keluar
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-screen relative overflow-hidden">
        
        <!-- Ornamen Background -->
        <div class="absolute w-[600px] h-[600px] bg-blue-600/10 rounded-full blur-[120px] top-[-200px] right-[-200px] pointer-events-none"></div>

        <!-- Top Header -->
        <header class="h-20 flex items-center justify-between px-8 border-b border-slate-800/50 bg-slate-900/50 backdrop-blur-md z-10 relative">
            <div class="flex items-center gap-4">
                <button class="md:hidden text-slate-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h2 class="text-xl font-semibold text-white tracking-wide">Manajemen Warga</h2>
            </div>
            
            <div class="flex items-center gap-6">
                <!-- Search -->

                <div class="flex items-center gap-3 border-l border-slate-700 pl-6">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-medium text-white"><?= htmlspecialchars($nama_admin) ?></p>
                        <p class="text-xs text-slate-400"><?= htmlspecialchars($role_display) ?></p>
                    </div>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_admin) ?>&background=3b82f6&color=fff&rounded=true" alt="Profile" class="w-10 h-10 rounded-full border-2 border-slate-700">
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <div class="flex-1 overflow-y-auto p-8 relative z-10">

            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-white mb-1">Data Warga Desa</h1>
                    <p class="text-slate-400 text-sm">Kelola informasi warga, kode rumah, dan Generate QR Code.</p>
                </div>
                <button onclick="document.getElementById('modalTambah').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-medium transition-colors flex items-center gap-2 shadow-lg shadow-blue-500/30">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    Tambah Warga Baru
                </button>
            </div>

            <!-- Table Card -->
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 text-sm text-slate-400 bg-slate-800/20">
                                <th class="py-4 px-6 font-medium">Kode Rumah</th>
                                <th class="py-4 px-6 font-medium">Nama Warga (KK)</th>
                                <th class="py-4 px-6 font-medium">Alamat Lengkap</th>
                                <th class="py-4 px-6 font-medium">Gang / Rute</th>
                                <th class="py-4 px-6 font-medium text-center">QR Code</th>
                                <th class="py-4 px-6 font-medium text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-800/50">
                            <?php if (mysqli_num_rows($query_warga) > 0): ?>
                                <?php while ($warga = mysqli_fetch_assoc($query_warga)): ?>
                                <tr class="hover:bg-slate-800/20 transition-colors group">
                                    <td class="py-4 px-6 font-medium text-blue-400"><?= htmlspecialchars($warga['kode_rumah']) ?></td>
                                    <td class="py-4 px-6 text-white font-medium">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold"><?= substr($warga['nama'], 0, 1) ?></div>
                                            <?= htmlspecialchars($warga['nama']) ?>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-slate-400"><?= htmlspecialchars($warga['alamat']) ?></td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-blue-500/10 text-blue-400 text-xs font-semibold border border-blue-500/20">
                                            <?= htmlspecialchars($warga['gang'] ?: '-') ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <button onclick="showQR('<?= $warga['qr_code'] ?>', '<?= htmlspecialchars($warga['nama']) ?>', '<?= htmlspecialchars($warga['kode_rumah']) ?>')" class="text-xs px-3 py-1.5 bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 rounded-lg hover:bg-indigo-500/20 transition-colors flex items-center justify-center gap-2 mx-auto">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                                            Lihat QR
                                        </button>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="flex items-center justify-end gap-2 transition-opacity">
                                            <button onclick="editWarga('<?= $warga['id'] ?>', '<?= htmlspecialchars($warga['nama'], ENT_QUOTES) ?>', '<?= htmlspecialchars($warga['kode_rumah'], ENT_QUOTES) ?>', '<?= htmlspecialchars($warga['alamat'], ENT_QUOTES) ?>', '<?= htmlspecialchars($warga['gang'] ?: '', ENT_QUOTES) ?>')" class="p-1.5 text-slate-400 hover:text-blue-400 bg-slate-800 rounded-lg hover:bg-slate-700 transition-colors" title="Edit">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                            </button>
                                            <button onclick="confirmHapus('warga.php?hapus=<?= $warga['id'] ?>')" class="p-1.5 text-slate-400 hover:text-red-400 bg-slate-800 rounded-lg hover:bg-slate-700 transition-colors" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-500">Belum ada data warga terdaftar.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <footer class="mt-8 text-center text-sm text-slate-500 pb-4">
                &copy; <?= date('Y') ?> SiJumpa - Sistem Jumpitan Desa Nangungan.
            </footer>

        </div>
    </main>

    <!-- Modal Tambah Warga -->
    <div id="modalTambah" class="fixed inset-0 z-50 flex items-center justify-center hidden" onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-md modal-overlay"></div>
        <div class="modal-content bg-gradient-to-b from-slate-800 to-slate-900 border border-slate-700/50 rounded-2xl w-full max-w-lg relative z-10 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.5)] overflow-hidden">
            <div class="relative px-7 pt-7 pb-5">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-cyan-400 to-sky-500"></div>
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center shadow-lg shadow-blue-500/25 flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Tambah Warga Baru</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Daftarkan data warga dan generate QR Code otomatis</p>
                        </div>
                    </div>
                    <button onclick="document.getElementById('modalTambah').classList.add('hidden')" class="w-8 h-8 rounded-lg bg-slate-700/50 hover:bg-slate-600 flex items-center justify-center text-slate-400 hover:text-white transition-all duration-200 hover:rotate-90 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>
            <div class="h-px bg-gradient-to-r from-transparent via-slate-700 to-transparent"></div>
            <form method="POST" class="px-7 py-6 space-y-5">
                <div class="form-group">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                        <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        Nama Kepala Keluarga
                    </label>
                    <input type="text" name="nama" required placeholder="Masukkan nama lengkap KK" class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 hover:border-slate-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                            <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                            Kode Rumah
                        </label>
                        <input type="text" name="kode_rumah" required placeholder="Cth: RT01-005" class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 hover:border-slate-500">
                    </div>
                    <div class="form-group">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                            <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            Gang / Rute
                        </label>
                        <?php $gang_options = mysqli_query($conn, "SELECT nama FROM gang ORDER BY nama ASC"); ?>
                        <select name="gang" required class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 hover:border-slate-500 appearance-none cursor-pointer">
                            <option value="" disabled selected>Pilih Gang</option>
                            <?php while($opt = mysqli_fetch_assoc($gang_options)): ?>
                                <option value="<?php echo htmlspecialchars($opt['nama']); ?>"><?php echo htmlspecialchars($opt['nama']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                        <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        Alamat Lengkap
                    </label>
                    <textarea name="alamat" rows="3" required placeholder="Masukkan alamat lengkap warga..." class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 hover:border-slate-500 resize-none"></textarea>
                </div>
                <div class="flex gap-3 justify-end pt-2 border-t border-slate-700/50">
                    <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')" class="px-5 py-2.5 text-sm font-medium text-slate-300 hover:text-white bg-slate-700/50 hover:bg-slate-600 rounded-xl transition-all duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Batal
                    </button>
                    <button type="submit" name="tambah" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-0.5 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Simpan Warga
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Warga -->
    <div id="modalEdit" class="fixed inset-0 z-50 flex items-center justify-center hidden" onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-md modal-overlay"></div>
        <div class="modal-content bg-gradient-to-b from-slate-800 to-slate-900 border border-slate-700/50 rounded-2xl w-full max-w-lg relative z-10 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.5)] overflow-hidden">
            <div class="relative px-7 pt-7 pb-5">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 via-orange-400 to-yellow-500"></div>
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/25 flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Edit Data Warga</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Perbarui informasi warga yang sudah terdaftar</p>
                        </div>
                    </div>
                    <button onclick="document.getElementById('modalEdit').classList.add('hidden')" class="w-8 h-8 rounded-lg bg-slate-700/50 hover:bg-slate-600 flex items-center justify-center text-slate-400 hover:text-white transition-all duration-200 hover:rotate-90 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>
            <div class="h-px bg-gradient-to-r from-transparent via-slate-700 to-transparent"></div>
            <form method="POST" class="px-7 py-6 space-y-5">
                <input type="hidden" name="id_edit" id="edit_id">
                <div class="form-group">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                        <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        Nama Kepala Keluarga
                    </label>
                    <input type="text" name="nama" id="edit_nama" required class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 transition-all duration-200 hover:border-slate-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                            <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                            Kode Rumah
                        </label>
                        <input type="text" name="kode_rumah" id="edit_kode_rumah" required class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 transition-all duration-200 hover:border-slate-500">
                    </div>
                    <div class="form-group">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                            <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            Gang / Rute
                        </label>
                        <?php $edit_gang_options = mysqli_query($conn, "SELECT nama FROM gang ORDER BY nama ASC"); ?>
                        <select name="gang" id="edit_gang" required class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 transition-all duration-200 hover:border-slate-500 appearance-none cursor-pointer">
                            <option value="" disabled>Pilih Gang</option>
                            <?php while($opt = mysqli_fetch_assoc($edit_gang_options)): ?>
                                <option value="<?php echo htmlspecialchars($opt['nama']); ?>"><?php echo htmlspecialchars($opt['nama']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                        <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        Alamat Lengkap
                    </label>
                    <textarea name="alamat" id="edit_alamat" rows="3" required class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 transition-all duration-200 hover:border-slate-500 resize-none"></textarea>
                </div>
                <div class="flex gap-3 justify-end pt-2 border-t border-slate-700/50">
                    <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')" class="px-5 py-2.5 text-sm font-medium text-slate-300 hover:text-white bg-slate-700/50 hover:bg-slate-600 rounded-xl transition-all duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Batal
                    </button>
                    <button type="submit" name="edit" class="bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 hover:-translate-y-0.5 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Update Warga
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Lihat QR Code -->
    <div id="modalQR" class="fixed inset-0 z-50 flex items-center justify-center hidden" onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-md modal-overlay" onclick="document.getElementById('modalQR').classList.add('hidden')"></div>
        <div class="modal-content bg-gradient-to-b from-slate-800 to-slate-900 border border-slate-700/50 rounded-2xl w-full max-w-sm relative z-10 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.5)] overflow-hidden text-center">
            <div class="relative px-8 pt-7 pb-5">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 via-purple-400 to-pink-500"></div>
                <button onclick="document.getElementById('modalQR').classList.add('hidden')" class="absolute top-4 right-4 w-8 h-8 rounded-lg bg-slate-700/50 hover:bg-slate-600 flex items-center justify-center text-slate-400 hover:text-white transition-all duration-200 hover:rotate-90">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                <h3 class="text-xl font-bold text-white mb-2">QR Code Rumah</h3>
                <p class="text-sm text-slate-400 mb-6">Scan QR ini menggunakan aplikasi SiJumpa Petugas</p>
            </div>
            <div class="px-8 pb-8">
                <div class="bg-white p-4 rounded-2xl inline-block shadow-lg shadow-blue-500/20 mb-6">
                    <img id="qrImage" src="" alt="QR Code" class="w-48 h-48 mx-auto">
                </div>
                <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700/50">
                    <p id="qrWargaName" class="text-lg font-bold text-white"></p>
                    <div class="mt-3 py-1.5 px-3 bg-blue-500/20 border border-blue-500/30 rounded-lg inline-block">
                        <p id="qrDataString" class="text-sm font-mono text-blue-400 font-medium"></p>
                    </div>
                </div>
                <p id="qrPrintTime" class="hidden text-xs text-slate-500 mt-4 border-t border-slate-700/50 pt-2"></p>
                <button onclick="window.print()" class="w-full mt-6 bg-slate-700/50 hover:bg-slate-600 text-white px-5 py-3 rounded-xl text-sm font-medium transition-all duration-200 flex items-center justify-center gap-2 border border-slate-600/50 hover:-translate-y-0.5">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                    Cetak QR Code
                </button>
            </div>
        </div>
    </div>

    <script>
        function showQR(qrCode, namaWarga, kodeRumah) {
            document.getElementById('qrImage').src = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + encodeURIComponent(qrCode) + '&margin=10';
            document.getElementById('qrWargaName').innerText = namaWarga;
            document.getElementById('qrDataString').innerText = qrCode;
            
            const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            
            const now = new Date();
            const namaHari = hari[now.getDay()];
            const tgl = now.getDate();
            const namaBulan = bulan[now.getMonth()];
            const thn = now.getFullYear();
            const jam = String(now.getHours()).padStart(2, '0');
            const menit = String(now.getMinutes()).padStart(2, '0');
            
            const waktuFormatted = `Dicetak pada: ${namaHari}, ${tgl} ${namaBulan} ${thn} - ${jam}:${menit} WIB`;
            document.getElementById('qrPrintTime').innerText = waktuFormatted;
            
            document.getElementById('modalQR').classList.remove('hidden');
        }

        function editWarga(id, nama, kode_rumah, alamat, gang) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_kode_rumah').value = kode_rumah;
            document.getElementById('edit_gang').value = gang;
            document.getElementById('edit_alamat').value = alamat;
            document.getElementById('modalEdit').classList.remove('hidden');
        }

        function confirmHapus(url) {
            Swal.fire({
                title: 'Hapus Data Warga?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#334155',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                background: '#1e293b',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            })
        }
    </script>

    <?php if(isset($_SESSION['swal'])): ?>
    <script>
        Swal.fire({
            title: '<?= $_SESSION['swal']['title'] ?>',
            text: '<?= $_SESSION['swal']['text'] ?>',
            icon: '<?= $_SESSION['swal']['icon'] ?>',
            confirmButtonColor: '#3b82f6',
            background: '#1e293b',
            color: '#fff'
        });
    </script>
    <?php unset($_SESSION['swal']); endif; ?>

    <style>
        @keyframes modalFadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes modalSlideUp { from { opacity: 0; transform: scale(0.95) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        .modal-overlay { animation: modalFadeIn 0.2s ease-out forwards; }
        .modal-content { animation: modalSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .form-input:focus { box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        #modalEdit .form-input:focus { box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1); }
        .form-group { transition: transform 0.15s ease; }
        select.form-input {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.25em 1.25em;
            padding-right: 2.5rem;
        }
        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(0.7); cursor: pointer; }
    </style>
</body>
</html>


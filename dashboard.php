<?php
session_start();
require_once 'config/koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'bendahara') {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$nama_admin = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Admin SiJumpa';
$role_admin = isset($_SESSION['role']) ? $_SESSION['role'] : 'admin';
$role_display = ($role_admin === 'admin') ? 'Super Admin' : (($role_admin === 'bendahara') ? 'Bendahara' : 'Petugas');

// Waktu saat ini
$bulan_ini = date('m');
$tahun_ini = date('Y');
$hari_ini = date('Y-m-d');

// 1. Total Jumpitan Masuk (Bulan Ini)
$query_total_uang = mysqli_query($conn, "SELECT SUM(nominal) as total FROM transaksi_jumpitan WHERE status='bayar' AND MONTH(tanggal) = '$bulan_ini' AND YEAR(tanggal) = '$tahun_ini'");
$row_uang = mysqli_fetch_assoc($query_total_uang);
$total_uang = $row_uang['total'] ? $row_uang['total'] : 0;

// Perbandingan dengan Bulan Lalu
$bulan_lalu = date('m', strtotime('-1 month'));
$tahun_lalu = date('Y', strtotime('-1 month'));
$query_total_uang_lalu = mysqli_query($conn, "SELECT SUM(nominal) as total FROM transaksi_jumpitan WHERE status='bayar' AND MONTH(tanggal) = '$bulan_lalu' AND YEAR(tanggal) = '$tahun_lalu'");
$row_uang_lalu = mysqli_fetch_assoc($query_total_uang_lalu);
$total_uang_lalu = $row_uang_lalu['total'] ? $row_uang_lalu['total'] : 0;

if ($total_uang_lalu > 0) {
    $persen_uang = (($total_uang - $total_uang_lalu) / $total_uang_lalu) * 100;
} else {
    $persen_uang = $total_uang > 0 ? 100 : 0;
}
$persen_uang_format = ($persen_uang >= 0 ? '+' : '') . number_format($persen_uang, 1) . '%';
$persen_uang_color = $persen_uang >= 0 ? 'emerald' : 'rose';

// 2. Total Warga & Warga Sudah Bayar (Bulan Ini)
$query_warga = mysqli_query($conn, "SELECT COUNT(id) as total FROM warga");
$total_warga = mysqli_fetch_assoc($query_warga)['total'];

$query_warga_bayar = mysqli_query($conn, "SELECT COUNT(DISTINCT warga_id) as total FROM transaksi_jumpitan WHERE status='bayar' AND MONTH(tanggal) = '$bulan_ini' AND YEAR(tanggal) = '$tahun_ini'");
$warga_bayar = mysqli_fetch_assoc($query_warga_bayar)['total'];

$persen_bayar = $total_warga > 0 ? round(($warga_bayar / $total_warga) * 100) : 0;
$warga_belum_bayar = $total_warga - $warga_bayar;

// 3. Total Petugas & Petugas Aktif Hari Ini
$query_petugas = mysqli_query($conn, "SELECT COUNT(id) as total FROM users WHERE role='petugas'");
$total_petugas = mysqli_fetch_assoc($query_petugas)['total'];

$query_petugas_aktif = mysqli_query($conn, "SELECT COUNT(DISTINCT petugas_id) as total FROM jadwal WHERE tanggal = '$hari_ini'");
$petugas_aktif = mysqli_fetch_assoc($query_petugas_aktif)['total'];

// 4. Pengeluaran & Saldo Kas
$query_total_pengeluaran = mysqli_query($conn, "SELECT SUM(nominal) as total FROM pengeluaran WHERE MONTH(tanggal) = '$bulan_ini' AND YEAR(tanggal) = '$tahun_ini'");
$row_pengeluaran = mysqli_fetch_assoc($query_total_pengeluaran);
$total_pengeluaran = $row_pengeluaran['total'] ? $row_pengeluaran['total'] : 0;

$query_total_pengeluaran_lalu = mysqli_query($conn, "SELECT SUM(nominal) as total FROM pengeluaran WHERE MONTH(tanggal) = '$bulan_lalu' AND YEAR(tanggal) = '$tahun_lalu'");
$row_pengeluaran_lalu = mysqli_fetch_assoc($query_total_pengeluaran_lalu);
$total_pengeluaran_lalu = $row_pengeluaran_lalu['total'] ? $row_pengeluaran_lalu['total'] : 0;

if ($total_pengeluaran_lalu > 0) {
    $persen_pengeluaran = (($total_pengeluaran - $total_pengeluaran_lalu) / $total_pengeluaran_lalu) * 100;
} else {
    $persen_pengeluaran = $total_pengeluaran > 0 ? 100 : 0;
}
$persen_pengeluaran_format = ($persen_pengeluaran >= 0 ? '+' : '') . number_format($persen_pengeluaran, 1) . '%';
$persen_pengeluaran_color = $persen_pengeluaran >= 0 ? 'rose' : 'emerald';

// Total Pendapatan All-Time
$query_uang_all = mysqli_query($conn, "SELECT SUM(nominal) as total FROM transaksi_jumpitan WHERE status='bayar'");
$row_uang_all = mysqli_fetch_assoc($query_uang_all);
$total_uang_all = $row_uang_all['total'] ? $row_uang_all['total'] : 0;

// Total Pengeluaran All-Time
$query_pengeluaran_all = mysqli_query($conn, "SELECT SUM(nominal) as total FROM pengeluaran");
$row_pengeluaran_all = mysqli_fetch_assoc($query_pengeluaran_all);
$total_pengeluaran_all = $row_pengeluaran_all['total'] ? $row_pengeluaran_all['total'] : 0;

// Saldo Kas Sisa
$saldo_kas = $total_uang_all - $total_pengeluaran_all;

// Data Transaksi Terakhir (Limit 5 dengan Filter)
$where_trx = "1=1";
if (isset($_GET['status']) && $_GET['status'] != '') {
    $status_filter = mysqli_real_escape_string($conn, $_GET['status']);
    $where_trx .= " AND t.status = '$status_filter'";
}

$query_trx = mysqli_query($conn, "
    SELECT t.*, w.nama as nama_warga, u.nama as nama_petugas 
    FROM transaksi_jumpitan t 
    LEFT JOIN warga w ON t.warga_id = w.id 
    LEFT JOIN users u ON t.petugas_id = u.id 
    WHERE $where_trx
    ORDER BY t.tanggal DESC LIMIT 5
");

// Data Aktivitas Terbaru (Limit 4)
$query_aktivitas = mysqli_query($conn, "
    SELECT t.*, w.nama as nama_warga, u.nama as nama_petugas 
    FROM transaksi_jumpitan t 
    LEFT JOIN warga w ON t.warga_id = w.id 
    LEFT JOIN users u ON t.petugas_id = u.id 
    ORDER BY t.tanggal DESC LIMIT 4
");

// Data Grafik
$filter_grafik = isset($_GET['filter_grafik']) ? $_GET['filter_grafik'] : 'minggu';
$grafik_labels = [];
$grafik_data = [];

if ($filter_grafik == 'tahun') {
    // 12 Bulan dalam tahun ini
    $tahun_ini = date('Y');
    $bulan_indo = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    for ($i = 1; $i <= 12; $i++) {
        $grafik_labels[] = $bulan_indo[$i - 1];
        $q_grafik = mysqli_query($conn, "SELECT SUM(nominal) as total FROM transaksi_jumpitan WHERE status='bayar' AND MONTH(tanggal) = '$i' AND YEAR(tanggal) = '$tahun_ini'");
        $r_grafik = mysqli_fetch_assoc($q_grafik);
        $grafik_data[] = $r_grafik['total'] ? $r_grafik['total'] : 0;
    }
} elseif ($filter_grafik == 'bulan') {
    // 30 Hari Terakhir
    for ($i = 29; $i >= 0; $i--) {
        $tgl = date('Y-m-d', strtotime("-$i days"));
        $grafik_labels[] = date('d M', strtotime($tgl));
        $q_grafik = mysqli_query($conn, "SELECT SUM(nominal) as total FROM transaksi_jumpitan WHERE status='bayar' AND DATE(tanggal) = '$tgl'");
        $r_grafik = mysqli_fetch_assoc($q_grafik);
        $grafik_data[] = $r_grafik['total'] ? $r_grafik['total'] : 0;
    }
} else {
    // 7 Hari Terakhir
    for ($i = 6; $i >= 0; $i--) {
        $tgl = date('Y-m-d', strtotime("-$i days"));
        $grafik_labels[] = date('D', strtotime($tgl));
        $q_grafik = mysqli_query($conn, "SELECT SUM(nominal) as total FROM transaksi_jumpitan WHERE status='bayar' AND DATE(tanggal) = '$tgl'");
        $r_grafik = mysqli_fetch_assoc($q_grafik);
        $grafik_data[] = $r_grafik['total'] ? $r_grafik['total'] : 0;
    }
}

$grafik_labels_json = json_encode($grafik_labels);
$grafik_data_json = json_encode($grafik_data);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SiJumpa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0f172a;
        }
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #0f172a; 
        }
        ::-webkit-scrollbar-thumb {
            background: #334155; 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #475569; 
        }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .nav-item.active {
            background: linear-gradient(to right, rgba(59, 130, 246, 0.2), transparent);
            border-left: 4px solid #3b82f6;
            color: white;
        }
    </style>
</head>
<body class="text-slate-300 flex h-screen overflow-hidden selection:bg-blue-500/30">

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col hidden md:flex transition-all duration-300">
        <!-- Logo -->
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

        <!-- Logout Button -->
        <div class="p-4 border-t border-slate-800">
            <a href="logout.php" onclick="event.preventDefault(); Swal.fire({title: 'Konfirmasi Keluar', text: 'Apakah Anda yakin ingin keluar dari sistem?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#334155', confirmButtonText: 'Ya, Keluar!', cancelButtonText: 'Batal', background: '#1e293b', color: '#fff'}).then((result) => { if(result.isConfirmed) { window.location.href = 'logout.php'; } })" class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Keluar
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
                <h2 class="text-xl font-semibold text-white tracking-wide">Overview</h2>
            </div>
            
            <div class="flex items-center gap-6">


                <!-- Profile -->
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
            
            <!-- Welcome Banner -->
            <div class="glass-card rounded-2xl p-6 mb-8 flex items-center justify-between bg-gradient-to-r from-blue-900/40 to-indigo-900/40 border-l-4 border-l-blue-500">
                <div>
                    <h3 class="text-2xl font-bold text-white mb-1">Selamat datang kembali, <?= htmlspecialchars($nama_admin) ?>! 👋</h3>
                    <p class="text-slate-400 text-sm">Pantau aktivitas jumpitan Desa Nangungan hari ini. Semua sistem berjalan normal.</p>
                </div>
                <div class="hidden lg:block text-right">
                    <p class="text-sm text-slate-400 mb-1">Tanggal Hari Ini</p>
                    <p class="text-white font-medium bg-slate-800/80 px-4 py-2 rounded-lg inline-block"><?= date('d F Y') ?></p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                
                <!-- Card 1: Pendapatan -->
                <div class="glass-card p-6 rounded-2xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mr-4 -mt-4 w-24 h-24 rounded-full bg-emerald-500/10 blur-2xl group-hover:bg-emerald-500/20 transition-all"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-<?= $persen_uang_color ?>-500/10 text-<?= $persen_uang_color ?>-400 border border-<?= $persen_uang_color ?>-500/20" title="Dibanding bulan lalu">
                            <?= $persen_uang_format ?>
                        </span>
                    </div>
                    <h4 class="text-slate-400 text-sm font-medium mb-1">Total Jumpitan Masuk</h4>
                    <h2 class="text-3xl font-bold text-white">Rp <?= number_format($total_uang, 0, ',', '.') ?></h2>
                    <p class="text-xs text-slate-500 mt-2">Bulan ini (<?= date('F') ?>)</p>
                </div>

                <!-- Card 2: Pengeluaran -->
                <div class="glass-card p-6 rounded-2xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mr-4 -mt-4 w-24 h-24 rounded-full bg-rose-500/10 blur-2xl group-hover:bg-rose-500/20 transition-all"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div class="w-12 h-12 rounded-xl bg-rose-500/20 flex items-center justify-center text-rose-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-<?= $persen_pengeluaran_color ?>-500/10 text-<?= $persen_pengeluaran_color ?>-400 border border-<?= $persen_pengeluaran_color ?>-500/20" title="Dibanding bulan lalu">
                            <?= $persen_pengeluaran_format ?>
                        </span>
                    </div>
                    <h4 class="text-slate-400 text-sm font-medium mb-1">Pengeluaran Kas</h4>
                    <h2 class="text-3xl font-bold text-white">Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?></h2>
                    <p class="text-xs text-slate-500 mt-2">Bulan ini (<?= date('F') ?>)</p>
                </div>

                <!-- Card 3: Saldo Kas -->
                <div class="glass-card p-6 rounded-2xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mr-4 -mt-4 w-24 h-24 rounded-full bg-blue-500/10 blur-2xl group-hover:bg-blue-500/20 transition-all"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center text-blue-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                    <h4 class="text-slate-400 text-sm font-medium mb-1">Saldo Sisa Kas</h4>
                    <h2 class="text-3xl font-bold text-emerald-400">Rp <?= number_format($saldo_kas, 0, ',', '.') ?></h2>
                    <p class="text-xs text-slate-500 mt-2">Akumulasi saldo bersih</p>
                </div>

                <!-- Card 4: Warga Sudah Bayar -->
                <div class="glass-card p-6 rounded-2xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mr-4 -mt-4 w-24 h-24 rounded-full bg-purple-500/10 blur-2xl group-hover:bg-purple-500/20 transition-all"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center text-purple-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-purple-500/10 text-purple-400 border border-purple-500/20"><?= $persen_bayar ?>% Tuntas</span>
                    </div>
                    <h4 class="text-slate-400 text-sm font-medium mb-1">Warga Sudah Bayar</h4>
                    <h2 class="text-3xl font-bold text-white"><?= $warga_bayar ?> <span class="text-lg font-normal text-slate-500">/ <?= $total_warga ?> KK</span></h2>
                    <div class="w-full bg-slate-800 rounded-full h-1.5 mt-3">
                        <div class="bg-purple-500 h-1.5 rounded-full" style="width: <?= $persen_bayar ?>%"></div>
                    </div>
                </div>

            </div>

            <!-- Charts & Lists Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                
                <!-- Chart Area -->
                <div class="glass-card rounded-2xl p-6 lg:col-span-2">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-white">Grafik Jumpitan Masuk</h3>
                        <form id="formFilterGrafik" method="GET" action="dashboard.php">
                            <select name="filter_grafik" onchange="document.getElementById('formFilterGrafik').submit()" class="bg-slate-800 border border-slate-700 text-sm rounded-lg px-3 py-1.5 focus:outline-none focus:border-blue-500 text-slate-300">
                                <option value="minggu" <?= $filter_grafik == 'minggu' ? 'selected' : '' ?>>7 Hari Terakhir</option>
                                <option value="bulan" <?= $filter_grafik == 'bulan' ? 'selected' : '' ?>>30 Hari Terakhir</option>
                                <option value="tahun" <?= $filter_grafik == 'tahun' ? 'selected' : '' ?>>Tahun Ini</option>
                            </select>
                        </form>
                    </div>
                    <div class="h-72 w-full relative">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Realtime Activity -->
                <div class="glass-card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-white">Aktivitas Petugas</h3>
                        <a href="#" class="text-xs text-blue-400 hover:text-blue-300">Lihat Semua</a>
                    </div>
                    
                    <div class="space-y-5">
                        <?php if(mysqli_num_rows($query_aktivitas) > 0): ?>
                            <?php 
                            $colors = ['blue', 'purple', 'emerald', 'rose'];
                            $i = 0;
                            $count = mysqli_num_rows($query_aktivitas);
                            while($act = mysqli_fetch_assoc($query_aktivitas)): 
                                $color = $colors[$i % count($colors)];
                                $is_last = ($i == $count - 1);
                            ?>
                            <!-- Activity Item -->
                            <div class="flex gap-4">
                                <div class="relative mt-1">
                                    <div class="w-2.5 h-2.5 bg-<?= $color ?>-500 rounded-full"></div>
                                    <?php if(!$is_last): ?>
                                    <div class="w-0.5 h-full bg-slate-700 absolute top-2.5 left-1/2 -translate-x-1/2"></div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-slate-200"><?= htmlspecialchars($act['nama_petugas']) ?> <span class="text-slate-500 font-normal">menarik jumpitan dari</span> <span class="text-white"><?= htmlspecialchars($act['nama_warga']) ?></span></p>
                                    <p class="text-xs text-emerald-400 mt-1 font-medium">+ Rp <?= number_format($act['nominal'], 0, ',', '.') ?></p>
                                    <p class="text-xs text-slate-500 mt-1"><?= date('d M Y, H:i', strtotime($act['tanggal'])) ?></p>
                                </div>
                            </div>
                            <?php $i++; endwhile; ?>
                        <?php else: ?>
                            <p class="text-sm text-slate-500 italic">Belum ada aktivitas transaksi terbaru.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Recent Transactions Table -->
            <div class="glass-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-white">Transaksi Jumpitan Terakhir</h3>
                    <div class="flex gap-2">
                        <form method="GET" class="flex gap-2">
                            <?php if (isset($_GET['filter_grafik'])): ?>
                                <input type="hidden" name="filter_grafik" value="<?= htmlspecialchars($_GET['filter_grafik']) ?>">
                            <?php endif; ?>
                            <select name="status" onchange="this.form.submit()" class="bg-slate-800 border border-slate-700 text-sm text-slate-300 rounded-lg px-3 py-1.5 focus:outline-none focus:border-blue-500">
                                <option value="">Semua Status</option>
                                <option value="bayar" <?= (isset($_GET['status']) && $_GET['status'] == 'bayar') ? 'selected' : '' ?>>Berhasil / Bayar</option>
                                <option value="belum" <?= (isset($_GET['status']) && $_GET['status'] == 'belum') ? 'selected' : '' ?>>Belum Bayar</option>
                            </select>
                        </form>
                        <button onclick="Swal.fire({title: 'Fitur Belum Tersedia', text: 'Fitur Export PDF sedang dikembangkan!', icon: 'info', confirmButtonColor: '#3b82f6', background: '#1e293b', color: '#fff'})" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 text-sm text-slate-400">
                                <th class="pb-3 px-4 font-medium">ID Transaksi</th>
                                <th class="pb-3 px-4 font-medium">Tanggal & Waktu</th>
                                <th class="pb-3 px-4 font-medium">Warga (KK)</th>
                                <th class="pb-3 px-4 font-medium">Petugas</th>
                                <th class="pb-3 px-4 font-medium">Metode</th>
                                <th class="pb-3 px-4 font-medium text-right">Jumlah</th>
                                <th class="pb-3 px-4 font-medium text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php if (mysqli_num_rows($query_trx) > 0): ?>
                                <?php while ($trx = mysqli_fetch_assoc($query_trx)): ?>
                                <tr class="border-b border-slate-800/50 hover:bg-slate-800/20 transition-colors">
                                    <td class="py-4 px-4 font-medium text-blue-400">#TRX-<?= str_pad($trx['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                    <td class="py-4 px-4 text-slate-300"><?= date('d M Y, H:i', strtotime($trx['tanggal'])) ?></td>
                                    <td class="py-4 px-4 text-white"><?= htmlspecialchars($trx['nama_warga']) ?></td>
                                    <td class="py-4 px-4 text-slate-300"><?= htmlspecialchars($trx['nama_petugas']) ?></td>
                                    <td class="py-4 px-4">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-800 text-slate-300 text-xs font-medium border border-slate-700">
                                            <?php if(strpos(strtolower($trx['keterangan']), 'qris') !== false): ?>
                                                <svg class="w-3.5 h-3.5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                                </svg>
                                                QRIS
                                            <?php else: ?>
                                                <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                Tunai
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right font-medium text-white">Rp <?= number_format($trx['nominal'], 0, ',', '.') ?></td>
                                    <td class="py-4 px-4 text-center">
                                        <?php if($trx['status'] == 'bayar'): ?>
                                            <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-medium border border-emerald-500/20">Berhasil</span>
                                        <?php elseif($trx['status'] == 'belum'): ?>
                                            <span class="px-2.5 py-1 rounded-full bg-rose-500/10 text-rose-400 text-xs font-medium border border-rose-500/20">Belum Bayar</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 rounded-full bg-slate-500/10 text-slate-400 text-xs font-medium border border-slate-500/20">Kosong</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-500">Belum ada transaksi</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <footer class="mt-8 text-center text-sm text-slate-500 pb-4 border-t border-slate-800 pt-6">
                &copy; <?= date('Y') ?> SiJumpa - Sistem Jumpitan Desa Nangungan. Dibuat dengan terintegrasi Flutter.
            </footer>

        </div>
    </main>

    <!-- Script for Chart -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            
            // Gradient for chart
            let gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.5)'); // Blue
            gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= $grafik_labels_json ?>,
                    datasets: [{
                        label: 'Jumpitan Masuk (Rp)',
                        data: <?= $grafik_data_json ?>,
                        borderColor: '#3b82f6',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        pointBackgroundColor: '#0f172a',
                        pointBorderColor: '#3b82f6',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#cbd5e1',
                            borderColor: 'rgba(59, 130, 246, 0.5)',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)',
                                drawBorder: false,
                            },
                            ticks: {
                                color: '#64748b',
                                callback: function(value) {
                                    return value / 1000 + 'k';
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false,
                            },
                            ticks: {
                                color: '#64748b'
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                }
            });
        });
    </script>
    <?php if(isset($_SESSION['swal_login'])): ?>
    <script>
        Swal.fire({
            title: 'Login Berhasil!',
            text: 'Selamat datang kembali di Panel Admin SiJumpa, <?= htmlspecialchars($_SESSION['nama']) ?>!',
            icon: 'success',
            confirmButtonColor: '#3b82f6',
            background: '#1e293b',
            color: '#fff'
        });
    </script>
    <?php unset($_SESSION['swal_login']); endif; ?>
</body>
</html>

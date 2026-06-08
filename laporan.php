<?php
session_start();
require_once 'config/koneksi.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$nama_admin = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Admin SiJumpa';
$role_admin = isset($_SESSION['role']) ? $_SESSION['role'] : 'admin';
$role_display = ($role_admin === 'admin') ? 'Super Admin' : (($role_admin === 'bendahara') ? 'Bendahara' : 'Petugas');

// Filter
$where = "1=1";
if(isset($_GET['status']) && $_GET['status'] != '') {
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    $where .= " AND t.status = '$status'";
}

// Ambil Data Laporan
$query_laporan = mysqli_query($conn, "
    SELECT t.*, w.nama as nama_warga, u.nama as nama_petugas 
    FROM transaksi_jumpitan t 
    LEFT JOIN warga w ON t.warga_id = w.id 
    LEFT JOIN users u ON t.petugas_id = u.id 
    WHERE $where
    ORDER BY t.tanggal DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Transaksi | SiJumpa</title>
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
    </style>
</head>
<body class="text-slate-300 flex h-screen overflow-hidden selection:bg-blue-500/30">

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
        <div class="flex-1 overflow-y-auto py-6 px-3 space-y-1">
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Menu Utama</p>

            <a href="dashboard.php" class="nav-item flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 text-slate-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                Dashboard
            </a>

            <?php if ($role_admin === 'admin'): ?>
            <a href="warga.php" class="nav-item flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 text-slate-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                Manajemen Warga
            </a>
            <a href="petugas.php" class="nav-item flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 text-slate-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
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

            <a href="laporan.php" class="nav-item active flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800">
                <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                Laporan Transaksi
            </a>
            <a href="pengeluaran.php" class="nav-item flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 text-slate-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Pengeluaran Kas
            </a>

            <?php if ($role_admin === 'admin'): ?>
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-6 mb-2">Pengaturan</p>
            <a href="audit.php" class="nav-item flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 text-slate-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                Sistem & Audit
            </a>
            <?php endif; ?>
        </div>
        <div class="p-4 border-t border-slate-800">
            <a href="logout.php" onclick="event.preventDefault(); Swal.fire({title: 'Konfirmasi Keluar', text: 'Apakah Anda yakin ingin keluar dari sistem?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#334155', confirmButtonText: 'Ya, Keluar!', cancelButtonText: 'Batal', background: '#1e293b', color: '#fff'}).then((result) => { if(result.isConfirmed) { window.location.href = 'logout.php'; } })" class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                Keluar
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-screen relative overflow-hidden">
        <!-- <div class="absolute w-[600px] h-[600px] bg-rose-600/10 rounded-full blur-[120px] top-[-200px] left-[-200px] pointer-events-none"></div> -->

        <header class="h-20 flex items-center justify-between px-8 border-b border-slate-800/50 bg-slate-900/50 backdrop-blur-md z-10 relative">
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-semibold text-white tracking-wide">Laporan Transaksi</h2>
            </div>
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3 border-l border-slate-700 pl-6">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-medium text-white"><?= htmlspecialchars($nama_admin) ?></p>
                        <p class="text-xs text-slate-400"><?= htmlspecialchars($role_display) ?></p>
                    </div>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_admin) ?>&background=3b82f6&color=fff&rounded=true" alt="Profile" class="w-10 h-10 rounded-full border-2 border-slate-700">
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8 relative z-10">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-white mb-1">Semua Riwayat Jumpitan</h1>
                    <p class="text-slate-400 text-sm">Lihat, filter, dan export laporan transaksi warga ke format PDF/Excel.</p>
                </div>
                <div class="flex gap-3">
                    <form method="GET" class="flex gap-2">
                        <select name="status" onchange="this.form.submit()" class="bg-slate-800 border border-slate-700 text-sm text-slate-300 rounded-lg px-4 py-2 focus:outline-none focus:border-rose-500">
                            <option value="">Semua Status</option>
                            <option value="bayar" <?= (isset($_GET['status']) && $_GET['status'] == 'bayar') ? 'selected' : '' ?>>Berhasil / Bayar</option>
                            <option value="belum" <?= (isset($_GET['status']) && $_GET['status'] == 'belum') ? 'selected' : '' ?>>Belum Bayar</option>
                        </select>
                    </form>
                    <button onclick="Swal.fire({title: 'Fitur Belum Tersedia', text: 'Fitur Export PDF sedang dikembangkan!', icon: 'info', confirmButtonColor: '#e11d48', background: '#1e293b', color: '#fff'})" class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 shadow-lg shadow-rose-500/30">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Export PDF
                    </button>
                </div>
            </div>

            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 text-sm text-slate-400 bg-slate-800/20">
                                <th class="py-4 px-6 font-medium">ID Transaksi</th>
                                <th class="py-4 px-6 font-medium">Tanggal & Waktu</th>
                                <th class="py-4 px-6 font-medium">Nama Warga</th>
                                <th class="py-4 px-6 font-medium">Petugas</th>
                                <th class="py-4 px-6 font-medium">Catatan</th>
                                <th class="py-4 px-6 font-medium text-right">Jumlah</th>
                                <th class="py-4 px-6 font-medium text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-800/50">
                            <?php if (mysqli_num_rows($query_laporan) > 0): ?>
                                <?php while ($trx = mysqli_fetch_assoc($query_laporan)): ?>
                                <tr class="hover:bg-slate-800/20 transition-colors group">
                                    <td class="py-4 px-6 font-medium text-blue-400">#TRX-<?= str_pad($trx['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                    <td class="py-4 px-6 text-slate-300"><?= date('d M Y, H:i', strtotime($trx['tanggal'])) ?></td>
                                    <td class="py-4 px-6 text-white font-medium"><?= htmlspecialchars($trx['nama_warga']) ?></td>
                                    <td class="py-4 px-6 text-slate-300"><?= htmlspecialchars($trx['nama_petugas']) ?></td>
                                    <td class="py-4 px-6 text-slate-400 italic"><?= htmlspecialchars($trx['keterangan'] ?: '-') ?></td>
                                    <td class="py-4 px-6 text-right font-medium text-white">Rp <?= number_format($trx['nominal'], 0, ',', '.') ?></td>
                                    <td class="py-4 px-6 text-center">
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
                                    <td colspan="7" class="py-12 text-center text-slate-500">Belum ada transaksi ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <footer class="mt-8 text-center text-sm text-slate-500 pb-4">
                &copy; <?= date('Y') ?> SiJumpa - Sistem Jumpitan Desa Nangungan.
            </footer>
        </div>
    </main>
</body>
</html>

<?php
session_start();
require_once 'config/koneksi.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Hapus otomatis jadwal yang tanggalnya sudah lewat dari hari ini (kemarin atau sebelumnya)
mysqli_query($conn, "DELETE FROM jadwal WHERE tanggal < CURDATE()");

$nama_admin = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Admin SiJumpa';
$role_admin = isset($_SESSION['role']) ? $_SESSION['role'] : 'admin';
$role_display = ($role_admin === 'admin') ? 'Super Admin' : (($role_admin === 'bendahara') ? 'Bendahara' : 'Petugas');

// Proses Hapus Jadwal
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM jadwal WHERE id = $id_hapus");
    $_SESSION['swal'] = ['title' => 'Berhasil!', 'text' => 'Jadwal penarikan berhasil dihapus.', 'icon' => 'success'];
    header("Location: jadwal.php");
    exit;
}
// Proses Tambah Jadwal
if (isset($_POST['tambah'])) {
    $petugas_id = (int)$_POST['petugas_id'];
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $gang = mysqli_real_escape_string($conn, $_POST['gang']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);

    mysqli_query($conn, "INSERT INTO jadwal (tanggal, petugas_id, gang, keterangan) VALUES ('$tanggal', $petugas_id, '$gang', '$keterangan')");
    $_SESSION['swal'] = ['title' => 'Berhasil!', 'text' => 'Jadwal baru berhasil ditambahkan.', 'icon' => 'success'];
    header("Location: jadwal.php");
    exit;
}

// Proses Edit Jadwal
if (isset($_POST['edit'])) {
    $id_edit = (int)$_POST['id_edit'];
    $petugas_id = (int)$_POST['petugas_id'];
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $gang = mysqli_real_escape_string($conn, $_POST['gang']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);

    mysqli_query($conn, "UPDATE jadwal SET tanggal='$tanggal', petugas_id=$petugas_id, gang='$gang', keterangan='$keterangan' WHERE id=$id_edit");
    $_SESSION['swal'] = ['title' => 'Berhasil!', 'text' => 'Data jadwal berhasil diperbarui.', 'icon' => 'success'];
    header("Location: jadwal.php");
    exit;
}

// Ambil Data Jadwal
$query_jadwal = mysqli_query($conn, "
    SELECT j.*, u.nama as nama_petugas 
    FROM jadwal j 
    JOIN users u ON j.petugas_id = u.id 
    ORDER BY j.tanggal DESC
");

// Ambil List Petugas untuk form
$query_petugas = mysqli_query($conn, "SELECT id, nama FROM users WHERE role = 'petugas'");

// Ambil List Gang untuk form
$query_gang = mysqli_query($conn, "SELECT nama FROM gang ORDER BY nama ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal & Rute | SiJumpa</title>
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

    <main class="flex-1 flex flex-col h-screen relative overflow-hidden">
        <div class="absolute w-[600px] h-[600px] bg-emerald-600/10 rounded-full blur-[120px] top-[-200px] right-[-200px] pointer-events-none"></div>

        <header class="h-20 flex items-center justify-between px-8 border-b border-slate-800/50 bg-slate-900/50 backdrop-blur-md z-10 relative">
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-semibold text-white tracking-wide">Jadwal & Rute</h2>
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
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-white mb-1">Jadwal Penarikan</h1>
                    <p class="text-slate-400 text-sm">Atur jadwal dan rute penarikan jumpitan untuk masing-masing petugas.</p>
                </div>
                <button onclick="document.getElementById('modalTambah').classList.remove('hidden')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl font-medium transition-colors flex items-center gap-2 shadow-lg shadow-emerald-500/30">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    Buat Jadwal Baru
                </button>
            </div>

            <div class="glass-card rounded-2xl overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800 text-sm text-slate-400 bg-slate-800/20">
                            <th class="py-4 px-6 font-medium">Tanggal</th>
                            <th class="py-4 px-6 font-medium">Nama Petugas</th>
                            <th class="py-4 px-6 font-medium">Gang Tugas</th>
                            <th class="py-4 px-6 font-medium">Rute / Keterangan</th>
                            <th class="py-4 px-6 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-800/50">
                        <?php if (mysqli_num_rows($query_jadwal) > 0): ?>
                            <?php while ($jadwal = mysqli_fetch_assoc($query_jadwal)): ?>
                            <tr class="hover:bg-slate-800/20 transition-colors group">
                                <td class="py-4 px-6 text-white font-medium">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex flex-col items-center justify-center border border-emerald-500/20">
                                            <span class="text-xs text-emerald-400 font-medium"><?= date('M', strtotime($jadwal['tanggal'])) ?></span>
                                            <span class="text-sm font-bold text-white"><?= date('d', strtotime($jadwal['tanggal'])) ?></span>
                                        </div>
                                        <?= date('l, d F Y', strtotime($jadwal['tanggal'])) ?>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-slate-300"><?= htmlspecialchars($jadwal['nama_petugas']) ?></td>
                                <td class="py-4 px-6 text-emerald-400 font-medium"><?= htmlspecialchars($jadwal['gang'] ?? '-') ?></td>
                                <td class="py-4 px-6 text-slate-400"><?= htmlspecialchars($jadwal['keterangan']) ?></td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2 transition-opacity">
                                        <button onclick="editJadwal('<?= $jadwal['id'] ?>', '<?= $jadwal['tanggal'] ?>', '<?= $jadwal['petugas_id'] ?>', '<?= htmlspecialchars($jadwal['gang'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($jadwal['keterangan']) ?>')" class="p-1.5 text-slate-400 hover:text-emerald-400 bg-slate-800 rounded-lg hover:bg-slate-700 transition-colors" title="Edit">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                        </button>
                                        <button onclick="confirmHapus('jadwal.php?hapus=<?= $jadwal['id'] ?>')" class="p-1.5 text-slate-400 hover:text-red-400 bg-slate-800 rounded-lg hover:bg-slate-700 transition-colors inline-block" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="py-12 text-center text-slate-500">Belum ada jadwal penarikan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <footer class="mt-8 text-center text-sm text-slate-500 pb-4">
                &copy; <?= date('Y') ?> SiJumpa - Sistem Jumpitan Desa Nangungan.
            </footer>
        </div>
    </main>

    <!-- Modal Tambah Jadwal -->
    <div id="modalTambah" class="fixed inset-0 z-50 flex items-center justify-center hidden" onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-md modal-overlay"></div>
        <div class="modal-content bg-gradient-to-b from-slate-800 to-slate-900 border border-slate-700/50 rounded-2xl w-full max-w-lg relative z-10 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.5)] overflow-hidden">
            <!-- Header with gradient accent -->
            <div class="relative px-7 pt-7 pb-5">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-500"></div>
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/25 flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Buat Jadwal Penarikan</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Tentukan jadwal, petugas, dan rute penarikan</p>
                        </div>
                    </div>
                    <button onclick="document.getElementById('modalTambah').classList.add('hidden')" class="w-8 h-8 rounded-lg bg-slate-700/50 hover:bg-slate-600 flex items-center justify-center text-slate-400 hover:text-white transition-all duration-200 hover:rotate-90 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>
            
            <div class="h-px bg-gradient-to-r from-transparent via-slate-700 to-transparent"></div>

            <!-- Form Body -->
            <form method="POST" class="px-7 py-6 space-y-5">
                <!-- Row 1: Tanggal -->
                <div class="form-group">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        Tanggal Penarikan
                    </label>
                    <input type="date" name="tanggal" required class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all duration-200 hover:border-slate-500">
                </div>

                <!-- Row 2: Petugas & Gang side by side -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            Petugas
                        </label>
                        <select name="petugas_id" required class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all duration-200 hover:border-slate-500 appearance-none cursor-pointer">
                            <option value="">Pilih Petugas</option>
                            <?php 
                            mysqli_data_seek($query_petugas, 0);
                            while($p = mysqli_fetch_assoc($query_petugas)): 
                            ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            Gang / Rute
                        </label>
                        <select name="gang" required class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all duration-200 hover:border-slate-500 appearance-none cursor-pointer">
                            <option value="">Pilih Gang</option>
                            <?php 
                            mysqli_data_seek($query_gang, 0);
                            while($g = mysqli_fetch_assoc($query_gang)): 
                            ?>
                                <option value="<?= htmlspecialchars($g['nama']) ?>"><?= htmlspecialchars($g['nama']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- Row 3: Keterangan -->
                <div class="form-group">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Rute / Keterangan
                    </label>
                    <textarea name="keterangan" rows="3" required placeholder="Cth: Mulai dari Blok A lalu ke Blok C..." class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all duration-200 hover:border-slate-500 resize-none"></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 justify-end pt-2 border-t border-slate-700/50">
                    <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')" class="px-5 py-2.5 text-sm font-medium text-slate-300 hover:text-white bg-slate-700/50 hover:bg-slate-600 rounded-xl transition-all duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Batal
                    </button>
                    <button type="submit" name="tambah" class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:-translate-y-0.5 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Simpan Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Jadwal -->
    <div id="modalEdit" class="fixed inset-0 z-50 flex items-center justify-center hidden" onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-md modal-overlay"></div>
        <div class="modal-content bg-gradient-to-b from-slate-800 to-slate-900 border border-slate-700/50 rounded-2xl w-full max-w-lg relative z-10 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.5)] overflow-hidden">
            <!-- Header with gradient accent -->
            <div class="relative px-7 pt-7 pb-5">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-400 to-violet-500"></div>
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/25 flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Edit Jadwal Penarikan</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Perbarui informasi jadwal yang sudah ada</p>
                        </div>
                    </div>
                    <button onclick="document.getElementById('modalEdit').classList.add('hidden')" class="w-8 h-8 rounded-lg bg-slate-700/50 hover:bg-slate-600 flex items-center justify-center text-slate-400 hover:text-white transition-all duration-200 hover:rotate-90 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>
            
            <div class="h-px bg-gradient-to-r from-transparent via-slate-700 to-transparent"></div>

            <!-- Form Body -->
            <form method="POST" class="px-7 py-6 space-y-5">
                <input type="hidden" name="id_edit" id="edit_id">

                <!-- Row 1: Tanggal -->
                <div class="form-group">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                        <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        Tanggal Penarikan
                    </label>
                    <input type="date" name="tanggal" id="edit_tanggal" required class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 hover:border-slate-500">
                </div>

                <!-- Row 2: Petugas & Gang side by side -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                            <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            Petugas
                        </label>
                        <select name="petugas_id" id="edit_petugas_id" required class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 hover:border-slate-500 appearance-none cursor-pointer">
                            <option value="">Pilih Petugas</option>
                            <?php 
                            mysqli_data_seek($query_petugas, 0);
                            while($p = mysqli_fetch_assoc($query_petugas)): 
                            ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                            <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            Gang / Rute
                        </label>
                        <select name="gang" id="edit_gang" required class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 hover:border-slate-500 appearance-none cursor-pointer">
                            <option value="">Pilih Gang</option>
                            <?php 
                            mysqli_data_seek($query_gang, 0);
                            while($g = mysqli_fetch_assoc($query_gang)): 
                            ?>
                                <option value="<?= htmlspecialchars($g['nama']) ?>"><?= htmlspecialchars($g['nama']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- Row 3: Keterangan -->
                <div class="form-group">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                        <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Rute / Keterangan
                    </label>
                    <textarea name="keterangan" id="edit_keterangan" rows="3" required placeholder="Cth: Mulai dari Blok A lalu ke Blok C..." class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 hover:border-slate-500 resize-none"></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 justify-end pt-2 border-t border-slate-700/50">
                    <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')" class="px-5 py-2.5 text-sm font-medium text-slate-300 hover:text-white bg-slate-700/50 hover:bg-slate-600 rounded-xl transition-all duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Batal
                    </button>
                    <button type="submit" name="edit" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-0.5 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Update Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editJadwal(id, tanggal, petugas_id, gang, keterangan) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_tanggal').value = tanggal;
            document.getElementById('edit_petugas_id').value = petugas_id;
            document.getElementById('edit_gang').value = gang;
            document.getElementById('edit_keterangan').value = keterangan;
            document.getElementById('modalEdit').classList.remove('hidden');
        }

        function confirmHapus(url) {
            Swal.fire({
                title: 'Hapus Jadwal?',
                text: "Jadwal yang dihapus tidak dapat dikembalikan!",
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
            confirmButtonColor: '#10b981',
            background: '#1e293b',
            color: '#fff'
        });
    </script>
    <?php unset($_SESSION['swal']); endif; ?>
    
    <style>
        /* Modal Animations */
        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes modalSlideUp {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-overlay {
            animation: modalFadeIn 0.2s ease-out forwards;
        }
        .modal-content {
            animation: modalSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Form field focus glow */
        .form-input:focus {
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        #modalEdit .form-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Form group hover lift */
        .form-group {
            transition: transform 0.15s ease;
        }

        /* Select dropdown arrow */
        select.form-input {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.25em 1.25em;
            padding-right: 2.5rem;
        }

        /* Date input icon color fix */
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.7);
            cursor: pointer;
        }
    </style>
</body>
</html>

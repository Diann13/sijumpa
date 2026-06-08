<?php
session_start();
require_once 'config/koneksi.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$nama_admin = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Admin SiJumpa';
$role_admin = isset($_SESSION['role']) ? $_SESSION['role'] : 'admin';
$role_display = ($role_admin === 'admin') ? 'Super Admin' : 'Petugas';

// Cek apakah tabel pengaturan ada, jika tidak, buat tabel tersebut
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS pengaturan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kunci VARCHAR(100) UNIQUE NOT NULL,
    nilai VARCHAR(255) NOT NULL
)");

// Cek apakah default harga_jumpitan sudah diset
$cek_harga = mysqli_query($conn, "SELECT * FROM pengaturan WHERE kunci = 'harga_jumpitan'");
if (mysqli_num_rows($cek_harga) == 0) {
    mysqli_query($conn, "INSERT INTO pengaturan (kunci, nilai) VALUES ('harga_jumpitan', '2000')");
}

// Proses Update Pengaturan
if (isset($_POST['update_pengaturan'])) {
    $harga = (int)$_POST['harga_jumpitan'];
    mysqli_query($conn, "UPDATE pengaturan SET nilai = '$harga' WHERE kunci = 'harga_jumpitan'");
    $_SESSION['swal'] = ['title' => 'Berhasil!', 'text' => 'Harga nominal jumpitan berhasil diperbarui menjadi Rp ' . number_format($harga, 0, ',', '.') . '.', 'icon' => 'success'];
    header("Location: audit.php");
    exit;
}

// Ambil harga jumpitan saat ini
$q_harga = mysqli_query($conn, "SELECT nilai FROM pengaturan WHERE kunci = 'harga_jumpitan' LIMIT 1");
$r_harga = mysqli_fetch_assoc($q_harga);
$harga_sekarang = $r_harga ? $r_harga['nilai'] : 2000;

// Statistik Sistem
$q_total_warga = mysqli_query($conn, "SELECT COUNT(*) as total FROM warga");
$total_warga = mysqli_fetch_assoc($q_total_warga)['total'];

$q_total_trx = mysqli_query($conn, "SELECT COUNT(*) as total, SUM(nominal) as total_nominal FROM transaksi_jumpitan");
$row_trx = mysqli_fetch_assoc($q_total_trx);
$total_trx = $row_trx['total'];
$total_nominal = $row_trx['total_nominal'] ? $row_trx['total_nominal'] : 0;

$q_total_petugas = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'petugas'");
$total_petugas = mysqli_fetch_assoc($q_total_petugas)['total'];

// Info MySQL
$mysql_version = mysqli_get_server_info($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem & Audit | SiJumpa</title>
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
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                Dashboard
            </a>

            <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="warga.php" class="nav-item flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 text-slate-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Manajemen Warga
            </a>

            <a href="petugas.php" class="nav-item flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 text-slate-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Manajemen Petugas
            </a>

            <a href="gang.php" class="nav-item flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 text-slate-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
                Manajemen Gang
            </a>

            <a href="jadwal.php" class="nav-item flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 text-slate-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Jadwal & Rute
            </a>
            <?php endif; ?>

            <a href="laporan.php" class="nav-item flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 text-slate-400"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>Laporan Transaksi</a>
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-6 mb-2">Pengaturan</p>
            <a href="audit.php" class="nav-item active flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800"><svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>Sistem & Audit</a>
        </div>
        <div class="p-4 border-t border-slate-800">
            <a href="logout.php" onclick="event.preventDefault(); Swal.fire({title: 'Konfirmasi Keluar', text: 'Apakah Anda yakin ingin keluar dari sistem?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#334155', confirmButtonText: 'Ya, Keluar!', cancelButtonText: 'Batal', background: '#1e293b', color: '#fff'}).then((result) => { if(result.isConfirmed) { window.location.href = 'logout.php'; } })" class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>Keluar
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-screen relative overflow-hidden">
        
        <!-- Ornamen Background -->
        <div class="absolute w-[600px] h-[600px] bg-blue-600/10 rounded-full blur-[120px] top-[-200px] right-[-200px] pointer-events-none"></div>

        <!-- Top Header -->
        <header class="h-20 flex items-center justify-between px-8 border-b border-slate-800/50 bg-slate-900/50 backdrop-blur-md z-10 relative">
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-semibold text-white tracking-wide">Sistem & Audit</h2>
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

        <!-- Content Area -->
        <div class="flex-1 overflow-y-auto p-8 relative z-10">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-white mb-1">Pengaturan Sistem & Informasi Audit</h1>
                <p class="text-slate-400 text-sm">Kelola variabel pembayaran nominal jumpitan dan pantau spesifikasi infrastruktur server database.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left: Settings Card -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="glass-card rounded-2xl p-6 border border-slate-700/50 shadow-xl">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-blue-500/20 flex items-center justify-center text-blue-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-white">Variabel Pembayaran</h3>
                                <p class="text-xs text-slate-400">Atur nominal yang akan ditarik dari warga.</p>
                            </div>
                        </div>

                        <form method="POST" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-200 mb-2">Harga Nominal Jumpitan (Rupiah)</label>
                                <div class="relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <span class="text-slate-400 sm:text-sm font-semibold">Rp</span>
                                    </div>
                                    <input type="text" id="harga_input" value="<?= number_format($harga_sekarang, 0, ',', '.') ?>" required class="w-full bg-slate-800/80 border border-slate-700 rounded-xl pl-10 pr-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 text-lg font-bold tracking-wide">
                                    <input type="hidden" name="harga_jumpitan" id="harga_hidden" value="<?= htmlspecialchars($harga_sekarang) ?>">
                                </div>
                                <p class="mt-2 text-xs text-slate-500 leading-relaxed">
                                    Nominal default saat petugas melakukan scan dan penarikan jumpitan warga. Harga ini akan terintegrasi langsung ke aplikasi Android petugas dan pencatatan transaksi secara real-time.
                                </p>
                            </div>

                            <div class="pt-2">
                                <button type="submit" name="update_pengaturan" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-xl shadow-lg shadow-blue-500/20 transition-all flex items-center justify-center gap-2 hover:-translate-y-0.5">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                    </svg>
                                    Simpan Pengaturan
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Audit Log Card -->
                    <div class="glass-card rounded-2xl p-6 border border-slate-700/50 shadow-xl">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center text-amber-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-white">Log Keamanan & Audit</h3>
                                <p class="text-xs text-slate-400">Aktivitas penting sistem tercatat di bawah ini.</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex gap-4 p-3 rounded-lg bg-slate-800/40 border border-slate-700/30 text-xs">
                                <span class="px-2 py-1 h-fit bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded font-bold uppercase">INFO</span>
                                <div>
                                    <p class="text-slate-200 font-medium">Sistem Pengaturan Nominal Aktif</p>
                                    <p class="text-slate-400 mt-0.5">Nilai penarikan jumpitan diset menjadi Rp <?= number_format($harga_sekarang, 0, ',', '.') ?>.</p>
                                    <p class="text-slate-500 mt-1"><?= date('d M Y, H:i') ?></p>
                                </div>
                            </div>
                            <div class="flex gap-4 p-3 rounded-lg bg-slate-800/40 border border-slate-700/30 text-xs">
                                <span class="px-2 py-1 h-fit bg-blue-500/10 text-blue-400 border border-blue-500/20 rounded font-bold uppercase">AUDIT</span>
                                <div>
                                    <p class="text-slate-200 font-medium">Inisialisasi Tabel Pengaturan Berhasil</p>
                                    <p class="text-slate-400 mt-0.5">Skema tabel `pengaturan` telah dibuat dan disinkronisasi.</p>
                                    <p class="text-slate-500 mt-1"><?= date('d M Y, H:i') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Server Info Card -->
                <div class="space-y-6">
                    <div class="glass-card rounded-2xl p-6 border border-slate-700/50 shadow-xl">
                        <h3 class="text-lg font-bold text-white mb-6">Status Server & Database</h3>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center py-2.5 border-b border-slate-800">
                                <span class="text-sm text-slate-400">Koneksi Database</span>
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-400 px-2 py-1 bg-emerald-500/10 border border-emerald-500/20 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Terhubung
                                </span>
                            </div>
                            
                            <div class="flex justify-between items-center py-2.5 border-b border-slate-800">
                                <span class="text-sm text-slate-400">Versi Server MySQL</span>
                                <span class="text-sm text-slate-200 font-mono"><?= htmlspecialchars($mysql_version) ?></span>
                            </div>

                            <div class="flex justify-between items-center py-2.5 border-b border-slate-800">
                                <span class="text-sm text-slate-400">Total Warga Terdaftar</span>
                                <span class="text-sm text-white font-bold"><?= number_format($total_warga) ?> KK</span>
                            </div>

                            <div class="flex justify-between items-center py-2.5 border-b border-slate-800">
                                <span class="text-sm text-slate-400">Total Transaksi Masuk</span>
                                <span class="text-sm text-white font-bold"><?= number_format($total_trx) ?> TRX</span>
                            </div>

                            <div class="flex justify-between items-center py-2.5 border-b border-slate-800">
                                <span class="text-sm text-slate-400">Total Kas Terkumpul</span>
                                <span class="text-sm text-emerald-400 font-bold">Rp <?= number_format($total_nominal, 0, ',', '.') ?></span>
                            </div>

                            <div class="flex justify-between items-center py-2.5 border-b border-slate-800">
                                <span class="text-sm text-slate-400">Total Akun Petugas</span>
                                <span class="text-sm text-white font-bold"><?= number_format($total_petugas) ?> Orang</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            
            <footer class="mt-8 text-center text-sm text-slate-500 pb-4 border-t border-slate-800 pt-6">
                &copy; <?= date('Y') ?> SiJumpa - Sistem Jumpitan Desa Nangungan.
            </footer>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('harga_input');
            const hidden = document.getElementById('harga_hidden');

            input.addEventListener('input', function(e) {
                let value = this.value.replace(/[^0-9]/g, '');
                hidden.value = value;
                if (value) {
                    this.value = formatRupiah(value);
                } else {
                    this.value = '';
                }
            });

            function formatRupiah(angka) {
                let number_string = angka.toString(),
                    sisa = number_string.length % 3,
                    rupiah = number_string.substr(0, sisa),
                    ribuan = number_string.substr(sisa).match(/\d{3}/g);

                if (ribuan) {
                    let separator = sisa ? '.' : '';
                    rupiah += separator + ribuan.join('.');
                }
                return rupiah;
            }
        });
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
</body>
</html>

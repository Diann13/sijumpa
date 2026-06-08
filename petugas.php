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

// Proses Hapus Petugas
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM users WHERE id = $id_hapus AND role IN ('petugas', 'bendahara')");
    $_SESSION['swal'] = ['title' => 'Berhasil!', 'text' => 'Akun petugas/bendahara berhasil dihapus.', 'icon' => 'success'];
    header("Location: petugas.php");
    exit;
}

// Proses Tambah Petugas
if (isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    mysqli_query($conn, "INSERT INTO users (nama, username, password, role) VALUES ('$nama', '$username', '$password', '$role')");
    $_SESSION['swal'] = ['title' => 'Berhasil!', 'text' => 'Akun baru berhasil dibuat.', 'icon' => 'success'];
    header("Location: petugas.php");
    exit;
}

// Proses Edit Petugas
if (isset($_POST['edit'])) {
    $id_edit = (int)$_POST['id_edit'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET nama='$nama', username='$username', password='$password', role='$role' WHERE id=$id_edit AND role IN ('petugas', 'bendahara')");
    } else {
        mysqli_query($conn, "UPDATE users SET nama='$nama', username='$username', role='$role' WHERE id=$id_edit AND role IN ('petugas', 'bendahara')");
    }
    
    $_SESSION['swal'] = ['title' => 'Berhasil!', 'text' => 'Data akun berhasil diperbarui.', 'icon' => 'success'];
    header("Location: petugas.php");
    exit;
}

// Ambil Data Petugas
$query_petugas = mysqli_query($conn, "SELECT * FROM users WHERE role IN ('petugas', 'bendahara') ORDER BY role, created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Petugas | SiJumpa</title>
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
            box-shadow: 0 0 0 3px rgba(147, 51, 234, 0.15);
        }
        #modalEdit .form-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
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
        <div class="absolute w-[600px] h-[600px] bg-purple-600/10 rounded-full blur-[120px] top-[-200px] right-[-200px] pointer-events-none"></div>

        <!-- Top Header -->
        <header class="h-20 flex items-center justify-between px-8 border-b border-slate-800/50 bg-slate-900/50 backdrop-blur-md z-10 relative">
            <div class="flex items-center gap-4">
                <button class="md:hidden text-slate-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <h2 class="text-xl font-semibold text-white tracking-wide">Manajemen Petugas</h2>
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

        <!-- Main Content Area -->
        <div class="flex-1 overflow-y-auto p-8 relative z-10">

            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-white mb-1">Daftar Pengguna Sistem</h1>
                    <p class="text-slate-400 text-sm">Kelola akun dan akses petugas penarik jumpitan serta bendahara desa.</p>
                </div>
                <button onclick="document.getElementById('modalTambah').classList.remove('hidden')" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2.5 rounded-xl font-medium transition-colors flex items-center gap-2 shadow-lg shadow-purple-500/30">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                    Tambah Pengguna
                </button>
            </div>

            <!-- Table Card -->
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 text-sm text-slate-400 bg-slate-800/20">
                                <th class="py-4 px-6 font-medium">Nama Petugas</th>
                                <th class="py-4 px-6 font-medium">Username</th>
                                <th class="py-4 px-6 font-medium">Role</th>
                                <th class="py-4 px-6 font-medium">Tanggal Bergabung</th>
                                <th class="py-4 px-6 font-medium text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-800/50">
                            <?php if (mysqli_num_rows($query_petugas) > 0): ?>
                                <?php while ($petugas = mysqli_fetch_assoc($query_petugas)): ?>
                                <tr class="hover:bg-slate-800/20 transition-colors group">
                                    <td class="py-4 px-6 text-white font-medium">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold text-purple-400"><?= substr($petugas['nama'], 0, 1) ?></div>
                                            <?= htmlspecialchars($petugas['nama']) ?>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-slate-400">@<?= htmlspecialchars($petugas['username']) ?></td>
                                    <td class="py-4 px-6">
                                        <?php if ($petugas['role'] === 'bendahara'): ?>
                                            <span class="px-2.5 py-1 rounded-md bg-amber-500/10 text-amber-400 text-xs font-semibold border border-amber-500/20 capitalize">
                                                Bendahara
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 rounded-md bg-purple-500/10 text-purple-400 text-xs font-semibold border border-purple-500/20 capitalize">
                                                Petugas
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-slate-400"><?= date('d M Y', strtotime($petugas['created_at'])) ?></td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="flex items-center justify-end gap-2 transition-opacity">
                                            <button onclick="editPetugas('<?= $petugas['id'] ?>', '<?= htmlspecialchars($petugas['nama'], ENT_QUOTES) ?>', '<?= htmlspecialchars($petugas['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($petugas['role'], ENT_QUOTES) ?>')" class="p-1.5 text-slate-400 hover:text-amber-400 bg-slate-800 rounded-lg hover:bg-slate-700 transition-colors" title="Edit Akun">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                            </button>
                                            <button onclick="confirmHapus('petugas.php?hapus=<?= $petugas['id'] ?>')" class="p-1.5 text-slate-400 hover:text-red-400 bg-slate-800 rounded-lg hover:bg-slate-700 transition-colors" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-500">Belum ada data petugas.</td>
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

    <!-- Modal Tambah Petugas -->
    <div id="modalTambah" class="fixed inset-0 z-50 flex items-center justify-center hidden" onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-md modal-overlay"></div>
        <div class="modal-content bg-gradient-to-b from-slate-800 to-slate-900 border border-slate-700/50 rounded-2xl w-full max-w-lg relative z-10 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.5)] overflow-hidden">
            <!-- Header with gradient accent -->
            <div class="relative px-7 pt-7 pb-5">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-500 via-indigo-400 to-violet-500"></div>
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-purple-500/25 flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Buat Akun Petugas</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Daftarkan petugas penarik jumpitan atau bendahara baru</p>
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
                <div class="form-group">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                        <svg class="w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        Nama Lengkap
                    </label>
                    <input type="text" name="nama" required placeholder="Masukkan nama lengkap petugas..." class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 hover:border-slate-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                            <svg class="w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 100-8 4 4 0 000 8zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" /></svg>
                            Username
                        </label>
                        <input type="text" name="username" required placeholder="Cth: petugas01" class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 hover:border-slate-500">
                    </div>
                    <div class="form-group">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                            <svg class="w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            Role Akun
                        </label>
                        <select name="role" required class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 hover:border-slate-500 appearance-none cursor-pointer">
                            <option value="petugas">Petugas Lapangan</option>
                            <option value="bendahara">Bendahara (Keuangan)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                        <svg class="w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        Password Sementara
                    </label>
                    <input type="text" name="password" required value="Petugas123" class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 hover:border-slate-500">
                    <p class="text-xs text-slate-500 mt-1.5">Pengguna bisa mengubah password ini nanti melalui dashboard.</p>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 justify-end pt-2 border-t border-slate-700/50">
                    <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')" class="px-5 py-2.5 text-sm font-medium text-slate-300 hover:text-white bg-slate-700/50 hover:bg-slate-600 rounded-xl transition-all duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Batal
                    </button>
                    <button type="submit" name="tambah" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg shadow-purple-500/25 hover:shadow-purple-500/40 hover:-translate-y-0.5 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Simpan Akun
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Petugas -->
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
                            <h3 class="text-lg font-bold text-white">Edit Akun Petugas</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Perbarui profil petugas atau ubah password mereka</p>
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

                <div class="form-group">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                        <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        Nama Lengkap
                    </label>
                    <input type="text" name="nama" id="edit_nama" required placeholder="Masukkan nama lengkap petugas..." class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 hover:border-slate-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                            <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 100-8 4 4 0 000 8zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" /></svg>
                            Username
                        </label>
                        <input type="text" name="username" id="edit_username" required placeholder="Cth: petugas01" class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 hover:border-slate-500">
                    </div>
                    <div class="form-group">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                            <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            Role Akun
                        </label>
                        <select name="role" id="edit_role" required class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 hover:border-slate-500 appearance-none cursor-pointer">
                            <option value="petugas">Petugas Lapangan</option>
                            <option value="bendahara">Bendahara (Keuangan)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                        <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        Password Baru <span class="text-slate-500 font-normal text-xs">(Kosongkan jika tidak diubah)</span>
                    </label>
                    <input type="text" name="password" placeholder="Ketik password baru jika ingin mengubah..." class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 hover:border-slate-500">
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 justify-end pt-2 border-t border-slate-700/50">
                    <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')" class="px-5 py-2.5 text-sm font-medium text-slate-300 hover:text-white bg-slate-700/50 hover:bg-slate-600 rounded-xl transition-all duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Batal
                    </button>
                    <button type="submit" name="edit" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-0.5 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Update Akun
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editPetugas(id, nama, username, role) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_role').value = role;
            document.getElementById('modalEdit').classList.remove('hidden');
        }

        function confirmHapus(url) {
            Swal.fire({
                title: 'Hapus Petugas?',
                text: "Petugas ini tidak akan bisa login lagi!",
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
            confirmButtonColor: '#9333ea',
            background: '#1e293b',
            color: '#fff'
        });
    </script>
    <?php unset($_SESSION['swal']); endif; ?>

</body>
</html>

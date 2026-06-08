<?php
session_start();
require_once 'config/koneksi.php';

// Cek apakah user sudah login dan role-nya admin atau bendahara
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'bendahara') {
    header("Location: dashboard.php");
    exit;
}

$nama_admin = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Admin Keuangan';
$role_admin = isset($_SESSION['role']) ? $_SESSION['role'] : 'bendahara';
$role_display = ($role_admin === 'admin') ? 'Super Admin' : (($role_admin === 'bendahara') ? 'Bendahara' : 'Petugas');

// Proses Hapus Pengeluaran
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM pengeluaran WHERE id = $id_hapus");
    $_SESSION['swal'] = ['title' => 'Berhasil!', 'text' => 'Data pengeluaran berhasil dihapus.', 'icon' => 'success'];
    header("Location: pengeluaran.php");
    exit;
}

// Helper: upload nota
function uploadNota($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['image/jpeg','image/png','image/gif','application/pdf'];
    if (!in_array($file['type'], $allowed)) return null;
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $dir = 'uploads/nota/';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $filename = 'nota_' . time() . '_' . rand(1000,9999) . '.' . $ext;
    move_uploaded_file($file['tmp_name'], $dir . $filename);
    return $dir . $filename;
}

// Proses Tambah Pengeluaran
if (isset($_POST['tambah'])) {
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $nominal = (int)str_replace(['.','Rp ',' '], '', $_POST['nominal']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $nota_path = uploadNota($_FILES['nota'] ?? null);
    $nota_sql = $nota_path ? "'" . mysqli_real_escape_string($conn, $nota_path) . "'" : 'NULL';

    mysqli_query($conn, "INSERT INTO pengeluaran (tanggal, nominal, keterangan, nota_path) VALUES ('$tanggal', $nominal, '$keterangan', $nota_sql)");
    $_SESSION['swal'] = ['title' => 'Berhasil!', 'text' => 'Data pengeluaran baru berhasil ditambahkan.', 'icon' => 'success'];
    header("Location: pengeluaran.php");
    exit;
}

// Proses Edit Pengeluaran
if (isset($_POST['edit'])) {
    $id_edit = (int)$_POST['id_edit'];
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $nominal = (int)str_replace(['.','Rp ',' '], '', $_POST['nominal']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $nota_path_new = uploadNota($_FILES['nota'] ?? null);

    if ($nota_path_new) {
        $nota_sql = "nota_path='" . mysqli_real_escape_string($conn, $nota_path_new) . "', ";
    } else {
        $nota_sql = '';
    }
    mysqli_query($conn, "UPDATE pengeluaran SET tanggal='$tanggal', nominal=$nominal, keterangan='$keterangan', {$nota_sql}id=$id_edit WHERE id=$id_edit");
    $_SESSION['swal'] = ['title' => 'Berhasil!', 'text' => 'Data pengeluaran berhasil diperbarui.', 'icon' => 'success'];
    header("Location: pengeluaran.php");
    exit;
}

// Ambil Data Pengeluaran
$query_pengeluaran = mysqli_query($conn, "SELECT * FROM pengeluaran ORDER BY tanggal DESC, id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengeluaran | SiJumpa</title>
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
        .nav-item.active { background: linear-gradient(to right, rgba(239, 68, 68, 0.2), transparent); border-left: 4px solid #ef4444; color: white; }
        
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
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
        }
        #modalEdit .form-input:focus {
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
        }

        /* Form group hover lift */
        .form-group {
            transition: transform 0.15s ease;
        }

        /* Date input icon color fix */
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.7);
            cursor: pointer;
        }
    </style>
</head>
<body class="text-slate-300 flex h-screen overflow-hidden selection:bg-red-500/30">

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col hidden md:flex transition-all duration-300">
        <div class="h-20 flex items-center px-6 border-b border-slate-800">
            <div class="flex items-center gap-3">
                <img src="assets/logo.png" alt="Logo SiJumpa" class="w-10 h-10 object-contain drop-shadow-[0_0_10px_rgba(239,68,68,0.3)] hover:scale-105 transition-transform">
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

            <a href="laporan.php" class="nav-item flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 text-slate-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Laporan Transaksi
            </a>

            <a href="pengeluaran.php" class="nav-item active flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800">
                <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Pengeluaran Kas
            </a>

            <?php if ($_SESSION['role'] === 'admin'): ?>
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-6 mb-2">Pengaturan</p>
            <a href="audit.php" class="nav-item flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800 text-slate-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Sistem & Audit
            </a>
            <?php endif; ?>
        </div>
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
        <div class="absolute w-[600px] h-[600px] bg-red-600/10 rounded-full blur-[120px] top-[-200px] right-[-200px] pointer-events-none"></div>

        <!-- Top Header -->
        <header class="h-20 flex items-center justify-between px-8 border-b border-slate-800/50 bg-slate-900/50 backdrop-blur-md z-10 relative">
            <div class="flex items-center gap-4">
                <button class="md:hidden text-slate-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <h2 class="text-xl font-semibold text-white tracking-wide">Pengeluaran Kas</h2>
            </div>
            
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3 border-l border-slate-700 pl-6">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-medium text-white"><?= htmlspecialchars($nama_admin) ?></p>
                        <p class="text-xs text-slate-400"><?= htmlspecialchars($role_display) ?></p>
                    </div>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_admin) ?>&background=ef4444&color=fff&rounded=true" alt="Profile" class="w-10 h-10 rounded-full border-2 border-slate-700">
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <div class="flex-1 overflow-y-auto p-8 relative z-10">

            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-white mb-1">Manajemen Pengeluaran Kas</h1>
                    <p class="text-slate-400 text-sm">Catat dan kelola pengeluaran dana yang bersumber dari hasil jumpitan desa.</p>
                </div>
                <button onclick="document.getElementById('modalTambah').classList.remove('hidden')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-xl font-medium transition-colors flex items-center gap-2 shadow-lg shadow-red-500/30">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Tambah Pengeluaran
                </button>
            </div>

            <!-- Table Card -->
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 text-sm text-slate-400 bg-slate-800/20">
                                <th class="py-4 px-6 font-medium">Tanggal</th>
                                <th class="py-4 px-6 font-medium">Keterangan</th>
                                <th class="py-4 px-6 font-medium text-right">Nominal</th>
                                <th class="py-4 px-6 font-medium text-center">Nota</th>
                                <th class="py-4 px-6 font-medium text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-800/50">
                            <?php if (mysqli_num_rows($query_pengeluaran) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($query_pengeluaran)): ?>
                                <tr class="hover:bg-slate-800/20 transition-colors group">
                                    <td class="py-4 px-6 text-white font-medium">
                                        <?= date('d M Y', strtotime($row['tanggal'])) ?>
                                    </td>
                                    <td class="py-4 px-6 text-slate-300 font-medium max-w-xs truncate" title="<?= htmlspecialchars($row['keterangan']) ?>">
                                        <?= htmlspecialchars($row['keterangan']) ?>
                                    </td>
                                    <td class="py-4 px-6 text-right text-red-400 font-semibold">
                                        Rp <?= number_format($row['nominal'], 0, ',', '.') ?>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <?php if (!empty($row['nota_path'])): ?>
                                            <a href="<?= htmlspecialchars($row['nota_path']) ?>" target="_blank" class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 hover:bg-amber-500/20 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                Lihat Nota
                                            </a>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-600">–</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button onclick="editPengeluaran('<?= $row['id'] ?>', '<?= $row['tanggal'] ?>', '<?= $row['nominal'] ?>', '<?= htmlspecialchars($row['keterangan'], ENT_QUOTES) ?>')" class="p-1.5 text-slate-400 hover:text-amber-400 bg-slate-800 rounded-lg hover:bg-slate-700 transition-colors" title="Edit">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                            </button>
                                            <button onclick="confirmHapus('pengeluaran.php?hapus=<?= $row['id'] ?>')" class="p-1.5 text-slate-400 hover:text-red-400 bg-slate-800 rounded-lg hover:bg-slate-700 transition-colors" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-500">Belum ada data pengeluaran kas.</td>
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

    <!-- Modal Tambah Pengeluaran -->
    <div id="modalTambah" class="fixed inset-0 z-50 flex items-center justify-center hidden" onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-md modal-overlay"></div>
        <div class="modal-content bg-gradient-to-b from-slate-800 to-slate-900 border border-slate-700/50 rounded-2xl w-full max-w-lg relative z-10 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.5)] overflow-hidden">
            <!-- Header with gradient accent -->
            <div class="relative px-7 pt-7 pb-5">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-red-500 via-rose-400 to-pink-500"></div>
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center shadow-lg shadow-red-500/25 flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Catat Pengeluaran Baru</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Catat nominal dan upload bukti pengeluaran kas</p>
                        </div>
                    </div>
                    <button onclick="document.getElementById('modalTambah').classList.add('hidden')" class="w-8 h-8 rounded-lg bg-slate-700/50 hover:bg-slate-600 flex items-center justify-center text-slate-400 hover:text-white transition-all duration-200 hover:rotate-90 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>
            
            <div class="h-px bg-gradient-to-r from-transparent via-slate-700 to-transparent"></div>

            <!-- Form Body -->
            <form method="POST" enctype="multipart/form-data" class="px-7 py-6 space-y-5">
                <!-- Row 1: Tanggal & Nominal side by side -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                            <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            Tanggal
                        </label>
                        <input type="date" name="tanggal" required value="<?= date('Y-m-d') ?>" class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-all duration-200 hover:border-slate-500">
                    </div>
                    <div class="form-group">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                            <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Nominal (Rp)
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-semibold">Rp</span>
                            <input type="text" name="nominal" id="tambah_nominal" required placeholder="0" inputmode="numeric" class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-500 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-all duration-200 hover:border-slate-500" oninput="formatRupiah(this)">
                        </div>
                    </div>
                </div>

                <!-- Row 2: Keterangan -->
                <div class="form-group">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                        <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Keterangan / Keperluan
                    </label>
                    <textarea name="keterangan" rows="3" required placeholder="Cth: Pembelian lampu jalan Gang 1..." class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-all duration-200 hover:border-slate-500 resize-none"></textarea>
                </div>

                <!-- Row 3: Upload Nota -->
                <div class="form-group">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                        <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                        Upload Nota <span class="text-slate-500 font-normal text-xs">(Opsional, JPG/PNG/PDF)</span>
                    </label>
                    <div class="relative">
                        <input type="file" name="nota" id="tambah_nota" accept="image/*,.pdf" class="hidden" onchange="showFileName(this, 'tambah_nota_label')">
                        <label for="tambah_nota" id="tambah_nota_label" class="cursor-pointer flex items-center gap-3 w-full bg-slate-800/80 border border-dashed border-slate-600/50 rounded-xl px-4 py-3.5 text-sm text-slate-400 hover:border-red-500/50 hover:text-slate-300 transition-all duration-200">
                            <svg class="w-5 h-5 flex-shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                            <span>Klik untuk upload nota / bukti pengeluaran</span>
                        </label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 justify-end pt-2 border-t border-slate-700/50">
                    <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')" class="px-5 py-2.5 text-sm font-medium text-slate-300 hover:text-white bg-slate-700/50 hover:bg-slate-600 rounded-xl transition-all duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Batal
                    </button>
                    <button type="submit" name="tambah" class="bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg shadow-red-500/25 hover:shadow-red-500/40 hover:-translate-y-0.5 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Simpan Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Pengeluaran -->
    <div id="modalEdit" class="fixed inset-0 z-50 flex items-center justify-center hidden" onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-md modal-overlay"></div>
        <div class="modal-content bg-gradient-to-b from-slate-800 to-slate-900 border border-slate-700/50 rounded-2xl w-full max-w-lg relative z-10 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.5)] overflow-hidden">
            <!-- Header with gradient accent -->
            <div class="relative px-7 pt-7 pb-5">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 via-orange-400 to-yellow-500"></div>
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/25 flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Edit Catatan Pengeluaran</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Perbarui informasi nominal atau bukti pengeluaran</p>
                        </div>
                    </div>
                    <button onclick="document.getElementById('modalEdit').classList.add('hidden')" class="w-8 h-8 rounded-lg bg-slate-700/50 hover:bg-slate-600 flex items-center justify-center text-slate-400 hover:text-white transition-all duration-200 hover:rotate-90 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>
            
            <div class="h-px bg-gradient-to-r from-transparent via-slate-700 to-transparent"></div>

            <!-- Form Body -->
            <form method="POST" enctype="multipart/form-data" class="px-7 py-6 space-y-5">
                <input type="hidden" name="id_edit" id="edit_id">

                <!-- Row 1: Tanggal & Nominal side by side -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                            <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            Tanggal
                        </label>
                        <input type="date" name="tanggal" id="edit_tanggal" required class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 transition-all duration-200 hover:border-slate-500">
                    </div>
                    <div class="form-group">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                            <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Nominal (Rp)
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-semibold">Rp</span>
                            <input type="text" name="nominal" id="edit_nominal" required inputmode="numeric" class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 transition-all duration-200 hover:border-slate-500" oninput="formatRupiah(this)">
                        </div>
                    </div>
                </div>

                <!-- Row 2: Keterangan -->
                <div class="form-group">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                        <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Keterangan / Keperluan
                    </label>
                    <textarea name="keterangan" id="edit_keterangan" rows="3" required class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 transition-all duration-200 hover:border-slate-500 resize-none"></textarea>
                </div>

                <!-- Row 3: Upload Nota -->
                <div class="form-group">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-200 mb-2">
                        <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                        Ganti Nota <span class="text-slate-500 font-normal text-xs">(Opsional — kosongkan jika tidak diganti)</span>
                    </label>
                    <div class="relative">
                        <input type="file" name="nota" id="edit_nota" accept="image/*,.pdf" class="hidden" onchange="showFileName(this, 'edit_nota_label')">
                        <label for="edit_nota" id="edit_nota_label" class="cursor-pointer flex items-center gap-3 w-full bg-slate-800/80 border border-dashed border-slate-600/50 rounded-xl px-4 py-3.5 text-sm text-slate-400 hover:border-amber-500/50 hover:text-slate-300 transition-all duration-200">
                            <svg class="w-5 h-5 flex-shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                            <span>Klik untuk upload nota baru</span>
                        </label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 justify-end pt-2 border-t border-slate-700/50">
                    <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')" class="px-5 py-2.5 text-sm font-medium text-slate-300 hover:text-white bg-slate-700/50 hover:bg-slate-600 rounded-xl transition-all duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Batal
                    </button>
                    <button type="submit" name="edit" class="bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 hover:-translate-y-0.5 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Update Catatan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function formatRupiah(input) {
            let raw = input.value.replace(/[^\d]/g, '');
            if (raw === '') { input.value = ''; return; }
            input.value = parseInt(raw, 10).toLocaleString('id-ID');
        }

        function getRawNominal(id) {
            const val = document.getElementById(id).value.replace(/[^\d]/g, '');
            return val || '0';
        }

        function showFileName(input, labelId) {
            const label = document.getElementById(labelId);
            if (input.files && input.files[0]) {
                label.querySelector('span').textContent = input.files[0].name;
            }
        }

        function editPengeluaran(id, tanggal, nominal, keterangan) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_tanggal').value = tanggal;
            // Format nominal as Rupiah
            const editNominalEl = document.getElementById('edit_nominal');
            editNominalEl.value = parseInt(nominal, 10).toLocaleString('id-ID');
            document.getElementById('edit_keterangan').value = keterangan;
            document.getElementById('edit_nota_label').querySelector('span').textContent = 'Klik untuk upload nota baru';
            document.getElementById('modalEdit').classList.remove('hidden');
        }

        // Before submit: strip formatting so PHP gets raw int
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                this.querySelectorAll('input[inputmode="numeric"]').forEach(function(el) {
                    el.value = el.value.replace(/[^\d]/g, '');
                });
            });
        });

        function confirmHapus(url) {
            Swal.fire({
                title: 'Hapus Data Pengeluaran?',
                text: "Data pengeluaran yang dihapus tidak dapat dikembalikan!",
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
            confirmButtonColor: '#ef4444',
            background: '#1e293b',
            color: '#fff'
        });
    </script>
    <?php unset($_SESSION['swal']); endif; ?>

</body>
</html>

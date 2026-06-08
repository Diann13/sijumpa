<?php
session_start();
require_once 'config/koneksi.php';

// Access control - admin only
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$nama_admin = $_SESSION['nama'] ?? 'Admin SiJumpa';
$role_admin = $_SESSION['role'];
$role_display = ($role_admin === 'admin') ? 'Super Admin' : 'Petugas';

// Handle delete
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM gang WHERE id = $id_hapus");
    $_SESSION['swal'] = ['title' => 'Berhasil!', 'text' => 'Gang berhasil dihapus.', 'icon' => 'success'];
    header('Location: gang.php');
    exit;
}

// Handle add
if (isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    mysqli_query($conn, "INSERT INTO gang (nama) VALUES ('$nama')");
    $_SESSION['swal'] = ['title' => 'Berhasil!', 'text' => 'Gang baru ditambahkan.', 'icon' => 'success'];
    header('Location: gang.php');
    exit;
}

// Handle edit
if (isset($_POST['edit'])) {
    $id = (int)$_POST['id_edit'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    mysqli_query($conn, "UPDATE gang SET nama='$nama' WHERE id=$id");
    $_SESSION['swal'] = ['title' => 'Berhasil!', 'text' => 'Gang diperbarui.', 'icon' => 'success'];
    header('Location: gang.php');
    exit;
}

// Fetch all gangs
$gangs = mysqli_query($conn, "SELECT * FROM gang ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Gang | SiJumpa</title>
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
        .nav-item.active { background: linear-gradient(to right, rgba(59,130,246,0.2), transparent); border-left: 4px solid #3b82f6; color: white; }
        
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
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        #modalEdit .form-input:focus {
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
        }

        /* Form group hover lift */
        .form-group {
            transition: transform 0.15s ease;
        }
    </style>
</head>
<body class="text-slate-300 flex h-screen overflow-hidden selection:bg-blue-500/30">
    <!-- Sidebar -->
    <?php include 'config/sidebar.php'; ?>
    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-screen relative overflow-hidden">
        <header class="h-20 flex items-center justify-between px-8 border-b border-slate-800/50 bg-slate-900/50 backdrop-blur-md">
            <h2 class="text-xl font-semibold text-white">Manajemen Gang</h2>
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
                    <h1 class="text-2xl font-bold text-white mb-1">Daftar Gang / Rute</h1>
                    <p class="text-slate-400 text-sm">Kelola daftar gang yang digunakan untuk penugasan petugas dan pengelompokan warga.</p>
                </div>
                <button onclick="document.getElementById('modalTambah').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-medium transition-colors flex items-center gap-2 shadow-lg shadow-blue-500/30">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Tambah Gang
                </button>
            </div>
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 text-sm text-slate-400 bg-slate-800/20">
                                <th class="py-4 px-6 font-medium">Nama Gang</th>
                                <th class="py-4 px-6 font-medium text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-800/50">
                            <?php if(mysqli_num_rows($gangs) > 0): ?>
                                <?php while($g = mysqli_fetch_assoc($gangs)): ?>
                                    <tr class="hover:bg-slate-800/20 transition-colors">
                                        <td class="py-4 px-6 text-white font-medium"><?= htmlspecialchars($g['nama']) ?></td>
                                        <td class="py-4 px-6 text-center">
                                            <button onclick="editGang('<?= $g['id'] ?>', '<?= htmlspecialchars($g['nama'], ENT_QUOTES) ?>')" class="p-1.5 text-slate-400 hover:text-blue-400 bg-slate-800 rounded-lg hover:bg-slate-700" title="Edit"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button>
                                            <button onclick="confirmHapus('gang.php?hapus=<?= $g['id'] ?>')" class="p-1.5 text-slate-400 hover:text-red-400 bg-slate-800 rounded-lg hover:bg-slate-700" title="Hapus"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3"/></svg></button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="2" class="py-12 text-center text-slate-500">Belum ada gang.</td></tr>
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
    <!-- Modals -->
    <!-- Modal Tambah Gang -->
    <div id="modalTambah" class="fixed inset-0 z-50 flex items-center justify-center hidden" onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-md modal-overlay"></div>
        <div class="modal-content bg-gradient-to-b from-slate-800 to-slate-900 border border-slate-700/50 rounded-2xl w-full max-w-lg relative z-10 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.5)] overflow-hidden">
            <!-- Header with gradient accent -->
            <div class="relative px-7 pt-7 pb-5">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-cyan-400 to-sky-500"></div>
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center shadow-lg shadow-blue-500/25 flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Tambah Gang Baru</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Daftarkan rute gang baru untuk jumpitan desa</p>
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
                        <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        Nama Gang
                    </label>
                    <input type="text" name="nama" required placeholder="Masukkan nama gang (cth: Gang Merpati)..." class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 hover:border-slate-500">
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 justify-end pt-2 border-t border-slate-700/50">
                    <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')" class="px-5 py-2.5 text-sm font-medium text-slate-300 hover:text-white bg-slate-700/50 hover:bg-slate-600 rounded-xl transition-all duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Batal
                    </button>
                    <button type="submit" name="tambah" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-0.5 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Simpan Gang
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal Edit Gang -->
    <div id="modalEdit" class="fixed inset-0 z-50 flex items-center justify-center hidden" onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-md modal-overlay"></div>
        <div class="modal-content bg-gradient-to-b from-slate-800 to-slate-900 border border-slate-700/50 rounded-2xl w-full max-w-lg relative z-10 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.5)] overflow-hidden">
            <!-- Header with gradient accent -->
            <div class="relative px-7 pt-7 pb-5">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 via-yellow-400 to-orange-500"></div>
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-yellow-600 flex items-center justify-center shadow-lg shadow-amber-500/25 flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Edit Nama Gang</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Perbarui nama gang atau rute penarikan</p>
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
                        <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        Nama Gang
                    </label>
                    <input type="text" name="nama" id="edit_nama" required placeholder="Masukkan nama gang (cth: Gang Merpati)..." class="form-input w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 transition-all duration-200 hover:border-slate-500">
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 justify-end pt-2 border-t border-slate-700/50">
                    <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')" class="px-5 py-2.5 text-sm font-medium text-slate-300 hover:text-white bg-slate-700/50 hover:bg-slate-600 rounded-xl transition-all duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Batal
                    </button>
                    <button type="submit" name="edit" class="bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 hover:-translate-y-0.5 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Update Gang
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function editGang(id, nama) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('modalEdit').classList.remove('hidden');
        }
        function confirmHapus(url) {
            Swal.fire({title:'Hapus Gang?',text:'Data yang dihapus tidak dapat dikembalikan!',icon:'warning',showCancelButton:true,confirmButtonColor:'#ef4444',cancelButtonColor:'#334155',confirmButtonText:'Ya, Hapus!',cancelButtonText:'Batal',background:'#1e293b',color:'#fff'}).then((result) => { if(result.isConfirmed) { window.location.href = url; } });
        }
    </script>
    <?php if(isset($_SESSION['swal'])): ?>
    <script>
        Swal.fire({title:'<?= $_SESSION['swal']['title'] ?>',text:'<?= $_SESSION['swal']['text'] ?>',icon:'<?= $_SESSION['swal']['icon'] ?>',confirmButtonColor:'#3b82f6',background:'#1e293b',color:'#fff'});
    </script>
    <?php unset($_SESSION['swal']); endif; ?>
</body>
</html>

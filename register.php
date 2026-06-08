<?php
session_start();
require_once 'config/koneksi.php';

$msg = '';
$msgType = ''; // 'success' or 'error'

if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}

if (isset($_POST['register'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi'];
    
    // Validasi input
    if (empty($nama) || empty($username) || empty($password) || empty($konfirmasi)) {
        $msg = "Semua kolom wajib diisi!";
        $msgType = 'error';
    } elseif ($password !== $konfirmasi) {
        $msg = "Konfirmasi password tidak cocok!";
        $msgType = 'error';
    } else {
        // Cek apakah username sudah ada
        $cek_username = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
        if (mysqli_num_rows($cek_username) > 0) {
            $msg = "Username sudah digunakan, silakan pilih yang lain!";
            $msgType = 'error';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert ke database dengan role otomatis 'petugas'
            $query = "INSERT INTO users (nama, username, password, role) VALUES ('$nama', '$username', '$hashed_password', 'petugas')";
            
            if (mysqli_query($conn, $query)) {
                $msg = "Silahkan login melalui aplikasi mobile SiJumpa";
                $msgType = 'success';
            } else {
                $msg = "Terjadi kesalahan: " . mysqli_error($conn);
                $msgType = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi | SiJumpa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/syle.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen flex items-center justify-center relative overflow-hidden text-slate-200 selection:bg-purple-500/30">

    <!-- Ornamen Background Animasi -->
    <div class="absolute w-96 h-96 bg-purple-600 rounded-full blur-[100px] opacity-20 -top-20 -right-20 mix-blend-screen animate-pulse" style="animation-duration: 5s;"></div>
    <div class="absolute w-[500px] h-[500px] bg-blue-600 rounded-full blur-[120px] opacity-20 bottom-[-100px] left-[-100px] mix-blend-screen animate-pulse" style="animation-duration: 7s;"></div>
    <div class="absolute w-72 h-72 bg-indigo-500 rounded-full blur-[90px] opacity-20 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 mix-blend-screen"></div>

    <div class="w-full max-w-md p-6 relative z-10 animate-fade-up">
        
        <div class="glass-panel rounded-3xl p-8 sm:p-10 shadow-2xl">
            
            <!-- Header -->
            <div class="text-center mb-8 animate-fade-up delay-100 opacity-0" style="animation-fill-mode: forwards;">
                <div class="flex justify-center mb-6">
                    <img src="assets/logo.png" alt="Logo SiJumpa" class="h-20 w-auto drop-shadow-[0_0_15px_rgba(168,85,247,0.3)] hover:scale-105 transition-transform duration-300">
                </div>
                <h1 class="text-3xl font-bold text-white tracking-tight mb-2">Daftar Petugas</h1>
                <p class="text-sm text-slate-400 font-medium">Bergabung sebagai Petugas SiJumpa</p>
            </div>

            <!-- Pesan Notifikasi -->
            <?php if($msg !== '') : ?>
                <?php if($msgType === 'error') : ?>
                    <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-3 animate-fade-up" style="animation-fill-mode: forwards;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <p><?= htmlspecialchars($msg) ?></p>
                    </div>
                <?php else : ?>
                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            Swal.fire({
                                title: 'Pendaftaran Berhasil!',
                                text: '<?= htmlspecialchars($msg) ?>',
                                icon: 'success',
                                confirmButtonColor: '#a855f7',
                                background: '#1e293b',
                                color: '#fff',
                                confirmButtonText: 'Kembali ke Login'
                            }).then((result) => {
                                window.location.href = 'login.php';
                            });
                        });
                    </script>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" class="space-y-4 animate-fade-up delay-200 opacity-0" style="animation-fill-mode: forwards;">
                
                <div class="space-y-2">
                    <label for="nama" class="text-sm font-medium text-slate-300 ml-1">Nama Lengkap</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" id="nama" name="nama" value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>" class="input-glass w-full pl-11 pr-4 py-3 rounded-xl text-sm" placeholder="Masukkan nama lengkap" required>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="username" class="text-sm font-medium text-slate-300 ml-1">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M14.243 5.757a6 6 0 10-.986 9.284 1 1 0 111.087 1.678A8 8 0 1118 10a3 3 0 01-4.8 2.401A4 4 0 1114 10a1 1 0 102 0c0-1.537-.586-3.07-1.757-4.243zM12 10a2 2 0 10-4 0 2 2 0 004 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" id="username" name="username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" class="input-glass w-full pl-11 pr-4 py-3 rounded-xl text-sm" placeholder="Pilih username" required>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="pass" class="text-sm font-medium text-slate-300 ml-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input id="pass" type="password" name="password" class="input-glass w-full pl-11 pr-12 py-3 rounded-xl text-sm" placeholder="Buat password" required>
                        <button type="button" onclick="togglePass()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-white transition-colors">
                            <svg id="eye-icon" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="space-y-2">
                    <label for="konfirmasi" class="text-sm font-medium text-slate-300 ml-1">Konfirmasi Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input id="konfirmasi" type="password" name="konfirmasi" class="input-glass w-full pl-11 pr-4 py-3 rounded-xl text-sm" placeholder="Ulangi password" required>
                    </div>
                </div>

                <button type="submit" name="register" class="w-full relative group overflow-hidden bg-gradient-to-r from-purple-500 to-indigo-600 text-white font-semibold py-3.5 rounded-xl shadow-lg shadow-purple-500/30 hover:shadow-purple-500/50 transition-all duration-300 hover:-translate-y-0.5 active:translate-y-0 mt-6">
                    <span class="relative z-10 flex items-center justify-center gap-2">
                        Daftar Akun
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </span>
                    <div class="absolute inset-0 h-full w-full bg-white/20 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left duration-300 ease-out"></div>
                </button>

                <p class="text-center text-sm text-slate-400 mt-4">
                    Sudah punya akun? <a href="login.php" class="text-purple-400 hover:text-purple-300 hover:underline font-medium transition-colors">Login di sini</a>
                </p>

            </form>

        </div>
        
        <p class="text-center text-sm mt-8 text-slate-500 animate-fade-up delay-300 opacity-0" style="animation-fill-mode: forwards;">
            &copy; 2026 SiJumpa System. Hak cipta dilindungi.
        </p>

    </div>

    <script src="assets/script.js"></script>
</body>
</html>

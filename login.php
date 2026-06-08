<?php
session_start();
require_once 'config/koneksi.php';

if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    $query = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
    
    if (mysqli_num_rows($query) === 1) {
        $row = mysqli_fetch_assoc($query);
        // Cek password hash
        if (password_verify($password, $row['password'])) {
            if ($row['role'] === 'petugas') {
                $msg = "Akses ditolak! Website ini khusus untuk Admin. Petugas harap login via Aplikasi Mobile (Flutter).";
            } else {
                $_SESSION['user'] = $row['username'];
                $_SESSION['role'] = $row['role']; 
                $_SESSION['nama'] = $row['nama']; 
                $_SESSION['swal_login'] = true;
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $msg = "Password yang Anda masukkan salah!";
        }
    } else {
        $msg = "Username tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SiJumpa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/syle.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
</head>
<body class="min-h-screen flex items-center justify-center relative overflow-hidden text-slate-200 selection:bg-blue-500/30">

    <!-- Ornamen Background Animasi -->
    <div class="absolute w-96 h-96 bg-blue-600 rounded-full blur-[100px] opacity-20 -top-20 -left-20 mix-blend-screen animate-pulse" style="animation-duration: 4s;"></div>
    <div class="absolute w-[500px] h-[500px] bg-indigo-600 rounded-full blur-[120px] opacity-20 bottom-[-100px] right-[-100px] mix-blend-screen animate-pulse" style="animation-duration: 6s;"></div>
    <div class="absolute w-72 h-72 bg-purple-500 rounded-full blur-[90px] opacity-20 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 mix-blend-screen"></div>

    <div class="w-full max-w-md p-6 relative z-10 animate-fade-up">
        
        <div class="glass-panel rounded-3xl p-8 sm:p-10 shadow-2xl">
            
            <!-- Header -->
            <div class="text-center mb-8 animate-fade-up delay-100 opacity-0" style="animation-fill-mode: forwards;">
                <div class="flex justify-center mb-6">
                    <img src="assets/logo.png" alt="Logo SiJumpa" class="h-20 w-auto drop-shadow-[0_0_15px_rgba(59,130,246,0.3)] hover:scale-105 transition-transform duration-300">
                </div>
                <h1 class="text-3xl font-bold text-white tracking-tight mb-2">SiJumpa</h1>
                <p class="text-sm text-slate-400 font-medium">Sistem Jumpitan Desa Nangungan</p>
            </div>

            <!-- Pesan Error -->
            <?php if(isset($msg)) : ?>
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-3 animate-fade-up" style="animation-fill-mode: forwards;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <p><?= htmlspecialchars($msg) ?></p>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" class="space-y-5 animate-fade-up delay-200 opacity-0" style="animation-fill-mode: forwards;">
                
                <div class="space-y-2">
                    <label for="username" class="text-sm font-medium text-slate-300 ml-1">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" id="username" name="username" class="input-glass w-full pl-11 pr-4 py-3.5 rounded-xl text-sm" placeholder="Masukkan username" required autocomplete="username">
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
                        <input id="pass" type="password" name="password" class="input-glass w-full pl-11 pr-12 py-3.5 rounded-xl text-sm" placeholder="Masukkan password" required autocomplete="current-password">
                        <button type="button" onclick="togglePass()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-white transition-colors">
                            <svg id="eye-icon" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <div class="relative flex items-center justify-center">
                            <input type="checkbox" name="remember" class="peer sr-only">
                            <div class="w-5 h-5 border-2 border-slate-500 rounded bg-transparent peer-checked:bg-blue-500 peer-checked:border-blue-500 transition-all"></div>
                            <svg class="absolute w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="text-sm text-slate-400 group-hover:text-slate-300 transition-colors">Ingat saya</span>
                    </label>
                    <a href="forgot.php" class="text-sm font-medium text-blue-400 hover:text-blue-300 hover:underline transition-colors">Lupa sandi?</a>
                </div>

                <button type="submit" name="login" class="w-full relative group overflow-hidden bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-semibold py-3.5 rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 transition-all duration-300 hover:-translate-y-0.5 active:translate-y-0 mt-4">
                    <span class="relative z-10 flex items-center justify-center gap-2">
                        Masuk ke Sistem
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </span>
                    <div class="absolute inset-0 h-full w-full bg-white/20 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left duration-300 ease-out"></div>
                </button>

                <p class="text-center text-sm text-slate-400 mt-4">
                    Belum punya akun? <a href="register.php" class="text-blue-400 hover:text-blue-300 hover:underline font-medium transition-colors">Daftar Petugas</a>
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
<?php
session_start();

// Jika user sudah login, langsung ke dashboard
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiJumpa - Sistem Jumpitan Desa Nangungan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/syle.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="min-h-screen text-slate-200 selection:bg-blue-500/30" style="background: linear-gradient(135deg, #0f172a 0%, #1a1a3e 50%, #2d1b5e 100%);">

    <!-- Ornamen Background Animasi -->
    <div class="fixed w-96 h-96 bg-blue-600 rounded-full blur-[100px] opacity-20 -top-20 -left-20 mix-blend-screen animate-pulse" style="animation-duration: 4s;"></div>
    <div class="fixed w-[500px] h-[500px] bg-indigo-600 rounded-full blur-[120px] opacity-20 bottom-[-100px] right-[-100px] mix-blend-screen animate-pulse" style="animation-duration: 6s;"></div>
    <div class="fixed w-72 h-72 bg-purple-500 rounded-full blur-[90px] opacity-20 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 mix-blend-screen"></div>

    <!-- Navbar -->
    <nav class="relative z-50 backdrop-blur-md bg-slate-900/30 border-b border-slate-700/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-indigo-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">SiJumpa</h1>
                        <p class="text-xs text-slate-400">Sistem Jumpitan Desa</p>
                    </div>
                </div>
                <a href="login.php" class="px-4 py-2 rounded-lg bg-blue-500/20 border border-blue-500/30 text-blue-300 hover:bg-blue-500/30 transition-all duration-300 text-sm font-medium">
                    Login Admin
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="relative z-10">
        <!-- Hero Section -->
        <section class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto text-center">
                <!-- Logo -->
                <div class="flex justify-center mb-8 animate-fade-up">
                    <div class="p-6 bg-gradient-to-br from-blue-500/20 to-indigo-600/20 rounded-2xl border border-blue-500/30 backdrop-blur-sm">
                        <svg class="w-24 h-24 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8.5 10a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM12.5 10a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM4 10a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm0-2a6 6 0 100-12 6 6 0 000 12z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <!-- Heading -->
                <h1 class="text-5xl sm:text-6xl font-bold text-white mb-6 animate-fade-up delay-100 opacity-0" style="animation-fill-mode: forwards;">
                    Selamat Datang di <span class="bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent">SiJumpa</span>
                </h1>

                <!-- Description -->
                <p class="text-xl text-slate-300 mb-8 max-w-2xl mx-auto animate-fade-up delay-200 opacity-0" style="animation-fill-mode: forwards;">
                    Sistem Manajemen Jumpitan Terpadu untuk Desa Nangungan. Kelola iuran warga, laporan keuangan, dan transaksi dengan mudah dan transparan.
                </p>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center animate-fade-up delay-300 opacity-0" style="animation-fill-mode: forwards;">
                    <!-- Admin Button -->
                    <a href="login.php" class="group relative overflow-hidden px-8 py-4 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-semibold shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 transition-all duration-300 hover:-translate-y-1 active:translate-y-0">
                        <span class="relative z-10 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            Masuk Admin
                        </span>
                        <div class="absolute inset-0 h-full w-full bg-white/20 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left duration-300 ease-out"></div>
                    </a>

                    <!-- App Download Button -->
                    <a href="#download" class="group relative overflow-hidden px-8 py-4 rounded-xl bg-slate-700/50 border-2 border-slate-600/50 text-white font-semibold hover:bg-slate-700/70 hover:border-blue-500/50 transition-all duration-300 hover:-translate-y-1 active:translate-y-0">
                        <span class="relative z-10 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Download Aplikasi
                        </span>
                    </a>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section class="py-20 px-4 sm:px-6 lg:px-8 bg-slate-900/20 backdrop-blur-sm">
            <div class="max-w-6xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
                    <!-- Feature 1 -->
                    <div class="glass-panel rounded-2xl p-8 hover:shadow-lg hover:shadow-blue-500/20 transition-all duration-300 transform hover:-translate-y-1">
                        <div class="w-14 h-14 bg-blue-500/20 rounded-xl flex items-center justify-center mb-4 border border-blue-500/30">
                            <svg class="w-7 h-7 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Manajemen Cepat</h3>
                        <p class="text-slate-400">Kelola data warga, iuran, dan transaksi dengan antarmuka yang intuitif dan responsif.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="glass-panel rounded-2xl p-8 hover:shadow-lg hover:shadow-blue-500/20 transition-all duration-300 transform hover:-translate-y-1">
                        <div class="w-14 h-14 bg-indigo-500/20 rounded-xl flex items-center justify-center mb-4 border border-indigo-500/30">
                            <svg class="w-7 h-7 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Laporan Lengkap</h3>
                        <p class="text-slate-400">Dapatkan laporan keuangan terperinci dan visualisasi data yang mudah dipahami untuk keperluan pelaporan.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="glass-panel rounded-2xl p-8 hover:shadow-lg hover:shadow-blue-500/20 transition-all duration-300 transform hover:-translate-y-1">
                        <div class="w-14 h-14 bg-purple-500/20 rounded-xl flex items-center justify-center mb-4 border border-purple-500/30">
                            <svg class="w-7 h-7 text-purple-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Keamanan Terjamin</h3>
                        <p class="text-slate-400">Sistem login terenkripsi dan manajemen akses berbasis peran untuk melindungi data sensitif desa.</p>
                    </div>
                </div>

                <!-- About Content -->
                <div class="glass-panel rounded-2xl p-8 sm:p-12 border border-slate-700/30">
                    <h2 class="text-3xl font-bold text-white mb-6">Tentang SiJumpa</h2>
                    <div class="space-y-4 text-slate-300 leading-relaxed">
                        <p>
                            <strong class="text-white">SiJumpa</strong> adalah sistem manajemen jumpitan (iuran kas) yang dirancang khusus untuk memudahkan pengelolaan keuangan desa secara digital dan transparan. Dengan platform ini, seluruh proses pencatatan, pelaporannya dan audit menjadi lebih efisien.
                        </p>
                        <p>
                            Platform ini menyediakan dua antarmuka utama:
                        </p>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li><strong>Web Admin Panel:</strong> Untuk pengelola desa dan bendahara dalam mengurus laporan dan keuangan</li>
                            <li><strong>Aplikasi Mobile:</strong> Untuk petugas lapangan dalam melakukan pencatatan transaksi dan validasi pembayaran</li>
                        </ul>
                        <p>
                            Dengan fitur-fitur lengkap seperti manajemen data warga, pencatatan transaksi, laporan keuangan real-time, dan integrasi QR Code, SiJumpa memastikan setiap transaksi tercatat dengan akurat dan transparan.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Download Section -->
        <section id="download" class="py-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-12">
                    <h2 class="text-4xl font-bold text-white mb-4">Download Aplikasi Mobile</h2>
                    <p class="text-slate-400 text-lg">Akses SiJumpa di perangkat mobile Anda untuk mobilitas maksimal</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Android -->
                    <div class="glass-panel rounded-2xl p-8 border border-slate-700/30 hover:border-blue-500/50 transition-all duration-300">
                        <div class="flex items-center justify-center mb-6">
                            <svg class="w-16 h-16 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="currentColor">
                                <path d="M36 12c0-1.1-.9-2-2-2H14c-1.1 0-2 .9-2 2v24c0 1.1.9 2 2 2h20c1.1 0 2-.9 2-2V12zm-2 24H14V12h20v24z" />
                                <circle cx="24" cy="32" r="2" fill="currentColor" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-2 text-center">Android</h3>
                        <p class="text-slate-400 text-center mb-6">Kompatibel dengan Android 6.0+</p>
                        <a href="#" class="w-full block text-center px-6 py-3 bg-green-500/20 border border-green-500/50 text-green-300 rounded-lg hover:bg-green-500/30 transition-all duration-300 font-medium">
                            Download APK
                        </a>
                        <p class="text-xs text-slate-500 text-center mt-3">Atau scan QR Code di Play Store</p>
                    </div>

                    <!-- iOS -->
                    <div class="glass-panel rounded-2xl p-8 border border-slate-700/30 hover:border-blue-500/50 transition-all duration-300">
                        <div class="flex items-center justify-center mb-6">
                            <svg class="w-16 h-16 text-slate-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="currentColor">
                                <path d="M35 3H13c-2.2 0-4 1.8-4 4v34c0 2.2 1.8 4 4 4h22c2.2 0 4-1.8 4-4V7c0-2.2-1.8-4-4-4zm0 38H13V7h22v34z" />
                                <circle cx="24" cy="40" r="1.5" fill="currentColor" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-2 text-center">iOS</h3>
                        <p class="text-slate-400 text-center mb-6">Kompatibel dengan iOS 12.0+</p>
                        <a href="#" class="w-full block text-center px-6 py-3 bg-slate-600/30 border border-slate-600/50 text-slate-300 rounded-lg hover:bg-slate-600/50 transition-all duration-300 font-medium cursor-not-allowed">
                            Coming Soon
                        </a>
                        <p class="text-xs text-slate-500 text-center mt-3">Segera tersedia di App Store</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-slate-700/30 py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-6xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                    <div>
                        <h4 class="text-white font-bold mb-4">SiJumpa</h4>
                        <p class="text-slate-400 text-sm">Sistem Manajemen Jumpitan untuk Desa Nangungan</p>
                    </div>
                    <div>
                        <h4 class="text-white font-bold mb-4">Akses</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="login.php" class="text-slate-400 hover:text-blue-400 transition-colors">Login Admin</a></li>
                            <li><a href="register.php" class="text-slate-400 hover:text-blue-400 transition-colors">Registrasi Petugas</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-bold mb-4">Informasi</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="text-slate-400 hover:text-blue-400 transition-colors">Tentang Kami</a></li>
                            <li><a href="#" class="text-slate-400 hover:text-blue-400 transition-colors">Bantuan</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-bold mb-4">Hubungi</h4>
                        <p class="text-slate-400 text-sm">Desa Nangungan</p>
                        <p class="text-slate-400 text-sm">Kabupaten Sleman, DIY</p>
                    </div>
                </div>
                <div class="border-t border-slate-700/30 pt-8">
                    <p class="text-center text-slate-500 text-sm">
                        &copy; 2026 SiJumpa System. Hak cipta dilindungi. Dikembangkan dengan ❤️ untuk Desa Nangungan.
                    </p>
                </div>
            </div>
        </footer>
    </div>

    <script src="assets/script.js"></script>
    <script>
        // Smooth scroll untuk anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>

</html>
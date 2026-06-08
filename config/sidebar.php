<?php
/**
 * Sidebar Bersama SiJumpa
 * Digunakan oleh semua halaman utama.
 * Pastikan session sudah di-start sebelum include file ini.
 */
$_sb_page = basename($_SERVER['PHP_SELF']);
$_sb_role = $_SESSION['role'] ?? 'petugas';
$_sb_nama = $_SESSION['nama'] ?? 'Pengguna';

if (!function_exists('_sb_nav')) {
    function _sb_nav($page) {
        global $_sb_page;
        $active = ($_sb_page === $page);
        return 'nav-item ' . ($active ? 'active' : 'text-slate-400') .
               ' flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors hover:text-white hover:bg-slate-800';
    }
    function _sb_ico($page) {
        global $_sb_page;
        return 'w-5 h-5' . ($_sb_page === $page ? ' text-blue-500' : '');
    }
}
?>
<aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col hidden md:flex transition-all duration-300">

    <!-- Logo -->
    <div class="h-20 flex items-center px-6 border-b border-slate-800">
        <div class="flex items-center gap-3">
            <img src="assets/logo.png" alt="Logo SiJumpa"
                 class="w-10 h-10 object-contain drop-shadow-[0_0_10px_rgba(59,130,246,0.3)] hover:scale-105 transition-transform">
            <div>
                <h1 class="text-white font-bold text-lg leading-tight">SiJumpa</h1>
                <p class="text-xs text-slate-400">Sistem Jumpitan Desa</p>
            </div>
        </div>
    </div>

    <!-- Menu Utama -->
    <div class="flex-1 overflow-y-auto py-6 px-3 space-y-1">
        <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Menu Utama</p>

        <!-- Dashboard -->
        <a href="dashboard.php" class="<?= _sb_nav('dashboard.php') ?>">
            <svg class="<?= _sb_ico('dashboard.php') ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z
                       M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z
                       M4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z
                       M14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            Dashboard
        </a>

        <?php if ($_sb_role === 'admin'): ?>

        <!-- Manajemen Warga -->
        <a href="warga.php" class="<?= _sb_nav('warga.php') ?>">
            <svg class="<?= _sb_ico('warga.php') ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857
                       M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857
                       m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z
                       m6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Manajemen Warga
        </a>

        <!-- Manajemen Petugas -->
        <a href="petugas.php" class="<?= _sb_nav('petugas.php') ?>">
            <svg class="<?= _sb_ico('petugas.php') ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04
                       A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622
                       0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            Manajemen Petugas
        </a>

        <!-- Manajemen Gang -->
        <a href="gang.php" class="<?= _sb_nav('gang.php') ?>">
            <svg class="<?= _sb_ico('gang.php') ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7
                       m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618
                       a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
            Manajemen Gang
        </a>

        <!-- Jadwal & Rute -->
        <a href="jadwal.php" class="<?= _sb_nav('jadwal.php') ?>">
            <svg class="<?= _sb_ico('jadwal.php') ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Jadwal &amp; Rute
        </a>

        <?php endif; ?>

        <!-- Laporan Transaksi -->
        <a href="laporan.php" class="<?= _sb_nav('laporan.php') ?>">
            <svg class="<?= _sb_ico('laporan.php') ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586
                       a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Laporan Transaksi
        </a>

        <!-- Pengeluaran Kas -->
        <a href="pengeluaran.php" class="<?= _sb_nav('pengeluaran.php') ?>">
            <svg class="<?= _sb_ico('pengeluaran.php') ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Pengeluaran Kas
        </a>

        <?php if ($_sb_role === 'admin'): ?>
        <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-6 mb-2">Pengaturan</p>

        <!-- Sistem & Audit -->
        <a href="audit.php" class="<?= _sb_nav('audit.php') ?>">
            <svg class="<?= _sb_ico('audit.php') ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066
                       c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35
                       a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065
                       c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37
                       a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573
                       c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Sistem &amp; Audit
        </a>
        <?php endif; ?>
    </div>

    <!-- Tombol Keluar -->
    <div class="p-4 border-t border-slate-800">
        <a href="logout.php"
           onclick="event.preventDefault();Swal.fire({title:'Konfirmasi Keluar',text:'Apakah Anda yakin ingin keluar?',icon:'warning',showCancelButton:true,confirmButtonColor:'#ef4444',cancelButtonColor:'#334155',confirmButtonText:'Ya, Keluar!',cancelButtonText:'Batal',background:'#1e293b',color:'#fff'}).then((r)=>{if(r.isConfirmed)window.location.href='logout.php';})"
           class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Keluar
        </a>
    </div>
</aside>

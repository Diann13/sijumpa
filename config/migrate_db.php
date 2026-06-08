<?php
require_once __DIR__ . '/koneksi.php';

echo "Memulai migrasi database...\n";

// 1. Alter users table to add 'bendahara' to role enum
$alter_users = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'petugas', 'bendahara')";
if (mysqli_query($conn, $alter_users)) {
    echo "- Tabel 'users' berhasil dimodifikasi untuk mendukung role bendahara.\n";
} else {
    echo "- Gagal memodifikasi tabel 'users': " . mysqli_error($conn) . "\n";
}

// 2. Create pengeluaran table
$create_pengeluaran = "CREATE TABLE IF NOT EXISTS pengeluaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    nominal INT NOT NULL,
    keterangan TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// 3. Create gang management table
$create_gang = "CREATE TABLE IF NOT EXISTS gang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (mysqli_query($conn, $create_gang)) {
    echo "- Tabel 'gang' berhasil dibuat/dikonfirmasi.\n";
} else {
    echo "- Gagal membuat tabel 'gang': " . mysqli_error($conn) . "\n";
}
if (mysqli_query($conn, $create_pengeluaran)) {
    echo "- Tabel 'pengeluaran' berhasil dibuat/dikonfirmasi.\n";
} else {
    echo "- Gagal membuat tabel 'pengeluaran': " . mysqli_error($conn) . "\n";
}

// 3. Add 'gang' column to warga table if not exists
$cek_gang = mysqli_query($conn, "SHOW COLUMNS FROM warga LIKE 'gang'");
if (mysqli_num_rows($cek_gang) == 0) {
    $add_gang = "ALTER TABLE warga ADD COLUMN gang VARCHAR(50) DEFAULT NULL";
    if (mysqli_query($conn, $add_gang)) {
        echo "- Kolom 'gang' berhasil ditambahkan ke tabel 'warga'.\n";
    } else {
        echo "- Gagal menambahkan kolom 'gang' ke tabel 'warga': " . mysqli_error($conn) . "\n";
    }
} else {
    echo "- Kolom 'gang' sudah ada di tabel 'warga'.\n";
}

// 4. Add 'password' column to warga table if not exists
$cek_password = mysqli_query($conn, "SHOW COLUMNS FROM warga LIKE 'password'");
if (mysqli_num_rows($cek_password) == 0) {
    $add_password = "ALTER TABLE warga ADD COLUMN password VARCHAR(255) DEFAULT NULL";
    if (mysqli_query($conn, $add_password)) {
        echo "- Kolom 'password' berhasil ditambahkan ke tabel 'warga'.\n";
    } else {
        echo "- Gagal menambahkan kolom 'password' ke tabel 'warga': " . mysqli_error($conn) . "\n";
    }
} else {
    echo "- Kolom 'password' sudah ada di tabel 'warga'.\n";
}

echo "Migrasi database selesai!\n";

// 5. Add 'nota_path' column to pengeluaran table if not exists
$cek_nota = mysqli_query($conn, "SHOW COLUMNS FROM pengeluaran LIKE 'nota_path'");
if (mysqli_num_rows($cek_nota) == 0) {
    if (mysqli_query($conn, "ALTER TABLE pengeluaran ADD COLUMN nota_path VARCHAR(255) DEFAULT NULL")) {
        echo "- Kolom 'nota_path' berhasil ditambahkan ke tabel 'pengeluaran'.<br>\n";
    } else {
        echo "- Gagal menambahkan kolom 'nota_path': " . mysqli_error($conn) . "<br>\n";
    }
} else {
    echo "- Kolom 'nota_path' sudah ada.<br>\n";
}

// 6. Add 'gang' column to jadwal table if not exists
$cek_gang_jadwal = mysqli_query($conn, "SHOW COLUMNS FROM jadwal LIKE 'gang'");
if (mysqli_num_rows($cek_gang_jadwal) == 0) {
    if (mysqli_query($conn, "ALTER TABLE jadwal ADD COLUMN gang VARCHAR(100) DEFAULT NULL")) {
        echo "- Kolom 'gang' berhasil ditambahkan ke tabel 'jadwal'.<br>\n";
    } else {
        echo "- Gagal menambahkan kolom 'gang' ke tabel 'jadwal': " . mysqli_error($conn) . "<br>\n";
    }
} else {
    echo "- Kolom 'gang' sudah ada di tabel 'jadwal'.<br>\n";
}

# 📊 SiJumpa - Sistem Jumpitan Desa Nangungan

Sistem manajemen jumpitan (iuran kas) terpadu untuk Desa Nangungan dengan antarmuka web dan aplikasi mobile.

## 🚀 Fitur Utama

### Web Admin Panel

- **Dashboard Analytics**: Laporan keuangan real-time dan visualisasi data
- **Manajemen Warga**: CRUD data warga dan informasi profil
- **Pencatatan Transaksi**: Kelola iuran masuk dan pengeluaran
- **Laporan Keuangan**: Generate laporan terperinci untuk audit
- **Manajemen Pengguna**: Kontrol akses berbasis peran (Admin, Bendahara, Petugas)
- **Jadwal Iuran**: Atur jadwal pengumpulan iuran
- **Integrasi QR Code**: Validasi pembayaran dengan QR scanning

### Aplikasi Mobile (Flutter)

- Login untuk petugas lapangan
- Pencatatan transaksi real-time
- Validasi pembayaran QR Code
- Notifikasi dan laporan sinkron

## 📁 Struktur Direktori

```
sijumpa/
├── index.php              # Halaman utama (landing page)
├── login.php              # Login admin
├── register.php           # Registrasi petugas
├── dashboard.php          # Dashboard admin
├── warga.php              # Manajemen warga
├── transaksi.php          # Pencatatan transaksi
├── laporan.php            # Laporan keuangan
├── jadwal.php             # Jadwal iuran
├── pengeluaran.php        # Manajemen pengeluaran
├── petugas.php            # Manajemen petugas
├── audit.php              # Log audit
├── gang.php               # Manajemen gang/zona
├── logout.php             # Logout
├── config/
│   ├── koneksi.php        # Database connection
│   ├── sidebar.php        # Sidebar component
│   └── migrate_db.php     # Database migration
├── api/
│   ├── login.php          # API login mobile
│   ├── transaksi.php      # API transaksi
│   ├── get_warga.php      # API get data warga
│   ├── get_laporan.php    # API laporan
│   ├── scan_qr.php        # API QR scanning
│   └── ... (API endpoints lainnya)
├── assets/
│   ├── script.js          # JavaScript utilities
│   └── syle.css           # Custom CSS
├── uploads/
│   └── nota/              # Folder upload bukti/nota
└── .htaccess              # Apache rewrite rules
```

## 🔐 Sistem Autentikasi

### User Roles

1. **Super Admin**: Akses penuh ke semua fitur
2. **Bendahara**: Akses laporan keuangan dan manajemen transaksi
3. **Petugas**: Akses terbatas untuk pencatatan transaksi (via mobile)

### Keamanan

- Password hashing dengan PHP `password_hash()`
- Session management
- SQL prepared statements (recommended untuk enkripsi query)
- CSRF protection (dapat ditambahkan)

## 💻 Cara Menggunakan

### 1. Akses Halaman Utama

```
http://localhost/sijumpa/
```

Halaman ini menampilkan:

- Informasi tentang sistem
- Tombol "Masuk Admin" → ke login.php
- Tombol "Download Aplikasi" → download APK

### 2. Login Admin

```
http://localhost/sijumpa/login.php
```

Masukkan username dan password untuk akses admin panel

### 3. Akses Dashboard

```
http://localhost/sijumpa/dashboard.php
```

Kelola semua aspek sistem dari dashboard

### 4. Registrasi Petugas

```
http://localhost/sijumpa/register.php
```

Petugas lapangan dapat mendaftar untuk akses mobile app

## 🗄️ Database Schema

### Tabel Utama

- **users**: Simpan akun login (admin, bendahara, petugas)
- **warga**: Data warga desa
- **transaksi_jumpitan**: Catatan iuran masuk
- **pengeluaran**: Catatan pengeluaran kas
- **jadwal_iuran**: Jadwal pengumpulan iuran
- **gang**: Pembagian zona/gang

## 🔧 Konfigurasi

### Database Connection (config/koneksi.php)

```php
$conn = mysqli_connect(
    'localhost',    // host
    'root',         // username
    '',            // password
    'sijumpa'      // database name
);
```

## 📱 API Endpoints

### Mobile App Login

```
POST /api/login_warga.php
{
    "username": "petugas1",
    "password": "password123"
}
```

### Get Data Warga

```
GET /api/get_warga.php?gang_id=1
```

### Scan QR Code

```
POST /api/scan_qr.php
{
    "qr_code": "W001-2024-001",
    "nominal": 100000
}
```

## 🎨 Desain UI

Menggunakan:

- **Tailwind CSS**: Framework CSS modern
- **Custom Glass-morphism**: Efek blur glass panel
- **Gradient Backgrounds**: Animasi background gradient
- **Responsive Design**: Mobile-friendly interface

## 📝 Catatan

- Pastikan Apache mod_rewrite diaktifkan untuk .htaccess berfungsi
- Konfigurasi database sebelum menjalankan sistem
- Jangan lupa backup database secara berkala
- Untuk production, gunakan HTTPS dan environment variables

## 📞 Support

Untuk pertanyaan atau bantuan, hubungi:

- Desa Nangungan, Kabupaten Sleman, DIY
- Email: (akan ditambahkan)
- Telepon: (akan ditambahkan)

---

**Dikembangkan dengan ❤️ untuk Desa Nangungan**
© 2026 SiJumpa System. All rights reserved.

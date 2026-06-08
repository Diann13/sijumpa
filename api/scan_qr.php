<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/koneksi.php';

// Ambil qr_code dari parameter GET (Misal: scan_qr.php?qr_code=SJ-0001)
$qr_code = isset($_GET['qr_code']) ? mysqli_real_escape_string($conn, $_GET['qr_code']) : '';

if (empty($qr_code)) {
    echo json_encode([
        "status" => "error",
        "message" => "QR Code tidak boleh kosong"
    ]);
    exit;
}

// Cari data warga berdasarkan QR Code
$query = mysqli_query($conn, "SELECT id, nama, alamat, kode_rumah, gang FROM warga WHERE qr_code = '$qr_code'");

if (mysqli_num_rows($query) > 0) {
    $warga = mysqli_fetch_assoc($query);

    // Validasi Jadwal Petugas jika petugas_id dikirimkan
    $petugas_id = isset($_GET['petugas_id']) ? intval($_GET['petugas_id']) : 0;
    if ($petugas_id > 0) {
        $cek_petugas = mysqli_query($conn, "SELECT role FROM users WHERE id = $petugas_id LIMIT 1");
        if ($cek_petugas && mysqli_num_rows($cek_petugas) > 0) {
            $u_data = mysqli_fetch_assoc($cek_petugas);
            if ($u_data['role'] === 'petugas') {
                function normalize_gang($gang)
                {
                    return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string)$gang)));
                }

                $warga_gang = normalize_gang($warga['gang']);
                $jadwal_res = mysqli_query($conn, "SELECT gang FROM jadwal WHERE petugas_id = $petugas_id AND tanggal = CURDATE()");

                $scheduled_gangs = [];
                $scheduled_gangs_display = [];
                while ($row = mysqli_fetch_assoc($jadwal_res)) {
                    if (!empty($row['gang'])) {
                        $scheduled_gangs[] = normalize_gang($row['gang']);
                        $scheduled_gangs_display[] = trim($row['gang']);
                    }
                }

                if (empty($scheduled_gangs)) {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Gagal: Anda tidak memiliki jadwal penarikan jimpitan hari ini!"
                    ]);
                    exit;
                }

                if (empty($warga_gang) || !in_array($warga_gang, $scheduled_gangs, true)) {
                    $list_gang = implode(', ', $scheduled_gangs_display);
                    echo json_encode([
                        "status" => "error",
                        "message" => "Gagal: Jadwal Anda hari ini adalah di '" . $list_gang . "', tidak diizinkan menarik di gang '" . ($warga['gang'] ? $warga['gang'] : 'tidak terdaftar') . "'!"
                    ]);
                    exit;
                }
            }
        }
    }

    // Cek apakah warga sudah dikunjungi hari ini
    $visit_query = mysqli_query($conn, "SELECT status FROM transaksi_jumpitan WHERE warga_id = " . intval($warga['id']) . " AND DATE(tanggal) = CURDATE() ORDER BY tanggal DESC LIMIT 1");
    $warga['visited_today'] = mysqli_num_rows($visit_query) > 0;
    $warga['today_status'] = 'none';
    if ($visit_query && mysqli_num_rows($visit_query) > 0) {
        $row_visit = mysqli_fetch_assoc($visit_query);
        $warga['today_status'] = $row_visit['status'];
    }

    // Ambil harga jumpitan dari pengaturan
    $q_harga = mysqli_query($conn, "SELECT nilai FROM pengaturan WHERE kunci = 'harga_jumpitan' LIMIT 1");
    $harga = 2000; // default
    if ($q_harga && mysqli_num_rows($q_harga) > 0) {
        $r_harga = mysqli_fetch_assoc($q_harga);
        $harga = intval($r_harga['nilai']);
    }

    // Inject harga_jumpitan ke data warga agar aplikasi flutter bisa mengambil nominalnya secara dinamis
    $warga['harga_jumpitan'] = $harga;

    echo json_encode([
        "status" => "success",
        "message" => "Data warga ditemukan",
        "data" => $warga
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "QR Code tidak terdaftar di sistem"
    ]);
}

<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require_once '../config/koneksi.php';

$data = json_decode(file_get_contents("php://input"));

// CEK JSON VALID
if (!$data) {
    echo json_encode([
        "status" => "error",
        "message" => "Format JSON tidak valid"
    ]);
    exit;
}

// VALIDASI DATA WAJIB
if (
    isset($data->warga_id) &&
    isset($data->petugas_id)
) {
    $warga_id   = intval($data->warga_id);
    $petugas_id = intval($data->petugas_id);

    $status = isset($data->status)
        ? mysqli_real_escape_string($conn, $data->status)
        : 'bayar';

    $keterangan = ($status == 'belum')
        ? "Warga Belum Bayar"
        : "Via Aplikasi Petugas";

    /**
     * ============================================================
     * FIX BUG NOMINAL
     * ============================================================
     * Sebelumnya nominal bisa ketimpa dari tabel pengaturan.
     * Sekarang nominal bayar selalu ambil dari request Flutter.
     *
     * Klik 500  => masuk 500
     * Klik 1000 => masuk 1000
     * Klik 1500 => masuk 1500
     * Klik 2000 => masuk 2000
     * Belum bayar => masuk 0
     */
    $nominal = 0;

    if ($status == 'bayar') {
        if (isset($data->nominal) && intval($data->nominal) > 0) {
            $nominal = intval($data->nominal);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Nominal tidak boleh kosong"
            ]);
            exit;
        }
    } else {
        $nominal = 0;
    }

    // PASTIKAN PETUGAS VALID DAN ADA DI DATABASE
    $cek = mysqli_query(
        $conn,
        "SELECT id, role FROM users WHERE id = $petugas_id LIMIT 1"
    );

    if (!$cek || mysqli_num_rows($cek) === 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Petugas tidak ditemukan!"
        ]);
        exit;
    }

    $user_data = mysqli_fetch_assoc($cek);

    // CEK WARGA ADA ATAU TIDAK
    $warga_res = mysqli_query(
        $conn,
        "SELECT nama, gang FROM warga WHERE id = $warga_id LIMIT 1"
    );

    if (!$warga_res || mysqli_num_rows($warga_res) === 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Warga tidak ditemukan!"
        ]);
        exit;
    }

    $warga_data = mysqli_fetch_assoc($warga_res);

    // FUNGSI NORMALISASI GANG
    function normalize_gang($gang)
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string)$gang)));
    }

    /**
     * CEK APAKAH WARGA SUDAH DIKUNJUNGI HARI INI
     */
    $cek_hari_ini = mysqli_query(
        $conn,
        "SELECT id, status 
         FROM transaksi_jumpitan 
         WHERE warga_id = $warga_id 
         AND DATE(tanggal) = CURDATE() 
         ORDER BY tanggal DESC 
         LIMIT 1"
    );

    if ($cek_hari_ini && mysqli_num_rows($cek_hari_ini) > 0) {
        $existing = mysqli_fetch_assoc($cek_hari_ini);

        // Kalau sebelumnya "belum", lalu sekarang bayar, maka update jadi bayar
        if ($existing['status'] === 'belum' && $status === 'bayar') {
            $sql_update = "UPDATE transaksi_jumpitan 
                           SET nominal = $nominal, 
                               status = 'bayar', 
                               tanggal = NOW(), 
                               keterangan = '$keterangan' 
                           WHERE id = " . intval($existing['id']);

            if (mysqli_query($conn, $sql_update)) {
                echo json_encode([
                    "status" => "success",
                    "message" => "Transaksi hari ini diperbarui menjadi bayar."
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => mysqli_error($conn)
                ]);
            }
            exit;
        }

        echo json_encode([
            "status" => "error",
            "message" => "Warga sudah dikunjungi hari ini."
        ]);
        exit;
    }

    /**
     * CEK JADWAL GANG KHUSUS ROLE PETUGAS
     */
    if ($user_data['role'] === 'petugas') {
        $warga_gang = normalize_gang($warga_data['gang']);

        // Ambil jadwal petugas hari ini
        $jadwal_res = mysqli_query(
            $conn,
            "SELECT gang 
             FROM jadwal 
             WHERE petugas_id = $petugas_id 
             AND tanggal = CURDATE()"
        );

        $scheduled_gangs = [];
        $scheduled_gangs_display = [];

        if ($jadwal_res) {
            while ($row = mysqli_fetch_assoc($jadwal_res)) {
                if (!empty($row['gang'])) {
                    $scheduled_gangs[] = normalize_gang($row['gang']);
                    $scheduled_gangs_display[] = trim($row['gang']);
                }
            }
        }

        if (empty($scheduled_gangs)) {
            echo json_encode([
                "status" => "error",
                "message" => "Gagal: Anda tidak memiliki jadwal penarikan jimpitan hari ini!"
            ]);
            exit;
        }

        // Cek apakah gang warga sesuai jadwal petugas
        if (empty($warga_gang) || !in_array($warga_gang, $scheduled_gangs, true)) {
            $list_gang = implode(', ', $scheduled_gangs_display);

            echo json_encode([
                "status" => "error",
                "message" => "Gagal: Jadwal Anda hari ini adalah di '" . $list_gang . "', tidak diizinkan menarik di gang '" . ($warga_data['gang'] ? $warga_data['gang'] : 'tidak terdaftar') . "'!"
            ]);
            exit;
        }
    }

    /**
     * INSERT TRANSAKSI BARU
     */
    $sql = "INSERT INTO transaksi_jumpitan 
            (warga_id, petugas_id, nominal, status, tanggal, keterangan) 
            VALUES 
            ($warga_id, $petugas_id, $nominal, '$status', NOW(), '$keterangan')";

    if (mysqli_query($conn, $sql)) {
        echo json_encode([
            "status" => "success",
            "message" => "Berhasil!",
            "nominal" => $nominal
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => mysqli_error($conn)
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Data tidak lengkap"
    ]);
}
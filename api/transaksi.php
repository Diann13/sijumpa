<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../config/koneksi.php';

$data = json_decode(file_get_contents("php://input"));

// VALIDASI KETAT
if (
    isset($data->warga_id) &&
    isset($data->petugas_id)
) {
    $warga_id   = intval($data->warga_id);
    $petugas_id = intval($data->petugas_id);
    $status     = isset($data->status) ? mysqli_real_escape_string($conn, $data->status) : 'bayar';
    $keterangan = ($status == 'belum')
        ? "Warga Belum Bayar"
        : "Via Aplikasi Petugas";

    // Ambil nominal dari pengaturan database secara dinamis
    $nominal = 0;
    if ($status == 'bayar') {
        // Cek tabel pengaturan untuk harga_jumpitan
        $q_harga = mysqli_query($conn, "SELECT nilai FROM pengaturan WHERE kunci = 'harga_jumpitan' LIMIT 1");
        $harga_setting = 2000; // default fallback
        if ($q_harga && mysqli_num_rows($q_harga) > 0) {
            $r_harga = mysqli_fetch_assoc($q_harga);
            $harga_setting = intval($r_harga['nilai']);
        }

        // Gunakan nominal dari request jika dikirimkan dan bukan nominal default lama (2000), 
        // jika tidak dikirim atau nilainya 2000 sedangkan pengaturan sudah diubah, gunakan harga_setting.
        if (isset($data->nominal) && intval($data->nominal) > 0) {
            if (intval($data->nominal) == 2000 && $harga_setting != 2000) {
                $nominal = $harga_setting;
            } else {
                $nominal = intval($data->nominal);
            }
        } else {
            $nominal = $harga_setting;
        }
    }
    // PASTIKAN petugas_id valid dan ada di database
    $cek = mysqli_query($conn, "SELECT id, role FROM users WHERE id = $petugas_id LIMIT 1");
    if (mysqli_num_rows($cek) === 0) {
        echo json_encode(["status" => "error", "message" => "Petugas tidak ditemukan!"]);
        exit;
    }
    $user_data = mysqli_fetch_assoc($cek);

    // Jika role-nya adalah petugas, cek jadwal gang untuk hari ini
    if ($user_data['role'] === 'petugas') {
        function normalize_gang($gang)
        {
            return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string)$gang)));
        }

        // Ambil gang warga
        $warga_res = mysqli_query($conn, "SELECT nama, gang FROM warga WHERE id = $warga_id LIMIT 1");
        if (mysqli_num_rows($warga_res) === 0) {
            echo json_encode(["status" => "error", "message" => "Warga tidak ditemukan!"]);
            exit;
        }
        $warga_data = mysqli_fetch_assoc($warga_res);
        $warga_gang = normalize_gang($warga_data['gang']);

        // Cek apakah warga sudah dikunjungi hari ini
        $cek_hari_ini = mysqli_query($conn, "SELECT id, status FROM transaksi_jumpitan WHERE warga_id = $warga_id AND DATE(tanggal) = CURDATE() ORDER BY tanggal DESC LIMIT 1");
        if (mysqli_num_rows($cek_hari_ini) > 0) {
            $existing = mysqli_fetch_assoc($cek_hari_ini);
            if ($existing['status'] === 'belum' && $status === 'bayar') {
                $sql_update = "UPDATE transaksi_jumpitan SET nominal = $nominal, status = 'bayar', tanggal = NOW(), keterangan = '$keterangan' WHERE id = " . intval($existing['id']);
                if (mysqli_query($conn, $sql_update)) {
                    echo json_encode(["status" => "success", "message" => "Transaksi hari ini diperbarui menjadi bayar."]);
                } else {
                    echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
                }
                exit;
            }

            echo json_encode(["status" => "error", "message" => "Warga sudah dikunjungi hari ini."]);
            exit;
        }

        // Ambil jadwal petugas hari ini menggunakan tanggal server database
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

        // Cek apakah gang warga cocok dengan gang di jadwal petugas
        if (empty($warga_gang) || !in_array($warga_gang, $scheduled_gangs, true)) {
            $list_gang = implode(', ', $scheduled_gangs_display);
            echo json_encode([
                "status" => "error",
                "message" => "Gagal: Jadwal Anda hari ini adalah di '" . $list_gang . "', tidak diizinkan menarik di gang '" . ($warga_data['gang'] ? $warga_data['gang'] : 'tidak terdaftar') . "'!"
            ]);
            exit;
        }
    }

    $sql = "INSERT INTO transaksi_jumpitan (warga_id, petugas_id, nominal, status, tanggal, keterangan) 
            VALUES ($warga_id, $petugas_id, $nominal, '$status', NOW(), '$keterangan')";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status" => "success", "message" => "Berhasil!"]);
    } else {
        echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Data tidak lengkap"]);
}

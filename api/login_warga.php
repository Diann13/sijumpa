<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/koneksi.php';

$data = json_decode(file_get_contents('php://input'), true);
$nama = isset($data['nama']) ? trim($data['nama']) : '';
$qr_code = isset($data['qr_code']) ? trim($data['qr_code']) : '';
$password = isset($data['password']) ? trim($data['password']) : '';

if (empty($nama) || empty($qr_code) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Nama, QR Code, dan password harus diisi.'
    ]);
    exit;
}

$qr_code_safe = mysqli_real_escape_string($conn, $qr_code);
$query = mysqli_query($conn, "SELECT * FROM warga WHERE qr_code = '$qr_code_safe' LIMIT 1");

if (!$query || mysqli_num_rows($query) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'QR Code warga tidak ditemukan.'
    ]);
    exit;
}

$warga = mysqli_fetch_assoc($query);

if (strcasecmp(trim($warga['nama']), $nama) !== 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Nama tidak cocok dengan QR Code.'
    ]);
    exit;
}

$isValid = false;
if (!empty($warga['password'])) {
    if (password_verify($password, $warga['password'])) {
        $isValid = true;
    } elseif ($password === $warga['password']) {
        $isValid = true;
    }
} elseif ($password === $qr_code) {
    $isValid = true;
}

if (!$isValid) {
    echo json_encode([
        'success' => false,
        'message' => 'Password salah.'
    ]);
    exit;
}

// Return only safe warga info
unset($warga['password']);

echo json_encode([
    'success' => true,
    'message' => 'Login warga berhasil.',
    'data' => [
        'id' => intval($warga['id']),
        'nama' => $warga['nama'],
        'alamat' => $warga['alamat'],
        'kode_rumah' => $warga['kode_rumah'],
        'qr_code' => $warga['qr_code'],
        'gang' => $warga['gang']
    ]
]);

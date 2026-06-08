<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/koneksi.php';

$qr_code = isset($_GET['qr_code']) ? trim($_GET['qr_code']) : '';
$warga_id = isset($_GET['warga_id']) ? intval($_GET['warga_id']) : 0;

if (empty($qr_code) && $warga_id === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'QR Code atau warga_id harus diberikan.'
    ]);
    exit;
}

$where = '';
if (!empty($qr_code)) {
    $qr_code_safe = mysqli_real_escape_string($conn, $qr_code);
    $where = "qr_code = '$qr_code_safe'";
} else {
    $where = "id = $warga_id";
}

$query = mysqli_query($conn, "SELECT id, nama, alamat, kode_rumah, qr_code, gang FROM warga WHERE $where LIMIT 1");
if (!$query || mysqli_num_rows($query) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Warga tidak ditemukan.'
    ]);
    exit;
}

$warga = mysqli_fetch_assoc($query);
$warga_id = intval($warga['id']);

// Ringkasan pembayaran jimpitan warga
$query_summary = mysqli_query($conn, "SELECT COUNT(*) AS jumlah_bayar, COALESCE(SUM(nominal), 0) AS total_bayar FROM transaksi_jumpitan WHERE warga_id = $warga_id AND status = 'bayar'");
$summary = mysqli_fetch_assoc($query_summary);

// Transaksi warga
$query_transaksi = mysqli_query($conn, "SELECT t.id, t.nominal, t.status, t.tanggal, t.keterangan, u.nama AS nama_petugas FROM transaksi_jumpitan t LEFT JOIN users u ON t.petugas_id = u.id WHERE t.warga_id = $warga_id ORDER BY t.tanggal DESC");
$transaksi = [];
while ($row = mysqli_fetch_assoc($query_transaksi)) {
    $transaksi[] = $row;
}

// Total kas dan penggunaan dana
$query_total_kas = mysqli_query($conn, "SELECT COALESCE(SUM(nominal), 0) AS total_kas FROM transaksi_jumpitan WHERE status = 'bayar'");
$total_kas = mysqli_fetch_assoc($query_total_kas)['total_kas'];

$query_total_pengeluaran = mysqli_query($conn, "SELECT COALESCE(SUM(nominal), 0) AS total_pengeluaran FROM pengeluaran");
$total_pengeluaran = mysqli_fetch_assoc($query_total_pengeluaran)['total_pengeluaran'];

$saldo_kas = intval($total_kas) - intval($total_pengeluaran);

$query_pengeluaran = mysqli_query($conn, "SELECT id, tanggal, nominal, keterangan, nota_path FROM pengeluaran ORDER BY tanggal DESC LIMIT 20");
$pengeluaran = [];
while ($row = mysqli_fetch_assoc($query_pengeluaran)) {
    $pengeluaran[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => [
        'warga' => $warga,
        'summary' => [
            'jumlah_bayar' => intval($summary['jumlah_bayar']),
            'total_bayar' => intval($summary['total_bayar']),
            'total_kas' => intval($total_kas),
            'total_pengeluaran' => intval($total_pengeluaran),
            'saldo_kas' => intval($saldo_kas)
        ],
        'summary_warga' => [
            'jumlah_bayar' => intval($summary['jumlah_bayar']),
            'total_bayar' => intval($summary['total_bayar'])
        ],
        'summary_kas' => [
            'total_kas' => intval($total_kas),
            'total_pengeluaran' => intval($total_pengeluaran),
            'saldo_kas' => intval($saldo_kas)
        ],
        'transaksi' => $transaksi,
        'pengeluaran' => $pengeluaran
    ]
]);

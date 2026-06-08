<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/koneksi.php';

$petugas_id = isset($_GET['petugas_id']) ? intval($_GET['petugas_id']) : 0;

if ($petugas_id === 0) {
    echo json_encode(["status" => "error", "message" => "Petugas ID tidak valid"]);
    exit;
}

$query_total = mysqli_query(
    $conn,
    "SELECT SUM(nominal) as total FROM transaksi_jumpitan 
     WHERE DATE(tanggal) = CURDATE() AND petugas_id = $petugas_id"
);
$total_today = mysqli_fetch_assoc($query_total)['total'] ?? 0;

$query_count = mysqli_query(
    $conn,
    "SELECT COUNT(*) as jumlah FROM transaksi_jumpitan 
     WHERE DATE(tanggal) = CURDATE() AND petugas_id = $petugas_id"
);
$count_today = mysqli_fetch_assoc($query_count)['jumlah'] ?? 0;

echo json_encode([
    "status" => "success",
    "data" => [
        "total_nominal" => (int)$total_today,
        "total_warga"   => (int)$count_today
    ]
]);

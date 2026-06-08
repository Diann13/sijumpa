<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/koneksi.php';

$petugas_id = isset($_GET['petugas_id']) ? intval($_GET['petugas_id']) : 0;

if ($petugas_id === 0) {
    echo json_encode(["success" => false, "message" => "Petugas ID tidak valid"]);
    exit;
}

$q_warga = mysqli_query($conn, "SELECT COUNT(*) as total FROM warga");
$total_warga = mysqli_fetch_assoc($q_warga)['total'];

$q_today = mysqli_query(
    $conn,
    "SELECT SUM(nominal) as total FROM transaksi_jumpitan 
     WHERE DATE(tanggal) = CURDATE() AND petugas_id = $petugas_id"
);
$income_today = mysqli_fetch_assoc($q_today)['total'] ?? 0;

$q_month = mysqli_query(
    $conn,
    "SELECT SUM(nominal) as total FROM transaksi_jumpitan 
     WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE()) AND petugas_id = $petugas_id"
);
$income_month = mysqli_fetch_assoc($q_month)['total'] ?? 0;

$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $label = date('D', strtotime("-{$i} days"));
    $q_chart = mysqli_query(
        $conn,
        "SELECT SUM(nominal) as total FROM transaksi_jumpitan 
         WHERE DATE(tanggal) = CURDATE() - INTERVAL $i DAY AND petugas_id = $petugas_id"
    );
    $total = mysqli_fetch_assoc($q_chart)['total'] ?? 0;
    $chart_data[] = ["label" => $label, "value" => (int)$total];
}

echo json_encode([
    "success" => true,
    "data" => [
        "total_warga"   => (int)$total_warga,
        "income_today"  => (int)$income_today,
        "income_month"  => (int)$income_month,
        "chart"         => $chart_data
    ]
]);

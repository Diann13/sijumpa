<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../config/koneksi.php';

$warga_id = isset($_GET['warga_id']) ? (int)$_GET['warga_id'] : 0;

if ($warga_id == 0) {
    echo json_encode(["status" => "error", "message" => "Invalid ID"]);
    exit;
}

$query = mysqli_query(
    $conn,
    "SELECT status FROM transaksi_jumpitan 
     WHERE warga_id = $warga_id 
     AND DATE(tanggal) = CURDATE() 
     ORDER BY tanggal DESC 
     LIMIT 1"
);

if (mysqli_num_rows($query) > 0) {
    $row = mysqli_fetch_assoc($query);
    echo json_encode([
        "status" => "success",
        "has_record" => true,
        "visited" => true,
        "payment_status" => $row['status'] // 'bayar' atau 'belum'
    ]);
} else {
    echo json_encode([
        "status" => "success",
        "has_record" => false,
        "payment_status" => "none"
    ]);
}

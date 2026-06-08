<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/koneksi.php';

$petugas_id = isset($_GET['petugas_id']) ? intval($_GET['petugas_id']) : 0;

if ($petugas_id === 0) {
    echo json_encode(["success" => false, "message" => "Petugas ID tidak valid"]);
    exit;
}

$sql = "
    SELECT t.*, w.nama as nama_warga 
    FROM transaksi_jumpitan t 
    LEFT JOIN warga w ON t.warga_id = w.id 
    WHERE t.petugas_id = $petugas_id
    ORDER BY t.tanggal DESC
";

$query = mysqli_query($conn, $sql);

$transaksi = [];
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $transaksi[] = $row;
    }
}

echo json_encode([
    "success" => true,
    "data" => $transaksi
]);
?>

<?php
require_once '../config/koneksi.php';

$query = mysqli_query($conn, "SELECT petugas_id, COUNT(*) as total FROM transaksi_jumpitan GROUP BY petugas_id");
$data = [];
while($row = mysqli_fetch_assoc($query)) {
    $data[] = $row;
}

echo json_encode($data, JSON_PRETTY_PRINT);
?>

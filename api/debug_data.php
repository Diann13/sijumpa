<?php
require_once '../config/koneksi.php';

$query = mysqli_query($conn, "SELECT * FROM transaksi_jumpitan LIMIT 20");
$data = [];
while($row = mysqli_fetch_assoc($query)) {
    $data[] = $row;
}

echo json_encode($data, JSON_PRETTY_PRINT);
?>

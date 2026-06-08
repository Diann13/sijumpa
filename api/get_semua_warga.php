<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/koneksi.php';

$query = mysqli_query($conn, "SELECT * FROM warga ORDER BY nama ASC");
$warga = [];

while ($row = mysqli_fetch_assoc($query)) {
    $warga[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $warga
]);
?>

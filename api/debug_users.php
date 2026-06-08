<?php
require_once '../config/koneksi.php';
$query = mysqli_query($conn, "SELECT id, username, nama FROM users");
$data = [];
while($row = mysqli_fetch_assoc($query)) { $data[] = $row; }
echo json_encode($data, JSON_PRETTY_PRINT);
?>

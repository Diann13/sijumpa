<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/koneksi.php';

// Hapus otomatis jadwal yang tanggalnya sudah lewat dari hari ini (kemarin atau sebelumnya)
mysqli_query($conn, "DELETE FROM jadwal WHERE tanggal < CURDATE()");

if (isset($_GET['petugas_id'])) {
    $petugas_id = (int)$_GET['petugas_id'];
    
    // Ambil jadwal yang akan datang atau hari ini
    $today = date('Y-m-d');
    $query = mysqli_query($conn, "
        SELECT * FROM jadwal 
        WHERE petugas_id = $petugas_id 
        AND tanggal >= '$today'
        ORDER BY tanggal ASC
    ");
    
    $jadwal = [];
    while ($row = mysqli_fetch_assoc($query)) {
        $jadwal[] = $row;
    }
    
    echo json_encode([
        "success" => true,
        "data" => $jadwal
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Petugas ID tidak ditemukan"
    ]);
}
?>

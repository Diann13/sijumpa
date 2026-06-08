<?php

header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "db_sijumpa");

if ($conn->connect_error) {
    die(json_encode([
        "success" => false,
        "message" => "Koneksi gagal"
    ]));
}

$qr = $_GET['qr_code'];

$query = "SELECT * FROM warga WHERE qr_code='$qr' LIMIT 1";
$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) > 0){

    $data = mysqli_fetch_assoc($result);

    // Ambil harga jumpitan dari pengaturan
    $q_harga = mysqli_query($conn, "SELECT nilai FROM pengaturan WHERE kunci = 'harga_jumpitan' LIMIT 1");
    $harga = 2000; // default
    if ($q_harga && mysqli_num_rows($q_harga) > 0) {
        $r_harga = mysqli_fetch_assoc($q_harga);
        $harga = intval($r_harga['nilai']);
    }
    
    // Inject harga_jumpitan ke data warga agar aplikasi flutter bisa mengambil nominalnya secara dinamis
    $data['harga_jumpitan'] = $harga;

    echo json_encode([
        "success" => true,
        "data" => $data
    ]);

}else{

    echo json_encode([
        "success" => false,
        "message" => "Warga tidak ditemukan"
    ]);
}
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../config/koneksi.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id) && (!empty($data->username) || !empty($data->password))) {
    $id = (int)$data->id;
    $username = $data->username;
    $password = $data->password;
    
    $updateFields = [];
    if (!empty($username)) {
        // Cek apakah username sudah dipakai orang lain
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username' AND id != $id");
        if (mysqli_num_rows($check) > 0) {
            echo json_encode([
                "success" => false,
                "message" => "Username sudah digunakan"
            ]);
            exit;
        }
        $updateFields[] = "username = '$username'";
    }
    
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updateFields[] = "password = '$hashedPassword'";
    }
    
    $queryStr = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = $id";
    
    if (mysqli_query($conn, $queryStr)) {
        // Ambil data terbaru
        $res = mysqli_query($conn, "SELECT id, nama, username, role FROM users WHERE id = $id");
        $user = mysqli_fetch_assoc($res);
        
        echo json_encode([
            "success" => true,
            "message" => "Profil berhasil diperbarui",
            "data" => $user
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Gagal memperbarui profil"
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Data tidak lengkap"
    ]);
}
?>

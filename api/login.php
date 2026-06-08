<?php

header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "db_sijumpa");

if ($conn->connect_error) {
    die(json_encode([
        "success" => false,
        "message" => "Koneksi database gagal"
    ]));
}

$username = $_POST['username'];
$password = $_POST['password'];

$query = "SELECT * FROM users WHERE username='$username' LIMIT 1";

$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) > 0){

    $user = mysqli_fetch_assoc($result);

    if ($user['role'] !== 'petugas') {
        echo json_encode([
            "success" => false,
            "message" => "Akses ditolak. Aplikasi ini hanya untuk petugas."
        ]);
        exit;
    }

    if (password_verify($password, $user['password']) || $password == $user['password']) {

        echo json_encode([
            "success" => true,
            "message" => "Login berhasil",
            "data" => [
                "id" => $user['id'],
                "nama" => $user['nama'],
                "username" => $user['username'],
                "role" => $user['role']
            ]
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Password salah"
        ]);
    }

} else {

    echo json_encode([
        "success" => false,
        "message" => "Username tidak ditemukan"
    ]);
}
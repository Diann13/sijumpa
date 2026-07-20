<?php
$host = "localhost";
$user = "trplbmyi_sijumpamu";
$pass = "si_JUMPA123";
$db   = "trplbmyi_Akademik_B24011";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

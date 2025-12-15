<?php
include '../db.php';
header('Content-Type: application/json');


$type = $_GET['type'] ?? null;
$value = trim($_GET['value'] ?? "");

$response = ["valid" => true, "message" => ""];


if ($type === "nis" && $value !== "") {
    $cek = $conn->prepare("SELECT id_siswa FROM siswa WHERE nis = ?");
    $cek->bind_param("s", $value);
    $cek->execute();
    $cek->store_result();

    if ($cek->num_rows > 0) {
        $response = ["valid" => false, "message" => "NIS sudah terdaftar"];
    }
    $cek->close();
}


elseif ($type === "telepon" && $value !== "") {
    if (!preg_match("/^[0-9]{10,15}$/", $value)) {
        $response = ["valid" => false, "message" => "Nomor telepon harus angka (10-15 digit)"];
    }
}


elseif ($type === "nama" && $value !== "") {
    if (!preg_match("/^[a-zA-Z\s\.-]+$/u", $value)) {
        $response = ["valid" => false, "message" => "Nama hanya boleh huruf, spasi, titik (.) atau tanda hubung (-)"];
    }
}

echo json_encode($response);

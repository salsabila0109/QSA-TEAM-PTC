<?php
header("Content-Type: application/json");
include "db.php";

// pastikan ada id_siswa dikirim
$id_siswa = isset($_GET['id_siswa']) ? $_GET['id_siswa'] : '';

if ($id_siswa == '') {
    echo json_encode(["status" => "error", "message" => "ID siswa tidak ditemukan"]);
    exit;
}

// Ambil data notifikasi berdasarkan id siswa
$query = "SELECT 
            id_notif,
            id_siswa,
            nama_siswa,
            nama_mata_pelajaran,
            DATE_FORMAT(tanggal, '%d-%m-%Y') AS tanggal,
            TIME_FORMAT(jam, '%H:%i:%s') AS jam,
            status
          FROM notifikasi 
          WHERE id_siswa = '$id_siswa'
          ORDER BY tanggal DESC, jam DESC";

$result = $conn->query($query);

if (!$result) {
    echo json_encode(["status" => "error", "message" => "Query error: " . $conn->error]);
    exit;
}

$notifikasi = [];
while ($row = $result->fetch_assoc()) {
    $notifikasi[] = $row;
}

// Keluarkan hasil dalam format JSON
echo json_encode([
    "status" => "success",
    "data" => $notifikasi
]);

$conn->close();
?>

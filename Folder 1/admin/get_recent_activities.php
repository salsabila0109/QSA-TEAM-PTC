<?php
include '../db.php';

$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$data = [];

$q = "
    SELECT s.id_siswa, s.nama_siswa, a.status, a.waktu_absensi_tercatat
    FROM absensi_siswa a
    JOIN siswa s ON s.id_siswa = a.id_siswa
    WHERE DATE(a.waktu_absensi_tercatat) = '$tanggal'
    ORDER BY a.waktu_absensi_tercatat DESC
";

$res = $conn->query($q);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $data[] = [
            'id_siswa' => $row['id_siswa'],
            'name' => $row['nama_siswa'],
            'time' => date('H:i:s', strtotime($row['waktu_absensi_tercatat'])),
            'status' => ucfirst($row['status'])
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($data);
?>

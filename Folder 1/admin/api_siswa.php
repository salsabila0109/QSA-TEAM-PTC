<?php
// api_siswa.php
// Mengembalikan data siswa + kelas dalam bentuk JSON

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan hanya admin yang boleh akses
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Akses ditolak: bukan admin.'
    ]);
    exit;
}

// Koneksi ke database
require_once __DIR__ . '/../db.php';

// Query ambil data siswa + nama kelas
$sql = "
    SELECT 
        siswa.id_siswa,
        siswa.nis,
        siswa.nama_siswa,
        siswa.nomor_telepon_orangtua,
        kelas.nama_kelas
    FROM siswa
    LEFT JOIN kelas ON siswa.id_kelas = kelas.id_kelas
    ORDER BY siswa.id_siswa DESC
";

$result = $conn->query($sql);

header('Content-Type: application/json; charset=utf-8');

if (!$result) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Query error: ' . $conn->error
    ]);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id_siswa'               => $row['id_siswa'],
        'nis'                    => $row['nis'],
        'nama_siswa'             => $row['nama_siswa'],
        'nama_kelas'             => $row['nama_kelas'],
        'nomor_telepon_orangtua' => $row['nomor_telepon_orangtua'],
    ];
}

echo json_encode([
    'success' => true,
    'data'    => $data
]);

<?php
session_start();


$API_KEY = "PRESENTECH_KNN_2025";
$role = strtolower(trim($_SESSION['role_pengguna'] ?? ''));
$keyQ = trim($_GET['key'] ?? '');

if ($role !== 'admin' && $keyQ !== $API_KEY) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Akses ditolak';
    exit;
}

// koneksi DB
require_once __DIR__ . '/../db.php';

// ===========================
// Tentukan semester aktif
// ===========================
$bulan     = (int) date('n');
$semester  = in_array($bulan, [7, 8, 9, 10, 11, 12]) ? 'Ganjil' : 'Genap';
$tahunNow  = (int) date('Y');
$tahunAjar = ($semester === 'Ganjil') ? $tahunNow : $tahunNow - 1;

// ===========================
// Ambil data hasil prediksi
// ===========================
$sql = "
    SELECT id_siswa, nama_siswa, jumlah_hadir, status, semester, tanggal_hasil_diproses
    FROM hasil_sistem_cerdas
    WHERE semester = ?
    ORDER BY nama_siswa ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $semester);
$stmt->execute();
$res = $stmt->get_result();

// Header untuk file CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=hasil_knn_' . $semester . '_' . $tahunAjar . '.csv');

$output = fopen('php://output', 'w');

// Header kolom
fputcsv($output, [
    'No',
    'ID Siswa',
    'Nama Siswa',
    'Jumlah Hadir',
    'Status Prediksi',
    'Semester',
    'Tanggal Diproses'
]);

$no = 1;
while ($row = $res->fetch_assoc()) {
    fputcsv($output, [
        $no++,
        $row['id_siswa'],
        $row['nama_siswa'],
        $row['jumlah_hadir'],
        $row['status'],
        $row['semester'],
        $row['tanggal_hasil_diproses']
    ]);
}

fclose($output);
exit;

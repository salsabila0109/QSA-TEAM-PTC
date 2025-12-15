<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/services/KNNAbsensi.php';

$semester = $_GET['semester'] ?? 'Ganjil';
$tahunAjar = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$idKelas = (isset($_GET['kelas']) && $_GET['kelas'] !== '') ? (int)$_GET['kelas'] : null;

$rows = KNNAbsensi::getHadirCountsForSemester($conn, $semester, $tahunAjar, $idKelas);

header('Content-Type: text/csv; charset=utf-8');
$fname = "knn_absensi_{$semester}_{$tahunAjar}".($idKelas?("_kelas_{$idKelas}"):'').".csv";
header("Content-Disposition: attachment; filename=\"$fname\"");

$out = fopen('php://output', 'w');
fputcsv($out, ['ID Siswa', 'Nama Siswa', 'Jumlah Hadir (semester)']);
foreach ($rows as $r) {
    fputcsv($out, [ $r['id_siswa'], $r['nama_siswa'], (int)$r['jml_hadir'] ]);
}
fclose($out);
exit;

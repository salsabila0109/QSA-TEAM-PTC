<?php
session_start();

// pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// koneksi ke database (naik satu folder ke atas)
require_once __DIR__ . '/../db.php';


$possible = [
    __DIR__ . '/KNNAbsensi.php',
    __DIR__ . '/services/KNNAbsensi.php',
    __DIR__ . '/../services/KNNAbsensi.php',
];
$knnLoaded = false;
foreach ($possible as $p) {
    if (file_exists($p)) {
        require_once $p;
        $knnLoaded = true;
        break;
    }
}
if (!$knnLoaded || !class_exists('KNNAbsensi')) {
    die("❌ File KNNAbsensi.php tidak ditemukan atau class KNNAbsensi tidak tersedia.");
}

// ambil parameter dari URL
$semester = $_GET['semester'] ?? 'Ganjil';
$tahunAjar = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$idKelas = (isset($_GET['kelas']) && $_GET['kelas'] !== '') ? (int)$_GET['kelas'] : null;

// ambil data hadir dari fungsi KNNAbsensi
$rows = KNNAbsensi::getHadirCountsForSemester($conn, $semester, $tahunAjar, $idKelas);

// header supaya browser langsung download file CSV
header('Content-Type: text/csv; charset=utf-8');
$fname = "knn_absensi_{$semester}_{$tahunAjar}" . ($idKelas ? "_kelas_{$idKelas}" : '') . ".csv";
header("Content-Disposition: attachment; filename=\"$fname\"");

// tulis data ke output CSV
$out = fopen('php://output', 'w');
fputcsv($out, ['ID Siswa', 'Nama Siswa', 'Jumlah Hadir (semester)', 'Status', 'Semester', 'Tanggal Diproses']);

// ambil juga data hasil prediksi dari tabel hasil_sistem_cerdas biar CSV-nya lengkap
$sql = $conn->prepare("
    SELECT id_siswa, nama_siswa, jumlah_hadir, status, semester, tanggal_hasil_diproses
    FROM hasil_sistem_cerdas
    WHERE semester = ?
    ORDER BY nama_siswa ASC
");
$sql->bind_param("s", $semester);
$sql->execute();
$result = $sql->get_result();

if ($result && $result->num_rows > 0) {
    while ($r = $result->fetch_assoc()) {
        fputcsv($out, [
            $r['id_siswa'],
            $r['nama_siswa'],
            (int)$r['jumlah_hadir'],
            $r['status'],
            $r['semester'],
            $r['tanggal_hasil_diproses']
        ]);
    }
} else {
    // fallback: jika tabel hasil_sistem_cerdas kosong, ambil dari hasil KNN
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id_siswa'],
            $r['nama_siswa'],
            (int)$r['jml_hadir'],
            '-', '-', '-'
        ]);
    }
}

fclose($out);
exit;
?>

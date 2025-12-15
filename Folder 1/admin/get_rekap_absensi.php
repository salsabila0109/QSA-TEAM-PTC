<?php
include '../db.php';

// Ambil tanggal dari parameter (default: hari ini)
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Ambil total siswa dari tabel siswa
$total_query = "SELECT COUNT(*) AS total_siswa FROM siswa";
$total_result = $conn->query($total_query);
$total_row = $total_result->fetch_assoc();
$total_siswa = (int)$total_row['total_siswa'];

// Query untuk menghitung jumlah hadir, izin, sakit, alpa
$query = "
    SELECT status, COUNT(*) AS jumlah
    FROM absensi_siswa
    WHERE DATE(waktu_absensi_tercatat) = ?
    GROUP BY status
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $tanggal);
$stmt->execute();
$result = $stmt->get_result();

$rekap = [
    'hadir' => 0,
    'izin' => 0,
    'sakit' => 0,
    'alpa' => 0,
    'total_siswa' => $total_siswa,
    'persen_hadir' => 0
];

// Masukkan hasil query ke array rekap
while ($row = $result->fetch_assoc()) {
    $status = strtolower($row['status']);
    if (isset($rekap[$status])) {
        $rekap[$status] = (int)$row['jumlah'];
    }
}

// Hitung persentase kehadiran (jika total_siswa > 0)
if ($total_siswa > 0) {
    $rekap['persen_hadir'] = round(($rekap['hadir'] / $total_siswa) * 100, 1);
}

// Kembalikan hasil dalam format JSON
header('Content-Type: application/json');
echo json_encode($rekap);
?>

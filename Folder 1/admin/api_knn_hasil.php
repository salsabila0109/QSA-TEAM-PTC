<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$API_KEY = "PRESENTECH_KNN_2025";
$role   = strtolower(trim($_SESSION['role_pengguna'] ?? ''));
$hdrKey = trim($_SERVER['HTTP_X_API_KEY'] ?? '');

if ($role !== 'admin' && $hdrKey !== $API_KEY) {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
  exit;
}

require_once __DIR__ . '/../db.php';

// semester aktif
$bulan     = (int) date('n');
$semester  = in_array($bulan, [7,8,9,10,11,12]) ? 'Ganjil' : 'Genap';
$tahunNow  = (int) date('Y');
$tahunAjar = ($semester === 'Ganjil') ? $tahunNow : $tahunNow - 1;
$semesterLabel = $semester . ' ' . $tahunAjar . '/' . ($tahunAjar + 1);

$sql = "
  SELECT id_siswa, nama_siswa, jumlah_hadir, status, semester, tanggal_hasil_diproses
  FROM hasil_sistem_cerdas
  WHERE semester = ?
  ORDER BY nama_siswa ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Gagal prepare query: ' . $conn->error]);
  exit;
}

$stmt->bind_param("s", $semester);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
$lastProcessed = null;

while ($row = $res->fetch_assoc()) {
  $rows[] = $row;
  if (!empty($row['tanggal_hasil_diproses'])) {
    if ($lastProcessed === null || $row['tanggal_hasil_diproses'] > $lastProcessed) {
      $lastProcessed = $row['tanggal_hasil_diproses'];
    }
  }
}
$stmt->close();

echo json_encode([
  'success' => true,
  'semester_label' => $semesterLabel,
  'last_processed' => $lastProcessed ? substr($lastProcessed, 0, 10) : null,
  'rows' => $rows
]);

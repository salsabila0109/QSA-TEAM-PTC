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
require_once __DIR__ . '/kNNAbsensi.php'; // pastikan path benar

// ====== Baca JSON dari body ======
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Body harus JSON.']);
  exit;
}

$students = $data['students'] ?? [];      // [{id_siswa:"202543", nama_siswa:"Budi"}, ...]
$counts   = $data['hadir_counts'] ?? [];  // {"202543": 12, ...}

if (!is_array($students) || count($students) === 0) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'students kosong.']);
  exit;
}
if (!is_array($counts)) $counts = [];

// ====== Semester aktif (SERVER yang tentukan biar konsisten) ======
$bulan     = (int) date('n');
$semester  = in_array($bulan, [7,8,9,10,11,12]) ? 'Ganjil' : 'Genap';
$tahunNow  = (int) date('Y');
$tahunAjar = ($semester === 'Ganjil') ? $tahunNow : $tahunNow - 1;
$semesterLabel = $semester . ' ' . $tahunAjar . '/' . ($tahunAjar + 1);

$knn = new KNNAbsensi(5);
$tanggalProses = date('Y-m-d H:i:s');

// Upsert (wajib: id_siswa di table harus UNIQUE/PRIMARY KEY)
$sqlUpsert = "
  INSERT INTO hasil_sistem_cerdas
    (id_siswa, nama_siswa, jumlah_hadir, status, semester, tanggal_hasil_diproses)
  VALUES
    (?, ?, ?, ?, ?, ?)
  ON DUPLICATE KEY UPDATE
    nama_siswa = VALUES(nama_siswa),
    jumlah_hadir = VALUES(jumlah_hadir),
    status = VALUES(status),
    semester = VALUES(semester),
    tanggal_hasil_diproses = VALUES(tanggal_hasil_diproses)
";

$stmt = $conn->prepare($sqlUpsert);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Gagal prepare upsert: ' . $conn->error]);
  exit;
}

$count = 0;

foreach ($students as $s) {
  $idSiswa = (string)($s['id_siswa'] ?? '');
  if ($idSiswa === '') continue;

  $namaSiswa = (string)($s['nama_siswa'] ?? ('Siswa ' . $idSiswa));
  $jmlHadir  = (int)($counts[$idSiswa] ?? 0);

  $prediksi = $knn->predictOne([$jmlHadir]);

  // jika id_siswa di DB tipe INT, cast ke int:
  $idInt = (int)$idSiswa;

  $stmt->bind_param("isisss", $idInt, $namaSiswa, $jmlHadir, $prediksi, $semester, $tanggalProses);
  $stmt->execute();
  $count++;
}

$stmt->close();

echo json_encode([
  'success' => true,
  'message' => "✅ Berhasil memproses {$count} prediksi KNN ({$semesterLabel})",
  'semester_label' => $semesterLabel,
  'last_processed' => substr($tanggalProses, 0, 10)
]);

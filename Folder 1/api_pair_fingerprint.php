<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';


if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
  http_response_code(403);
  echo json_encode(["success" => false, "message" => "Akses ditolak. Hanya admin."]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(["success" => false, "message" => "Method harus POST."]);
  exit;
}

$fingerId = trim($_POST['finger_id'] ?? '');
$idSiswa  = trim($_POST['id_siswa'] ?? '');

if ($fingerId === '' || !ctype_digit($fingerId) || $idSiswa === '' || !ctype_digit($idSiswa)) {
  echo json_encode(["success" => false, "message" => "finger_id dan id_siswa wajib angka."]);
  exit;
}

$conn->begin_transaction();

try {
  // Pastikan siswa ada
  $stmt = $conn->prepare("SELECT id_siswa FROM siswa WHERE id_siswa = ? LIMIT 1");
  $stmt->bind_param("i", $idSiswa);
  $stmt->execute();
  $exists = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$exists) {
    throw new Exception("Siswa tidak ditemukan.");
  }

  // Pastikan finger_id belum dipakai siswa lain
  $stmt = $conn->prepare("SELECT id_siswa FROM siswa WHERE finger_id = ? AND id_siswa <> ? LIMIT 1");
  $stmt->bind_param("ii", $fingerId, $idSiswa);
  $stmt->execute();
  $dup = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($dup) {
    throw new Exception("Finger ID sudah dipakai oleh siswa lain (ID: {$dup['id_siswa']}).");
  }

  // Update pairing
  $stmt = $conn->prepare("UPDATE siswa SET finger_id = ? WHERE id_siswa = ? LIMIT 1");
  $stmt->bind_param("ii", $fingerId, $idSiswa);
  $stmt->execute();
  $stmt->close();

  $conn->commit();

  echo json_encode([
    "success" => true,
    "message" => "Pairing berhasil.",
    "data" => [
      "id_siswa" => (int)$idSiswa,
      "finger_id" => (int)$fingerId
    ]
  ]);
} catch (Exception $e) {
  $conn->rollback();
  echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();

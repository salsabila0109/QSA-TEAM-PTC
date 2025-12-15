<?php
session_start();
require_once __DIR__ . "/../db.php";

header("Content-Type: application/json; charset=utf-8");

// Cek login orangtua
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'orangtua') {
  echo json_encode(["success" => false, "message" => "Unauthorized"]);
  exit;
}

$nama_pengguna = $_SESSION['nama_pengguna'] ?? ($_SESSION['username'] ?? 'Orangtua');
$id_orangtua = $_SESSION['id_pengguna'] ?? ($_SESSION['id_orangtua'] ?? 0);

$anak_id = null;
$nama_siswa = "";
$nama_kelas = ""; // TAMBAHAN

// Ambil id_siswa anak
$stmt_anak = $conn->prepare("SELECT id_siswa FROM orangtua WHERE id_orangtua=?");
$stmt_anak->bind_param("i", $id_orangtua);
$stmt_anak->execute();
$res_anak = $stmt_anak->get_result();

if ($res_anak->num_rows > 0) {
  $row = $res_anak->fetch_assoc();
  $anak_id = $row['id_siswa'];

  // Ambil nama siswa + nama kelas (JOIN)
  // Asumsi: siswa punya kolom id_kelas, dan tabel kelas punya id_kelas & nama_kelas
  $stmt_info = $conn->prepare("
    SELECT s.nama_siswa, k.nama_kelas
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE s.id_siswa = ?
    LIMIT 1
  ");
  $stmt_info->bind_param("i", $anak_id);
  $stmt_info->execute();
  $res_info = $stmt_info->get_result();

  if ($res_info->num_rows > 0) {
    $row_info = $res_info->fetch_assoc();
    $nama_siswa = $row_info['nama_siswa'] ?? "";
    $nama_kelas = $row_info['nama_kelas'] ?? "";
  }
}

echo json_encode([
  "success" => true,
  "nama_pengguna" => $nama_pengguna,
  "id_orangtua" => $id_orangtua,
  "anak_id" => $anak_id,
  "nama_siswa" => $nama_siswa,
  "nama_kelas" => $nama_kelas // TAMBAHAN
]);

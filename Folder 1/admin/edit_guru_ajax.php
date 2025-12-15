<?php
session_start();

if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    http_response_code(403);
    exit('❌ Unauthorized');
}

include '../db.php';

$id_guru = $_POST['id_guru']     ?? '';
$nip     = $_POST['nip']         ?? '';
$nama    = $_POST['nama_guru']   ?? '';
$telp    = $_POST['no_telepon']  ?? '';

if ($id_guru === '') {
    http_response_code(400);
    exit('❌ ID guru wajib.');
}

// Rapikan value
$id_guru = (int)$id_guru;
$nip     = trim($nip);
$nama    = trim($nama);
$telp    = trim($telp);

// ==============================
// 1) Cek apakah NIP sudah dipakai guru lain
// ==============================
$cekSql = "SELECT COUNT(*) AS jml 
           FROM guru 
           WHERE nip = ? AND id_guru <> ?";
$cekStmt = $conn->prepare($cekSql);
if (!$cekStmt) {
    http_response_code(500);
    exit('❌ Gagal menyiapkan query cek NIP: ' . $conn->error);
}
$cekStmt->bind_param("si", $nip, $id_guru);
$cekStmt->execute();
$cekResult = $cekStmt->get_result();
$rowCek = $cekResult->fetch_assoc();
$cekStmt->close();

if ($rowCek && $rowCek['jml'] > 0) {
    http_response_code(409); // conflict
    exit("❌ Gagal: NIP '$nip' sudah digunakan oleh guru lain. Gunakan NIP yang berbeda.");
}

// ==============================
// 2) Lanjut UPDATE kalau tidak duplikat
// ==============================
$stmt = $conn->prepare("
    UPDATE guru 
    SET nip = ?, 
        nama_guru = ?, 
        no_telepon = ?, 
        tanggal_diperbarui = NOW()
    WHERE id_guru = ?
");
if (!$stmt) {
    http_response_code(500);
    exit('❌ Gagal menyiapkan query update: ' . $conn->error);
}

$stmt->bind_param("sssi", $nip, $nama, $telp, $id_guru);

if ($stmt->execute()) {
    echo "✅ Data guru berhasil diperbarui.";
} else {
    http_response_code(500);
    echo "❌ Gagal memperbarui data: " . $stmt->error;
}

$stmt->close();

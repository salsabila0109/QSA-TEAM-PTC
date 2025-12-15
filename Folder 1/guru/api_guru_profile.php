<?php
// guru/api_guru_profile.php
// Mengembalikan profil singkat guru (nama + url foto) dalam bentuk JSON

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Pastikan yang akses adalah guru yang sudah login
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'guru') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Tidak ada sesi guru yang aktif.'
    ]);
    exit;
}

require_once __DIR__ . '/../db.php';

$id_guru = $_SESSION['id_pengguna'] ?? 0;
if (!$id_guru) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID guru tidak valid.'
    ]);
    exit;
}

// Ambil nama + foto dari tabel guru
$stmt = $conn->prepare("SELECT nama_guru, foto FROM guru WHERE id_guru = ?");
$stmt->bind_param("i", $id_guru);
$stmt->execute();
$stmt->bind_result($nama_guru, $foto);
$found = $stmt->fetch();
$stmt->close();

if (!$found) {
    echo json_encode([
        'success' => false,
        'message' => 'Data guru tidak ditemukan.'
    ]);
    exit;
}

// Buat URL foto publik (sama konsepnya seperti di profil_guru.php)
if ($foto && $foto !== '') {
    $foto_url = '../uploads/guru/' . rawurlencode($foto);
} else {
    $foto_url = '../uploads/guru/default_avatar.png';
}

echo json_encode([
    'success'   => true,
    'nama_guru' => $nama_guru,
    'foto_url'  => $foto_url
]);

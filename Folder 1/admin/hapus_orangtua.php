<?php
include '../db.php';
session_start();

// Cek login admin
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Ambil ID dari request
$id = $_POST['id'] ?? '';
if (!$id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID tidak ditemukan.']);
    exit;
}

// Hapus data orang tua
$stmt = $conn->prepare("DELETE FROM orangtua WHERE id_orangtua = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data.']);
}

$stmt->close();
$conn->close();
?>

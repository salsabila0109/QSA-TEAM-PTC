<?php
session_start();
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    http_response_code(403); exit('Unauthorized');
}
include '../db.php';

$id = $_GET['id'] ?? '';
if ($id === '') { http_response_code(400); exit('ID guru wajib.'); }

$stmt = $conn->prepare("DELETE FROM guru WHERE id_guru = ?");
$stmt->bind_param("i", $id);
echo $stmt->execute() ? "✅ Data guru dihapus." : "❌ Gagal menghapus data.";
$stmt->close();

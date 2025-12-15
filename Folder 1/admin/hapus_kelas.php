<?php
include '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kelas = $_POST['id_kelas'] ?? '';

    if ($id_kelas) {
        // Menggunakan prepared statement untuk keamanan
        $stmt = $conn->prepare("DELETE FROM kelas WHERE id_kelas = ?");
        $stmt->bind_param("s", $id_kelas);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Gagal menghapus: ' . $conn->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'ID kelas tidak ditemukan']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Request harus POST']);
}

<?php
include '../db.php';

// Pastikan semua data terkirim
if (
    isset($_POST['id_siswa']) &&
    isset($_POST['id_mapel']) &&
    isset($_POST['tanggal']) &&
    isset($_POST['status'])
) {
    $id_siswa = (int)$_POST['id_siswa'];
    $id_mapel = (int)$_POST['id_mapel'];
    $tanggal = $_POST['tanggal'];
    $status = $_POST['status'];

    // Cek apakah sudah ada data absensi untuk tanggal & mapel tersebut
    $cek = $conn->prepare("
        SELECT id_absensi FROM absensi_siswa 
        WHERE id_siswa = ? 
        AND id_mata_pelajaran = ? 
        AND DATE(waktu_absensi_tercatat) = ?
    ");
    $cek->bind_param('iis', $id_siswa, $id_mapel, $tanggal);
    $cek->execute();
    $hasil = $cek->get_result();

    if ($hasil->num_rows > 0) {
        // Jika ada, update status-nya
        $row = $hasil->fetch_assoc();
        $update = $conn->prepare("
            UPDATE absensi_siswa 
            SET status = ? 
            WHERE id_absensi = ?
        ");
        $update->bind_param('si', $status, $row['id_absensi']);
        $update->execute();
        echo "✅ Status berhasil diperbarui.";
    } else {
        // Jika belum ada, insert baru
        $insert = $conn->prepare("
            INSERT INTO absensi_siswa (id_siswa, id_mata_pelajaran, waktu_absensi_tercatat, status)
            VALUES (?, ?, NOW(), ?)
        ");
        $insert->bind_param('iis', $id_siswa, $id_mapel, $status);
        $insert->execute();
        echo "✅ Data absensi baru berhasil ditambahkan.";
    }
} else {
    echo "❌ Data tidak lengkap.";
}
?>

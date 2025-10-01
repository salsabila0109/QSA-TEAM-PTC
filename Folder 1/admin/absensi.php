<?php
session_start();
include '../db.php';

if ($_SESSION['role_pengguna'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$result = $conn->query("SELECT a.*, s.nama_siswa, s.kelas 
                        FROM absensi a 
                        JOIN siswa s ON a.id_siswa = s.id_siswa 
                        ORDER BY a.tanggal DESC, a.waktu DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pemantauan Kehadiran</title>
    <link rel="stylesheet" href="absensi.css">
</head>
<body>
<div class="dashboard-container">
    <h2>Data Kehadiran Siswa</h2>
    <table>
        <tr>
            <th>Nama</th>
            <th>Kelas</th>
            <th>Mata Pelajaran</th>
            <th>Tanggal</th>
            <th>Waktu</th>
            <th>Status</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['nama_siswa'] ?></td>
            <td><?= $row['kelas'] ?></td>
            <td><?= $row['mata_pelajaran'] ?></td>
            <td><?= $row['tanggal'] ?></td>
            <td><?= $row['waktu'] ?></td>
            <td><?= $row['status'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <br>
    <a href="export_excel.php" class="btn btn-export">Export ke Excel</a>
</div>
</body>
</html>

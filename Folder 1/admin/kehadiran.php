<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';


if ($_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}


$result = $conn->query("
    SELECT 
        a.id_absensi,
        s.nama_siswa,
        k.nama_kelas,
        a.waktu_absensi_tercatat AS tanggal_waktu,
        a.status
    FROM absensi_siswa a
    JOIN siswa s ON a.id_siswa = s.id_siswa
    JOIN kelas k ON s.id_kelas = k.id_kelas
    ORDER BY a.waktu_absensi_tercatat DESC
");


if(!$result){
    die("Error query: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kehadiran Siswa</title>
    <link rel="stylesheet" href="kehadiran.css">
</head>
<body>
<div class="dashboard-container">
    <h2>Pemantauan Kehadiran Siswa</h2>
    <?php if($result->num_rows > 0): ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>Kelas</th>
            <th>Tanggal & Waktu</th>
            <th>Status</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id_absensi'] ?></td>
            <td><?= $row['nama_siswa'] ?></td>
            <td><?= $row['nama_kelas'] ?></td>
            <td><?= $row['tanggal_waktu'] ?></td>
            <td><?= $row['status'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
        <p>Data kehadiran belum ada.</p>
    <?php endif; ?>
</div>
</body>
</html>

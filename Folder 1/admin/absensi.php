<?php
session_start();
include '../db.php';

// Cek login admin
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// Query data absensi terbaru, ambil nama siswa dari tabel siswa
$result = $conn->query("
    SELECT s.id_siswa, s.kelas, a.id_mata_pelajaran, a.waktu_absensi_tercatat, a.status
    FROM absensi_siswa a
    JOIN siswa s ON a.id_sesi = s.id_sesi
    ORDER BY a.waktu_absensi_tercatat DESC
");

if (!$result) {
    die("Query error: " . $conn->error);
}
?>

<div class="dashboard-container">
    <h2>Data Kehadiran Siswa</h2>
    <table>
        <tr>
            <th>Nama Siswa</th>
            <th>Kelas</th>
            <th>Mata Pelajaran</th>
            <th>Waktu Absensi</th>
            <th>Status</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
            <td><?= htmlspecialchars($row['kelas']) ?></td>
            <td><?= htmlspecialchars($row['id_mata_pelajaran']) ?></td>
            <td><?= htmlspecialchars($row['waktu_absensi_tercatat']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <br>
    <a href="export_excel.php" class="btn-export">Export ke Excel</a>
</div>

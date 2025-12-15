<?php
session_start();
include '../db.php';

// Pastikan hanya guru atau orang tua yang bisa akses
if (!isset($_SESSION['role_pengguna']) || !in_array($_SESSION['role_pengguna'], ['guru','orangtua'])) {
    header("Location: ../login.php");
    exit;
}

// Ambil notifikasi terbaru
$query = "
    SELECT n.id_notifikasi, n.pesan, n.waktu_dikirim, s.nama_siswa
    FROM notifikasi n
    LEFT JOIN siswa s ON n.id_siswa = s.id_siswa
    ORDER BY n.waktu_dikirim DESC
    LIMIT 20
";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifikasi</title>
<link rel="stylesheet" href="notifikasi.css">
</head>
<body>
<div class="sidebar">
    <h2>Menu</h2>
    <?php if($_SESSION['role_pengguna'] === 'guru'): ?>
        <a href="dashboard_guru.php">Dashboard</a>
        <a href="riwayat_absensi.php">Riwayat Absensi</a>
        <a href="data_mapel.php">Data Mata Pelajaran</a>
    <?php endif; ?>
    <a href="../logout.php">Logout</a>
</div>

<div class="content">
    <h1>🔔 Notifikasi</h1>
    <?php
    if ($result->num_rows > 0) {
        echo "<ul class='notifikasi-list'>";
        while ($row = $result->fetch_assoc()) {
            $namaSiswa = $row['nama_siswa'] ?? 'Siswa';
            echo "<li>
                    <span class='pesan'>{$row['pesan']} ({$namaSiswa})</span>
                    <span class='waktu'>{$row['waktu_dikirim']}</span>
                  </li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='no-data'>Belum ada notifikasi.</p>";
    }
    ?>
</div>
</body>
</html>

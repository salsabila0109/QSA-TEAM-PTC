<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';
if ($_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$result = $conn->query("SELECT * FROM siswa");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Siswa</title>
    <!-- Link ke CSS terpisah -->
    <link rel="stylesheet" href="manajemen_data_siswa.css">
    <!-- Font Awesome untuk ikon tombol -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<header>Manajemen Data Siswa</header>

<div class="container">
    <a href="tambah_siswa.php" class="btn btn-tambah">+ Tambah Siswa</a>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>Nomor Ortu</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?= $row['id_siswa'] ?></td>
                <td><?= $row['nis'] ?></td>
                <td><?= $row['nama_siswa'] ?></td>
                <td><?= $row['id_kelas'] ?></td>
                <td><?= $row['nomor_telepon_orangtua'] ?></td>
                <td class="aksi">
                    <a href="edit_siswa.php?id=<?= $row['id_siswa'] ?>" class="btn btn-edit">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    <a href="hapus_siswa.php?id=<?= $row['id_siswa'] ?>" class="btn btn-hapus" onclick="return confirm('Yakin hapus?')">
                        <i class="fa-solid fa-trash"></i>
                    </a>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>

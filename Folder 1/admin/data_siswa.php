<?php
session_start();
include '../db.php';

if ($_SESSION['role_pengguna'] != 'admin') {
    header("Location: ../login.php");
    exit;
}


if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $conn->query("DELETE FROM siswa WHERE id_siswa=$id");
    header("Location: data_siswa.php");
    exit;
}


$result = $conn->query("SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.id_kelas = k.id_kelas ORDER BY s.id_siswa ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manajemen Data Siswa</title>
    <link rel="stylesheet" href="data_siswa.css">
</head>
<body>
<div class="container">
    <h2>Manajemen Data Siswa</h2>
    <a href="tambah_siswa.php" class="btn btn-tambah">+ Tambah Siswa</a>

    <table>
        <tr>
            <th>ID</th>
            <th>NIS</th>
            <th>Nama</th>
            <th>Kelas</th>
            <th>Telepon Orangtua</th>
            <th>Aksi</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id_siswa'] ?></td>
            <td><?= $row['nis'] ?></td>
            <td><?= $row['nama_siswa'] ?></td>
            <td><?= $row['nama_kelas'] ?></td>
            <td><?= $row['nomor_telepon_orangtua'] ?></td>
            <td>
                <a href="edit_siswa.php?id=<?= $row['id_siswa'] ?>" class="btn btn-edit">Edit</a>
                <a href="data_siswa.php?hapus=<?= $row['id_siswa'] ?>" class="btn btn-hapus" onclick="return confirm('Yakin hapus?')">Hapus</a>
            </td>
        </tr>

        <?php endwhile; ?>
    </table>
</div>
</body>
</html>

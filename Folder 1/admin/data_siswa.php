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

// Ambil data siswa beserta id_kelas (bukan nama)
$result = $conn->query("
    SELECT nis, nama_siswa, id_kelas, nomor_telepon_orangtua, id_siswa
    FROM siswa 
    ORDER BY nama_siswa ASC
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manajemen Data Siswa</title>
    <link rel="stylesheet" href="data_siswa.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container">
<div class="top-bar">
    <a href="dashboard_admin.php" class="btn-back"><i class="fa fa-arrow-left"></i></a>
    <h2 class="title-center">Manajemen Data Siswa</h2>
    <a href="tambah_siswa.php" class="btn-tambah"><i class="fa fa-plus"></i> Tambah Siswa</a>
</div>

<!-- 🔹 Tabel Data Siswa -->
<table>
    <tr>
        <th>NIS</th>
        <th>Nama</th>
        <th>Id Kelas</th> <!-- Ubah label jadi Id Kelas -->
        <th>Telepon Orangtua</th>
        <th>Aksi</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['nis'] ?></td>
        <td><?= $row['nama_siswa'] ?></td>
        <td><?= $row['id_kelas'] ?></td> <!-- Tampilkan Id Kelas -->
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

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

// Ambil semua data mapel
$result = $conn->query("SELECT * FROM mata_pelajaran");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Data Mata Pelajaran</title>
    <link rel="stylesheet" href="manajemen_data_mapel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container">
    <h2>Manajemen Data Mata Pelajaran</h2>
    <a href="tambah_mapel.php" class="btn btn-tambah">+ Tambah Mapel</a>

    <table>
        <thead>
            <tr>
                <th>ID Mapel</th>
                <th>Nama Mapel</th>
                <th>Kode Mapel</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0) { ?>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?= $row['id_mata_pelajaran'] ?></td>
                    <td><?= $row['nama_mapel'] ?></td>
                    <td><?= $row['kode_mapel'] ?></td>
                    <td class="aksi">
                        <a href="edit_mapel.php?id=<?= $row['id_mata_pelajaran'] ?>" class="btn btn-edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <a href="hapus_mapel.php?id=<?= $row['id_mata_pelajaran'] ?>" 
                           class="btn btn-hapus" 
                           onclick="return confirm('Yakin hapus mapel ini?')">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="4" style="text-align:center;">Belum ada data mata pelajaran</td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>

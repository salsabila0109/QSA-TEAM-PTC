<?php
session_start();
include '../db.php';

// Cek login admin
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Ambil semua data mata pelajaran
$result = $conn->query("SELECT * FROM mata_pelajaran ORDER BY id_mata_pelajaran DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Data Mata Pelajaran</title>
    <link rel="stylesheet" href="data_mapel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container">
    <h1><i class="fas fa-book"></i> Manajemen Data Mata Pelajaran</h1>

    <!-- Tombol Tambah -->
    <a href="tambah_mapel.php" class="btn btn-tambah"><i class="fas fa-plus"></i> Tambah Mapel</a>

    <!-- Tabel Data -->
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Mata Pelajaran</th>
                <th>Kode Mapel</th>
                <th>Aksi</th>

            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): $no = 1; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no ?></td>
                        <td><?= htmlspecialchars($row['nama_mapel']) ?></td>
                        <td><?= htmlspecialchars($row['kode_mapel']) ?></td>
                        <td class="aksi">
                            <a href="edit_mapel.php?id=<?= $row['id_mata_pelajaran'] ?>" class="btn btn-edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="hapus_mapel.php?id=<?= $row['id_mata_pelajaran'] ?>" class="btn btn-hapus" onclick="return confirm('Yakin ingin menghapus mapel ini?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php $no++; endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center;">Belum ada data mata pelajaran</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>

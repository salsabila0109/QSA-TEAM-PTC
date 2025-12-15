<?php
session_start();
include '../db.php';

// Cek login admin
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Ambil data mata pelajaran
$result = $conn->query("SELECT * FROM mata_pelajaran ORDER BY id_mata_pelajaran DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Data Mata Pelajaran</title>
    <link rel="stylesheet" href="manajemen_data_mapel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container">

    <!-- Tombol Back Bulat -->
    <div class="header-nav">
        <!-- ganti tujuan back ke dashboard_admin.html -->
        <a href="dashboard_admin.html" class="btn-kembali" title="Kembali">&#8592;</a>
        <h1>Manajemen Data Mata Pelajaran</h1>
    </div>

    <!-- Tombol Tambah Mapel -->
    <a href="tambah_mapel.php" class="btn btn-tambah">
        <i class="fas fa-plus"></i> Tambah Mapel
    </a>

    <!-- Tabel Mata Pelajaran -->
    <table id="tabel-mapel">
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
                    <tr id="row-<?= $row['id_mata_pelajaran'] ?>">
                        <td><?= $no ?></td>
                        <td><?= htmlspecialchars($row['nama_mapel']) ?></td>
                        <td><?= htmlspecialchars($row['kode_mapel']) ?></td>
                        <td class="aksi">
                            <a href="edit_mapel.php?id=<?= $row['id_mata_pelajaran'] ?>" class="btn btn-edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-hapus" onclick="hapusMapel(<?= $row['id_mata_pelajaran'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php $no++; endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align:center;">Belum ada data mata pelajaran</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function hapusMapel(id) {
    if (!confirm('Yakin ingin menghapus mata pelajaran ini?')) return;

    fetch('hapus_mapel_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id
    })
    .then(res => res.text())
    .then(response => {
        if (response.trim() === 'success') {
            document.getElementById('row-' + id).remove();
        } else {
            alert('Gagal menghapus data!');
        }
    })
    .catch(err => console.error('Error:', err));
}
</script>
</body>
</html>

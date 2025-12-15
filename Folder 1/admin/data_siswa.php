<?php
session_start();
include '../db.php';

if ($_SESSION['role_pengguna'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// Ambil data siswa beserta id_kelas
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
    <link rel="stylesheet" href="manajemen_data_siswa.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container">
    <div class="top-bar">
        <h2 class="title-center">Manajemen Data Siswa</h2>
    </div>

    <!-- ✅ Tombol Back (Floating) -->
    <a href="../admin/dashboard_admin.html" class="btn-kembali" title="Kembali" aria-label="Kembali">
    &#8592;
    </a>


    <!-- 🔹 Tabel Data Siswa -->
    <table>
        <tr>
            <th>NIS</th>
            <th>Nama</th>
            <th>ID Kelas</th>
            <th>Telepon Orangtua</th>
            <th>Aksi</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['nis']) ?></td>
            <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
            <td><?= htmlspecialchars($row['id_kelas']) ?></td>
            <td><?= htmlspecialchars($row['nomor_telepon_orangtua']) ?></td>
            <td>
                <a href="edit_siswa.php?id=<?= $row['id_siswa'] ?>" class="btn btn-edit">
                    <i class="fa-solid fa-pen-to-square"></i>
                </a>
                <a href="javascript:void(0);" onclick="hapusSiswa(<?= $row['id_siswa'] ?>)" class="btn btn-hapus">
                    <i class="fa-solid fa-trash"></i>
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<script>
function hapusSiswa(id) {
    if (confirm('Yakin ingin menghapus siswa ini?')) {
        fetch('hapus_siswa.php?id=' + id, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.text())
        .then(result => {
            if (result.trim() === "success") {
                location.reload();
            } else {
                alert('Gagal menghapus data!');
            }
        })
        .catch(error => console.error('Error:', error));
    }
}
</script>
</body>
</html>

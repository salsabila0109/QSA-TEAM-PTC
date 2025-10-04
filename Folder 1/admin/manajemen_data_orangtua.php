<?php
include '../db.php';

// Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Ambil data orangtua dan relasinya dengan siswa + kelas
$query = "
    SELECT 
        o.id_orangtua,
        o.nama_orangtua,
        o.no_hp,
        s.id_siswa,
        s.nama_siswa,
        k.nama_kelas,
        k.wali_kelas
    FROM orangtua o
    LEFT JOIN siswa s ON o.id_siswa = s.id_siswa
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    ORDER BY o.id_orangtua ASC
";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Data Orang Tua</title>
    <link rel="stylesheet" href="manajemen_data_orangtua.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h2>Manajemen Data Orang Tua</h2>

        <!-- 🔹 Tombol tambah di kiri atas -->
        <div class="top-bar">
            <a href="tambah_orangtua.php" class="btn-tambah">
                <i class="fa-solid fa-user-plus"></i> Tambah Orangtua
            </a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID Orang Tua</th>
                    <th>Nama Orang Tua</th>
                    <th>Nomor HP</th>
                    <th>ID Siswa</th>
                    <th>Nama Siswa</th>
                    <th>Nama Kelas</th>
                    <th>Wali Kelas</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id_orangtua']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_orangtua']); ?></td>
                            <td><?php echo htmlspecialchars($row['no_hp']); ?></td>
                            <td><?php echo htmlspecialchars($row['id_siswa']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_siswa']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_kelas']); ?></td>
                            <td><?php echo htmlspecialchars($row['wali_kelas']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="empty">Tidak ada data ditemukan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="back-container">
        </div>
    </div>
</body>
</html>

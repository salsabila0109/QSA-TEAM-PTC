<?php
session_start();
include '../db.php';

// Cek apakah admin login
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Ambil data kelas dari database
$query = "SELECT * FROM kelas ORDER BY id_kelas DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kelas</title>
    <link rel="stylesheet" href="data_kelas.css">
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-chalkboard"></i> Manajemen Data Kelas</h1>
        <a href="tambah_kelas.php" class="tambah-btn"><i class="fas fa-plus"></i> Tambah Kelas</a>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>ID Kelas</th>
                    <th>Nama Kelas</th>
                    <th>Wali Kelas</th>
                    <th>Tanggal Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($result->num_rows > 0) {
                    $no = 1;
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                            <td>{$no}</td>
                            <td>{$row['id_kelas']}</td>
                            <td>{$row['nama_kelas']}</td>
                            <td>{$row['wali_kelas']}</td>
                            <td>{$row['tanggal_dibuat']}</td>
                            <td>
                                <a href='edit_kelas.php?id={$row['id_kelas']}' class='edit-btn'>
                                    <i class='fas fa-edit'></i>
                                </a>
                                <a href='hapus_kelas.php?id={$row['id_kelas']}' class='hapus-btn' onclick=\"return confirm('Yakin ingin menghapus data ini?');\">
                                    <i class='fas fa-trash'></i> 
                                </a>
                            </td>
                        </tr>";
                        $no++;
                    }
                } else {
                    echo "<tr><td colspan='6' class='empty'>Belum ada data kelas</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>

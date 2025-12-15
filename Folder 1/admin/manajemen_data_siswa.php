<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';


// Cek admin
if ($_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}


// Query ambil siswa + nama kelas
$result = $conn->query("
    SELECT siswa.id_siswa, siswa.nis, siswa.nama_siswa, siswa.nomor_telepon_orangtua, kelas.nama_kelas
    FROM siswa
    LEFT JOIN kelas ON siswa.id_kelas = kelas.id_kelas
    ORDER BY siswa.id_siswa DESC
");

if(!$result){
    die("Query Error: " . $conn->error);
}

?>

<div class="content">


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Data Siswa</title>
    <link rel="stylesheet" href="manajemen_data_siswa.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<a href="javascript:history.back()" class="btn-kembali" title="Kembali">&#8592;</a>

<div class="container">
<div class="search-container">
    <input type="text" placeholder="Cari siswa..." id="search-input">
    <span class="search-icon">&#128269;</span>
</div>
    
<header class="topbar">
    <div class="judul">Manajemen Data Siswa</div>
</header>


<div class="container">
    <a href="tambah_siswa.php" class="btn btn-tambah">+ Tambah Siswa</a>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Nama Kelas</th>
                <th>Nomor Ortu</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id_siswa'] ?></td>
                <td><?= $row['nis'] ?></td>
                <td><?= $row['nama_siswa'] ?></td>
                <td><?= $row['nama_kelas'] ?></td>
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
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('burger-btn').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });
});
</script>
<script>
const searchInput = document.getElementById('search-input');
searchInput.addEventListener('keyup', function() {
    const filter = searchInput.value.toLowerCase();
    const table = document.querySelector('table');
    const rows = table.getElementsByTagName('tr');

    // Mulai dari i = 1 untuk skip header
    for (let i = 1; i < rows.length; i++) {
        const nameCell = rows[i].getElementsByTagName('td')[2];
        if (nameCell) {
            const textValue = nameCell.textContent || nameCell.innerText;
            rows[i].style.display = textValue.toLowerCase().includes(filter) ? '' : 'none';
        }
    }
});
</script>
</body>
</html>

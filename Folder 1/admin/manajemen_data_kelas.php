<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

$result = $conn->query("SELECT * FROM kelas");
?>
<div class="container">
    <a href="tambah_kelas.php" class="btn btn-tambah">+ Tambah Kelas</a>
    <table>
        <thead>
            <tr>
                <th>ID Kelas</th>
                <th>Nama Kelas</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?= $row['id_kelas'] ?></td>
                <td><?= $row['nama_kelas'] ?></td>
                <td class="aksi">
                    <a href="edit_kelas.php?id=<?= $row['id_kelas'] ?>" class="btn btn-edit">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    <a href="hapus_kelas.php?id=<?= $row['id_kelas'] ?>" class="btn btn-hapus" onclick="return confirm('Yakin hapus?')">
                        <i class="fa-solid fa-trash"></i>
                    </a>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

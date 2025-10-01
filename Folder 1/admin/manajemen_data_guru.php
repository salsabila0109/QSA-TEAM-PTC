<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

$result = $conn->query("SELECT * FROM guru");
?>
<div class="container">
    <a href="tambah_guru.php" class="btn btn-tambah">+ Tambah Guru</a>
    <table>
        <thead>
            <tr>
                <th>ID Guru</th>
                <th>NIP</th>
                <th>Nama Guru</th>
                <th>UID RFID</th>
                <th>No Telepon</th>
                <th>Tanggal Dibuat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?= $row['id_guru'] ?></td>
                <td><?= $row['nip'] ?></td>
                <td><?= $row['nama_guru'] ?></td>
                <td><?= $row['uid_rfid_guru'] ?></td>
                <td><?= $row['no_telepon'] ?></td>
                <td><?= $row['tanggal_data_dibuat'] ?></td>
                <td class="aksi">
                    <a href="edit_guru.php?id=<?= $row['id_guru'] ?>" class="btn btn-edit">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    <a href="hapus_guru.php?id=<?= $row['id_guru'] ?>" class="btn btn-hapus" onclick="return confirm('Yakin hapus?')">
                        <i class="fa-solid fa-trash"></i>
                    </a>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

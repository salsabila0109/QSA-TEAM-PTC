<?php
session_start();
include '../db.php';

if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$result = $conn->query("SELECT * FROM guru ORDER BY id_guru DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manajemen Data Guru</title>
    <link rel="stylesheet" href="manajemen_data_guru.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="container">
    <header>Manajemen Data Guru</header>
    <a href="tambah_guru.php" class="btn btn-tambah">+ Tambah Guru</a>
    <table>
        <thead>
            <tr>
                <th>ID Guru</th>
                <th>NIP</th>
                <th>Nama Guru</th>
                <th>No Telepon</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr id="row-<?= (int)$row['id_guru'] ?>">
                <td><?= htmlspecialchars($row['id_guru']) ?></td>
                <td><?= htmlspecialchars($row['nip']) ?></td>
                <td><?= htmlspecialchars($row['nama_guru']) ?></td>
                <td><?= htmlspecialchars($row['no_telepon']) ?></td>
                <td class="aksi">
                    <a href="edit_guru.php?id=<?= (int)$row['id_guru'] ?>" class="btn btn-edit" title="Edit">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    <button class="btn btn-hapus hapus-btn" data-id="<?= (int)$row['id_guru'] ?>" title="Hapus">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<script>
$(document).ready(function(){
    $('.hapus-btn').on('click', function(){
        const id_guru = $(this).data('id');

        Swal.fire({
            title: 'Yakin hapus?',
            text: 'Data guru ini akan dihapus permanen!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'hapus_guru.php',
                    type: 'POST',
                    data: { id_guru: id_guru },
                    dataType: 'json',
                    cache: false,
                    success: function(response){
                        if (response.success) {
                            $('#row-' + id_guru).fadeOut(600, function(){ $(this).remove(); });
                            Swal.fire({ icon: 'success', title: 'Data berhasil dihapus', showConfirmButton: false, timer: 1500 });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Gagal menghapus data', text: response.error || 'Terjadi kesalahan pada server.' });
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'Kesalahan koneksi', text: 'Gagal menghubungi server.' });
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>

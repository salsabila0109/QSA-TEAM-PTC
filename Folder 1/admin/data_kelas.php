<?php
session_start();
include '../db.php';

// Cek apakah admin login
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Ambil data kelas + nama wali kelas (nama_guru) dari tabel guru
$query = "
    SELECT 
        k.id_kelas,
        k.nama_kelas,
        k.wali_kelas,
        k.tanggal_dibuat,
        g.nama_guru
    FROM kelas k
    LEFT JOIN guru g ON g.id_guru = k.wali_kelas
    ORDER BY k.id_kelas DESC
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Wali Kelas</title>
    <link rel="stylesheet" href="data_kelas.css">
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container">
        <div class="header-nav">
            <div class="btn-group-nav">
                <a href="tambah_kelas.php" class="btn-back">&#8592;</a>
                <a href="manajemen_data_kelas.php" class="btn-next">&#8594;</a>
            </div>
            <h1>Data Nama Wali Kelas</h1>
        </div>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>ID Kelas</th>
                    <th>Nama Kelas</th>
                    <th>Nama Wali Kelas</th>
                    <th>Tanggal Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
<?php 
if ($result && $result->num_rows > 0) {
    $no = 1;
    while ($row = $result->fetch_assoc()) {
        $id_kelas   = htmlspecialchars($row['id_kelas'] ?? '', ENT_QUOTES, 'UTF-8');
        $nama_kelas = htmlspecialchars($row['nama_kelas'] ?? '', ENT_QUOTES, 'UTF-8');
        $nama_guru  = htmlspecialchars($row['nama_guru'] ?? '', ENT_QUOTES, 'UTF-8');
        $tgl_dibuat = htmlspecialchars($row['tanggal_dibuat'] ?? '', ENT_QUOTES, 'UTF-8');

        if ($nama_guru === '' || $nama_guru === null) {
            $nama_guru = '—';
        }

        echo "<tr id='row-{$id_kelas}'>
            <td>{$no}</td>
            <td>{$id_kelas}</td>
            <td>{$nama_kelas}</td>
            <td>{$nama_guru}</td>
            <td>{$tgl_dibuat}</td>
            <td>
                <a href='edit_kelas.php?id={$id_kelas}' class='icon-btn edit-btn' title='Edit'>
                    <i class='fas fa-pen-to-square'></i>
                </a>
                <a href='#' class='icon-btn hapus-btn' data-id='{$id_kelas}' title='Hapus'>
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

<script>
$(document).ready(function(){
    $('.hapus-btn').on('click', function(e){
        e.preventDefault();
        const id_kelas = $(this).data('id');
        const row = $(this).closest('tr');

        Swal.fire({
            title: 'Yakin hapus?',
            text: 'Data kelas ini akan dihapus permanen!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if(result.isConfirmed){
                $.ajax({
                    url: 'hapus_kelas.php',
                    type: 'POST',
                    data: { id_kelas: id_kelas },
                    dataType: 'json',
                    success: function(res){
                        if(res.success){
                            row.fadeOut(500, function(){ $(this).remove(); });
                            Swal.fire({
                                icon: 'success',
                                title: 'Data berhasil dihapus',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Error', res.error || 'Gagal menghapus data', 'error');
                        }
                    },
                    error: function(){
                        Swal.fire('Error', 'Gagal menghubungi server', 'error');
                    }
                });
            }
        });
    });
});
</script>

</body>
</html>

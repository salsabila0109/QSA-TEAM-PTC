<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';
if ($_SESSION['role_pengguna'] !== 'admin') {
    exit('akses ditolak');
}

// Query siswa + nama kelas
$result = $conn->query("
    SELECT siswa.*, kelas.nama_kelas 
    FROM siswa 
    LEFT JOIN kelas ON siswa.id_kelas = kelas.id_kelas
");

while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>{$row['id_siswa']}</td>
            <td>{$row['nis']}</td>
            <td>{$row['nama_siswa']}</td>
            <td>{$row['nama_kelas']}</td>
            <td>{$row['nomor_telepon_orangtua']}</td>
            <td class='aksi'>
                <a href='edit_siswa.php?id={$row['id_siswa']}' class='btn btn-edit'>
                    <i class='fa-solid fa-pen-to-square'></i>
                </a>
                <a href='#' class='btn btn-hapus' onclick='hapusSiswa({$row['id_siswa']})'>
                    <i class='fa-solid fa-trash'></i>
                </a>
            </td>
          </tr>";
}
?>

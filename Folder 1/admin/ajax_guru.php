<?php
// Mengembalikan <tr>…</tr> untuk <tbody>
session_start();
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    http_response_code(403); exit;
}
include '../db.php';

$res = $conn->query("SELECT id_guru, nip, nama_guru, no_telepon, tanggal_diperbarui 
                     FROM guru ORDER BY id_guru DESC");

if ($res && $res->num_rows) {
    while ($row = $res->fetch_assoc()) {
        $tglEdit = $row['tanggal_diperbarui'] ?: '-';
        echo '<tr>'.
                '<td>'.htmlspecialchars($row['id_guru']).'</td>'.
                '<td>'.htmlspecialchars($row['nip']).'</td>'.
                '<td>'.htmlspecialchars($row['nama_guru']).'</td>'.
                '<td>'.htmlspecialchars($row['no_telepon']).'</td>'.
                '<td>'.htmlspecialchars($tglEdit).'</td>'.
                '<td class="aksi">'.
                    '<a href="#" class="icon-btn btn-edit" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen-to-square"></i></a>'.
                    '<a href="#" data-id="'.(int)$row['id_guru'].'" class="icon-btn btn-hapus" title="Hapus" aria-label="Hapus"><i class="fa-solid fa-trash"></i></a>'.
                '</td>'.
             '</tr>';
    }
} else {
    echo '<tr><td colspan="6" class="empty">Belum ada data guru.</td></tr>';
}

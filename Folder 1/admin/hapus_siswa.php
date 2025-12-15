<?php
include '../db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $hapus = $conn->query("DELETE FROM siswa WHERE id_siswa = $id");

    if ($hapus) {
        // Jika lewat AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo "success";
        } else {
            // Jika akses biasa
            header("Location: manajemen_data_siswa.php");
        }
    } else {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo "error";
        } else {
            echo "Gagal menghapus data.";
        }
    }
}
?>

<?php
session_start();

// Hapus semua session admin
session_unset(); // menghapus semua session
session_destroy(); // menghancurkan session

// Redirect ke halaman selamat datang
header("Location: ../index.php"); // sesuaikan dengan nama file halaman selamat datang
exit;
?>

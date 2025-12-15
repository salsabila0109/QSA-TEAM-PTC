<?php
session_start();

// Cek apakah user login dan role-nya guru
if(!isset($_SESSION['username']) || $_SESSION['role'] !== 'guru') {
    // Jika bukan guru, redirect ke halaman utama
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>PresenTech - Guru</title>
</head>
<body>
    <h1>Selamat datang, Guru <?php echo $_SESSION['username']; ?></h1>
    <p>Ini halaman khusus guru.</p>

    <!-- Tombol Logout Guru -->
    <a href="logout_guru.php" onclick="return confirm('Apakah Anda yakin ingin keluar (Guru)?');">Logout</a>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role_pengguna'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - PresenTech</title>
    <link rel="stylesheet" href="../index.css"> 
</head>
<body>
    <div class="welcome-container">
        <div class="logo-title">
            <img src="../logo.png" alt="Logo PresenTech" class="logo">
        </div>
        <h2>Selamat Datang Admin, <?php echo $_SESSION['username']; ?>! 👋</h2>
        <p>Anda login sebagai Admin</p>
        <a href="admin_dashboard.php" class="btn">Mulai</a>
    <a href="logout.php" onclick="return confirmLogout()">Logout</a>
</header>

<script>
function confirmLogout() {
    return confirm("Yakin ingin logout?");
}
</script>
    </div>
</body>
</html>

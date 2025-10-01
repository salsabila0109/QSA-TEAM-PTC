<?php
session_start();
if ($_SESSION['role_pengguna'] != 'orangtua') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard orangtua</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="dashboard-container">
    <h2>Selamat datang orangtua, <?= $_SESSION['username']; ?>!</h2>
    <a href="logout.php">Logout</a>
</div>
</body>
</html>

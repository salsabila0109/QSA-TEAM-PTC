<?php
session_start();
include '../db.php'; 

if ($_SESSION['role_pengguna'] != 'admin') {
    header("Location: ../login.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_kelas = $_POST['id_kelas'];
    $nama_kelas = $_POST['nama_kelas'];

    $sql = "INSERT INTO kelas (id_kelas, nama_kelas) VALUES ('$id_kelas', '$nama_kelas')";
    if ($conn->query($sql)) {
        header("Location: data_kelas.php"); 
        exit;
    } else {
        $error = "Gagal menambahkan kelas: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tambah Kelas</title>
    <link rel="stylesheet" href="tambah_kelas.css">
</head>
<body>
<div class="container">
    <h2>Tambah Kelas</h2>
    <?php if(isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
    <form method="post">
        <label>Id Kelas</label>
        <input type="text" name="id_kelas" placeholder="Contoh: id kelas 1" required>

        <label>Nama Kelas</label>
        <input type="text" name="nama_kelas" placeholder="Nama Kelas, misal 8A" required>

        <div class="btn-group">
            <button type="submit" class="btn btn-submit">Tambah</button>
            <button type="button" class="btn btn-back" onclick="history.back()">Kembali</button>
        </div>
    </form>
</div>
</body>
</html>

<?php
session_start();
include '../db.php';

if ($_SESSION['role_pengguna'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'];
$siswa = $conn->query("SELECT * FROM siswa WHERE id_siswa=$id")->fetch_assoc();
$kelas_result = $conn->query("SELECT * FROM kelas ORDER BY nama_kelas ASC");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis = $_POST['nis'];
    $nama = $_POST['nama'];
    $id_kelas = $_POST['id_kelas'];
    $telepon = $_POST['telepon'];

    $sql = "UPDATE siswa SET nis='$nis', nama_siswa='$nama', id_kelas='$id_kelas', nomor_telepon_orangtua='$telepon' 
            WHERE id_siswa=$id";
    $conn->query($sql);

    header("Location: data_siswa.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Siswa</title>
    <link rel="stylesheet" href="edit_siswa.css">
</head>
<body>
<div class="container">
    <h2>Edit Siswa</h2>
    <form method="post">
        <label>NIS</label>
        <input type="text" name="nis" value="<?= $siswa['nis'] ?>" required>

        <label>Nama Siswa</label>
        <input type="text" name="nama" value="<?= $siswa['nama_siswa'] ?>" required>

        <label>Kelas</label>
        <select name="id_kelas" required>
            <option value="">-- Pilih Kelas --</option>
            <?php while ($k = $kelas_result->fetch_assoc()): ?>
                <option value="<?= $k['id_kelas'] ?>" <?= $k['id_kelas']==$siswa['id_kelas']?'selected':'' ?>>
                    <?= $k['nama_kelas'] ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>No. Telepon Orangtua</label>
        <input type="text" name="telepon" value="<?= $siswa['nomor_telepon_orangtua'] ?>">

        <div class="btn-group">
            <button type="submit" class="btn btn-submit">Update</button>
            <button type="button" class="btn btn-back" onclick="history.back()">Kembali</button>
        </div>
    </form>
</div>
</body>
</html>

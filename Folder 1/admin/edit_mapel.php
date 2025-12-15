<?php
session_start();
include '../db.php';

// Cek admin login
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Ambil id mapel dari URL
if(!isset($_GET['id'])){
    header("Location: data_mapel.php");
    exit;
}
$id = intval($_GET['id']);

// Ambil data mapel
$stmt = $conn->prepare("SELECT * FROM mata_pelajaran WHERE id_mata_pelajaran=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows==0){
    die("Mata pelajaran tidak ditemukan!");
}
$mapel = $result->fetch_assoc();
$stmt->close();

// Update mapel
$message = "";
if($_SERVER["REQUEST_METHOD"]=="POST"){
    $nama_mapel = trim($_POST['nama_mapel']);
    $kode_mapel = trim($_POST['kode_mapel']);

    if($nama_mapel && $kode_mapel){
        $stmt = $conn->prepare("UPDATE mata_pelajaran SET nama_mapel=?, kode_mapel=? WHERE id_mata_pelajaran=?");
        $stmt->bind_param("ssi", $nama_mapel, $kode_mapel, $id);
        if($stmt->execute()){
            $message = "Mata pelajaran berhasil diperbarui!";
        } else {
            $message = "Terjadi kesalahan: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "Nama mapel dan kode mapel tidak boleh kosong!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Mata Pelajaran</title>
<link rel="stylesheet" href="tambah_mapel.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<a href="javascript:history.back()" class="btn-back" title="Kembali">&#8592;</a>


<div class="container">
    <h1><i class="fas fa-edit"></i> Edit Mata Pelajaran</h1>

    <?php if($message) echo "<p class='message'>$message</p>"; ?>

    <form method="POST">
        <label for="nama_mapel">Nama Mapel</label>
        <input type="text" id="nama_mapel" name="nama_mapel" value="<?= htmlspecialchars($mapel['nama_mapel']) ?>" required>

        <label for="kode_mapel">Kode Mapel</label>
        <input type="text" id="kode_mapel" name="kode_mapel" value="<?= htmlspecialchars($mapel['kode_mapel']) ?>" required>

        <button type="submit" class="btn btn-simpan"><i class="fas fa-save"></i> Simpan Perubahan</button>
    </form>
</div>
</body>
</html>

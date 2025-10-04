<?php
session_start();
include '../db.php';

// Cek apakah admin login
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";

// Proses form submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_mapel = trim($_POST['nama_mapel']);
    $kode_mapel = trim($_POST['kode_mapel']);

    if(!empty($nama_mapel) && !empty($kode_mapel)) {
        // Cek apakah kode_mapel sudah ada
        $cek = $conn->prepare("SELECT * FROM mata_pelajaran WHERE kode_mapel = ?");
        $cek->bind_param("s", $kode_mapel);
        $cek->execute();
        $cek->store_result();

        if($cek->num_rows > 0){
            $message = "Kode Mapel '$kode_mapel' sudah digunakan!";
        } else {
            $stmt = $conn->prepare("INSERT INTO mata_pelajaran (nama_mapel, kode_mapel) VALUES (?, ?)");
            $stmt->bind_param("ss", $nama_mapel, $kode_mapel);
            if($stmt->execute()) {
                $message = "Mata pelajaran berhasil ditambahkan!";
                // Reset form
                $nama_mapel = "";
                $kode_mapel = "";
            } else {
                $message = "Terjadi kesalahan: " . $stmt->error;
            }
            $stmt->close();
        }

        $cek->close();
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
<title>Tambah Mata Pelajaran</title>
<link rel="stylesheet" href="tambah_mapel.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container">
    <h1><i class="fas fa-book"></i> Tambah Mata Pelajaran</h1>

    <?php if($message): ?>
        <p class="message"><?= $message ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="nama_mapel">Nama Mata Pelajaran</label>
        <input type="text" id="nama_mapel" name="nama_mapel" placeholder="Masukkan nama mata pelajaran..." value="<?= isset($nama_mapel) ? htmlspecialchars($nama_mapel) : '' ?>" required>

        <label for="kode_mapel">Kode Mata Pelajaran</label>
        <input type="text" id="kode_mapel" name="kode_mapel" placeholder="Masukkan kode mata pelajaran..." value="<?= isset($kode_mapel) ? htmlspecialchars($kode_mapel) : '' ?>" required>

        <button type="submit" class="btn btn-simpan"><i class="fas fa-save"></i> Simpan</button>
    </form>
</div>
</body>
</html>

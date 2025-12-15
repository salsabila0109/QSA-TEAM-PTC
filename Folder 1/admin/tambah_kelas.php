<?php
session_start();
include '../db.php'; 

if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kelas   = trim($_POST['id_kelas'] ?? '');
    $nama_kelas = trim($_POST['nama_kelas'] ?? '');

    if ($id_kelas === '' || $nama_kelas === '') {
        $error = "Id Kelas dan Nama Kelas wajib diisi.";
    } else {
        // Insert pakai prepared statement (lebih aman)
        $stmt = $conn->prepare("INSERT INTO kelas (id_kelas, nama_kelas) VALUES (?, ?)");
        $stmt->bind_param("is", $id_kelas, $nama_kelas);

        if ($stmt->execute()) {
            $stmt->close();
            // Langsung kembali ke manajemen + notif
            header("Location: manajemen_data_kelas.php?added=1");
            exit;
        } else {
            $error = "Gagal menambahkan kelas: " . $stmt->error;
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Kelas</title>
    <link rel="stylesheet" href="tambah_kelas.css">
</head>
<body>

<!-- Tombol Navigasi (Back saja, tombol Next dihapus) -->
<div class="btn-group-nav">
    <a href="manajemen_data_kelas.php" class="btn-circle">&#8592;</a>
</div>

<div class="container">
    <h2>Tambah Kelas</h2>

    <?php if(isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="on">
        <label>Id Kelas</label>
        <input type="text" name="id_kelas" placeholder="Contoh: id kelas 81" required>

        <label>Nama Kelas</label>
        <input type="text" name="nama_kelas" placeholder="Nama Kelas, misal 8A" required>

        <div class="btn-group">
            <button type="submit" class="btn btn-submit">Tambah</button>
        </div>
    </form>
</div>

</body>
</html>

<?php
session_start();
include '../db.php';

// Pastikan hanya guru yang login
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'guru') {
    header("Location: ../login.php");
    exit;
}

// Ambil ID guru dari session
$id_guru = $_SESSION['id_guru'];

// Ambil data guru dari database
$query = mysqli_query($conn, "SELECT * FROM guru WHERE id_guru='$id_guru'");
$data = mysqli_fetch_assoc($query);

// Jika tombol simpan ditekan
if (isset($_POST['simpan'])) {
    $nama_guru = trim($_POST['nama_guru']);
    $foto_lama = $data['foto'];

    // Jika upload foto baru
    if (!empty($_FILES['foto']['name'])) {
        $nama_file_baru = time() . "_" . basename($_FILES['foto']['name']);
        $tmp = $_FILES['foto']['tmp_name'];
        $upload_dir = dirname(__DIR__) . "/uploads/guru/";
        $path_simpan = $upload_dir . $nama_file_baru;

        // Pastikan folder upload ada
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Pindahkan file ke folder uploads
        if (move_uploaded_file($tmp, $path_simpan)) {
            // Hapus foto lama jika ada
            if (!empty($foto_lama) && file_exists($upload_dir . $foto_lama)) {
                unlink($upload_dir . $foto_lama);
            }

            // Update data guru + foto baru
            $update = mysqli_query($conn, "
                UPDATE guru 
                SET nama_guru='$nama_guru', foto='$nama_file_baru', tanggal_diperbarui=NOW() 
                WHERE id_guru='$id_guru'
            ");
        }
    } else {
        // Update hanya nama guru
        $update = mysqli_query($conn, "
            UPDATE guru 
            SET nama_guru='$nama_guru', tanggal_diperbarui=NOW() 
            WHERE id_guru='$id_guru'
        ");
    }

    // Redirect agar tidak muncul pesan berulang setelah refresh
    if ($update) {
        header("Location: edit_profil_guru.php?success=1");
        exit;
    }
}

// Ambil ulang data terbaru
$query = mysqli_query($conn, "SELECT * FROM guru WHERE id_guru='$id_guru'");
$data = mysqli_fetch_assoc($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Profil Guru</title>
    <link rel="stylesheet" href="edit_profil_guru.css">
</head>
<body>
<div class="container">
    <?php if (isset($_GET['success'])): ?>
        <div class="success">Profil berhasil diperbarui.</div>
    <?php endif; ?>

    <div class="foto-container">
        <img src="<?php echo !empty($data['foto']) 
            ? '../uploads/guru/'.$data['foto'] 
            : '../uploads/guru/default.jpg'; ?>" 
            alt="Foto Profil" class="foto-profil">
    </div>

    <h2>Edit Profil Guru</h2>
    <form method="post" enctype="multipart/form-data">
        <label>Nama Guru</label>
        <input type="text" name="nama_guru" 
               value="<?php echo htmlspecialchars($data['nama_guru']); ?>" required>

        <label>Foto Profil</label>
        <input type="file" name="foto" accept="image/*">

        <div class="btn-group">
            <button type="submit" name="simpan">💾 Simpan</button>
            <a href="dashboard_guru.php" class="btn-back">⬅️</a>
        </div>
    </form>
</div>
</body>
</html>

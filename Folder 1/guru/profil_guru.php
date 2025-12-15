<?php
session_start();
include '../db.php';

if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'guru') {
    header("Location: ../login_guru.php");
    exit;
}

$guru_id = $_SESSION['id_pengguna'] ?? 0;
$message = "";
$errors  = [];

// ================== Ambil data guru ==================
$stmt = $conn->prepare("SELECT nama_guru, foto FROM guru WHERE id_guru=?");
$stmt->bind_param("i", $guru_id);
$stmt->execute();
$stmt->bind_result($nama_db, $foto_db);
$stmt->fetch();
$stmt->close();

// ================== Handle update profil ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    if ($nama === '') {
        $errors[] = "Nama tidak boleh kosong.";
    }

    $uploaded_filename = null;

    // ---- proses upload foto (opsional) ----
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['foto'];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Terjadi kesalahan saat mengunggah file.";
        } else {
            $allowed_ext = ['jpg','jpeg','png','webp'];
            $max_size    = 2 * 1024 * 1024; // 2MB
            $ext         = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $mime        = mime_content_type($f['tmp_name']);
            $valid_mimes = ['image/jpeg','image/png','image/webp'];

            if (!in_array($ext, $allowed_ext) || !in_array($mime, $valid_mimes)) {
                $errors[] = "Tipe file tidak didukung. Gunakan JPG, PNG, atau WEBP.";
            } elseif ($f['size'] > $max_size) {
                $errors[] = "Ukuran file terlalu besar (maks 2MB).";
            } elseif (!is_uploaded_file($f['tmp_name'])) {
                $errors[] = "Upload tidak valid.";
            } else {
                $upload_dir = dirname(__DIR__) . '/uploads/guru';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $uploaded_filename = uniqid('guru_', true) . '.' . $ext;
                $destination       = $upload_dir . '/' . $uploaded_filename;

                if (!move_uploaded_file($f['tmp_name'], $destination)) {
                    $errors[] = "Gagal menyimpan file.";
                } else {
                    @chmod($destination, 0644);
                }
            }
        }
    }

    // ---- kalau tidak ada error, update DB ----
    if (empty($errors)) {
        if ($uploaded_filename) {
            // hapus foto lama kalau ada
            if ($foto_db && file_exists(dirname(__DIR__) . '/uploads/guru/' . $foto_db)) {
                @unlink(dirname(__DIR__) . '/uploads/guru/' . $foto_db);
            }

            $stmt = $conn->prepare("UPDATE guru SET nama_guru=?, foto=? WHERE id_guru=?");
            $stmt->bind_param("ssi", $nama, $uploaded_filename, $guru_id);
            $foto_db = $uploaded_filename;
        } else {
            $stmt = $conn->prepare("UPDATE guru SET nama_guru=? WHERE id_guru=?");
            $stmt->bind_param("si", $nama, $guru_id);
        }

        if ($stmt->execute()) {
            // ======= PENTING: sinkronkan SESSION + redirect =======
            $_SESSION['nama_guru'] = $nama;   // supaya dashboard pakai nama baru
            $stmt->close();

            header("Location: dashboard_guru.html");
            exit;
        } else {
            $errors[] = "Gagal memperbarui profil: " . $conn->error;
        }
        $stmt->close();
    }
}

// ================== Fungsi URL foto publik ==================
function public_photo_url($filename) {
    return $filename
        ? '../uploads/guru/' . rawurlencode($filename)
        : '../uploads/guru/default_avatar.png';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profil Guru</title>
<link rel="stylesheet" href="profil_guru.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>

<a href="dashboard_guru.html" class="btn-back">
    <i class="fas fa-arrow-left"></i>
</a>

<div class="profil-container">
    <div class="profil-card">
        <div class="profil-avatar">
            <img src="<?= public_photo_url($foto_db) ?>" alt="Foto Profil">
        </div>
        <div class="profil-info">
            <h2>Edit Profil Guru</h2>

            <?php if(!empty($errors)): ?>
                <div class="message error">
                    <ul>
                        <?php foreach($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <label>Nama Guru</label>
                <input type="text" name="nama" value="<?= htmlspecialchars($nama_db) ?>" required>

                <label>Foto Profil</label>
                <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp">

                <div class="btn-group">
                    <button type="submit" class="btn">💾 Simpan</button>
                    <a href="ubah_password.php" class="btn-ubah-password">
                        <i class="fas fa-lock"></i> Ubah Password
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>

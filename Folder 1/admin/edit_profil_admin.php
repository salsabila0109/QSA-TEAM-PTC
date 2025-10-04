<?php
session_start();
include '../db.php';

// Pastikan admin login
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$username_session = $_SESSION['username'];
$message = "";
$errors = [];

// Ambil data admin dari username session
$stmt = $conn->prepare("SELECT foto FROM admin WHERE username = ?");
$stmt->bind_param("s", $username_session);
$stmt->execute();
$stmt->bind_result($foto_db);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    if ($username === '') $errors[] = "Username tidak boleh kosong.";

    $uploaded_filename = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['foto'];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Terjadi kesalahan saat mengunggah file.";
        } else {
            $allowed_ext = ['jpg','jpeg','png','webp'];
            $max_size = 2*1024*1024;
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $mime = mime_content_type($f['tmp_name']);
            $valid_mimes = ['image/jpeg','image/png','image/webp'];

            if (!in_array($ext,$allowed_ext) || !in_array($mime,$valid_mimes)) {
                $errors[] = "Tipe file tidak didukung. Gunakan JPG, PNG, WEBP.";
            } elseif ($f['size'] > $max_size) {
                $errors[] = "Ukuran file terlalu besar (max 2MB).";
            } else {
                $upload_dir = __DIR__ . '/uploads/admin_photos';
                if (!is_dir($upload_dir)) mkdir($upload_dir,0755,true);
                $uploaded_filename = uniqid('admin_',true).'.'.$ext;
                $destination = $upload_dir.'/'.$uploaded_filename;
                if (!move_uploaded_file($f['tmp_name'],$destination)) {
                    $errors[] = "Gagal menyimpan file.";
                } else {
                    @chmod($destination,0644);
                }
            }
        }
    }

    if (empty($errors)) {
        if ($uploaded_filename) {
            if ($foto_db && file_exists(__DIR__.'/uploads/admin_photos/'.$foto_db)) {
                @unlink(__DIR__.'/uploads/admin_photos/'.$foto_db);
            }
            $stmt = $conn->prepare("UPDATE admin SET username=?, foto=? WHERE username=?");
            $stmt->bind_param("sss",$username,$uploaded_filename,$username_session);
        } else {
            $stmt = $conn->prepare("UPDATE admin SET username=? WHERE username=?");
            $stmt->bind_param("ss",$username,$username_session);
        }

        if ($stmt->execute()) {
            $message = "Profil berhasil diperbarui.";
            $_SESSION['username'] = $username;
            if ($uploaded_filename) $foto_db = $uploaded_filename;
            $username_session = $username; // update session untuk form
        } else {
            $errors[] = "Gagal memperbarui profil: ".$conn->error;
        }
        $stmt->close();
    }
}

// Fungsi URL foto publik
function public_photo_url($filename){
    return $filename ? 'uploads/admin_photos/'.rawurlencode($filename) : null;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profil Admin</title>
<link rel="stylesheet" href="edit_profil_admin.css">
</head>
<body>
<div class="password-container">
    <h2>Edit Profil Admin</h2>

    <?php if($message): ?>
        <div class="message success"><?=htmlspecialchars($message)?></div>
    <?php endif; ?>

    <?php if(!empty($errors)): ?>
        <div class="message error">
            <ul>
                <?php foreach($errors as $e): ?>
                    <li><?=htmlspecialchars($e)?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label>Username</label>
        <input type="text" name="username" value="<?=htmlspecialchars($username_session)?>" required>

        <label>Foto Profil</label>
        <div class="preview">
            <?php if($foto_db && file_exists(__DIR__.'/uploads/admin_photos/'.$foto_db)): ?>
                <img src="<?=public_photo_url($foto_db)?>" alt="Foto Profil">
            <?php else: ?>
                <img src="data:image/svg+xml;charset=UTF-8,<?php echo rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80"><rect width="100%" height="100%" fill="#e0e0e0"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#888" font-size="12">No Image</text></svg>'); ?>" alt="No Image">
            <?php endif; ?>
            <!-- Input file + label "Simpan Foto" -->
            <input type="file" name="foto" id="foto" accept=".jpg,.jpeg,.png,.webp">
            <label id="fotoLabel" style="display:none;color:#00796B;">📌 Klik "Simpan" untuk mengunggah foto</label>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn">Simpan</button>
            <a href="javascript:history.back()" class="btn-back">Kembali</a>
        </div>
    </form>
</div>

<script>
document.getElementById('foto').addEventListener('change', function() {
    document.getElementById('fotoLabel').style.display = this.files.length ? 'block' : 'none';
});
</script>

</body>
</html>

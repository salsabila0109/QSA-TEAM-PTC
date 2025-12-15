<?php
session_start();
include '../db.php';

// Pastikan guru sudah login
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'guru') {
    header("Location: ../login.php");
    exit;
}

$id_guru = $_SESSION['id_pengguna'] ?? 0;

// Ambil data guru
$stmt = $conn->prepare("SELECT nama_guru, username FROM guru WHERE id_guru = ?");
$stmt->bind_param("i", $id_guru);
$stmt->execute();
$stmt->bind_result($nama_guru, $username);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ubah Password Guru</title>
<link rel="stylesheet" href="ubah_password.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>

<div class="ubah-password-container">
    <!-- Tombol Back Bulat -->
    <a href="profil_guru.php" class="btn-back">
        <i class="fas fa-arrow-left"></i>
    </a>

    <h2>Ubah Password</h2>

    <form action="proses_ubah_password.php" method="POST">
        <div class="form-group">
            <label>Nama Guru:</label>
            <input type="text" value="<?= htmlspecialchars($nama_guru) ?>" readonly>
        </div>

        <!-- Password Lama -->
        <div class="form-group password-wrapper">
            <label>Password Lama:</label>
            <input type="password" name="password_lama" placeholder="Masukkan password lama" required autocomplete="current-password">
            <i class="fa-solid fa-eye toggle-password"></i>
        </div>

        <!-- Password Baru -->
        <div class="form-group password-wrapper">
            <label>Password Baru:</label>
            <input type="password" name="password_baru" placeholder="Masukkan password baru" required autocomplete="new-password">
            <i class="fa-solid fa-eye toggle-password"></i>
        </div>

        <!-- Konfirmasi Password -->
        <div class="form-group password-wrapper">
            <label>Konfirmasi Password Baru:</label>
            <input type="password" name="konfirmasi_password" placeholder="Ulangi password baru" required autocomplete="new-password">
            <i class="fa-solid fa-eye toggle-password"></i>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-simpan"><i class="fas fa-save"></i> Simpan Perubahan</button>
        </div>
    </form>
</div>

<script>
// Toggle show/hide password
document.querySelectorAll(".toggle-password").forEach(icon => {
    icon.addEventListener("click", () => {
        const input = icon.previousElementSibling;
        if (input.type === "password") {
            input.type = "text";
            icon.classList.replace("fa-eye", "fa-eye-slash");
        } else {
            input.type = "password";
            icon.classList.replace("fa-eye-slash", "fa-eye");
        }
    });
});
</script>

</body>
</html>

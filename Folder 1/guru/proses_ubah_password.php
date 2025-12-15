<?php
session_start();
include '../db.php';

if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'guru') {
    header("Location: ../login.php");
    exit;
}

$id_guru = $_SESSION['id_pengguna'] ?? 0;
$password_lama = $_POST['password_lama'] ?? '';
$password_baru = $_POST['password_baru'] ?? '';
$konfirmasi = $_POST['konfirmasi_password'] ?? '';

if (empty($password_lama) || empty($password_baru) || empty($konfirmasi)) {
    die("Semua field harus diisi!");
}

// Ambil password lama dari database
$stmt = $conn->prepare("SELECT password FROM guru WHERE id_guru = ?");
$stmt->bind_param("i", $id_guru);
$stmt->execute();
$stmt->bind_result($password_db);
$stmt->fetch();
$stmt->close();

// Password default
$default_password = "guru123";

// Cek password lama benar
$cek_password_lama = false;
if (empty($password_db)) {
    // Jika belum ada password, pakai default
    if ($password_lama === $default_password) {
        $cek_password_lama = true;
    }
} else {
    // Password sudah terenkripsi
    if (password_verify($password_lama, $password_db)) {
        $cek_password_lama = true;
    }
}

if (!$cek_password_lama) {
    echo "<script>alert('Password lama salah!'); window.location.href='ubah_password.php';</script>";
    exit;
}

// Cek konfirmasi password
if ($password_baru !== $konfirmasi) {
    echo "<script>alert('Konfirmasi password tidak cocok!'); window.location.href='ubah_password.php';</script>";
    exit;
}

// Hash password baru
$hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);

// Update password baru
$stmt = $conn->prepare("UPDATE guru SET password = ? WHERE id_guru = ?");
$stmt->bind_param("si", $hashed_password, $id_guru);
if ($stmt->execute()) {
    echo "<script>alert('✅ Password berhasil diubah!'); window.location.href='profil_guru.php';</script>";
} else {
    echo "<script>alert('Terjadi kesalahan saat mengubah password!'); window.location.href='ubah_password.php';</script>";
}
$stmt->close();
?>

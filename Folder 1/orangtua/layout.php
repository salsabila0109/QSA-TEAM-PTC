<?php
// orangtua/layout.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'orangtua') {
    header("Location: ../login.php"); exit;
}
$nama_pengguna = $_SESSION['nama_pengguna'] ?? ($_SESSION['username'] ?? 'Orangtua');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?? 'Orangtua | PresenTech' ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand fw-semibold" href="/orangtua/dashboard_orangtua.php">PresenTech</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="/orangtua/anak_saya.php">Anak Saya</a></li>
        <li class="nav-item"><a class="nav-link" href="/orangtua/riwayat_absensi.php">Riwayat Absensi</a></li>
        <li class="nav-item"><a class="nav-link" href="/orangtua/notifikasi.php">Notifikasi</a></li>
      </ul>
      <div class="d-flex gap-3 align-items-center">
        <span class="text-muted small">Halo, <?= htmlspecialchars($nama_pengguna) ?></span>
        <a href="/orangtua/profil_orangtua.php" class="btn btn-sm btn-outline-secondary">Profil</a>
        <a href="/logout.php" class="btn btn-sm btn-outline-danger">Keluar</a>
      </div>
    </div>
  </div>
</nav>
<div class="container my-4">
<?php $footer = '\n</div>\n<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>\n</body>\n</html>\n'; ?>
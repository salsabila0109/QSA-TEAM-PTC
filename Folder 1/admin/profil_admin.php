<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone sesuai daerah
date_default_timezone_set('Asia/Makassar');

// Simpan waktu login jika belum ada di session
if (!isset($_SESSION['last_login'])) {
    $_SESSION['last_login'] = date("Y-m-d H:i");
}
?>
<link rel="stylesheet" href="profil_admin.css">

<a href="dashboard_admin.html" class="btn-kembali" title="Kembali ke Dashboard">&#8592;</a>

<div class="profil-container">
    <div class="profil-card">
        <div class="profil-avatar">
            <!-- Bisa taruh img user di sini -->
        </div>
        <div class="profil-info">
            <h2><?php echo $_SESSION['username'] ?? 'Admin'; ?></h2>
            <p class="role">Administrator Sistem</p>
            <p class="last-login">
                Terakhir login: <?php echo $_SESSION['last_login']; ?>
            </p>
            <div class="profil-actions">
                <a href="edit_profil_admin.php" class="btn">🖊️ Edit Profil</a>
                <a href="logout.php" class="btn-logout" onclick="return confirm('Apakah Anda yakin ingin logout?');">Logout</a>
            </div>
        </div>
    </div>
</div>

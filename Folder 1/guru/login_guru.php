<?php
header("Location: ../login.php");
exit;

// ====== SESSION PERSIST 30 HARI ======
$DAYS = 30;
$lifetime = 60 * 60 * 24 * $DAYS;

ini_set('session.gc_maxlifetime', (string)$lifetime);
$params = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $lifetime,
    'path'     => $params['path'] ?? '/',
    'domain'   => $params['domain'] ?? '',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();
include '../db.php';

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['role_pengguna']) && $_SESSION['role_pengguna'] === 'guru') {
    header("Location: dashboard_guru.html");
    exit;
}

$error = '';
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Guru - PresenTech</title>
<link rel="stylesheet" href="login_guru.css">
</head>
<body>

<div class="login-container">
    <h2>Login Guru</h2>
    <form action="proses_login_guru.php" method="POST" autocomplete="on">
        <div class="input-group">
            <label>Username (Nama Guru)</label>
            <input type="text" name="username" required autocomplete="username">
        </div>
        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required autocomplete="current-password">
        </div>
        <?php if ($error): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        <button type="submit">Login</button>
    </form>
</div>

</body>
</html>

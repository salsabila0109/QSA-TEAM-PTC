<?php
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
require_once __DIR__ . "/../db.php";

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM orangtua WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && $password === 'orangtua12345') {
        session_regenerate_id(true);

        $_SESSION['id_pengguna']   = (int)$user['id_orangtua'];
        $_SESSION['id_orangtua']   = (int)$user['id_orangtua'];
        $_SESSION['role_pengguna'] = 'orangtua';
        $_SESSION['nama_pengguna'] = $user['nama_orangtua'] ?? $user['username'];
        $_SESSION['username']      = $user['username'] ?? $user['nama_orangtua'];
        $_SESSION['foto_orangtua'] = $user['foto'] ?? 'default-avatar.png';

        header("Location: dashboard_orangtua.html");
        exit;
    } else {
        $flash = "Username atau password salah";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Login Orangtua</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="login_orangtua.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>

<a href="javascript:history.back()" class="btn-kembali" title="Kembali">&#8592;</a>

<div class="login-wrapper">
    <div class="login-card">
        <h2>Login Orangtua</h2>

        <?php if ($flash): ?>
            <div class="alert"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <!-- PENTING: jangan autocomplete off, biar browser bisa simpan password -->
        <form method="post" autocomplete="on" novalidate>
            <input type="text" name="username" class="form-control" placeholder="Nomor Telepon" required autocomplete="username">

            <div class="password-wrapper">
                <input type="password" id="password" name="password" class="form-control" placeholder="Password" required autocomplete="current-password">
                <i class="fa-solid fa-eye toggle-password" aria-label="Tampilkan password" role="button" tabindex="0"></i>
            </div>

            <button type="submit" class="btn">Login</button>
        </form>
    </div>
</div>

<script>
document.querySelectorAll(".toggle-password").forEach(icon => {
    const toggle = () => {
        const input = icon.previousElementSibling;
        if (!input) return;

        if (input.type === "password") {
            input.type = "text";
            icon.classList.replace("fa-eye", "fa-eye-slash");
        } else {
            input.type = "password";
            icon.classList.replace("fa-eye-slash", "fa-eye");
        }
    };

    icon.addEventListener("click", toggle);
    icon.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.key === " ") {
            e.preventDefault();
            toggle();
        }
    });
});
</script>

</body>
</html>

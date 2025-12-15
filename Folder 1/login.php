<?php
// ====== SESSION PERSIST 30 HARI (samakan dengan login_guru / login_orangtua) ======
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
include 'db.php';

$error = "";

// ---- helper: deteksi hash password (bcrypt/argon) ----
function looks_like_hash($s) {
    if (!is_string($s) || $s === '') return false;
    return (strpos($s, '$2y$') === 0 || strpos($s, '$2a$') === 0 || strpos($s, '$argon2') === 0);
}

// ---- helper: verifikasi password (support hash + plaintext) ----
function verify_password_flexible($input, $stored) {
    if ($stored === null) return false;
    $stored = (string)$stored;

    // kalau hash -> password_verify
    if (looks_like_hash($stored)) {
        return password_verify($input, $stored);
    }

    // fallback plaintext (kalau DB kamu masih simpan polos)
    return hash_equals($stored, $input);
}

// ---- helper: fetch 1 row aman dari table tertentu ----
function fetch_one_by_username(mysqli $conn, $sql, $username) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = "Username dan password wajib diisi!";
    } else {

        // =========================
        // 1) COBA LOGIN ADMIN (table: user ATAU users)
        // =========================
        $admin = fetch_one_by_username($conn, "SELECT * FROM user WHERE username=? LIMIT 1", $username);

        // fallback kalau projectmu ternyata pakai tabel `user`
        if (!$admin) {
            $admin = fetch_one_by_username($conn, "SELECT * FROM user WHERE username=? LIMIT 1", $username);
        }

        if ($admin) {
            // role bisa `role_pengguna` atau `role`
            $role = $admin['role_pengguna'] ?? ($admin['role'] ?? '');
            $passDb = $admin['password'] ?? '';

            if (($role === 'admin') && verify_password_flexible($password, $passDb)) {
                // set session konsisten
                $_SESSION['username'] = $admin['username'] ?? $username;
                $_SESSION['role_pengguna'] = 'admin';
                $_SESSION['role'] = 'admin';

                // SIMPAN NAMA ADMIN KE localStorage, LALU REDIRECT (tetap seperti punyamu)
                $adminName = $admin['username'] ?? $username;

                echo "<!DOCTYPE html>
                <html lang='id'>
                <head>
                    <meta charset='UTF-8'>
                    <title>Redirect...</title>
                </head>
                <body>
                    <script>
                        localStorage.setItem('adminName', " . json_encode($adminName) . ");
                        window.location.href = 'admin/dashboard_admin.html';
                    </script>
                </body>
                </html>";
                exit;
            }
        }

        // =========================
        // 2) COBA LOGIN GURU (table: guru, kolom nama_guru)
        // =========================
        $guru = fetch_one_by_username($conn, "SELECT * FROM guru WHERE nama_guru=? LIMIT 1", $username);

        if ($guru) {
            // aturan kamu sebelumnya:
            // - kalau kolom password di DB ada & hash -> verify
            // - kalau kosong -> default "guru123"
            $default_password = "guru123";
            $passGuruDb = $guru['password'] ?? '';

            $ok = false;
            if (!empty($passGuruDb)) {
                $ok = verify_password_flexible($password, $passGuruDb);
            } else {
                $ok = hash_equals($default_password, $password);
            }

            if ($ok) {
                session_regenerate_id(true);

                $_SESSION['id_pengguna'] = (int)($guru['id_guru'] ?? 0);
                $_SESSION['role_pengguna'] = 'guru';
                $_SESSION['role'] = 'guru';

                $_SESSION['nama_guru'] = $guru['nama_guru'] ?? $username;
                $_SESSION['nama_pengguna'] = $guru['nama_guru'] ?? $username;
                $_SESSION['username'] = $guru['nama_guru'] ?? $username;

                header("Location: guru/dashboard_guru.html");
                exit;
            }
        }

        // =========================
        // 3) COBA LOGIN ORANGTUA (table: orangtua, kolom username)
        // =========================
        $ortu = fetch_one_by_username($conn, "SELECT * FROM orangtua WHERE username=? LIMIT 1", $username);

        if ($ortu) {
            // kompatibel dengan 2 versi kamu:
            // - versi lama: password disimpan di DB (plaintext)
            // - versi baru: password default hardcode
            //
            // Kamu sebut password orangtua "12345" (sementara file lama pakai 'orangtua12345').
            // Maka saya terima keduanya agar tidak bikin kamu bolak-balik.
            $passOrtuDb = $ortu['password'] ?? '';
            $default1 = "12345";
            $default2 = "orangtua12345";

            $ok = false;
            if (!empty($passOrtuDb)) {
                $ok = verify_password_flexible($password, $passOrtuDb);
            } else {
                $ok = (hash_equals($default1, $password) || hash_equals($default2, $password));
            }

            // Kalau DB ada password tapi user masih pakai default, tetap izinkan juga (opsional)
            if (!$ok) {
                $ok = (hash_equals($default1, $password) || hash_equals($default2, $password));
            }

            if ($ok) {
                session_regenerate_id(true);

                $_SESSION['id_pengguna']   = (int)($ortu['id_orangtua'] ?? 0);
                $_SESSION['id_orangtua']   = (int)($ortu['id_orangtua'] ?? 0);
                $_SESSION['role_pengguna'] = 'orangtua';
                $_SESSION['role'] = 'orangtua';

                $_SESSION['nama_pengguna'] = $ortu['nama_orangtua'] ?? ($ortu['username'] ?? $username);
                $_SESSION['username']      = $ortu['username'] ?? $username;
                $_SESSION['foto_orangtua'] = $ortu['foto'] ?? 'default-avatar.png';

                header("Location: orangtua/dashboard_orangtua.html");
                exit;
            }
        }

        // kalau sampai sini, berarti tidak ada yang cocok
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - PresenTech</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="tombol_panah.css"> 
</head>
<body>
    </a>

    <div class="login-card">
        <h2>Login Present Tech</h2>

        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

        <form method="post">
            <label>Username</label>
            <input type="text" name="username" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit" class="btn-login">Login</button>
        </form>
    </div>
</body>
</html>

<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM user WHERE username='$username' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_pengguna'] = $user['role_pengguna'];

            switch ($user['role_pengguna']) {
                case 'admin':
                    header("Location: admin/dashboard_admin.php");
                    break;
                case 'guru':
                    header("Location: guru/dashboard_guru.php");
                    break;
                case 'orangtua':
                    header("Location: ortu/dashboard_orangtua.php");
                    break;
                default:
                    $error = "Role pengguna tidak dikenali!";
                    break;
            }
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
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
    
    <a href="index.php" class="btn-back-arrow">
        <i class="fas fa-arrow-left"></i>
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

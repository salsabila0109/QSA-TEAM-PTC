<?php
session_start();
include 'db.php';

if(isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Query login orangtua
    $stmt = $conn->prepare("SELECT * FROM orangtua WHERE username=? AND password=?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $_SESSION['role_pengguna'] = 'orangtua';
        $_SESSION['username'] = $row['username'];
        $_SESSION['id_orangtua'] = $row['id_orangtua'];
        header("Location: orangtua/dashboard_orangtua.php");
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Orangtua</title>
    <style>
        body { font-family: Arial; background:#f5f5f5; display:flex; justify-content:center; align-items:center; height:100vh; }
        .login-box { background:white; padding:30px; border-radius:10px; box-shadow:0 3px 10px rgba(0,0,0,0.2);}
        input { display:block; width:100%; margin:10px 0; padding:8px; }
        button { padding:8px 16px; background:#00796b; color:white; border:none; border-radius:5px; cursor:pointer; }
        button:hover { background:#004d40; }
        .error { color:red; }
    </style>
</head>
<body>
<div class="login-box">
    <h2>Login Orangtua</h2>
    <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>
</div>
</body>
</html>

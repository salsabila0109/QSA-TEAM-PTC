<?php
header("Location: ../login.php");
exit;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM guru WHERE nama_guru = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $guru = $result->fetch_assoc();

        // Password default
        $default_password = "guru123";
        $login_ok = false;

        if (!empty($guru['password'])) {
            // jika password di DB sudah diset dan terenkripsi
            if (password_verify($password, $guru['password'])) {
                $login_ok = true;
            }
        } else {
            // jika password di DB kosong → hanya boleh login dengan default password
            if ($password === $default_password) {
                $login_ok = true;
            }
        }

        if ($login_ok) {
            $_SESSION['id_pengguna'] = $guru['id_guru'];
            $_SESSION['role_pengguna'] = 'guru';
            $_SESSION['nama_guru'] = $guru['nama_guru'];
            header("Location: dashboard_guru.html");
            exit;
        } else {
            header("Location: login_guru.php?error=Password salah");
            exit;
        }

    } else {
        header("Location: login_guru.php?error=Nama guru tidak ditemukan");
        exit;
    }
} else {
    header("Location: login_guru.php");
    exit;
}
?>

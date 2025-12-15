<?php
// Alias: semua login dipusatkan di login.php
header("Location: login.php");
exit;


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

   
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

    
        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            
            if ($row['role'] == 'admin') {
                header("Location: dashboard_admin.php");
            } elseif ($row['role'] == 'guru') {
                header("Location: dashboard_guru.php");
            } elseif ($row['role'] == 'orangtua') {
                header("Location: dashboard_orangtua.php");
            }
            exit();
        } else {
            echo "Password salah!";
        }
    } else {
        echo "Username tidak ditemukan!";
    }
}
?>

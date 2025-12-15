<?php
session_start();

// Hapus semua session
$_SESSION = [];
session_destroy();

// Redirect ke login orangtua
header("Location: login_orangtua.php");
exit;
?>

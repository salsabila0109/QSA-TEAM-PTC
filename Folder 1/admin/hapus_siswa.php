<?php
include '../db.php';
$id = $_GET['id'];
$conn->query("DELETE FROM siswa WHERE id_siswa=$id");
header("Location: siswa.php");
exit;
?>

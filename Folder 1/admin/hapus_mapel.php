<?php
session_start();
include '../db.php';

// Cek admin login
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Cek apakah ada id mapel
if(isset($_GET['id'])){
    $id = intval($_GET['id']); // pastikan ID integer

    // Hapus data mapel
    $stmt = $conn->prepare("DELETE FROM mata_pelajaran WHERE id_mata_pelajaran = ?");
    $stmt->bind_param("i", $id);

    if($stmt->execute()){
        $stmt->close();
        // Redirect kembali ke halaman data_mapel
        header("Location: data_mapel.php?message=hapus_success");
        exit;
    } else {
        $error = "Terjadi kesalahan saat menghapus: " . $stmt->error;
        $stmt->close();
    }
} else {
    $error = "ID mapel tidak ditemukan!";
}

// Jika ada error tampilkan
if(isset($error)){
    echo "<p style='color:red;'>$error</p>";
    echo '<p><a href="data_mapel.php">Kembali</a></p>';
}
?>

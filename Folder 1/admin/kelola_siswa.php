// admin/kelola_siswa.php
session_start();
include '../db.php';
include 'fungsi_validasi.php';

// Pastikan hanya admin yang bisa akses
if ($_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Tambah Siswa
if(isset($_POST['tambah_siswa'])){
    $nis = $_POST['nis'];
    $nama = $_POST['nama'];
    $kelas = $_POST['id_kelas'];
    $telepon = $_POST['telepon'];

    // Extend: Validasi Data
    $error = validasi_siswa($nis, $nama, $telepon);
    if($error){
        echo "<p style='color:red;'>$error</p>";
    } else {
        $stmt = $conn->prepare("INSERT INTO siswa (nis, nama, id_kelas, telepon) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $nis, $nama, $kelas, $telepon);
        $stmt->execute();
        echo "<p style='color:green;'>Siswa berhasil ditambahkan!</p>";
    }
}

// Edit dan Hapus Siswa bisa dibuat serupa, pakai POST + validasi
?>
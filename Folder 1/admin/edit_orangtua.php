<?php
include '../db.php';
session_start();

// 🔒 Cek role admin
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// 🔹 Ambil ID orangtua dari URL
if (!isset($_GET['id'])) {
    echo "ID orangtua tidak ditemukan!";
    exit;
}

$id = $_GET['id'];
$query = mysqli_query($conn, "SELECT * FROM orangtua WHERE id_orangtua = '$id'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo "Data orangtua tidak ditemukan!";
    exit;
}

// 🔹 Update jika form dikirim
if (isset($_POST['update'])) {
    $nama = $_POST['nama_orangtua'];
    $nohp = $_POST['no_hp'];
    $idsiswa = $_POST['id_siswa'];

    $update = mysqli_query($conn, "UPDATE orangtua SET 
        nama_orangtua = '$nama',
        no_hp = '$nohp',
        id_siswa = '$idsiswa'
        WHERE id_orangtua = '$id'");

   if ($update) {
    echo "<script>alert('Data berhasil diperbarui!'); window.location='manajemen_data_orangtua.php';</script>";
} else {
    echo "<script>alert('Gagal memperbarui data!');</script>";
}

}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Data Orangtua</title>
    <link rel="stylesheet" href="tambah_orangtua.css"> <!-- pakai css sama dengan tambah -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="form-container">
    <h2><i class="fas fa-user-edit"></i> Edit Data Orang Tua</h2>

    <form method="POST">
        <label for="nama_orangtua">Nama Orangtua:</label>
        <input type="text" name="nama_orangtua" id="nama_orangtua" value="<?= htmlspecialchars($data['nama_orangtua']); ?>" required>

        <label for="no_hp">No. HP:</label>
        <input type="text" name="no_hp" id="no_hp" value="<?= htmlspecialchars($data['no_hp']); ?>" required>

        <label for="cariSiswa">Cari Siswa:</label>
        <input type="text" id="cariSiswa" placeholder="Ketik nama siswa..." autocomplete="off" required>
        <div id="hasilCari" class="hasil-cari"></div>

        <!-- Hidden input untuk menyimpan ID siswa terpilih -->
        <input type="hidden" name="id_siswa" id="idSiswaTerpilih" value="<?= $data['id_siswa']; ?>">

        <div class="button-group">
            <button type="submit" name="update" class="submit-btn">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
            <a href="manajemen_data_orangtua.php" class="btn-kembali" title="Kembali">←</a>
        </div>
    </form>
</div>

<script>
// Live search siswa
document.getElementById('cariSiswa').addEventListener('keyup', function() {
    const keyword = this.value.trim();
    const hasilDiv = document.getElementById('hasilCari');

    if (keyword.length > 0) {
        fetch('cari_siswa.php?q=' + encodeURIComponent(keyword))
        .then(response => response.text())
        .then(data => {
            hasilDiv.innerHTML = data;
            hasilDiv.style.display = 'block';
        });
    } else {
        hasilDiv.innerHTML = '';
        hasilDiv.style.display = 'none';
    }
});

// Pilih siswa dari hasil pencarian
function pilihSiswa(id, nama) {
    document.getElementById('cariSiswa').value = nama;
    document.getElementById('idSiswaTerpilih').value = id;
    document.getElementById('hasilCari').innerHTML = '';
}
</script>
</body>
</html>

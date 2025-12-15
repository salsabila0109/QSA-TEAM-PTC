<?php
session_start();
include '../db.php';

// Cek role admin
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";

// Jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_orangtua = $_POST['nama_orangtua'];
    $no_hp = $_POST['no_hp'];
    $id_siswa = $_POST['id_siswa'];

    // Username = nomor HP, password default = orangtua123
    $username = $no_hp;
    $password_plain = "orangtua123";
    $password = password_hash($password_plain, PASSWORD_DEFAULT);

    // Kalau tidak ada foto, set null (biar sesuai struktur tabel)
    $foto = null;

    // ✅ query sesuai struktur tabel kamu
    $query = "INSERT INTO orangtua (nama_orangtua, no_hp, username, password, id_siswa, foto)
              VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssis", $nama_orangtua, $no_hp, $username, $password, $id_siswa, $foto);

    if ($stmt->execute()) {
        $message = "✅ Data orangtua berhasil ditambahkan!<br>
        Username: <b>$username</b> | Password awal: <b>$password_plain</b>";
    } else {
        $message = "❌ Gagal menambahkan data: " . $conn->error;
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Orang Tua</title>
    <link rel="stylesheet" href="tambah_orangtua.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="form-container">
    <h2><i class="fas fa-user-plus"></i> Tambah Data Orang Tua</h2>

    <?php if (!empty($message)): ?>
        <div class="message"><?= $message; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="nama_orangtua">Nama Orang Tua:</label>
        <input type="text" name="nama_orangtua" id="nama_orangtua" placeholder="Masukkan nama orang tua" required>

        <label for="no_hp">No.Telepon:</label>
        <input type="text" name="no_hp" id="no_hp" placeholder="08xxxxxxxxxx" required>

        <label for="cariSiswa">Cari Siswa:</label>
        <input type="text" id="cariSiswa" placeholder="Ketik nama siswa..." autocomplete="off" required>
        <div id="hasilCari" class="hasil-cari"></div>

        <!-- Hidden input untuk menyimpan ID siswa terpilih -->
        <input type="hidden" name="id_siswa" id="idSiswaTerpilih">

        <div class="button-group">
            <button type="submit" class="submit-btn">
                <i class="fas fa-save"></i> Simpan Data
            </button>
            
            <a href="manajemen_data_orangtua.php" class="btn-kembali" title="Kembali">←</a>
</a>

            </a>
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

// Ketika memilih siswa dari hasil pencarian
function pilihSiswa(id, nama) {
    document.getElementById('cariSiswa').value = nama;
    document.getElementById('idSiswaTerpilih').value = id;
    document.getElementById('hasilCari').innerHTML = '';
}
</script>
</body>
</html>

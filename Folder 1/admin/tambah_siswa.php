<?php
session_start();
include '../db.php';


if ($_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}


$kelas_result = $conn->query("SELECT * FROM kelas ORDER BY nama_kelas ASC");



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis = $_POST['nis'];
    $nama = $_POST['nama'];
    $id_kelas = $_POST['id_kelas']; 
    $telepon = $_POST['telepon'];

    
    $cek_nis = $conn->prepare("SELECT id_siswa FROM siswa WHERE nis = ?");
    $cek_nis->bind_param("s", $nis);
    $cek_nis->execute();
    $cek_nis->store_result();

    if ($cek_nis->num_rows > 0) {
        $error = "NIS sudah terdaftar, silakan gunakan NIS lain.";
    } else {
        

        $sql = "INSERT INTO siswa (nis, nama_siswa, id_kelas, nomor_telepon_orangtua, tanggal_data_dibuat)
                VALUES ('$nis', '$nama', '$id_kelas', '$telepon', NOW())";

        if ($conn->query($sql)) {
            header("Location: data_siswa.php"); 
            exit;
        } else {
            $error = "Gagal menambah siswa: " . $conn->error;
        }
    }
    $cek_nis->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Siswa</title>
    <link rel="stylesheet" href="tambah_siswa.css">
</head>
<body>
<header>Tambah Data Siswa</header>
<div class="container">
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="post" id="formSiswa">
        <label>NIS</label>
        <input type="text" name="nis" id="nis" required>
        <p id="nisError" style="color:red;"></p>

        <label>Nama Siswa</label>
        <input type="text" name="nama" id="nama" required>
        <p id="namaError" style="color:red;"></p>

        <label>Kelas</label>
        <select name="id_kelas" required>
            <option value="">-- Pilih Kelas --</option>
            <?php while ($k = $kelas_result->fetch_assoc()): ?>
                <option value="<?= $k['id_kelas'] ?>"><?= $k['nama_kelas'] ?></option>
            <?php endwhile; ?>
        </select>

        <label>No. Telepon Orangtua</label>
        <input type="text" name="telepon" id="telepon">
        <p id="teleponError" style="color:red;"></p>

        <div class="btn-group">
            

            <button type="submit" class="btn-submit">Simpan</button>

            

            <a href="admin_dashboard.php" class="btn-tambah-siswa">Kembali</a>
        </div>
    </form>
</div>


<script>

document.getElementById("nis").addEventListener("blur", function() {
    let nis = this.value;
    fetch("validasi_data.php?type=nis&value=" + nis)
        .then(res => res.json())
        .then(data => {
            document.getElementById("nisError").innerText = data.valid ? "" : data.message;
        });
});


document.getElementById("nama").addEventListener("blur", function() {
    let nama = this.value;
    fetch("validasi_data.php?type=nama&value=" + nama)
        .then(res => res.json())
        .then(data => {
            document.getElementById("namaError").innerText = data.valid ? "" : data.message;
        });
});



document.getElementById("telepon").addEventListener("blur", function() {
    let telp = this.value;
    if (telp !== "") { 
        fetch("validasi_data.php?type=telepon&value=" + telp)
            .then(res => res.json())
            .then(data => {
                document.getElementById("teleponError").innerText = data.valid ? "" : data.message;
            });
    } else {
        document.getElementById("teleponError").innerText = "";
    }
});


document.getElementById("formSiswa").addEventListener("submit", async function(e) {
    e.preventDefault();

    
    let nisValid = await fetch("validasi_data.php?type=nis&value=" + document.getElementById("nis").value)
        .then(res => res.json()).then(d => { document.getElementById("nisError").innerText = d.valid ? "" : d.message; return d.valid; });

    let namaValid = await fetch("validasi_data.php?type=nama&value=" + document.getElementById("nama").value)
        .then(res => res.json()).then(d => { document.getElementById("namaError").innerText = d.valid ? "" : d.message; return d.valid; });

    let teleponValid = await fetch("validasi_data.php?type=telepon&value=" + document.getElementById("telepon").value)
        .then(res => res.json()).then(d => { document.getElementById("teleponError").innerText = d.valid ? "" : d.message; return d.valid; });

    if (nisValid && namaValid && teleponValid) {
        this.submit();
    } else {
        alert("Periksa kembali input Anda, ada yang belum valid.");
    }
});
</script>
</body>
</html>

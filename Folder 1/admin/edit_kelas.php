<?php
session_start();
include '../db.php';

// Cek admin login
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: data_kelas.php");
    exit;
}

$id_kelas = $_GET['id'];

// Ambil data kelas yang sedang diedit
$query = "SELECT * FROM kelas WHERE id_kelas = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_kelas);
$stmt->execute();
$result = $stmt->get_result();
$kelas = $result->fetch_assoc();

if (!$kelas) {
    echo "Data kelas tidak ditemukan!";
    exit;
}

// Ambil semua nama kelas untuk dropdown
$kelas_list = [];
$query2 = "SELECT nama_kelas FROM kelas ORDER BY nama_kelas ASC";
$result2 = $conn->query($query2);
while ($row = $result2->fetch_assoc()) {
    $kelas_list[] = $row['nama_kelas'];
}

// Ambil semua guru untuk dropdown Wali Kelas
$guru_list = [];
$query3 = "SELECT id_guru, nama_guru FROM guru ORDER BY nama_guru ASC";
$result3 = $conn->query($query3);
while ($row = $result3->fetch_assoc()) {
    $guru_list[] = $row;
}

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kelas = $_POST['nama_kelas'];
    $wali_kelas = $_POST['wali_kelas'];

    $update = "UPDATE kelas SET nama_kelas = ?, wali_kelas = ? WHERE id_kelas = ?";
    $stmt_update = $conn->prepare($update);
    $stmt_update->bind_param("ssi", $nama_kelas, $wali_kelas, $id_kelas);

    if ($stmt_update->execute()) {
        header("Location: data_kelas.php");
        exit;
    } else {
        $message = "Gagal memperbarui data kelas!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Kelas</title>
<link rel="stylesheet" href="edit_kelas.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container">
    <h1><i class="fas fa-edit"></i> Edit Kelas</h1>

    <?php if(isset($message)) echo "<p class='message'>$message</p>"; ?>

    <form action="" method="POST">
        <label for="nama_kelas">Nama Kelas</label>
        <select name="nama_kelas" id="nama_kelas" required>
            <?php
            foreach ($kelas_list as $nk) {
                $selected = ($nk == $kelas['nama_kelas']) ? "selected" : "";
                echo "<option value='".htmlspecialchars($nk)."' $selected>".htmlspecialchars($nk)."</option>";
            }
            ?>
        </select>

        <label for="wali_kelas">Wali Kelas</label>
        <select name="wali_kelas" id="wali_kelas" required>
            <?php
            foreach ($guru_list as $g) {
                $selected = ($g['nama_guru'] == $kelas['wali_kelas']) ? "selected" : "";
                echo "<option value='".htmlspecialchars($g['nama_guru'])."' $selected>".htmlspecialchars($g['nama_guru'])."</option>";
            }
            ?>
        </select>

        <div class="form-buttons">
            <button type="submit" class="btn save-btn"><i class="fas fa-save"></i> Simpan Perubahan</button>
        </div>
    </form>
</div>
</body>
</html>

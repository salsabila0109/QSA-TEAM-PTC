<?php
session_start();
include '../db.php';

// Cek admin login
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$id_kelas = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_kelas <= 0) {
    header("Location: manajemen_data_kelas.php");
    exit;
}

// Ambil data kelas yang sedang diedit
$query = "SELECT * FROM kelas WHERE id_kelas = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_kelas);
$stmt->execute();
$result = $stmt->get_result();
$kelas = $result->fetch_assoc();
$stmt->close();

if (!$kelas) {
    header("Location: manajemen_data_kelas.php?error=1");
    exit;
}

// Ambil semua guru untuk dropdown Wali Kelas
$guru_list = [];
$query_guru = "SELECT id_guru, nama_guru FROM guru ORDER BY nama_guru ASC";
$result_guru = $conn->query($query_guru);
while ($row = $result_guru->fetch_assoc()) {
    $guru_list[] = $row;
}

// Proses update
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kelas = trim($_POST['nama_kelas'] ?? '');
    $wali_kelas_raw = trim($_POST['wali_kelas'] ?? '');

    if ($nama_kelas === '') {
        $message = "Gagal memperbarui data kelas: Nama kelas wajib diisi.";
    } else {
        // jika wali_kelas kosong -> set NULL agar benar-benar NULL, bukan 0
        if ($wali_kelas_raw === '') {
            $update = "UPDATE kelas SET nama_kelas = ?, wali_kelas = NULL WHERE id_kelas = ?";
            $stmt_update = $conn->prepare($update);
            $stmt_update->bind_param("si", $nama_kelas, $id_kelas);
        } else {
            $wali_kelas = (int)$wali_kelas_raw;
            $update = "UPDATE kelas SET nama_kelas = ?, wali_kelas = ? WHERE id_kelas = ?";
            $stmt_update = $conn->prepare($update);
            $stmt_update->bind_param("sii", $nama_kelas, $wali_kelas, $id_kelas);
        }

        if ($stmt_update->execute()) {
            $stmt_update->close();
            // balik ke manajemen + notif
            header("Location: manajemen_data_kelas.php?updated=1");
            exit;
        } else {
            $message = "Gagal memperbarui data kelas: " . $stmt_update->error;
            $stmt_update->close();
        }
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

<!-- Tombol Kembali -->
<a href="javascript:history.back()" class="btn-kembali" title="Kembali">&#8592;</a>

<div class="container">
    <h1><i class="fas fa-edit"></i> Edit Kelas</h1>

    <?php if($message) echo "<p class='message'>".htmlspecialchars($message)."</p>"; ?>

    <form action="" method="POST" autocomplete="on">
        <label for="nama_kelas">Nama Kelas</label>
        <input type="text" name="nama_kelas" id="nama_kelas"
               value="<?= htmlspecialchars($kelas['nama_kelas']); ?>" required>

        <label for="wali_kelas">Nama Wali Kelas</label>
        <select name="wali_kelas" id="wali_kelas">
            <option value="">-- Pilih Wali Kelas --</option>
            <?php
            foreach ($guru_list as $g) {
                $selected = ((int)$g['id_guru'] === (int)$kelas['wali_kelas']) ? "selected" : "";
                echo "<option value='".htmlspecialchars($g['id_guru'])."' $selected>".htmlspecialchars($g['nama_guru'])."</option>";
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

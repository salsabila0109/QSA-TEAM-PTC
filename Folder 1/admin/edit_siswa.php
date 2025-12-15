<?php
session_start();
include '../db.php';

if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// ambil id dengan aman
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: manajemen_data_siswa.html");
    exit;
}

// ambil data siswa (prepared)
$stmt = $conn->prepare("SELECT * FROM siswa WHERE id_siswa = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$siswa = $res->fetch_assoc();
$stmt->close();

if (!$siswa) {
    header("Location: manajemen_data_siswa.html");
    exit;
}

// list kelas
$kelas_result = $conn->query("SELECT * FROM kelas ORDER BY nama_kelas ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis     = trim($_POST['nis'] ?? '');
    $nama    = trim($_POST['nama'] ?? '');
    $id_kls  = (int)($_POST['id_kelas'] ?? 0);
    $telepon = trim($_POST['telepon'] ?? '');

    // validasi minimal
    if ($nis === '' || $nama === '' || $id_kls <= 0) {
        $error = "Data wajib belum lengkap.";
    } else {
        // update pakai prepared statement
        $up = $conn->prepare("
            UPDATE siswa
            SET nis = ?, nama_siswa = ?, id_kelas = ?, nomor_telepon_orangtua = ?
            WHERE id_siswa = ?
        ");
        $up->bind_param("ssisi", $nis, $nama, $id_kls, $telepon, $id);

        $ok = $up->execute();
        $up->close();

        // redirect ke halaman manajemen dengan notif + id yang diupdate
        if ($ok) {
            header("Location: manajemen_data_siswa.html?updated=1&updated_id=".$id);
            exit;
        } else {
            $error = "Gagal update: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Siswa</title>
    <link rel="stylesheet" href="edit_siswa.css">
</head>
<body>

<!-- Tombol Kembali -->
<a href="javascript:history.back()" class="btn-kembali" title="Kembali">&#8592;</a>

<div class="container">
    <h2>Edit Siswa</h2>

    <?php if (!empty($error)): ?>
        <p style="color:#c00; margin: 0 0 10px;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" autocomplete="on">
        <label>NIS</label>
        <input type="text" name="nis" value="<?= htmlspecialchars($siswa['nis']) ?>" required>

        <label>Nama Siswa</label>
        <input type="text" name="nama" value="<?= htmlspecialchars($siswa['nama_siswa']) ?>" required>

        <label>Kelas</label>
        <select name="id_kelas" required>
            <option value="">-- Pilih Kelas --</option>
            <?php while ($k = $kelas_result->fetch_assoc()): ?>
                <option value="<?= (int)$k['id_kelas'] ?>" <?= ((int)$k['id_kelas'] === (int)$siswa['id_kelas']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($k['nama_kelas']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>No. Telepon Orangtua</label>
        <input type="text" name="telepon" value="<?= htmlspecialchars($siswa['nomor_telepon_orangtua'] ?? '') ?>">

        <div class="btn-group">
            <button type="submit" class="btn btn-submit">Update</button>
        </div>
    </form>
</div>
</body>
</html>

<?php
include '../db.php';
session_start();

// Pastikan admin login
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Tambah data guru
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['tambah'])) {
    $id_guru = $_POST['id_guru'];
    $nip = $_POST['nip'];
    $nama_guru = $_POST['nama_guru'];
    $no_telepon = $_POST['no_telepon'];

    // Ubah kolom agar sesuai dengan database kamu: pakai tanggal_data_dibuat
    $sql = "INSERT INTO guru (id_guru, nip, nama_guru, no_telepon, tanggal_data_dibuat) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $id_guru, $nip, $nama_guru, $no_telepon);

    if ($stmt->execute()) {
        $message = "✅ Data guru berhasil ditambahkan!";
    } else {
        $message = "❌ Gagal menambahkan data: " . $conn->error;
    }
    $stmt->close();
}

// Hapus data guru
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM guru WHERE id_guru = '$id'");
    header("Location: tambah_guru.php");
    exit;
}

// Ambil semua data guru
$result = mysqli_query($conn, "SELECT * FROM guru ORDER BY id_guru DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Guru</title>
    <link rel="stylesheet" href="tambah_guru.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h2>Manajemen Data Guru</h2>

        <?php if ($message): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST" class="form-tambah">
            <div class="form-group">
                <label for="id_guru">ID Guru:</label>
                <input type="number" id="id_guru" name="id_guru" required>
            </div>

            <div class="form-group">
                <label for="nip">NIP:</label>
                <input type="text" id="nip" name="nip" required>
            </div>

            <div class="form-group">
                <label for="nama_guru">Nama Guru:</label>
                <input type="text" id="nama_guru" name="nama_guru" required>
            </div>

            <div class="form-group">
                <label for="no_telepon">No. Telepon:</label>
                <input type="text" id="no_telepon" name="no_telepon" required>
            </div>

            <div class="btn-center">
                <button type="submit" name="tambah" class="btn-submit"><i class="fa-solid fa-user-plus"></i> Tambah Guru</button>
            </div>
        </form>

        <h3>Daftar Guru</h3>
        <table>
            <thead>
                <tr>
                    <th>ID Guru</th>
                    <th>NIP</th>
                    <th>Nama Guru</th>
                    <th>No. Telepon</th>
                    <th>Tanggal Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id_guru']); ?></td>
                            <td><?= htmlspecialchars($row['nip']); ?></td>
                            <td><?= htmlspecialchars($row['nama_guru']); ?></td>
                            <td><?= htmlspecialchars($row['no_telepon']); ?></td>
                            <td><?= htmlspecialchars($row['tanggal_data_dibuat']); ?></td>
                            <td class="aksi">
                                <a href="?hapus=<?= $row['id_guru']; ?>" class="btn-hapus" onclick="return confirm('Yakin ingin menghapus data ini?')">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="empty">Belum ada data guru.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="back-container">
            <button class="btn-back" onclick="history.back()"><i class="fa-solid fa-arrow-left"></i> Kembali</button>
        </div>
    </div>
</body>
</html>

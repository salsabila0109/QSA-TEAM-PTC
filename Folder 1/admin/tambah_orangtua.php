<?php
session_start();
include '../db.php';

// Pastikan hanya admin yang bisa mengakses
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

    // Username otomatis dari nomor HP
    $username = $no_hp;
    // Password default (di-hash agar aman)
    $password = password_hash("orangtua12345", PASSWORD_DEFAULT);

    $query = "INSERT INTO orangtua 
              (nama_orangtua, no_hp, id_siswa, username, password, must_change_password, created_at)
              VALUES (?, ?, ?, ?, ?, 1, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssiss", $nama_orangtua, $no_hp, $id_siswa, $username, $password);

    if ($stmt->execute()) {
        $message = "✅ Data orang tua berhasil ditambahkan!<br>Username: <b>$username</b> | Password awal: <b>orangtua12345</b>";
    } else {
        $message = "❌ Gagal menambahkan data: " . $conn->error;
    }
    $stmt->close();
}

// Ambil data siswa untuk dropdown
$siswa_result = mysqli_query($conn, "SELECT id_siswa, nama_siswa FROM siswa ORDER BY nama_siswa ASC");
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
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <label>Nama Orang Tua:</label>
            <input type="text" name="nama_orangtua" placeholder="Masukkan nama orang tua" required>

            <label>Nomor HP:</label>
            <input type="text" name="no_hp" placeholder="08xxxxxxxxxx" required>

            <label>Pilih Siswa:</label>
            <select name="id_siswa" required>
                <option value="">-- Pilih Siswa --</option>
                <?php while ($row = mysqli_fetch_assoc($siswa_result)): ?>
                    <option value="<?php echo $row['id_siswa']; ?>">
                        <?php echo htmlspecialchars($row['nama_siswa']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

      <div class="button-group">
    <button type="submit" class="submit-btn">
        <i class="fas fa-save"></i> Simpan Data
    </button>
    <a href="javascript:history.back()" class="back-btn">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>


        </form>
    </div>
</body>
</html>

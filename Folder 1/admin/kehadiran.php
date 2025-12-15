<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

// Cek hak akses admin
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";

// === (Opsional) Tutup Sesi, biarkan saja di backend kalau ada halaman lain yang pakai ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tutup_sesi'])) {
    if (isset($_SESSION['id_sesi_aktif'])) {
        $id_sesi = $_SESSION['id_sesi_aktif'];
        $stmt = $conn->prepare("UPDATE absensi_mapel SET waktu_selesai_sesi = NOW() WHERE id_sesi = ?");
        $stmt->bind_param("i", $id_sesi);
        $stmt->execute();
        unset($_SESSION['id_sesi_aktif']);
        $message = "🕓 Sesi berhasil ditutup!";
    } else {
        $message = "⚠️ Tidak ada sesi aktif untuk ditutup!";
    }
}

// === SATU VIEW SAJA: LIHAT KEHADIRAN SISWA ===
// Ambil data: ID siswa, nama siswa, nama mapel, status, waktu
$sql = "
    SELECT 
        s.id_siswa,
        s.nama_siswa,
        mp.nama_mapel,
        a.status,
        a.waktu_absensi_tercatat
    FROM absensi_siswa a
    JOIN siswa s ON a.id_siswa = s.id_siswa
    JOIN absensi_mapel am ON a.id_sesi = am.id_sesi
    JOIN mata_pelajaran mp ON am.id_mata_pelajaran = mp.id_mata_pelajaran
    ORDER BY a.waktu_absensi_tercatat DESC
";

$data_view = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kehadiran Siswa</title>
    <link rel="stylesheet" href="kehadiran.css">
</head>
<body>
<div class="dashboard-container">

    <!-- Tombol Kembali -->
    <a href="javascript:history.back()" class="btn-kembali" title="Kembali">&#8592;</a>

    <h2>Pemantauan Kehadiran Siswa</h2>

    <?php if ($message): ?>
        <div class="alert"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Card Data Kehadiran -->
    <div class="card">
        <h3>📋 Data Kehadiran Siswa</h3>

        <?php if ($data_view && $data_view->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID Siswa</th>
                        <th>Nama Siswa</th>
                        <th>Mapel</th>
                        <th>Status</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $data_view->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id_siswa']) ?></td>
                        <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                        <td><?= htmlspecialchars($row['nama_mapel']) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                        <td><?= htmlspecialchars($row['waktu_absensi_tercatat']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Data kehadiran siswa belum tersedia.</p>
        <?php endif; ?>
    </div>

    <?php /*
    <!-- Kalau suatu saat mau munculkan lagi tombol tutup sesi, tinggal buka komentar ini -->
    <div class="card">
        <h3>🚪 Tutup Sesi Pelajaran</h3>
        <form method="POST">
            <button type="submit" name="tutup_sesi" class="close-btn">
                Tutup Sesi Sekarang
            </button>
        </form>
    </div>
    */ ?>

</div>
</body>
</html>

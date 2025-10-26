<?php
// admin/tampil_hasil_knn.php
session_start();

// pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// koneksi db (asumsi db.php ada di root project dan dipanggil sebagai ../db.php dari admin/)
$dbPath = __DIR__ . '/../db.php';
if (!file_exists($dbPath)) {
    die('File koneksi database (db.php) tidak ditemukan pada: ' . $dbPath);
}
require_once $dbPath;

// MUAT KNNAbsensi.php DENGAN Fallback PATHS
$possible = [
    __DIR__ . '/KNNAbsensi.php',           // admin/KNNAbsensi.php
    __DIR__ . '/services/KNNAbsensi.php',  // admin/services/...
    __DIR__ . '/../services/KNNAbsensi.php', // ../services/KNNAbsensi.php
];

$knnLoaded = false;
foreach ($possible as $p) {
    if (file_exists($p)) {
        require_once $p;
        $knnLoaded = true;
        break;
    }
}

if (!$knnLoaded || !class_exists('KNNAbsensi')) {
    // tampilkan pesan user-friendly agar nggak crash fatal
    $msg = "File KNNAbsensi.php tidak ditemukan atau class KNNAbsensi tidak tersedia. ";
    $msg .= "Cek apakah file ada di salah satu lokasi:\n" . implode("\n", $possible);
    die(nl2br(htmlspecialchars($msg)));
}

/* ===========================
   Konfigurasi semester/tahun
   =========================== */
$bulan = (int)date('n');
$semester = in_array($bulan, [7,8,9,10,11,12], true) ? 'Ganjil' : 'Genap';
$tahunNow = (int)date('Y');
$tahunAjar = in_array($bulan, [7,8,9,10,11,12], true) ? $tahunNow : ($tahunNow - 1);

/* Ambil data hadir per siswa untuk semester ini */
$hadirData = KNNAbsensi::getHadirCountsForSemester($conn, $semester, $tahunAjar, null);

/* Jika tombol update diklik: proses simpan/update ke tabel hasil_sistem_cerdas */
$infoMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_prediksi'])) {
    $tanggalProses = date('Y-m-d');
    $countUpdated = 0;
    foreach ($hadirData as $r) {
        $idSiswa = (int)$r['id_siswa'];
        $namaSiswa = $r['nama_siswa'];
        $jmlHadir = (int)$r['jml_hadir'];
        $status = KNNAbsensi::labelByFixedThreshold($jmlHadir);

        // cek ada / update / insert
        $cek = $conn->prepare("SELECT id_siswa FROM hasil_sistem_cerdas WHERE id_siswa = ?");
        $cek->bind_param("i", $idSiswa);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $upd = $conn->prepare("UPDATE hasil_sistem_cerdas SET nama_siswa = ?, jumlah_hadir = ?, status = ?, semester = ?, tanggal_hasil_diproses = ? WHERE id_siswa = ?");
            $upd->bind_param("sisssi", $namaSiswa, $jmlHadir, $status, $semester, $tanggalProses, $idSiswa);
            $upd->execute();
            $upd->close();
        } else {
            $ins = $conn->prepare("INSERT INTO hasil_sistem_cerdas (id_siswa, nama_siswa, jumlah_hadir, status, semester, tanggal_hasil_diproses) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->bind_param("isisss", $idSiswa, $namaSiswa, $jmlHadir, $status, $semester, $tanggalProses);
            $ins->execute();
            $ins->close();
        }
        $cek->close();
        $countUpdated++;
    }
    $infoMsg = "✅ Selesai memperbarui prediksi untuk {$countUpdated} siswa. (Semester: {$semester} {$tahunAjar}/".($tahunAjar+1).")";
    // reload hadirData setelah update (opsional)
    $hadirData = KNNAbsensi::getHadirCountsForSemester($conn, $semester, $tahunAjar, null);
}

/* Ambil isi tabel hasil_sistem_cerdas untuk ditampilkan (urut nama) */
$resAll = $conn->query("SELECT id_siswa, nama_siswa, jumlah_hadir, status, semester, tanggal_hasil_diproses FROM hasil_sistem_cerdas WHERE semester = '" . $conn->real_escape_string($semester) . "' ORDER BY nama_siswa ASC");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Hasil Prediksi Kedisiplinan - Admin</title>
    <link rel="stylesheet" href="tampil_hasil_knn.css">
    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
    <div class="wrap">
        <h2>Hasil Prediksi Kedisiplinan Siswa</h2>
        <p class="meta">Semester: <strong><?=htmlspecialchars($semester)?> <?= $tahunAjar ?>/<?= $tahunAjar+1 ?></strong>.
            Target: 90 hari. Batas: Tidak Disiplin ≤30; Kurang 31–60; Disiplin &gt;60.</p>

        <?php if ($infoMsg): ?>
            <div class="notice success"><?= nl2br(htmlspecialchars($infoMsg)) ?></div>
        <?php endif; ?>

        <form method="post" style="text-align:center; margin-bottom:12px;">
            <button type="submit" name="update_prediksi" class="btn-update">🔄 Perbarui Data Prediksi</button>
        </form>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID Siswa</th>
                        <th>Nama Siswa</th>
                        <th>Jumlah Hadir (semester)</th>
                        <th>Status</th>
                        <th>Tanggal Diproses</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($resAll && $resAll->num_rows > 0) {
                    $no = 1;
                    while ($row = $resAll->fetch_assoc()) {
                        $color = '#ffffff';
                        if ($row['status'] === 'Disiplin') $color = '#e6ffef';
                        if ($row['status'] === 'Kurang Disiplin') $color = '#fff9e6';
                        if ($row['status'] === 'Tidak Disiplin') $color = '#ffecec';
                        echo "<tr style='background:{$color}'>";
                        echo "<td>{$no}</td>";
                        echo "<td>".htmlspecialchars($row['id_siswa'])."</td>";
                        echo "<td>".htmlspecialchars($row['nama_siswa'])."</td>";
                        echo "<td>".(int)$row['jumlah_hadir']."</td>";
                        echo "<td>".htmlspecialchars($row['status'])."</td>";
                        echo "<td>".htmlspecialchars($row['tanggal_hasil_diproses'])."</td>";
                        echo "</tr>";
                        $no++;
                    }
                } else {
                    echo '<tr><td colspan="6" style="text-align:center; padding:14px;">Belum ada data prediksi. Tekan "Perbarui Data Prediksi" untuk generate dari tabel absensi_siswa.</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

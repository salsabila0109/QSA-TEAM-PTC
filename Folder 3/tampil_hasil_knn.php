<?php
session_start();

// pastikan hanya admin
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// koneksi DB
require_once __DIR__ . '/../db.php';

// muat KNNAbsensi (di dalamnya sudah require model_data_knn.php)
require_once __DIR__ . '/kNNAbsensi.php';

// buat objek KNN
$knn = new KNNAbsensi(5);  // K=5 (lebih stabil)

// ===========================
// Tentukan semester aktif
// ===========================
$bulan = (int)date('n');
$semester = in_array($bulan, [7,8,9,10,11,12]) ? 'Ganjil' : 'Genap';
$tahunNow = (int)date('Y');
$tahunAjar = ($semester === 'Ganjil') ? $tahunNow : $tahunNow - 1;

// ===========================
// Ambil jumlah hadir siswa semester ini
// ===========================
$hadirData = KNNAbsensi::getHadirCountsForSemester($conn, $semester, $tahunAjar);

// ===========================
// Jika tombol update ditekan
// ===========================
$infoMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_prediksi'])) {

    $tanggalProses = date('Y-m-d H:i:s');
    $count = 0;

    foreach ($hadirData as $row) {

        $idSiswa   = (int)$row['id_siswa'];
        $namaSiswa = $row['nama_siswa'];
        $jmlHadir  = (int)$row['jml_hadir'];

        // 🔥 Prediksi KNN dari model dataset
        $prediksi = $knn->predictOne([$jmlHadir]);

        // cek apakah sudah ada datanya
        $cek = $conn->prepare("SELECT id_siswa FROM hasil_sistem_cerdas WHERE id_siswa = ?");
        $cek->bind_param("i", $idSiswa);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            // update
            $upd = $conn->prepare("
                UPDATE hasil_sistem_cerdas
                SET nama_siswa = ?, jumlah_hadir = ?, status = ?, semester = ?, tanggal_hasil_diproses = ?
                WHERE id_siswa = ?
            ");
            $upd->bind_param(
                "sisssi",
                $namaSiswa,
                $jmlHadir,
                $prediksi,
                $semester,
                $tanggalProses,
                $idSiswa
            );
            $upd->execute();
            $upd->close();
        } else {
            // insert baru
            $ins = $conn->prepare("
                INSERT INTO hasil_sistem_cerdas (id_siswa, nama_siswa, jumlah_hadir, status, semester, tanggal_hasil_diproses)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $ins->bind_param(
                "isisss",
                $idSiswa,
                $namaSiswa,
                $jmlHadir,
                $prediksi,
                $semester,
                $tanggalProses
            );
            $ins->execute();
            $ins->close();
        }

        $cek->close();
        $count++;
    }

    $infoMsg = "✅ Berhasil memproses {$count} prediksi KNN untuk semester $semester $tahunAjar/" . ($tahunAjar + 1);
}

// ===========================
// Ambil data hasil prediksi
// ===========================
$sqlPrediksi = "
    SELECT id_siswa, nama_siswa, jumlah_hadir, status, semester, tanggal_hasil_diproses
    FROM hasil_sistem_cerdas
    WHERE semester = '".$conn->real_escape_string($semester)."'
    ORDER BY nama_siswa ASC
";
$resAll = $conn->query($sqlPrediksi);

// ===========================
// Export CSV jika tombol diklik
// ===========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_csv'])) {

    $resExport = $conn->query($sqlPrediksi);

    // Header untuk file CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=hasil_knn_' . $semester . '_' . $tahunAjar . '.csv');

    $output = fopen('php://output', 'w');

    // Header kolom
    fputcsv($output, [
        'No',
        'ID Siswa',
        'Nama Siswa',
        'Jumlah Hadir',
        'Status Prediksi',
        'Semester',
        'Tanggal Diproses'
    ]);

    $no = 1;
    if ($resExport && $resExport->num_rows > 0) {
        while ($row = $resExport->fetch_assoc()) {
            fputcsv($output, [
                $no++,
                $row['id_siswa'],
                $row['nama_siswa'],
                $row['jumlah_hadir'],
                $row['status'],
                $row['semester'],
                $row['tanggal_hasil_diproses']
            ]);
        }
    }

    fclose($output);
    exit; // penting, supaya HTML di bawah tidak ikut terkirim
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Hasil Prediksi KNN</title>
    <link rel="stylesheet" href="tampil_hasil_knn.css">
</head>
<body>

<!-- Tombol kembali (opsional, karena CSS-nya sudah ada) -->
<a href="dashboard_admin.html" class="btn-kembali">&#8592;</a>

<div class="wrap">
    <h2>Hasil Prediksi Kedisiplinan Siswa (KNN)</h2>
    <p class="meta">
        Semester:
        <strong><?= htmlspecialchars($semester) ?> <?= $tahunAjar ?>/<?= $tahunAjar+1 ?></strong><br>
        Prediksi berdasarkan dataset model menggunakan KNN Euclidean.
    </p>

    <?php if ($infoMsg): ?>
        <div class="notice success"><?= htmlspecialchars($infoMsg) ?></div>
    <?php endif; ?>

    <!-- Tombol perbarui prediksi -->
    <form method="post" style="text-align:center;margin-bottom:10px;">
        <button type="submit" name="update_prediksi" class="btn-update">🔄 Perbarui Prediksi</button>
    </form>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>ID Siswa</th>
                    <th>Nama Siswa</th>
                    <th>Jumlah Hadir</th>
                    <th>Status Prediksi</th>
                    <th>Tanggal Diproses</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($resAll && $resAll->num_rows > 0) {
                $no = 1;
                while ($row = $resAll->fetch_assoc()) {

                    $color = '#fff';
                    if ($row['status'] === 'Disiplin')        $color = '#e8fff0';
                    if ($row['status'] === 'Kurang Disiplin') $color = '#fff9e5';
                    if ($row['status'] === 'Tidak Disiplin')  $color = '#ffecec';

                    echo "<tr style='background:{$color}'>";
                    echo "<td>{$no}</td>";
                    echo "<td>".htmlspecialchars($row['id_siswa'])."</td>";
                    echo "<td>".htmlspecialchars($row['nama_siswa'])."</td>";
                    echo "<td>".htmlspecialchars($row['jumlah_hadir'])."</td>";
                    echo "<td>".htmlspecialchars($row['status'])."</td>";
                    echo "<td>".htmlspecialchars($row['tanggal_hasil_diproses'])."</td>";
                    echo "</tr>";
                    $no++;
                }
            } else {
                echo "<tr><td colspan='6' style='text-align:center;padding:12px;'>Belum ada prediksi. Klik tombol untuk memproses.</td></tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Tombol Export CSV di kiri bawah -->
<form method="post" class="export-form">
    <button type="submit" name="export_csv" class="btn-export">
        ⬇ Export CSV
    </button>
</form>

</body>
</html>

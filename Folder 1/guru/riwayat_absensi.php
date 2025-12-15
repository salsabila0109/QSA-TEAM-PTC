<?php
session_start();
include '../db.php';

// Cek login guru
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] != 'guru') {
    header("Location: ../login.php");
    exit;
}

// Ambil nama guru dari session
$nama_guru = $_SESSION['nama_guru'] ?? 'Guru';

// Tanggal hari ini
$tanggal_hari_ini = date('Y-m-d');

// Ambil filter dari form GET
$kelas_terpilih   = $_GET['kelas']   ?? '';
$mapel_terpilih   = $_GET['mapel']   ?? '';
$tanggal_terpilih = $_GET['tanggal'] ?? $tanggal_hari_ini;

// Daftar kelas
$kelas_result = $conn->query("SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC");

// Daftar mata pelajaran
$mapel_result = $conn->query("SELECT id_mata_pelajaran, nama_mapel FROM mata_pelajaran ORDER BY nama_mapel ASC");

// Query absensi fix: LEFT JOIN supaya semua siswa muncul
$sql = "
SELECT 
    s.id_siswa,
    s.nama_siswa,
    k.nama_kelas,
    COALESCE(a.status, '-') AS status,
    COALESCE(DATE_FORMAT(a.waktu_absensi_tercatat, '%H:%i:%s'), '-') AS waktu,
    m.nama_mapel
FROM siswa s
LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
LEFT JOIN absensi_siswa a 
  ON a.id_siswa = s.id_siswa
  AND DATE(a.waktu_absensi_tercatat) = ?
LEFT JOIN mata_pelajaran m
    ON a.id_mata_pelajaran = m.id_mata_pelajaran
WHERE 1=1
";

$types  = 's';
$params = [$tanggal_terpilih];

// Filter kelas
if (!empty($kelas_terpilih)) {
    $sql    .= " AND s.id_kelas = ? ";
    $types  .= 'i';
    $params[] = (int)$kelas_terpilih;
}

// Filter mata pelajaran
if (!empty($mapel_terpilih)) {
    $sql    .= " AND a.id_mata_pelajaran = ? ";
    $types  .= 'i';
    $params[] = (int)$mapel_terpilih;
}

$sql .= " ORDER BY k.nama_kelas ASC, s.nama_siswa ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Query error: " . $conn->error);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat Absensi Guru</title>
<link rel="stylesheet" href="dashboard_guru.css">
<link rel="stylesheet" href="riwayat_absensi.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
.main-content { margin-left:0; padding:30px; }
.filter-form label { margin-right: 10px; font-weight:bold; }
.filter-form select, .filter-form input[type="date"] { margin-right:20px; padding:5px 8px; border-radius:5px; border:1px solid #ccc; }
.filter-form button { padding:5px 12px; border:none; background-color:#009688; color:white; border-radius:5px; cursor:pointer; }
.filter-form button:hover { background-color:#00796B; }
.table-container { margin-top:20px; }
table { width:100%; border-collapse:collapse; }
table th, table td { border:1px solid #ccc; padding:8px; text-align:left; }
.status.hadir { color:white; font-weight:bold; }
.status.izin  { color:white; font-weight:bold; }
.status.sakit { color:white; font-weight:bold; }
.status.alpa  { color:white; font-weight:bold; }
</style>

</head>
<body>
<a href="dashboard_guru.php" class="btn-back"><i class="fas fa-arrow-left"></i></a>

<div class="main-content">
    <h1>Halo <?= htmlspecialchars($nama_guru); ?> 👋</h1>
    <h2>Riwayat Absensi</h2>

    <form method="GET" class="filter-form">
        <label for="kelas">Pilih Kelas:</label>
        <select name="kelas" id="kelas">
            <option value="">Semua Kelas</option>
            <?php while ($kelas = $kelas_result->fetch_assoc()) { ?>
                <option value="<?= htmlspecialchars($kelas['id_kelas']); ?>" <?= ($kelas_terpilih == $kelas['id_kelas'])?'selected':'' ?>>
                    <?= htmlspecialchars($kelas['nama_kelas']); ?>
                </option>
            <?php } ?>
        </select>

        <label for="mapel">Pilih Mata Pelajaran:</label>
        <select name="mapel" id="mapel">
            <option value="">Semua Mata Pelajaran</option>
            <?php while ($mapel = $mapel_result->fetch_assoc()) { ?>
                <option value="<?= htmlspecialchars($mapel['id_mata_pelajaran']); ?>" <?= ($mapel_terpilih == $mapel['id_mata_pelajaran'])?'selected':'' ?>>
                    <?= htmlspecialchars($mapel['nama_mapel']); ?>
                </option>
            <?php } ?>
        </select>

        <label for="tanggal">Tanggal:</label>
        <input type="date" name="tanggal" id="tanggal" value="<?= htmlspecialchars($tanggal_terpilih); ?>">

        <button type="submit" title="Terapkan filter"><i class="fas fa-search"></i></button>
    </form>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>ID Siswa</th>
                    <th>Nama Siswa</th>
                    <th>Kelas</th>
                    <th>Mata Pelajaran</th>
                    <th>Status</th>
                    <th>Jam</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                $no = 1;
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>{$no}</td>
                        <td>".htmlspecialchars($row['id_siswa'])."</td>
                        <td>".htmlspecialchars($row['nama_siswa'])."</td>
                        <td>".htmlspecialchars($row['nama_kelas'] ?? '-')."</td>
                        <td>".htmlspecialchars($row['nama_mapel'] ?? '-')."</td>
                        <td><span class='status ".strtolower($row['status'])."'>".htmlspecialchars(ucfirst($row['status']))."</span></td>
                        <td>".htmlspecialchars($row['waktu'])."</td>
                    </tr>";
                    $no++;
                }
            } else {
                echo "<tr><td colspan='7'>Tidak ada data absensi untuk filter yang dipilih.</td></tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>

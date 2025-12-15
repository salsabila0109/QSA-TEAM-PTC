<?php
session_start();
include '../db.php';

if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'guru') {
    header("Location: ../login_guru.php");
    exit;
}

$id_guru = $_SESSION['id_pengguna'] ?? 0;

// cek password guru
$stmt = $conn->prepare("SELECT password FROM guru WHERE id_guru = ?");
$stmt->bind_param("i", $id_guru);
$stmt->execute();
$stmt->bind_result($password_guru);
$stmt->fetch();
$stmt->close();

// kalau masih default, arahkan ke ubah password
if ($password_guru === 'guru123') {
    header("Location: ubah_password.php");
    exit;
}

// Ambil data guru login
$stmt = $conn->prepare("SELECT nama_guru FROM guru WHERE id_guru = ?");
$stmt->bind_param("i", $id_guru);
$stmt->execute();
$stmt->bind_result($nama_guru);
$stmt->fetch();
$stmt->close();

// Ambil daftar kelas
$kelas_result = $conn->query("SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC");
$kelas_list = [];
$kelas_map  = [];
while ($k = $kelas_result->fetch_assoc()) {
    $kelas_list[] = $k;
    $kelas_map[$k['id_kelas']] = $k['nama_kelas'];
}
// Filter kelas & tanggal
$filter_kelas = isset($_GET['kelas']) ? (int)$_GET['kelas'] : '';
$filter_tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';

// Siapkan kondisi WHERE dinamis
$where = [];
if ($filter_kelas !== '') {
    $where[] = "s.id_kelas = $filter_kelas";
}

// LEFT JOIN absensi dengan filter tanggal di ON clause
$join_absensi = "LEFT JOIN absensi_siswa a ON a.id_siswa = s.id_siswa";
if ($filter_tanggal) {
    $join_absensi .= " AND DATE(a.waktu_absensi_tercatat) = '$filter_tanggal'";
}

// Bangun SQL final
$sql = "
SELECT 
    s.id_siswa,
    s.nama_siswa,
    s.id_kelas,
    k.nama_kelas,
    COALESCE(a.status, 'Alpa') AS status,
    COALESCE(DATE_FORMAT(a.waktu_absensi_tercatat, '%H:%i:%s'), '-') AS waktu
FROM siswa s
LEFT JOIN kelas k ON k.id_kelas = s.id_kelas
$join_absensi
";
if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY k.nama_kelas ASC, s.nama_siswa ASC";




$res = $conn->query($sql);

$absensi_data = [];
$statistik = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpa' => 0];

while ($row = $res->fetch_assoc()) {
    $status = ucfirst(strtolower($row['status'] ?? 'Alpa'));
    if (!isset($statistik[$status])) {
        $status = 'Alpa';
    }
    $statistik[$status]++;
    $absensi_data[] = $row;
}

$total_siswa = count($absensi_data);
$label_kelas = ($filter_kelas !== '' && isset($kelas_map[$filter_kelas])) ? $kelas_map[$filter_kelas] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Guru</title>
<link rel="stylesheet" href="dashboard_guru.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>PresenTech</h2>
    <a href="dashboard_guru.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
    <a href="riwayat_absensi.php"><i class="fas fa-history"></i> Riwayat Absensi</a>
    <a href="data_mapel.php"><i class="fas fa-book"></i> Data Mata Pelajaran</a>
    <a href="profil_guru.php"><i class="fas fa-user"></i> Profil Guru</a>
    <a href="../logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?')">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<!-- Main Content -->
<div class="main-content">
    <h1>Halo Guru, <?= htmlspecialchars($nama_guru) ?> 👋</h1>

    <!-- Filter Form -->
    <form method="GET" class="filter-form">
        <label for="kelas">Pilih Kelas:</label>
        <select name="kelas" id="kelas" onchange="this.form.submit()">
            <option value="">-- Semua Kelas --</option>
            <?php foreach ($kelas_list as $kelas): ?>
                <option value="<?= $kelas['id_kelas'] ?>" <?= ($filter_kelas == $kelas['id_kelas']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($kelas['nama_kelas']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="tanggal">Tanggal:</label>
        <input type="date" name="tanggal" id="tanggal" value="<?= $_GET['tanggal'] ?? '' ?>">

        <button type="submit"><i class="fas fa-search"></i></button>
    </form>

    <!-- Pie Chart -->
    <div class="chart-container">
        <h2>Statistik Absensi <?= $label_kelas ? "(Kelas $label_kelas)" : "" ?> <?= $filter_tanggal ? "Tanggal: $filter_tanggal" : "" ?></h2>
        <canvas id="absensiChart" width="200" height="200"></canvas>
    </div>

    <!-- Table Siswa -->
    <div class="table-container">
        <h2>Daftar Siswa <?= $label_kelas ? "(Kelas $label_kelas)" : "" ?></h2>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>ID Siswa</th>
                    <th>Nama Siswa</th>
                    <th>Kelas</th>
                    <th>Status</th>
                    <th>Jam</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_siswa > 0): $no = 1; foreach ($absensi_data as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['id_siswa']) ?></td>
                        <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                        <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                        <td><span class="status <?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                        <td><?= htmlspecialchars($row['waktu']) ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6">Belum ada data siswa</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('absensiChart').getContext('2d');
const absensiChart = new Chart(ctx, {
    type: 'pie',
    data: {
        labels: ['Hadir', 'Izin', 'Sakit', 'Alpa'],
        datasets: [{
            label: 'Jumlah Siswa',
            data: [
                <?= $statistik['Hadir'] ?>,
                <?= $statistik['Izin'] ?>,
                <?= $statistik['Sakit'] ?>,
                <?= $statistik['Alpa'] ?>
            ],
           backgroundColor: [
            '#4CaF50', // Hadir
            '#FF9800', // Izin
            '#2196F3', // Sakit
            '#F44336'  // Alpa
],

            borderColor: 'white',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: { enabled: true }
        }
    }
});
</script>

</body>
</html>

<?php
session_start();
require_once __DIR__ . "/../db.php";

// Cek login orangtua
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'orangtua') {
    header("Location: ../login.php");
    exit;
}

$nama_pengguna = $_SESSION['nama_pengguna'] ?? ($_SESSION['username'] ?? 'Orangtua');
$id_orangtua = $_SESSION['id_pengguna'] ?? 0;

// Ambil id_siswa dan nama siswa anak orangtua
$anak_id = null;
$nama_siswa = '';
$stmt_anak = $conn->prepare("SELECT id_siswa FROM orangtua WHERE id_orangtua=?");
$stmt_anak->bind_param("i", $id_orangtua);
$stmt_anak->execute();
$res_anak = $stmt_anak->get_result();
if($res_anak->num_rows > 0){
    $row = $res_anak->fetch_assoc();
    $anak_id = $row['id_siswa'];

    // Ambil nama siswa
    $stmt_nama = $conn->prepare("SELECT nama_siswa FROM siswa WHERE id_siswa=?");
    $stmt_nama->bind_param("i", $anak_id);
    $stmt_nama->execute();
    $res_nama = $stmt_nama->get_result();
    if($res_nama->num_rows > 0){
        $row_nama = $res_nama->fetch_assoc();
        $nama_siswa = $row_nama['nama_siswa'];
    }
}

// Ambil data absensi anak
$filter_tgl = $_GET['tgl'] ?? date('Y-m-d');
$absensi_data = ['Hadir'=>0,'Izin'=>0,'Sakit'=>0,'Alpa'=>0]; // default 0
if($anak_id){
    $stmt = $conn->prepare("
        SELECT status, COUNT(*) as jumlah
        FROM absensi_siswa
        WHERE id_siswa = ? AND DATE(waktu_absensi_tercatat) = ?
        GROUP BY status
    ");
    $stmt->bind_param("is", $anak_id, $filter_tgl);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        $status = ucfirst(strtolower($row['status']));
        if(array_key_exists($status, $absensi_data)){
            $absensi_data[$status] = (int)$row['jumlah'];
        }
    }
}

// Ambil semua data absensi anak (untuk tabel)
$tabel_data = [];
$absensi_hari_ini = [];
if($anak_id){
    $stmt2 = $conn->prepare("
        SELECT a.waktu_absensi_tercatat, a.status, k.nama_kelas, m.nama_mapel
        FROM absensi_siswa a
        JOIN siswa s ON a.id_siswa = s.id_siswa
        JOIN kelas k ON s.id_kelas = k.id_kelas
        JOIN mata_pelajaran m ON a.id_mata_pelajaran = m.id_mata_pelajaran
        WHERE s.id_siswa = ?
        ORDER BY a.waktu_absensi_tercatat DESC
    ");
    $stmt2->bind_param("i", $anak_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while($row = $res2->fetch_assoc()){
        // pisahkan absensi hari ini dan sebelumnya
        if(date('Y-m-d', strtotime($row['waktu_absensi_tercatat'])) === date('Y-m-d')){
            $absensi_hari_ini[] = $row;
        } else {
            $tabel_data[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Orangtua | PresenTech</title>
<link rel="stylesheet" href="dashboard_orangtua.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand"><h1>PresenTech</h1></div>
        <nav class="nav">
            <a class="active" href="dashboard_orangtua.php">🏠 Dashboard</a>
            <a href="profil_orangtua.php">👤 Profil</a>
            <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?')">🚪 Logout</a>
        </nav>
    </aside>

    <main class="content">
        <header class="topbar">
            <h2>Halo, <?= htmlspecialchars($nama_pengguna) ?> 👋</h2>
        </header>

        <section class="panel">
            <header class="panel-header"><strong>Riwayat Absensi Anak Saya</strong></header>
            <p><strong>ID Siswa:</strong> <?= htmlspecialchars($anak_id) ?> | <strong>Nama Siswa:</strong> <?= htmlspecialchars($nama_siswa) ?></p>
            <label for="tgl">Tanggal:</label>
            <input type="date" id="tgl" name="tgl" value="<?= htmlspecialchars($filter_tgl) ?>" onchange="window.location='?tgl='+this.value">
            
            <div class="chart-container">
                <canvas id="absensiChart"></canvas>
            </div>

            <!-- Notifikasi Hari Ini -->
            <?php if (!empty($absensi_hari_ini)): ?>
            <div id="notifikasi-hari-ini" class="notif-hari-ini">
                <h4>🔔 Notifikasi hari ini</h4>
                <table class="table notif-table">
                    <thead>
                        <tr>
                            <th>Tanggal & Jam</th>
                            <th>Kelas</th>
                            <th>Mata Pelajaran</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($absensi_hari_ini as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['waktu_absensi_tercatat']) ?></td>
                            <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                            <td><?= htmlspecialchars($row['nama_mapel']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="pindahkan" onclick="pindahkanKeTabel()">Klik di sini untuk pindahkan ke riwayat tabel</p>
            </div>
            <?php endif; ?>

            <div class="table-wrap" id="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tanggal & Jam</th>
                            <th>Kelas</th>
                            <th>Mata Pelajaran</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="tabel-utama">
                        <?php if($tabel_data): ?>
                            <?php foreach($tabel_data as $row): ?>
                                <tr>
                                <td><?= htmlspecialchars($row['waktu_absensi_tercatat']) ?></td>
                                <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                                <td><?= htmlspecialchars($row['nama_mapel']) ?></td>
                                <td><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
                        </tr>
                        <?php endforeach; ?>

                        <?php else: ?>
                            <tr><td colspan="4">Belum ada data absensi anak Anda.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script>
function pindahkanKeTabel() {
    const notif = document.getElementById('notifikasi-hari-ini');
    const rows = notif.querySelectorAll('tbody tr');
    const mainTbody = document.getElementById('tabel-utama');
    rows.forEach(r => mainTbody.prepend(r));
    notif.remove();
}
</script>

<script>
const ctx = document.getElementById('absensiChart').getContext('2d');
const absensiChart = new Chart(ctx, {
    type: 'pie',
    data: {
        labels: ['Hadir','Izin','Sakit','Alpa'],
        datasets: [{
            data: <?= json_encode(array_values($absensi_data)) ?>,
            backgroundColor:['#4CAF50','#FF9800','#2196F3','#F44336']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>

<script>
window.addEventListener("load", () => {
    fetch("update_status_absensi.php")
        .then(res => res.text())
        .then(console.log)
        .catch(console.error);
});
</script>

</body>
</html>

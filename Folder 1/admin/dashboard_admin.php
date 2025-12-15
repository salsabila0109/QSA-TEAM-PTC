<?php
session_start();
include '../db.php';

// Cek admin
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$siswa = [];
$result_siswa = $conn->query("SELECT * FROM siswa ORDER BY nama_siswa ASC");
while ($row = $result_siswa->fetch_assoc()) $siswa[] = $row;
$total_siswa = count($siswa);

$hadir_hari_ini = $izin_hari_ini = $sakit_hari_ini = $alpa_hari_ini = 0;
$today = date('Y-m-d');
$recentActivities = [];

foreach ($siswa as $s) {
    $uid = $s['id_siswa'];
    $res = $conn->query("
        SELECT * FROM absensi_siswa 
        WHERE id_siswa='$uid' 
        ORDER BY waktu_absensi_tercatat DESC 
        LIMIT 1
    ");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $status = strtolower(trim($row['status']));
        $jam = date('H:i:s', strtotime($row['waktu_absensi_tercatat']));
        switch ($status) {
            case 'hadir':  $hadir_hari_ini++;  $status_display = 'Hadir';  break;
            case 'izin':   $izin_hari_ini++;   $status_display = 'Izin';   break;
            case 'sakit':  $sakit_hari_ini++;  $status_display = 'Sakit';  break;
            default:       $alpa_hari_ini++;   $status_display = 'Alpa';   break;
        }
    } else { $status_display = 'Alpa'; $jam = '-'; $alpa_hari_ini++; }

    $recentActivities[] = [
        'id_siswa' => $uid,
        'name'     => $s['nama_siswa'],
        'time'     => $jam,
        'status'   => $status_display
    ];
}

$persen_hadir = $total_siswa > 0 ? round(($hadir_hari_ini / $total_siswa) * 100) : 0;
$pieData = [$hadir_hari_ini, $izin_hari_ini, $sakit_hari_ini, $alpa_hari_ini];

/* ============================
   KNN PER SEMESTER (weekday only, target 90 hari)
   ============================ */
$knn_status  = 'UNKNOWN';
$knn_checks  = [];
$knn_preview = [];
$knn_info_threshold = '-';
$rowsSem = [];
$k = 3; // default

try {
    $servicePath = __DIR__ . '/../services/KNNAbsensi.php';
    if (!file_exists($servicePath)) throw new Exception('File services/KNNAbsensi.php tidak ditemukan.');
    require_once $servicePath;
    if (!class_exists('KNNAbsensi')) throw new Exception('Class KNNAbsensi tidak ditemukan.');

    // Semester & tahun ajar otomatis
    $bulan = (int)date('n');
    $semesterAktif = in_array($bulan, [7,8,9,10,11,12], true) ? 'Ganjil' : 'Genap';
    $tahunNow = (int)date('Y');
    $tahunAjar = in_array($bulan, [7,8,9,10,11,12], true) ? $tahunNow : ($tahunNow - 1);

    // Data hadir semester (Senin–Jumat saja)
    $rowsSem = KNNAbsensi::getHadirCountsForSemester($conn, $semesterAktif, $tahunAjar);
    if (empty($rowsSem)) throw new Exception("Tidak ada data hadir semester {$semesterAktif} {$tahunAjar}/".($tahunAjar+1).".");

    // Target 90 hari → ambang 30/60
    $targetHariSemester = 90;
    $low = (int)floor($targetHariSemester * 1/3); // 30
    $mid = (int)floor($targetHariSemester * 2/3); // 60

    // Siapkan data latih
    $X=[]; $y=[]; $maxHadir=0;
    $labeler = function (int $j) use ($low, $mid): string {
        if ($j <= $low) return 'Tidak Disiplin';
        if ($j <= $mid) return 'Kurang Disiplin';
        return 'Disiplin';
    };
    foreach ($rowsSem as $r) {
        $j = (int)$r['jml_hadir'];
        $X[] = [ $j ];
        $y[] = $labeler($j);
        if ($j > $maxHadir) $maxHadir = $j;
    }
    // Anchor points agar stabil
    foreach (array_unique([0, $low, $mid, $targetHariSemester]) as $a) {
        $X[] = [ $a ];
        $y[] = $labeler($a);
    }

    // k aman
    $k = min(3, max(1, count($X)));

    // Latih KNN
    $knn = new KNNAbsensi($k);
    $knn->setTrainingData($X, $y);

    // Quick test: 3 zona
    $probeLow  = max(0, (int)floor($low/2));                         // ~15
    $probeMid  = (int)floor(($low + $mid) / 2);                      // ~45
    $probeHigh = (int)floor(($mid + $targetHariSemester) / 2);       // ~75

    $knn_quick_low  = $knn->predictBatch([[ $probeLow ]])[0];
    $knn_quick_mid  = $knn->predictBatch([[ $probeMid ]])[0];
    $knn_quick_high = $knn->predictBatch([[ $probeHigh ]])[0];

    $expected = [
        (string)$probeLow  => $labeler($probeLow),
        (string)$probeMid  => $labeler($probeMid),
        (string)$probeHigh => $labeler($probeHigh),
    ];
    $actual = [
        (string)$probeLow  => $knn_quick_low,
        (string)$probeMid  => $knn_quick_mid,
        (string)$probeHigh => $knn_quick_high,
    ];

    $ok = true;
    foreach ($expected as $kkey => $v) {
        if ($actual[$kkey] !== $v) { $ok = false; $knn_checks[] = "Expect {$kkey} → {$v}, got {$actual[$kkey]}"; }
    }
    $knn_status = $ok ? 'OK' : 'FAIL';

    // Preview 5 baris
    $limit = min(5, count($rowsSem));
    for ($i=0; $i<$limit; $i++) {
        $j = (int)$rowsSem[$i]['jml_hadir'];
        $p = $knn->predictBatch([[ $j ]])[0];
        $knn_preview[] = ['Nama'=>$rowsSem[$i]['nama_siswa'], 'JumlahHadir'=>$j, 'Prediksi'=>$p];
    }

    $knn_info_threshold = "Semester: {$semesterAktif} {$tahunAjar}/".($tahunAjar+1).", target={$targetHariSemester}, low={$low}, mid={$mid}, k={$k}, maxData={$maxHadir}";
} catch (Throwable $e) {
    $knn_status = 'FAIL';
    $knn_checks[] = $e->getMessage();
    $knn_info_threshold = '-';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="dashboard_admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Sidebar */
        .sidebar { width: 220px; background: #004D40; color: white; height: 100vh; position: fixed; padding: 20px; }
        .sidebar h2 { color: #fff; }
        .sidebar ul { list-style: none; padding: 0; }
        .sidebar ul li { margin: 15px 0; }
        .sidebar ul li a { color: white; text-decoration: none; display: block; }
        .sidebar ul li a.active { font-weight: bold; }

        /* Main content */
        .main-content { margin-left: 240px; padding: 20px; }

        /* Stats grid */
        .stats-grid-horizontal-wrapper { overflow-x: auto; padding-bottom: 10px; margin-top: 20px; }
        .stats-grid-horizontal { display: flex; flex-wrap: nowrap; gap: 20px; }
        .stats-grid-horizontal .stat-card { flex: 0 0 auto; background: white; border-radius: 20px; padding: 12px; text-align: center; min-width: 120px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        .stat-card h3 { color: #004D40; }
        .stat-number { font-size: 22px; font-weight: bold; color: #009688; }
        .stats-grid-horizontal {
        display: flex;
        justify-content: center; /* ini yang bikin card ke tengah */
        gap: 20px; /* jarak antar card */
        flex-wrap: wrap; /* biar kalau layar kecil, card turun ke bawah */
}

        /* Table & cards */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #B2DFDB; padding: 10px; text-align: center; }
        th { background-color: #004D40; color: white; }
        .recent-activity { background: white; padding: 20px; border-radius: 15px; margin-top: 30px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
        .recent-activity h3 { color: #004D40; }
        .btn { display:inline-block; padding:10px 14px; border-radius:10px; text-decoration:none; color:#fff; background:#009688; }
        .btn:hover { opacity: .9; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <ul>
    <li><a href="#" class="tablink active" onclick="openTab(event,'dashboard')">Dashboard</a></li>
    <li><a href="tampil_hasil_knn.php">Hasil KNN</a></li>
    <li><a href="manajemen_data_siswa.php">Manajemen Data Siswa</a></li>
    <li><a href="manajemen_data_guru.php">Manajemen Data Guru</a></li>
    <li><a href="manajemen_data_orangtua.php">Manajemen Data Orangtua</a></li>
    <li><a href="manajemen_data_kelas.php">Manajemen Data Kelas</a></li>
    <li><a href="manajemen_data_mapel.php">Manajemen Data Mapel</a></li>
    <li><a href="kehadiran.php">Pemantauan Kehadiran</a></li>
    <li><a href="notifikasi_kehadiran.html">Notifikasi Kehadiran</a></li>
    <li><a href="profil_admin.php">Profil Admin</a></li>
    <li><a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?')">Logout</a></li>
</ul>

</div>

<!-- Main Content -->
<div class="main-content">

    <!-DASHBOARD -->
    <div id="dashboard" class="tabcontent" style="display:block;">
        <h2>Selamat Datang Admin, <b><?php echo htmlspecialchars($_SESSION['username']); ?></b> 👋</h2>

   <!-- Statistik Kehadiran -->
<div class="stats-grid-horizontal-wrapper">

    <div class="stats-grid-horizontal">
    <div class="stat-card">
      <h3>Total Kehadiran</h3>
      <div class="stat-number" id="total-kehadiran"><?php echo $persen_hadir; ?>%</div>
    </div>

    <div class="stat-card">
      <h3>Hadir</h3>
      <div class="stat-number" id="hadir"><?php echo $hadir_hari_ini.'/'.$total_siswa; ?></div>
    </div>

    <div class="stat-card">
      <h3>Izin</h3>
      <div class="stat-number" id="izin"><?php echo $izin_hari_ini; ?></div>
    </div>

    <div class="stat-card">
      <h3>Sakit</h3>
      <div class="stat-number" id="sakit"><?php echo $sakit_hari_ini; ?></div>
    </div>

    <div class="stat-card">
      <h3>Alpa</h3>
      <div class="stat-number" id="alpa"><?php echo $alpa_hari_ini; ?></div>
    </div>
    
  </div>
</div>


        <!-- Chart Pie -->
        <div style="width:250px; height:250px; margin:20px auto;">
            <canvas id="pieChart"></canvas>
        </div>

        <!-- Aktivitas Absensi Terbaru -->
        <div class="recent-activity">
            <h3>Aktivitas Absensi Siswa</h3>
            <!-- Filter Tanggal -->
            <div style="margin-bottom:15px; text-align:right;">
            <label for="filterTanggal"><b>Pilih Tanggal:</b></label>
            <input type="date" id="filterTanggal" value="<?php echo date('Y-m-d'); ?>" 
            style="padding:6px 10px; border:1px solid #007165ff; border-radius:6px;">
            <!-- Info jumlah siswa untuk hari ini -->
                <p id="infoJumlahSiswa" style="margin-top:8px; font-weight:500; color:#00796B;">
                📅 Memuat data siswa...
                </p>
        </div>


<div class="chart-container" style="max-width:400px; margin:20px auto; display:none;">
    <canvas id="rekapChart"></canvas>
</div>


            <table>
                <thead><tr><th>ID Siswa</th><th>Nama Siswa</th><th>Jam</th><th>Status</th></tr></thead>
                <tbody id="activityTableBody"></tbody>
            </table>
        </div>
    </div>

    <!-- TAB: Hasil KNN (tabel penuh) -->
    <div id="knn" class="tabcontent" style="display:none;">
        <h3 style="margin:0 0 12px; text-align:center;">
    Klasifikasi KNN Absensi — Tabel Semester
    </h3>
    <p class="ket-info"></p>

        <p class="keterangan info">
        Keterangan: <strong>Semester Ganjil 2025/2026</strong>. 
        Target kehadiran: <strong>90</strong> hari belajar. 
        Batas kategori: <strong>Tidak Disiplin</strong> &le; 30 hari; 
        <strong>Kurang Disiplin</strong> 31–60 hari; 
        <strong>Disiplin</strong> &gt; 60 hari. 
  </p>

        <?php
    // Prediksi semua siswa semester (sekali hitung)
    $predAll = [];
    if (!empty($rowsSem) && isset($knn)) {
        $testX = array_map(fn($r)=>[(int)$r['jml_hadir']], $rowsSem);
        $predAll = $knn->predictBatch($testX);
    }
    ?>

    <div style="overflow-x:auto; background:#fff; border-radius:12px; padding:16px; box-shadow:0 2px 6px rgba(0,0,0,.06);">
        <table style="width:100%; border-collapse:collapse; font-size:14px;">
            <thead>
                <tr style="background:#00796B; color:#fff;">
                    <th style="padding:10px; border:1px solid #B2DFDB; width:80px;">No</th>
                    <th style="padding:10px; border:1px solid #B2DFDB; text-align:left;">Nama Siswa</th>
                    <th style="padding:10px; border:1px solid #B2DFDB;">Jumlah Hadir (semester)</th>
                    <th style="padding:10px; border:1px solid #B2DFDB;">Prediksi (KNN)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rowsSem) && !empty($predAll)): ?>
                    <?php foreach ($rowsSem as $i => $r): ?>
                        <tr style="background:<?php echo ($i%2? '#E8F5F3':'#fff'); ?>;">
                            <td style="padding:8px; border:1px solid #B2DFDB;"><?php echo $i+1; ?></td>
                            <td style="padding:8px; border:1px solid #B2DFDB; text-align:left;"><?php echo htmlspecialchars($r['nama_siswa']); ?></td>
                            <td style="padding:8px; border:1px solid #B2DFDB;"><?php echo (int)$r['jml_hadir']; ?></td>
                            <td style="padding:8px; border:1px solid #B2DFDB; font-weight:600;"><?php echo htmlspecialchars($predAll[$i]); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="padding:12px; border:1px solid #B2DFDB; text-align:center;">Belum ada data semester/prediksi.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="margin-top:14px;">
            <a class="btn" href="../knn_absensi_export.php">Download Excel</a>
        </div>
    </div>
</div>


    <!-- Hapus include agar tidak muncul di dashboard, tapi tetap load CSS-nya -->
    <link rel="stylesheet" href="manajemen_data_mapel.css">



</div>

<script>
function openTab(evt, tabName) {
    const tabcontent = document.getElementsByClassName("tabcontent");
    const tablinks = document.getElementsByClassName("tablink");
    for (let i=0;i<tabcontent.length;i++) tabcontent[i].style.display="none";
    for (let i=0;i<tablinks.length;i++) tablinks[i].classList.remove("active");
    document.getElementById(tabName).style.display="block";
    evt.currentTarget.classList.add("active");
}

document.addEventListener('DOMContentLoaded', () => {
    // Aktivitas Absensi Terbaru
    const recentActivities = <?php echo json_encode($recentActivities); ?>;
    const tbody = document.getElementById('activityTableBody');
    recentActivities.forEach(act => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${act.id_siswa}</td><td>${act.name}</td><td>${act.time}</td><td>${act.status}</td>`;
        tbody.appendChild(tr);
    });

    // Pie Chart
    const ctx = document.getElementById('pieChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Hadir','Izin','Sakit','Alpa'],
            datasets: [{
                data: <?php echo json_encode($pieData); ?>,
                backgroundColor:['#4CAF50','#FF9800','#2196F3','#F44336']
            }]
        }
    });
});

// === Filter Tanggal Absensi (Update Rekap & Tabel) ===
document.getElementById('filterTanggal').addEventListener('change', function() {
    const tanggalDipilih = this.value;
    const tbody = document.getElementById('activityTableBody');
    tbody.innerHTML = '<tr><td colspan="4">Memuat data...</td></tr>';

    // --- Ambil data aktivitas tabel ---
    fetch(`get_recent_activities.php?tanggal=${tanggalDipilih}`)
        .then(res => res.json())
        .then(data => {
            tbody.innerHTML = '';
            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4">Tidak ada data untuk tanggal ${tanggalDipilih}</td></tr>`;
            } else {
                data.forEach(act => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${act.id_siswa}</td><td>${act.name}</td><td>${act.time}</td><td>${act.status}</td>`;
                    tbody.appendChild(tr);
                });
            }
        })
        .catch(err => {
            console.error('Error ambil data aktivitas:', err);
            tbody.innerHTML = `<tr><td colspan="4">Gagal memuat data</td></tr>`;
        });


    // --- Ambil data rekap ---
    fetch(`get_rekap_absensi.php?tanggal=${tanggalDipilih}`)
        .then(res => res.json())
        .then(rekap => {
            document.getElementById('rekap-hadir').textContent = `Hadir: ${rekap.hadir}`;
            document.getElementById('rekap-izin').textContent = `Izin: ${rekap.izin}`;
            document.getElementById('rekap-sakit').textContent = `Sakit: ${rekap.sakit}`;
            document.getElementById('rekap-alpa').textContent = `Alpa: ${rekap.alpa}`;
        })
        .catch(err => console.error('Error ambil rekap:', err));
});

    // === Update Info Jumlah Siswa ===
    function updateInfoJumlahSiswa(tanggalDipilih) {
    fetch(`get_rekap_absensi.php?tanggal=${tanggalDipilih}`)
        .then(res => res.json())
        .then(rekap => {
            const total = rekap.hadir + rekap.izin + rekap.sakit + rekap.alpa;
            const info = document.getElementById('infoJumlahSiswa');
            info.textContent = `📅 ${total} siswa untuk tanggal ${tanggalDipilih}`;
        })
        .catch(err => console.error('Error ambil total siswa:', err));
}

// Jalankan pertama kali saat halaman dimuat
    document.addEventListener('DOMContentLoaded', () => {
    const tanggalAwal = document.getElementById('filterTanggal').value || new Date().toISOString().split('T')[0];
    updateInfoJumlahSiswa(tanggalAwal);
});

// Jalankan ulang setiap kali tanggal diganti
    document.getElementById('filterTanggal').addEventListener('change', function() {
    updateInfoJumlahSiswa(this.value);
});

</script>
</body>
</html>

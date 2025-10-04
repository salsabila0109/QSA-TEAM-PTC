<?php
session_start();
include '../db.php';

// Cek apakah user admin
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// Ambil semua siswa
$siswa = [];
$result_siswa = $conn->query("SELECT * FROM siswa");
while ($row = $result_siswa->fetch_assoc()) {
    $siswa[] = $row;
}
$total_siswa = count($siswa);


// Inisialisasi hitungan
$hadir_hari_ini = 0;
$izin_hari_ini = 0;
$sakit_hari_ini = 0;
$alpa_hari_ini = 0;

$today = date('Y-m-d');

// Hitung kehadiran dari absensi_siswa
foreach ($siswa as $s) {
    $uid = $s['id_siswa'];
    $res = $conn->query("SELECT * FROM absensi_siswa WHERE id_siswa='$uid' AND DATE(waktu_absensi_tercatat)='$today' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        switch ($row['status']) {
            case 'Hadir': $hadir_hari_ini++; break;
            case 'Izin': $izin_hari_ini++; break;
            case 'Sakit': $sakit_hari_ini++; break;
            case 'Alpa': $alpa_hari_ini++; break;
        }
    } else {
        $alpa_hari_ini++;
    }
}

$persen_hadir = $total_siswa > 0 ? round(($hadir_hari_ini/$total_siswa)*100) : 0;

// Pie chart data
$pieData = [$hadir_hari_ini,$izin_hari_ini,$sakit_hari_ini,$alpa_hari_ini];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Beranda Admin</title>
<link rel="stylesheet" href="dashboard_admin.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* Perbaikan bulat-bulat horizontal */
.stats-grid-horizontal-wrapper {
    overflow-x: auto;
    padding-bottom: 10px;
    margin-top: 20px;
}
.stats-grid-horizontal {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 20px;
}
.stats-grid-horizontal .stat-card {
    flex: 0 0 auto;
    margin: 0;
}

/* Scrollbar rapi */
.stats-grid-horizontal-wrapper::-webkit-scrollbar {
    height: 8px;
}
.stats-grid-horizontal-wrapper::-webkit-scrollbar-thumb {
    background: #009688;
    border-radius: 4px;
}
.stats-grid-horizontal-wrapper::-webkit-scrollbar-track {
    background: #e0f2f1;
    border-radius: 4px;
}
</style>
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="#" class="tablink active" onclick="openTab(event,'dashboard')">Dashboard</a>
    <a href="#" class="tablink" onclick="openTab(event,'siswa')">Manajemen Data Siswa</a>
    <a href="#" class="tablink" onclick="openTab(event,'guru')">Manajemen Data Guru</a>
    <a href="#" class="tablink" onclick="openTab(event,'orangtua')">Manajemen Data Orangtua</a>
    <a href="#" class="tablink" onclick="openTab(event,'kelas')">Manajemen Kelas</a>
    <a href="#" class="tablink" onclick="openTab(event,'mapel')">Manajemen Data Mapel</a>
    <a href="#" class="tablink" onclick="openTab(event,'kehadiran')">Pemantauan Kehadiran</a>
    <a href="#" class="tablink" onclick="openTab(event,'notifikasi')">Notifikasi Kehadiran</a>
    <a href="#" class="tablink" onclick="openTab(event,'profil')">Profil Admin</a>
    <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?')">Logout</a>
</div>


<div class="main-content">
    <!-- Dashboard -->
    <div id="dashboard" class="tabcontent" style="display:block;">
        <h2>Selamat Datang Admin, <?php echo $_SESSION['username']; ?>! 👋</h2>
        <p>Pilih menu di sebelah kiri untuk mengelola data siswa, guru, orangtua, kelas, mata pelajaran, melihat kehadiran siswa, atau mengekspor laporan</p>

        <div class="stats-grid-horizontal-wrapper">
            <div class="stats-grid-horizontal">
                <div class="stat-card">
                    <h3>Total Kehadiran</h3>
                    <div class="stat-number"><?php echo $persen_hadir; ?>%</div>
                </div>
                <div class="stat-card">
                    <h3>Hadir</h3>
                    <div class="stat-number"><?php echo $hadir_hari_ini.'/'.$total_siswa; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Izin</h3>
                    <div class="stat-number"><?php echo $izin_hari_ini; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Sakit</h3>
                    <div class="stat-number"><?php echo $sakit_hari_ini; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Alpa</h3>
                    <div class="stat-number"><?php echo $alpa_hari_ini; ?></div>
                </div>
            </div>
        </div>

        <div style="width:250px; height:250px; margin:20px auto;">
            <canvas id="pieChart"></canvas>
        </div>

        <div class="recent-activity">
            <h3>Aktivitas Absensi Terbaru</h3>
            <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse; text-align:center;">
                <thead>
                    <tr>
                        <th>ID Siswa</th>
                        <th>Nama Siswa</th>
                        <th>Jam</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="activityTableBody"></tbody>
            </table>
        </div>
    </div>

    <!-- Tab Lain -->
    <div id="siswa" class="tabcontent"><?php include 'manajemen_data_siswa.php'; ?></div>
    <div id="guru" class="tabcontent"><?php include 'manajemen_data_guru.php'; ?></div>
    <div id="orangtua" class="tabcontent"><?php include 'manajemen_data_orangtua.php'; ?></div>
    <div id="kelas" class="tabcontent"><?php include 'manajemen_data_kelas.php'; ?></div>
    <div id="mapel" class="tabcontent"><?php include 'manajemen_data_mapel.php'; ?></div>
    <div id="kehadiran" class="tabcontent"><?php include 'kehadiran.php'; ?></div>
    <div id="notifikasi" class="tabcontent"><?php include 'notifikasi_kehadiran.php'; ?></div>
    <div id="profil" class="tabcontent"><?php include 'profil_admin.php'; ?></div>
</div>

<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i=0;i<tabcontent.length;i++) tabcontent[i].style.display='none';
    tablinks = document.getElementsByClassName("tablink");
    for (i=0;i<tablinks.length;i++) tablinks[i].classList.remove('active');
    document.getElementById(tabName).style.display='block';
    evt.currentTarget.classList.add('active');
}

// Data aktivitas terbaru
const recentActivities = <?php 
$activities=[];
foreach($siswa as $s){
    $uid = $s['id_siswa'];
    $res = $conn->query("SELECT * FROM absensi_siswa WHERE id_siswa='$uid' AND DATE(waktu_absensi_tercatat)='$today' ORDER BY waktu_absensi_tercatat DESC LIMIT 1");
    if($res && $res->num_rows>0){
        $row=$res->fetch_assoc();
        $status = $row['status'];
        $jam = date('H:i:s', strtotime($row['waktu_absensi_tercatat']));
    } else {
        $status='Alpa';
        $jam='-';
    }
    $activities[] = [
        'id_siswa' => $s['id_siswa'], 
        'name' => $s['nama_siswa'],
        'time' => $jam,
        'status' => $status
    ];
}
echo json_encode($activities);
?>;

const activityTableBody = document.getElementById('activityTableBody');
recentActivities.forEach(act=>{
    const tr=document.createElement('tr');
    tr.innerHTML=`<td>${act.id_siswa}</td><td>${act.name}</td><td>${act.time}</td><td>${act.status}</td>`;
    activityTableBody.appendChild(tr);
});

// Pie chart
function initializePieChart(){
    const ctx=document.getElementById('pieChart').getContext('2d');
    new Chart(ctx,{
        type:'pie',
        data:{
            labels:['Hadir','Izin','Sakit','Alpa'],
            datasets:[{
                data:[<?php echo implode(',', $pieData); ?>],
                backgroundColor:['#4CAF50','#FFA500','#FF0000','#B0B0B0'],
                borderColor:'#fff',
                borderWidth:1
            }]
        },
        options:{responsive:true,plugins:{legend:{position:'bottom'}}}
    });
}

document.addEventListener('DOMContentLoaded',()=>{ initializePieChart(); });
</script>

</body>
</html>

<?php
session_start();
include '../db.php';

if ($_SESSION['role_pengguna'] != 'admin') {
    header("Location: ../login.php");
    exit;
}


$siswa = [];
$result_siswa = $conn->query("SELECT * FROM siswa");
while($row = $result_siswa->fetch_assoc()) {
    $siswa[] = $row;
}
$total_siswa = count($siswa);


$hadir_hari_ini = 0;
$izin_hari_ini = 0;
$sakit_hari_ini = 0;
$alpa_hari_ini = 0;

foreach ($siswa as $s) {
    $uid = $s['id_siswa'];
    $today = date('Y-m-d');
    $res = $conn->query("SELECT * FROM kehadiran WHERE id_siswa='$uid' AND tanggal='$today' LIMIT 1");
    if($res->num_rows > 0){
        $row = $res->fetch_assoc();
        switch($row['status']){
            case 'Hadir': $hadir_hari_ini++; break;
            case 'Izin': $izin_hari_ini++; break;
            case 'Sakit': $sakit_hari_ini++; break;
            case 'Alpa': $alpa_hari_ini++; break;
        }
    } else {
        $alpa_hari_ini++; // dianggap Alpa jika tidak ada record
    }
}

$total_scan_hari_ini = $hadir_hari_ini + $izin_hari_ini + $sakit_hari_ini;
$persen_hadir = $total_siswa > 0 ? round(($hadir_hari_ini/$total_siswa)*100) : 0;


$pieData = [
    'Hadir' => $hadir_hari_ini,
    'Izin' => $izin_hari_ini,
    'Sakit' => $sakit_hari_ini,
    'Alpa' => $alpa_hari_ini
];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Beranda Admin</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="#" class="tablink active" onclick="openTab('dashboard')">Dashboard</a>
    <a href="#" class="tablink" onclick="openTab('siswa')">Manajemen Data Siswa</a>
    <a href="#" class="tablink" onclick="openTab('guru')">Manajemen Data Guru</a>
    <a href="#" class="tablink" onclick="openTab('kelas')">Manajemen Kelas</a>
    <a href="#" class="tablink" onclick="openTab('kehadiran')">Pemantauan Kehadiran</a>
    <a href="#" class="tablink" onclick="openTab('notifikasi')">Notifikasi Kehadiran</a>
    <a href="#" class="tablink" onclick="openTab('export')">Export Excel</a>
    <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?')">Logout</a>

</div>

<div class="main-content">
 

    <div id="dashboard" class="tabcontent" style="display:block;">
        <h2>Selamat Datang Admin, <?php echo $_SESSION['username']; ?>! 👋</h2>
        <p>Gunakan menu di sebelah kiri untuk mengelola data siswa, guru, kelas, memvalidasi data, melihat kehadiran, mengirim notifikasi, atau mengekspor laporan.</p>

      
        <div class="stats-grid-horizontal">
            <div class="stat-card">
                <h3>Total Kehadiran</h3>
                <div class="stat-number"><?php echo $persen_hadir; ?>%</div>
                <div class="stat-change positive">+5% dari kemarin</div>
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

    
        <div style="width:250px; height:250px; margin:20px auto;">
            <canvas id="pieChart"></canvas>
        </div>

        
        <div class="chart-container">
            <h3>Statistik Kehadiran Mingguan</h3>
            <canvas id="attendanceChart"></canvas>
        </div>

    
        <div class="recent-activity">
            <h3>Aktivitas Absensi Terbaru</h3>
            <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse; text-align:center;">
                <thead>
                    <tr>
                        <th>Id_siswa</th>
                        <th>Nama Siswa</th>
                        <th>Jam</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="activityTableBody">
                </tbody>
            </table>
        </div>
    </div>

    
    <div id="siswa" class="tabcontent">
        <?php include 'manajemen_data_siswa.php'; ?>
    </div>

    
    <div id="guru" class="tabcontent">
        <?php include 'manajemen_data_guru.php'; ?>
    </div>

    
    <div id="kelas" class="tabcontent">
        <?php include 'manajemen_data_kelas.php'; ?>
    </div>

    
    <div id="kehadiran" class="tabcontent">
        <?php include 'kehadiran.php'; ?>
    </div>

    
    <div id="notifikasi" class="tabcontent">
        <?php include 'notifikasi_kehadiran.php'; ?>
    </div>

    
    <div id="export" class="tabcontent">
        <a href="ekspor_excel.php" class="btn btn-export">Export ke Excel</a>
    </div>
</div>

<script>
function openTab(tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for(i=0;i<tabcontent.length;i++) tabcontent[i].style.display='none';
    tablinks = document.getElementsByClassName("tablink");
    for(i=0;i<tablinks.length;i++) tablinks[i].classList.remove('active');
    document.getElementById(tabName).style.display='block';
    event.currentTarget.classList.add('active');
}

const recentActivities = <?php 
$activities=[];
foreach($siswa as $s){
    $uid = $s['id_siswa'];
    $today = date('Y-m-d');
    $res = $conn->query("SELECT * FROM kehadiran WHERE id_siswa='$uid' AND tanggal='$today' ORDER BY jam ASC LIMIT 1");
    if($res->num_rows>0){
        $row=$res->fetch_assoc();
        $status = strtolower($row['status']);
        $jam = $row['jam']?:'-';
    }else{
        $status='alpa';
        $jam='-';
    }
    $parts = explode(' ', $s['nama_siswa']);
    $inisial = strtoupper(substr($parts[0],0,1).substr($parts[1]??' ',0,1));
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
    const statusText = act.status.charAt(0).toUpperCase()+act.status.slice(1);
    tr.innerHTML=`<td>${act.id_siswa}</td><td>${act.name}</td><td>${act.time}</td><td>${statusText}</td>`;
    activityTableBody.appendChild(tr);
});


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
        options:{
            responsive:true,
            plugins:{
                legend:{position:'bottom'},
                tooltip:{
                    callbacks:{
                        label:function(context){
                            let total=context.dataset.data.reduce((a,b)=>a+b,0);
                            let value=context.parsed;
                            let percent=total?(value/total*100).toFixed(1):0;
                            return `${context.label}: ${value} (${percent}%)`;
                        }
                    }
                }
            }
        }
    });
}


function initializeBarChart(){
    const ctx=document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctx,{
        type:'bar',
        data:{
            labels:['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'],
            datasets:[
                {label:'Hadir',data:[<?php echo $hadir_hari_ini; ?>],backgroundColor:'#009688'},
                {label:'Izin',data:[<?php echo $izin_hari_ini; ?>],backgroundColor:'#FF9800'},
                {label:'Sakit',data:[<?php echo $sakit_hari_ini; ?>],backgroundColor:'#F44336'},
                {label:'Alpa',data:[<?php echo $alpa_hari_ini; ?>],backgroundColor:'#9E9E9E'}
            ]
        },
        options:{responsive:true,plugins:{legend:{position:'top'}},scales:{y:{beginAtZero:true,title:{display:true,text:'Jumlah Siswa'}}}}
    });
}

document.addEventListener('DOMContentLoaded',()=>{
    initializePieChart();
    initializeBarChart();
});
</script>

</body>
</html>

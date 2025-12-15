<?php
include '../db.php';
$status = $_GET['status'] ?? '';
$kelas = $_GET['kelas'] ?? '';
$today = date('Y-m-d');

$query = "
    SELECT s.nama_siswa, s.id_kelas, IFNULL(a.status, 'Alpa') as status
    FROM siswa s
    LEFT JOIN absensi_siswa a ON s.id_siswa = a.id_siswa AND DATE(a.waktu_absensi_tercatat) = '$today'
    WHERE IFNULL(a.status, 'Alpa') = '$status'
";

if($kelas != ''){
    $query .= " AND s.id_kelas = '$kelas'";
}

$result = $conn->query($query);
$no = 1;

if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        echo "<tr>
                <td>{$no}</td>
                <td>".htmlspecialchars($row['nama_siswa'])."</td>
                <td>".htmlspecialchars($row['id_kelas'])."</td>
                <td>".htmlspecialchars($row['status'])."</td>
              </tr>";
        $no++;
    }
} else {
    echo "<tr><td colspan='4'>Tidak ada siswa dengan status ini.</td></tr>";
}
?>

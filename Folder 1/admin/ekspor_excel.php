<?php
include '../db.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=laporan_absensi.xls");

$result = $conn->query("SELECT a.*, s.nama_siswa, s.kelas 
                        FROM absensi a 
                        JOIN siswa s ON a.id_siswa = s.id_siswa 
                        ORDER BY a.tanggal DESC, a.waktu DESC");

echo "Nama\tKelas\tMata Pelajaran\tTanggal\tWaktu\tStatus\n";
while ($row = $result->fetch_assoc()) {
    echo "{$row['nama_siswa']}\t{$row['kelas']}\t{$row['mata_pelajaran']}\t{$row['tanggal']}\t{$row['waktu']}\t{$row['status']}\n";
}
?>

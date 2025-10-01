<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

if ($_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}


$id_kelas = $_GET['id_kelas'] ?? null;


$query = "
    SELECT 
        s.id_siswa,
        s.nis,
        s.nama_siswa,
        m.nama_mapel,
        a.waktu_absensi_tercatat,
        a.status
    FROM absensi_siswa a
    JOIN siswa s ON a.id_siswa = s.id_siswa
    LEFT JOIN mata_pelajaran m ON a.id_mata_pelajaran = m.id_mata_pelajaran
";

if($id_kelas){
    $query .= " WHERE s.id_kelas = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_kelas);
}else{
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Notifikasi Kehadiran</title>
    <link rel="stylesheet" href="notifikasi_kehadiran.css">
</head>
<body>
<div class="dashboard-container">
    <h2>Notifikasi Kehadiran Siswa</h2>


    <label>Pilih Kelas:</label>
    <select name="id_kelas" id="kelasSelect">
        <option value="">-- Semua Kelas --</option>
        <?php
        $kelas_result = $conn->query("SELECT * FROM kelas ORDER BY nama_kelas ASC");
        while($k = $kelas_result->fetch_assoc()):
        ?>
        <option value="<?= $k['id_kelas'] ?>" <?= ($id_kelas == $k['id_kelas'])?'selected':'' ?>>
            <?= $k['nama_kelas'] ?>
        </option>
        <?php endwhile; ?>
    </select>

    <table>
        <tr>
            <th>ID Siswa</th>
            <th>NIS</th>
            <th>Nama Siswa</th>
            <th>Mata Pelajaran</th>
            <th>Tanggal & Waktu</th>
            <th>Status</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id_siswa'] ?></td>
            <td><?= $row['nis'] ?></td>
            <td><?= $row['nama_siswa'] ?></td>
            <td><?= $row['nama_mapel'] ?></td>
            <td><?= $row['waktu_absensi_tercatat'] ?></td>
            <td><?= $row['status'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <?php if($result->num_rows == 0) echo "<p>Belum ada data absensi untuk kelas ini.</p>"; ?>
</div>

<script>
document.getElementById('kelasSelect').addEventListener('change', function() {
    let id_kelas = this.value;
    window.location.href = 'notifikasi_kehadiran.php?id_kelas=' + id_kelas;
});
</script>

</body>
</html>

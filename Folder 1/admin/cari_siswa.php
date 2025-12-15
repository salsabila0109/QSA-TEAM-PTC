<?php
include '../db.php';

$q = $_GET['q'] ?? '';
$q = trim($q);

if ($q === '') {
    exit;
}

$stmt = $conn->prepare("SELECT id_siswa, nama_siswa FROM siswa WHERE nama_siswa LIKE CONCAT('%', ?, '%') ORDER BY nama_siswa ASC");
$stmt->bind_param("s", $q);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<div class='hasil-item' onclick=\"pilihSiswa('{$row['id_siswa']}', '" . htmlspecialchars($row['nama_siswa'], ENT_QUOTES) . "')\">"
           . htmlspecialchars($row['nama_siswa']) . "</div>";
    }
} else {
    echo "<div class='hasil-item kosong'>Tidak ditemukan</div>";
}
$stmt->close();
?>

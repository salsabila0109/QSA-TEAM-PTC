<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

if ($_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// --- Filter opsional (kelas/mapel/tanggal)
$id_kelas = $_GET['id_kelas'] ?? null;
$id_mapel = $_GET['id_mapel'] ?? null;
$tanggal  = $_GET['tanggal'] ?? null;

// --- Query utama (ambil dari tabel notifikasi)
$query = "
    SELECT 
        n.id_siswa,
        s.nama_siswa,
        n.nama_mata_pelajaran,
        n.tanggal,
        n.jam,
        n.status
    FROM notifikasi n
    JOIN siswa s ON n.id_siswa = s.id_siswa
";

// --- Tambahkan filter kalau dipilih
$conditions = [];
$params = [];
$types = "";

if ($id_kelas) {
    $conditions[] = "s.id_kelas = ?";
    $types .= "i";
    $params[] = $id_kelas;
}
if ($id_mapel) {
    $conditions[] = "n.nama_mata_pelajaran = (SELECT nama_mapel FROM mata_pelajaran WHERE id_mata_pelajaran = ?)";
    $types .= "i";
    $params[] = $id_mapel;
}
if ($tanggal) {
    $conditions[] = "n.tanggal = ?";
    $types .= "s";
    $params[] = $tanggal;
}

if ($conditions) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// --- Ambil daftar kelas dan mapel
$kelas_result = $conn->query("SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC");
$mapel_result = $conn->query("SELECT id_mata_pelajaran, nama_mapel FROM mata_pelajaran ORDER BY nama_mapel ASC");

// --- MODE AJAX: kirim hanya <tr> (tanpa reload)
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    ob_start();
    $no = 1;
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td>".$no++."</td>
                <td>".htmlspecialchars($row['id_siswa'])."</td>
                <td>".htmlspecialchars($row['nama_siswa'])."</td>
                <td>".htmlspecialchars($row['nama_mata_pelajaran'])."</td>
                <td>".htmlspecialchars($row['tanggal'])."</td>
                <td>".htmlspecialchars($row['jam'])."</td>
                <td>".htmlspecialchars($row['status'])."</td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='7'>Belum ada data notifikasi.</td></tr>";
    }
    $rowsHtml = ob_get_clean();

    header('Content-Type: text/html; charset=UTF-8');
    echo $rowsHtml;
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Notifikasi Kehadiran</title>
    <link rel="stylesheet" href="notifikasi_kehadiran.css?v=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="dashboard-container">

    <!-- Top bar: Back kiri, Export kanan -->
    <div class="top-bar">
        <a href="dashboard_admin.php" class="btn-back" title="Kembali">&#8592;</a>
        <a id="exportLink" href="#" class="btn-export">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>

    </div>

    <h2>Notifikasi Kehadiran Siswa</h2>

    <div class="filter-row">
        <label for="kelasSelect">Pilih Kelas:</label>
        <select id="kelasSelect">
            <option value="">-- Semua Kelas --</option>
            <?php while($k = $kelas_result->fetch_assoc()): ?>
                <option value="<?= $k['id_kelas'] ?>" <?= ($id_kelas == $k['id_kelas'])?'selected':'' ?>>
                    <?= htmlspecialchars($k['nama_kelas']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="mapelSelect">Pilih Mata Pelajaran:</label>
        <select id="mapelSelect">
            <option value="">-- Semua Mapel --</option>
            <?php while($m = $mapel_result->fetch_assoc()): ?>
                <option value="<?= $m['id_mata_pelajaran'] ?>" <?= ($id_mapel == $m['id_mata_pelajaran'])?'selected':'' ?>>
                    <?= htmlspecialchars($m['nama_mapel']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="tanggalSelect">Tanggal:</label>
        <input type="date" id="tanggalSelect" value="<?= htmlspecialchars($tanggal ?? '') ?>">
    </div>

    <div id="ringkasPilihan"></div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>ID Siswa</th>
                <th>Nama Siswa</th>
                <th>Mata Pelajaran</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody id="absensiBody">
            <?php 
            $no = 1;
            if ($result->num_rows > 0): 
                while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['id_siswa']) ?></td>
                        <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                        <td><?= htmlspecialchars($row['nama_mata_pelajaran']) ?></td>
                        <td><?= htmlspecialchars($row['tanggal']) ?></td>
                        <td><?= htmlspecialchars($row['jam']) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">Belum ada data notifikasi.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>

<script>
function updateRingkasan() {
    const kelasSel = document.getElementById('kelasSelect');
    const mapelSel = document.getElementById('mapelSelect');
    const tglSel   = document.getElementById('tanggalSelect');

    const kelasTxt = kelasSel.options[kelasSel.selectedIndex].text;
    const mapelTxt = mapelSel.options[mapelSel.selectedIndex].text;
    const tglTxt   = tglSel.value || "Semua";

    document.getElementById('ringkasPilihan').textContent =
        `Kelas: ${kelasTxt} | Mapel: ${mapelTxt} | Tanggal: ${tglTxt}`;
}

function filterData(){
    const id_kelas = document.getElementById('kelasSelect').value;
    const id_mapel = document.getElementById('mapelSelect').value;
    const tanggal  = document.getElementById('tanggalSelect').value;

    const params = new URLSearchParams();
    params.set('ajax', '1');
    if (id_kelas) params.set('id_kelas', id_kelas);
    if (id_mapel) params.set('id_mapel', id_mapel);
    if (tanggal)  params.set('tanggal', tanggal);

    fetch('notifikasi_kehadiran.php?' + params.toString(), { cache: 'no-store' })
        .then(res => res.text())
        .then(html => {
            document.getElementById('absensiBody').innerHTML = html;
            updateRingkasan();
        });
}

document.getElementById('kelasSelect').addEventListener('change', filterData);
document.getElementById('mapelSelect').addEventListener('change', filterData);
document.getElementById('tanggalSelect').addEventListener('change', filterData);

updateRingkasan();

document.getElementById('exportLink').addEventListener('click', function (e) {
    e.preventDefault();
    const id_kelas = document.getElementById('kelasSelect').value;
    const id_mapel = document.getElementById('mapelSelect').value;
    const tanggal  = document.getElementById('tanggalSelect').value;

    const params = new URLSearchParams();
    if (id_kelas) params.set('id_kelas', id_kelas);
    if (id_mapel) params.set('id_mapel', id_mapel);
    if (tanggal)  params.set('tanggal', tanggal);

    // Buka file export_excel.php + parameter filter
    window.location.href = 'export_excel.php?' + params.toString();
});

</script>
</body>
</html>

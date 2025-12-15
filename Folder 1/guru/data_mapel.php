<?php
session_start();
include '../db.php';

// Cek login guru
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] != 'guru') {
    header("Location: ../login.php");
    exit;
}

// Update status siswa
if (isset($_POST['update_status'])) {
    $id_siswa = $_POST['id_siswa'];
    $id_mapel = $_POST['id_mata_pelajaran'];
    $status = $_POST['status'];

    $query = $conn->prepare("UPDATE absensi_siswa SET status = ? WHERE id_siswa = ? AND id_mata_pelajaran = ?");
    $query->bind_param('sii', $status, $id_siswa, $id_mapel);
    $query->execute();

    // Redirect dengan highlight
    $kelas = $_GET['kelas'] ?? '';
    $mapel = $_GET['mapel'] ?? '';
    $tanggal = $_GET['tanggal'] ?? '';
    $redirect_url = "data_mapel.php?highlight_id={$id_siswa}";
    if($kelas) $redirect_url .= "&kelas={$kelas}";
    if($mapel) $redirect_url .= "&mapel={$mapel}";
    if($tanggal) $redirect_url .= "&tanggal={$tanggal}";

    echo "<script>
        alert('✅ Status siswa berhasil diperbarui!');
        window.location='{$redirect_url}';
    </script>";
    exit;
}

// Ambil daftar kelas dan mapel
$kelas_list = $conn->query("SELECT * FROM kelas ORDER BY nama_kelas ASC")->fetch_all(MYSQLI_ASSOC);
$mapel_list = $conn->query("SELECT * FROM mata_pelajaran ORDER BY nama_mapel ASC")->fetch_all(MYSQLI_ASSOC);

// Ambil filter
$filter_kelas = $_GET['kelas'] ?? '';
$filter_mapel = $_GET['mapel'] ?? '';
$filter_tanggal = $_GET['tanggal'] ?? '';
$highlight_id = $_GET['highlight_id'] ?? '';

// Query siswa
$sql = "
    SELECT s.id_siswa, s.nama_siswa, m.id_mata_pelajaran, m.nama_mapel, a.status
    FROM siswa s
    JOIN absensi_siswa a ON s.id_siswa = a.id_siswa
    JOIN mata_pelajaran m ON a.id_mata_pelajaran = m.id_mata_pelajaran
    JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE 1
";
$params = [];
$types = '';

if($filter_kelas !== '') { $sql .= " AND s.id_kelas = ?"; $params[] = $filter_kelas; $types .= 's'; }
if($filter_mapel !== '') { $sql .= " AND m.id_mata_pelajaran = ?"; $params[] = $filter_mapel; $types .= 's'; }
if($filter_tanggal !== '') { $sql .= " AND DATE(a.waktu_absensi_tercatat) = ?"; $params[] = $filter_tanggal; $types .= 's'; }

$stmt = $conn->prepare($sql);
if($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Mata Pelajaran</title>
    <link rel="stylesheet" href="data_mapel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<body>
<div class="container-mapel">

<a href="dashboard_guru.php" class="btn-back">
    <i class="fas fa-arrow-left"></i>
</a>
    <h2>📘 Data Mata Pelajaran</h2>

    <form method="GET" class="filter-form">
        <label for="kelas">Pilih Kelas:</label>
        <select name="kelas" id="kelas">
            <option value="">-- Semua Kelas --</option>
            <?php foreach($kelas_list as $kelas): ?>
                <option value="<?= $kelas['id_kelas']; ?>" <?= ($filter_kelas == $kelas['id_kelas']) ? 'selected' : ''; ?>>
                    <?= $kelas['nama_kelas']; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="mapel">Pilih Mapel:</label>
        <select name="mapel" id="mapel">
            <option value="">-- Semua Mapel --</option>
            <?php foreach($mapel_list as $mapel): ?>
                <option value="<?= $mapel['id_mata_pelajaran']; ?>" <?= ($filter_mapel == $mapel['id_mata_pelajaran']) ? 'selected' : ''; ?>>
                    <?= $mapel['nama_mapel']; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="tanggal">Tanggal:</label>
        <input type="date" name="tanggal" id="tanggal" value="<?= htmlspecialchars($filter_tanggal); ?>">

       <button type="submit" class="btn-submit">
        <i class="fas fa-search"></i>
        </button>

    </form>

    <table>
        <thead>
            <tr>
                <th>Nama Siswa</th>
                <th>Mata Pelajaran</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr <?= ($highlight_id == $row['id_siswa']) ? 'class="highlight-row"' : ''; ?>>
                <td><?= $row['nama_siswa']; ?></td>
                <td><?= $row['nama_mapel']; ?></td>
                <td>
                    <span class="status-badge <?= strtolower($row['status']); ?>"><?= $row['status']; ?></span>
                </td>
                <td>
                    <i class="fas fa-pencil-alt btn-edit"
                       onclick="editStatus('<?= $row['id_siswa']; ?>','<?= $row['status']; ?>','<?= $row['id_mata_pelajaran']; ?>')"></i>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Popup -->
<div class="popup-bg" id="popup-edit">
    <div class="popup-content">
        <h3>Ubah Status Siswa</h3>
        <form method="POST">
            <input type="hidden" name="id_siswa" id="edit-id">
            <input type="hidden" name="id_mata_pelajaran" id="edit-mapel">
            <select name="status" id="edit-status" required>
                <option value="hadir">Hadir</option>
                <option value="alpa">Alpa</option>
                <option value="sakit">Sakit</option>
                <option value="izin">Izin</option>
            </select>
            <div class="popup-btns">
                <button type="submit" name="update_status" class="btn-simpan">Simpan</button>
                <button type="button" class="btn-batal" onclick="tutupPopup()">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function editStatus(id,status,mapel){
    document.getElementById('popup-edit').style.display = 'flex';
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-status').value = status;
    document.getElementById('edit-mapel').value = mapel;
}
function tutupPopup(){
    document.getElementById('popup-edit').style.display = 'none';
}
</script>
</body>
</html>

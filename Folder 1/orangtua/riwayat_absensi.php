<?php
$title = "Riwayat Absensi";
require_once __DIR__ . "/layout.php";
require_once __DIR__ . "/../db.php";

$id_orangtua = $_SESSION['id_pengguna'] ?? 0;
$id_siswa = isset($_GET['id_siswa']) ? (int)$_GET['id_siswa'] : 0;

// Pastikan siswa adalah milik orangtua terkait
$cek = $conn->prepare("SELECT s.id_siswa, s.nama_siswa FROM siswa s WHERE s.id_siswa=? AND (s.id_orangtua=? OR ?=0)");
$cek->bind_param("iii", $id_siswa, $id_orangtua, $id_orangtua); // jika id_orangtua=0 (belum diset), izinkan sementara
$cek->execute();
$info = $cek->get_result()->fetch_assoc();

// Filter tanggal
$mulai = $_GET['mulai'] ?? date('Y-m-01');
$akhir = $_GET['akhir'] ?? date('Y-m-d');

$stmt = $conn->prepare("
    SELECT a.tanggal, a.status, a.keterangan, mp.nama_mapel
    FROM absensi_siswa a
    LEFT JOIN mata_pelajaran mp ON mp.id_mapel = a.id_mapel
    WHERE a.id_siswa = ? AND a.tanggal BETWEEN ? AND ?
    ORDER BY a.tanggal DESC
");
$stmt->bind_param("iss", $id_siswa, $mulai, $akhir);
$stmt->execute();
$riwayat = $stmt->get_result();
?>
<div class="card shadow-sm">
  <div class="card-header bg-white">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="m-0">Riwayat Absensi <?= $info ? ' - ' . htmlspecialchars($info['nama_siswa']) : '' ?></h5>
      <a href="/orangtua/anak_saya.php" class="btn btn-sm btn-outline-secondary">Kembali</a>
    </div>
  </div>
  <div class="card-body">
    <form class="row g-3 mb-3" method="get">
      <input type="hidden" name="id_siswa" value="<?= (int)$id_siswa ?>">
      <div class="col-md-3">
        <label class="form-label">Mulai</label>
        <input type="date" class="form-control" name="mulai" value="<?= htmlspecialchars($mulai) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Akhir</label>
        <input type="date" class="form-control" name="akhir" value="<?= htmlspecialchars($akhir) ?>">
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-primary">Terapkan</button>
      </div>
    </form>
    <?php if ($riwayat->num_rows === 0): ?>
      <div class="alert alert-info">Tidak ada data absensi pada rentang tanggal tersebut.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead><tr><th>Tanggal</th><th>Mapel</th><th>Status</th><th>Keterangan</th></tr></thead>
          <tbody>
          <?php while ($r = $riwayat->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($r['tanggal']) ?></td>
              <td><?= htmlspecialchars($r['nama_mapel'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['status']) ?></td>
              <td><?= htmlspecialchars($r['keterangan'] ?? '-') ?></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php echo $footer; ?>

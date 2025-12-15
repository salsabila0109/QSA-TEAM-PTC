<?php
$title = "Notifikasi";
require_once __DIR__ . "/layout.php";
require_once __DIR__ . "/../db.php";

$id_orangtua = $_SESSION['id_pengguna'] ?? 0;

/* ===== Ambil opsi dropdown ===== */
$kelas_opts = [];
$mats_opts  = [];

// Kelas
$kr = $conn->query("SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas");
if ($kr) { while ($row = $kr->fetch_assoc()) { $kelas_opts[] = $row; } }

// Mapel (struktur: id_mata_pelajaran, nama_mapel, kode_mapel)
$mr = $conn->query("SELECT id_mata_pelajaran, nama_mapel, kode_mapel FROM mata_pelajaran ORDER BY nama_mapel");
if ($mr) { while ($row = $mr->fetch_assoc()) { $mats_opts[] = $row; } }

/* ===== Baca filter dari query string ===== */
$f_kelas = $_GET['kelas'] ?? '';       // id_kelas (boleh kosong)
$f_mapel = $_GET['mapel'] ?? '';       // nama_mapel (boleh kosong)

// helper untuk bind param (string utk cek '', int utk pembanding id_kelas)
$f_kelas_str = ($f_kelas === '') ? '' : (string)$f_kelas;
$f_kelas_int = ($f_kelas === '') ? 0  : (int)$f_kelas;

/* ===== Query notifikasi (perubahan minimal) =====
   - HAPUS n.id dari SELECT & ORDER BY
   - JOIN kelas untuk bisa filter kelas
   - Tambahkan kondisi opsional utk kelas & mapel
*/
$sql = "
SELECT 
  n.id_siswa,
  n.nama_siswa,
  n.nama_mata_pelajaran,
  n.status,
  n.created_at
FROM notifikasi n
JOIN siswa  s ON s.id_siswa = n.id_siswa
JOIN kelas  k ON k.id_kelas = s.id_kelas
WHERE (s.id_orangtua = ? OR ? = 0)
  AND (? = '' OR k.id_kelas = ?)
  AND (? = '' OR n.nama_mata_pelajaran = ?)
ORDER BY n.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param(
  "iisiss",
  $id_orangtua,      // 1
  $id_orangtua,      // 2
  $f_kelas_str,      // 3 - cek kosong
  $f_kelas_int,      // 4 - bandingkan id_kelas
  $f_mapel,          // 5 - cek kosong
  $f_mapel           // 6 - bandingkan nama mapel
);
$stmt->execute();
$res = $stmt->get_result();
?>

<div class="card shadow-sm">
  <div class="card-header bg-white">
    <h5 class="m-0">Notifikasi Kehadiran</h5>
  </div>

  <div class="card-body">
    <!-- Filter -->
    <form class="row g-2 mb-3" method="get">
      <div class="col-md-4">
        <label class="form-label small">Kelas</label>
        <select name="kelas" class="form-select">
          <option value="">-- Semua Kelas --</option>
          <?php foreach ($kelas_opts as $k): ?>
            <option value="<?= htmlspecialchars($k['id_kelas']) ?>"
              <?= ($f_kelas !== '' && (string)$k['id_kelas'] === (string)$f_kelas) ? 'selected' : '' ?>>
              <?= htmlspecialchars($k['nama_kelas']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-5">
        <label class="form-label small">Mata Pelajaran</label>
        <select name="mapel" class="form-select">
          <option value="">-- Semua Mapel --</option>
          <?php foreach ($mats_opts as $m): ?>
            <option value="<?= htmlspecialchars($m['nama_mapel']) ?>"
              <?= ($f_mapel !== '' && $m['nama_mapel'] === $f_mapel) ? 'selected' : '' ?>>
              <?= htmlspecialchars($m['nama_mapel']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-success w-100" type="submit">Cari</button>
      </div>
    </form>

    <?php if ($res->num_rows === 0): ?>
      <div class="alert alert-secondary">Belum ada notifikasi.</div>
    <?php else: ?>
      <div class="list-group">
        <?php while ($n = $res->fetch_assoc()): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold"><?= htmlspecialchars($n['nama_siswa']) ?></div>
              <div class="small text-muted">
                <?= htmlspecialchars($n['nama_mata_pelajaran'] ?? '-') ?> • Status:
                <strong><?= htmlspecialchars($n['status']) ?></strong>
              </div>
            </div>
            <span class="badge text-bg-light"><?= htmlspecialchars($n['created_at'] ?? '') ?></span>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php echo $footer; ?>

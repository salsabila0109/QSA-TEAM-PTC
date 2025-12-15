<?php
$title = "Anak Saya";
require_once __DIR__ . "/layout.php";
require_once __DIR__ . "/../db.php";

$id_orangtua = $_SESSION['id_pengguna'] ?? 0;

// Ambil daftar anak berdasarkan id_orangtua (fallback jika kolom berbeda)
$sql = "SELECT s.id_siswa, s.nis, s.nama_siswa, k.nama_kelas
        FROM siswa s
        LEFT JOIN kelas k ON k.id_kelas = s.id_kelas
        WHERE (s.id_orangtua = ? OR s.wali_kelas = ?)"; // 'wali_kelas' fallback jika ada relasi berbeda
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_orangtua, $id_orangtua);
$stmt->execute();
$res = $stmt->get_result();
?>
<div class="card shadow-sm">
  <div class="card-header bg-white">
    <h5 class="m-0">Daftar Anak</h5>
  </div>
  <div class="card-body">
    <?php if ($res->num_rows === 0): ?>
      <div class="alert alert-warning">Belum ada data anak yang terhubung dengan akun Anda. Silakan hubungi admin.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Aksi</th></tr></thead>
          <tbody>
          <?php while ($row = $res->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['nis'] ?? '-') ?></td>
              <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
              <td><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
              <td>
                <a class="btn btn-sm btn-primary" href="/orangtua/riwayat_absensi.php?id_siswa=<?= (int)$row['id_siswa'] ?>">Lihat Absensi</a>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php echo $footer; ?>

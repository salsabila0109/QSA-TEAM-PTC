<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

// ============================
// PROSES HAPUS (via GET ?hapus=ID)
// ============================
if (isset($_GET['hapus'])) {
    $idHapus = (int)$_GET['hapus'];

    if ($idHapus > 0) {
        $stmt = $conn->prepare("DELETE FROM kelas WHERE id_kelas = ?");
        $stmt->bind_param("i", $idHapus);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            header("Location: manajemen_data_kelas.php?deleted=1");
            exit;
        } else {
            // biasanya gagal karena kelas masih dipakai (FK) / constraint
            header("Location: manajemen_data_kelas.php?error=1");
            exit;
        }
    } else {
        header("Location: manajemen_data_kelas.php?error=1");
        exit;
    }
}

// Ambil data kelas beserta nama guru (wali kelas)
$query = "
    SELECT k.id_kelas, k.nama_kelas, g.nama_guru
    FROM kelas k
    LEFT JOIN guru g ON k.wali_kelas = g.id_guru
    ORDER BY k.nama_kelas ASC
";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Data Kelas</title>
    <link rel="stylesheet" href="manajemen_data_kelas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="container">
    <!-- Tombol Back seragam -->
    <a href="dashboard_admin.html" class="btn-kembali" title="Kembali">&#8592;</a>

    <header>Manajemen Data Kelas</header>

    <a href="tambah_kelas.php" class="btn btn-tambah">+ Tambah Kelas</a>

    <table>
        <thead>
            <tr>
                <th>ID Kelas</th>
                <th>Nama Kelas</th>
                <th>Nama Wali Kelas</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$row['id_kelas'] ?></td>
                    <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                    <td><?= htmlspecialchars($row['nama_guru'] ?? '-') ?></td>
                    <td class="aksi">
                        <a href="edit_kelas.php?id=<?= (int)$row['id_kelas'] ?>" class="btn btn-edit" title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>

                        <a href="manajemen_data_kelas.php?hapus=<?= (int)$row['id_kelas'] ?>"
                           class="btn btn-hapus"
                           title="Hapus"
                           onclick="return confirm('Yakin hapus kelas ini? Jika masih dipakai data siswa, bisa gagal.');">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4" class="empty">Belum ada data kelas.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// NOTIF model "localhost says ✅ ..."
(function(){
    const params = new URLSearchParams(window.location.search);

    if (params.get('added') === '1') {
        alert('✅ Kelas berhasil ditambahkan.');
        params.delete('added');
    } else if (params.get('updated') === '1') {
        alert('✅ Data kelas berhasil diperbarui.');
        params.delete('updated');
    } else if (params.get('deleted') === '1') {
        alert('✅ Kelas berhasil dihapus.');
        params.delete('deleted');
    } else if (params.get('error') === '1') {
        alert('❌ Gagal memproses. Kemungkinan kelas masih dipakai data siswa.');
        params.delete('error');
    } else {
        return;
    }

    const newUrl = window.location.pathname + (params.toString() ? ('?' + params.toString()) : '');
    window.history.replaceState({}, '', newUrl);
})();
</script>

</body>
</html>

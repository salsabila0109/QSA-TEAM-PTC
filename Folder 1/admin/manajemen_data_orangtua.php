<?php
include '../db.php';

// Ambil data orangtua beserta siswa, kelas, dan nama wali kelas
$query = "
    SELECT 
        o.id_orangtua,
        o.nama_orangtua,
        o.no_hp,
        s.id_siswa,
        s.nama_siswa,
        k.id_kelas,
        k.nama_kelas,
        g.nama_guru AS wali_kelas_nama
    FROM orangtua o
    LEFT JOIN siswa s ON o.id_siswa = s.id_siswa
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    LEFT JOIN guru g ON k.wali_kelas = g.id_guru
    ORDER BY o.id_orangtua DESC
";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Data Orangtua</title>
    <link rel="stylesheet" href="manajemen_data_orangtua.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <!-- Tombol kembali -->
    <a href="dashboard_admin.html" class="btn-kembali" title="Kembali">&#8592;</a>


<div class="search-container">
    <input type="text" placeholder="Cari orangtua..." id="search-input">
    <span class="search-icon">&#128269;</span> <!-- Unicode 🔍 -->
</div>
    

    <!-- Header -->
    <header>Manajemen Data Orangtua</header>

    <!-- Container utama -->
    <div class="container">
        <!-- Tombol tambah -->
        <a href="tambah_orangtua.php" class="btn btn-tambah">
            <i class="fa-solid fa-user-plus"></i> Tambah Orangtua
        </a>

        <!-- Tabel data orangtua -->
        <table>
            <thead>
                <tr>
                    <th>ID Orangtua</th>
                    <th>Nama Orangtua</th>
                    <th>No.Telepon</th>
                    <th>Nama Siswa</th>
                    <th>Nama Kelas</th>
                    <th>Nama Wali Kelas</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr id="orangtua-<?= $row['id_orangtua'] ?>">
                            <td><?= htmlspecialchars($row['id_orangtua']); ?></td>
                            <td><?= htmlspecialchars($row['nama_orangtua']); ?></td>
                            <td><?= htmlspecialchars($row['no_hp']); ?></td>
                            <td><?= htmlspecialchars($row['nama_siswa']); ?></td>
                            <td><?= htmlspecialchars($row['nama_kelas']); ?></td>
                            <td><?= htmlspecialchars($row['wali_kelas_nama'] ?? '-'); ?></td>
                            <td class="aksi">
                                <a href="edit_orangtua.php?id=<?= $row['id_orangtua'] ?>" class="btn btn-edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <button class="btn btn-hapus" onclick="hapusOrangtua(<?= $row['id_orangtua'] ?>)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="empty">Tidak ada data ditemukan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Script AJAX Hapus -->
    <script>
    function hapusOrangtua(id) {
      if (!confirm("Yakin ingin menghapus data orang tua ini?")) return;

      fetch('hapus_orangtua.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          // Hapus baris tabel secara langsung
          const row = document.querySelector(`#orangtua-${id}`);
          if (row) row.remove();
          alert('' + data.message);
        } else {
          alert('' + data.message);
        }
      })
      .catch(() => alert('Terjadi kesalahan koneksi.'));
    }
    </script>
    <script>
const searchInput = document.getElementById('search-input');
searchInput.addEventListener('keyup', function() {
    const filter = searchInput.value.toLowerCase();
    const table = document.querySelector('table');
    const rows = table.getElementsByTagName('tr');

    // Mulai dari i = 1 untuk skip header
    for (let i = 1; i < rows.length; i++) {
        const nameCell = rows[i].getElementsByTagName('td')[1]; 
        if (nameCell) {
            const textValue = nameCell.textContent || nameCell.innerText;
            rows[i].style.display = textValue.toLowerCase().includes(filter) ? '' : 'none';
        }
    }
});
</script>
</body>
</html>

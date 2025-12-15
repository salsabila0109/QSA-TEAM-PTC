<?php
include '../db.php';

$query = "
    SELECT 
        o.id_orangtua,
        o.nama_orangtua,
        o.no_hp,
        s.id_siswa,
        s.nama_siswa,
        k.nama_kelas,
        k.wali_kelas
    FROM orangtua o
    LEFT JOIN siswa s ON o.id_siswa = s.id_siswa
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    ORDER BY o.id_orangtua ASC
";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0):
    while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?= htmlspecialchars($row['id_orangtua']); ?></td>
            <td><?= htmlspecialchars($row['nama_orangtua']); ?></td>
            <td><?= htmlspecialchars($row['no_hp']); ?></td>
            <td><?= htmlspecialchars($row['id_siswa']); ?></td>
            <td><?= htmlspecialchars($row['nama_siswa']); ?></td>
            <td><?= htmlspecialchars($row['nama_kelas']); ?></td>
            <td><?= htmlspecialchars($row['wali_kelas']); ?></td>
        </tr>
<?php endwhile;
else: ?>
    <tr><td colspan="7" class="empty">Tidak ada data ditemukan.</td></tr>
<?php endif; ?>

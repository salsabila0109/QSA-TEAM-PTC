<?php
session_start();
include '../db.php';

// Inisialisasi pesan
$message = "";

// Cek login admin
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['tambah'])) {
    // TIDAK perlu id_guru (AUTO_INCREMENT)
    $nip        = trim($_POST['nip'] ?? '');
    $nama_guru  = trim($_POST['nama_guru'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $username   = $nip; // default username = NIP

    // Validasi sederhana
    if ($nip === '' || $nama_guru === '' || $no_telepon === '') {
        $message = "❌ NIP, Nama Guru, dan No. Telepon wajib diisi.";
    } else {
        // 1) Cek NIP duplikat
        $cekSql  = "SELECT COUNT(*) AS jml FROM guru WHERE nip = ?";
        $cekStmt = $conn->prepare($cekSql);
        if (!$cekStmt) {
            $message = "❌ Gagal menyiapkan query cek NIP: " . $conn->error;
        } else {
            $cekStmt->bind_param("s", $nip);
            $cekStmt->execute();
            $cekResult = $cekStmt->get_result();
            $rowCek    = $cekResult->fetch_assoc();
            $cekStmt->close();

            if ($rowCek && $rowCek['jml'] > 0) {
                $message = "❌ Gagal menambahkan data: NIP '$nip' sudah digunakan oleh guru lain.";
            } else {
                // 2) Insert data guru baru
                $plain_password = "guru123";
                $password_hash  = password_hash($plain_password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("
                    INSERT INTO guru (nip, nama_guru, no_telepon, username, password, tanggal_data_dibuat)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                if (!$stmt) {
                    $message = "❌ Gagal menyiapkan query insert: " . $conn->error;
                } else {
                    $stmt->bind_param("sssss", $nip, $nama_guru, $no_telepon, $username, $password_hash);

                    if ($stmt->execute()) {
                        $newId   = $conn->insert_id; // id_guru yang baru
                        $message = "✅ Data guru berhasil ditambahkan! ID Guru: {$newId} (password default: guru123)";
                    } else {
                        $message = "❌ Gagal menambahkan data: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// Ambil semua data guru
$result = $conn->query("
    SELECT id_guru, nip, nama_guru, no_telepon, tanggal_diperbarui 
    FROM guru 
    ORDER BY id_guru DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Guru</title>
    <link rel="stylesheet" href="tambah_guru.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- Tombol kembali -->
<a href="manajemen_data_guru.php" class="btn-kembali" title="Kembali">←</a>

<div class="container">
    <h2>Manajemen Data Guru</h2>

    <?php if ($message): ?>
      <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" class="form-tambah" id="formTambah">
        <!-- NIP & Nama Guru sejajar (2 kolom) -->
        <div class="form-group">
            <label for="nip">NIP:</label>
            <input
                type="text"
                id="nip"
                name="nip"
                required
                placeholder="Masukkan NIP (contoh: NIP101)"
                inputmode="numeric"
                autocomplete="off"
            >
        </div>

        <div class="form-group">
            <label for="nama_guru">Nama Guru:</label>
            <input
                type="text"
                id="nama_guru"
                name="nama_guru"
                required
                placeholder="Masukkan nama guru"
                autocomplete="name"
            >
        </div>

        <!-- No Telepon full width dengan span 2 kolom -->
        <div class="form-group" style="grid-column: span 2;">
            <label for="no_telepon">No. Telepon:</label>
            <input
                type="text"
                id="no_telepon"
                name="no_telepon"
                required
                placeholder="08xxxxxxxxxx"
                inputmode="numeric"
                autocomplete="tel"
            >
        </div>

        <div class="btn-center">
            <button type="submit" name="tambah" class="btn-submit">
                <i class="fa-solid fa-user-plus"></i> Tambah Guru
            </button>
        </div>
    </form>

    <h3>Daftar Guru</h3>
    <table>
        <thead>
            <tr>
                <th>ID Guru</th>
                <th>NIP</th>
                <th>Nama Guru</th>
                <th>No. Telepon</th>
                <th>Tanggal Diperbarui</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="tbody-guru">
        <?php if ($result && $result->num_rows): ?>
          <?php while($row = $result->fetch_assoc()): ?>
            <tr data-id="<?= $row['id_guru'] ?>">
              <td><?= htmlspecialchars($row['id_guru']) ?></td>
              <td><?= htmlspecialchars($row['nip']) ?></td>
              <td><?= htmlspecialchars($row['nama_guru']) ?></td>
              <td><?= htmlspecialchars($row['no_telepon']) ?></td>
              <td><?= htmlspecialchars($row['tanggal_diperbarui'] ?: '-') ?></td>
              <td class="aksi">
                <a href="#" class="icon-btn btn-edit" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                <a href="#" class="icon-btn btn-hapus" title="Hapus"><i class="fa-solid fa-trash"></i></a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="6" class="empty">Belum ada data guru.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// Hapus guru via AJAX
document.addEventListener('click', function(e){
    const del = e.target.closest('.btn-hapus');
    if (!del) return;
    e.preventDefault();
    if (!confirm('Yakin ingin menghapus data ini?')) return;

    const row = del.closest('tr');
    const id = row.dataset.id;

    fetch('hapus_guru_ajax.php?id=' + encodeURIComponent(id))
        .then(res => res.text())
        .then(msg => {
            alert(msg);
            if (msg.startsWith('✅')) {
                row.remove(); // hanya hapus dari tabel bila sukses
            }
        });
});

// Edit guru via AJAX
document.addEventListener('click', function(e){
    const btn = e.target.closest('.btn-edit');
    if (!btn) return;
    e.preventDefault();

    const row  = btn.closest('tr');
    const id   = row.dataset.id;
    const nip  = row.children[1].innerText;
    const nama = row.children[2].innerText;
    const telp = row.children[3].innerText;

    const newNip  = prompt('Edit NIP:', nip);         if (newNip  === null) return;
    const newNama = prompt('Edit Nama Guru:', nama);  if (newNama === null) return;
    const newTelp = prompt('Edit No. Telepon:', telp); if (newTelp === null) return;

    const fd = new FormData();
    fd.append('id_guru', id);
    fd.append('nip', newNip);
    fd.append('nama_guru', newNama);
    fd.append('no_telepon', newTelp);

    fetch('edit_guru_ajax.php', { method:'POST', body: fd })
        .then(res => res.text())
        .then(msg => {
            alert(msg);
            if (msg.startsWith('✅')) {
                row.children[1].innerText = newNip;
                row.children[2].innerText = newNama;
                row.children[3].innerText = newTelp;
                row.children[4].innerText = new Date().toLocaleString();
            }
        });
});
</script>
</body>
</html>

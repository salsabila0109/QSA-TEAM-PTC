<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

// Cek login admin
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Inisialisasi pesan
$message = "";

// ==============================
// PROSES TAMBAH GURU (POST)
// ==============================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['tambah'])) {

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

            if ($rowCek && (int)$rowCek['jml'] > 0) {
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
                        $newId   = $conn->insert_id;
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

// ==============================
// AMBIL DATA GURU (UNTUK TABEL)
// ==============================
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Data Guru</title>

    <!-- Pakai CSS dari halaman manajemen (biar konsisten) -->
    <link rel="stylesheet" href="manajemen_data_guru.css">
    <!-- Ikon untuk tombol edit/hapus -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
      /* Tambahan kecil agar form rapi walau CSS lama belum punya */
      .form-card{
        margin-top: 12px;
        background:#fff;
        border-radius: 12px;
        padding: 14px;
        box-shadow: 0 10px 25px rgba(0,0,0,.06);
      }
      .message{
        margin: 10px 0 0;
        padding: 10px 12px;
        border-radius: 10px;
        background: #e7f7f3;
        color:#0a7c66;
        font-weight: 600;
      }
      .message:has(span.err){
        background:#ffecec;
        color:#b00020;
      }
      .form-grid{
        display:grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-top: 8px;
      }
      .form-group{
        display:flex;
        flex-direction:column;
        gap:6px;
      }
      .form-group label{
        font-weight:600;
        color:#004D40;
      }
      .form-group input{
        padding:10px 12px;
        border:1px solid #cfe3df;
        border-radius: 10px;
        outline: none;
      }
      .form-group input:focus{
        border-color:#0a7c66;
        box-shadow: 0 0 0 3px rgba(10,124,102,.12);
      }
      .span-2{ grid-column: span 2; }
      .btn-submit{
        background:#0a7c66;
        color:#fff;
        border:0;
        padding:10px 14px;
        border-radius: 10px;
        cursor:pointer;
        font-weight:700;
      }
      .btn-submit:hover{ filter: brightness(.98); }
      .section-title{
        margin: 18px 0 10px;
      }
      .aksi .icon-btn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        width: 38px;
        height: 34px;
        border-radius: 10px;
        text-decoration:none;
        margin-right:6px;
      }
      .aksi .btn-edit{ background:#0a7c66; color:#fff; }
      .aksi .btn-hapus{ background:#c62828; color:#fff; }
      .aksi .icon-btn:hover{ filter: brightness(.98); }
      .empty { text-align:center; padding:12px; }
    </style>
</head>
<body>

<!-- Tombol kembali -->
<a href="dashboard_admin.html" class="btn-kembali" title="Kembali">&#8592;</a>

<!-- Search bar (tetap) -->
<div class="search-container">
    <input type="text" placeholder="Cari guru..." id="search-input">
    <span class="search-icon">&#128269;</span>
</div>

<header>Manajemen Data Guru</header>

<div class="container">

    <!-- =========================
         FORM TAMBAH GURU (DALAM 1 HALAMAN)
    ========================== -->
    <div class="form-card">
        <h3 style="margin:0;">Tambah Guru</h3>

        <?php if ($message): ?>
            <?php
              $isErr = (strpos($message, '❌') !== false);
            ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST" id="formTambahGuru" autocomplete="on">
            <div class="form-grid">
                <div class="form-group">
                    <label for="nip">NIP</label>
                    <input
                        type="text"
                        id="nip"
                        name="nip"
                        required
                        placeholder="Masukkan NIP"
                        inputmode="numeric"
                        autocomplete="off"
                    >
                </div>

                <div class="form-group">
                    <label for="nama_guru">Nama Guru</label>
                    <input
                        type="text"
                        id="nama_guru"
                        name="nama_guru"
                        required
                        placeholder="Masukkan nama guru"
                        autocomplete="name"
                    >
                </div>

                <div class="form-group span-2">
                    <label for="no_telepon">No. Telepon</label>
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
            </div>

            <div style="margin-top:12px; display:flex; justify-content:flex-end;">
                <button type="submit" name="tambah" class="btn-submit">
                    <i class="fa-solid fa-user-plus"></i> Tambah Guru
                </button>
            </div>
        </form>
    </div>

    <!-- =========================
         TABEL DAFTAR GURU
    ========================== -->
    <h3 class="section-title">Daftar Guru</h3>

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
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr data-id="<?= (int)$row['id_guru'] ?>">
                    <td><?= htmlspecialchars($row['id_guru']) ?></td>
                    <td><?= htmlspecialchars($row['nip']) ?></td>
                    <td><?= htmlspecialchars($row['nama_guru']) ?></td>
                    <td><?= htmlspecialchars($row['no_telepon']) ?></td>
                    <td><?= htmlspecialchars($row['tanggal_diperbarui'] ?: '-') ?></td>
                    <td class="aksi">
                        <a href="#" class="icon-btn btn-edit" title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <a href="#" class="icon-btn btn-hapus" title="Hapus">
                            <i class="fa-solid fa-trash"></i>
                        </a>
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
// =========================
// SEARCH FILTER (nama guru)
// =========================
const searchInput = document.getElementById('search-input');
searchInput.addEventListener('keyup', function() {
    const filter = searchInput.value.toLowerCase();
    const rows = document.querySelectorAll('#tbody-guru tr');

    rows.forEach(tr => {
        const nameCell = tr.children[2];
        if (!nameCell) return;
        const textValue = (nameCell.textContent || '').toLowerCase();
        tr.style.display = textValue.includes(filter) ? '' : 'none';
    });
});

// =========================
// HAPUS GURU via AJAX (butuh hapus_guru_ajax.php)
// =========================
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
                row.remove();
            }
        })
        .catch(() => alert('Terjadi kesalahan jaringan.'));
});

// =========================
// EDIT GURU via AJAX (butuh edit_guru_ajax.php)
// =========================
document.addEventListener('click', function(e){
    const btn = e.target.closest('.btn-edit');
    if (!btn) return;
    e.preventDefault();

    const row  = btn.closest('tr');
    const id   = row.dataset.id;
    const nip  = row.children[1].innerText;
    const nama = row.children[2].innerText;
    const telp = row.children[3].innerText;

    const newNip  = prompt('Edit NIP:', nip);           if (newNip  === null) return;
    const newNama = prompt('Edit Nama Guru:', nama);    if (newNama === null) return;
    const newTelp = prompt('Edit No. Telepon:', telp);  if (newTelp === null) return;

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
        })
        .catch(() => alert('Terjadi kesalahan jaringan.'));
});
</script>

</body>
</html>

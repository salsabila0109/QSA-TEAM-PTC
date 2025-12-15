<?php
// ================== tambah_siswa.php ==================
session_start();
include '../db.php'; // harus mendefinisikan $conn (mysqli)

// --- Wajib admin ---
if (!isset($_SESSION['role_pengguna']) || $_SESSION['role_pengguna'] !== 'admin') {
  header("Location: ../login.php");
  exit;
}

/* ---------------- Helper: JSON response utk AJAX ---------------- */
function json_response($ok, $payload = [], $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array_merge(['success' => $ok], $payload));
  exit;
}

/* ---------------- (Opsional) Sinkron ke Firebase RTDB ----------------
   Menulis node ke: https://rfid-eb0c0-default-rtdb.asia-southeast1.firebasedatabase.app/siswa/<id>.json
   Menggunakan REST API via cURL dari PHP.
--------------------------------------------------------------------- */
function firebase_set($path, $dataJson) {
  $base = "https://rfid-eb0c0-default-rtdb.asia-southeast1.firebasedatabase.app";
  $url  = $base . $path . ".json";

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST   => "PUT",                 // set/replace node (POST untuk auto key)
    CURLOPT_POSTFIELDS      => $dataJson,
    CURLOPT_RETURNTRANSFER  => true,
    CURLOPT_HTTPHEADER      => ["Content-Type: application/json"]
  ]);
  $res = curl_exec($ch);
  $err = curl_error($ch);
  curl_close($ch);

  if ($err) return false;
  return $res !== false;
}

/* =========================================================================
   BARU: Generate ID siswa dari NIS
   Rule: id = 4 digit depan (tahun) + 2 digit belakang NIS
   Contoh: 2025123417 -> 202517
   Jika bentrok, cari slot kosong di rentang 202500-202599 (untuk tahun 2025)
   ========================================================================= */
function allocate_student_id(mysqli $conn, string $nis) {
  $nis = preg_replace('/\D+/', '', $nis); // pastikan numeric saja

  // Ambil 4 digit depan (tahun) dan 2 digit belakang
  if (!preg_match('/^(\d{4})\d*(\d{2})$/', $nis, $m)) {
    return false; // format NIS tidak memenuhi minimal 4 depan + 2 belakang
  }
  $year   = (int)$m[1];
  $suffix = (int)$m[2];

  $baseMin = $year * 100;        // contoh 2025*100 = 202500
  $minId   = $baseMin;           // 202500
  $maxId   = $baseMin + 99;      // 202599

  // Ambil semua id_siswa di tahun tersebut untuk cek bentrok (lebih efisien daripada query berulang)
  $used = array_fill(0, 100, false);
  $q = $conn->prepare("SELECT id_siswa FROM siswa WHERE CAST(id_siswa AS UNSIGNED) BETWEEN ? AND ?");
  $q->bind_param("ii", $minId, $maxId);
  $q->execute();
  $res = $q->get_result();
  while ($row = $res->fetch_assoc()) {
    $id = (int)$row['id_siswa'];
    if ($id >= $minId && $id <= $maxId) {
      $used[$id - $minId] = true; // mapping 202500->index 0, 202599->index 99
    }
  }
  $q->close();

  // Coba dulu ID sesuai 2 digit belakang NIS (suffix), jika sudah terpakai cari yang kosong
  for ($offset = 0; $offset < 100; $offset++) {
    $idx = ($suffix + $offset) % 100;     // 0..99
    if (!$used[$idx]) {
      return $minId + $idx;              // id final 6 digit
    }
  }

  // Jika penuh 1 tahun (sangat jarang)
  return false;
}

/* ---------------- Ambil list kelas untuk <select> (render non-AJAX) --------------- */
$kelas_result = $conn->query("SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC");

/* ---------------- Proses POST (AJAX & non-AJAX) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nis      = trim($_POST['nis']      ?? '');
  $nama     = trim($_POST['nama']     ?? '');
  $id_kelas = trim($_POST['id_kelas'] ?? '');
  $telepon  = trim($_POST['telepon']  ?? '');

  // Validasi sederhana
  if ($nis === '' || $nama === '' || $id_kelas === '') {
    if (isset($_GET['ajax'])) json_response(false, ['message' => 'Data wajib belum lengkap'], 400);
    $error = "Data wajib belum lengkap.";
  } else {
    // Cek NIS unik
    $cek = $conn->prepare("SELECT id_siswa FROM siswa WHERE nis = ?");
    $cek->bind_param("s", $nis);
    $cek->execute();
    $cek->store_result();

    if ($cek->num_rows > 0) {
      if (isset($_GET['ajax'])) json_response(false, ['message' => 'NIS sudah terdaftar'], 409);
      $error = "NIS sudah terdaftar, silakan gunakan NIS lain.";
    } else {

      // ==========================
      // BARU: Buat ID dari NIS
      // ==========================
      $customId = allocate_student_id($conn, $nis);
      if ($customId === false) {
        if (isset($_GET['ajax'])) json_response(false, ['message' => 'Format NIS tidak valid untuk pembuatan ID (butuh 4 digit depan + 2 digit belakang).'], 400);
        $error = "Format NIS tidak valid untuk pembuatan ID (butuh 4 digit depan + 2 digit belakang).";
        $cek->close();
        // tampilkan form lagi
      } else {

        // Insert siswa (ISI id_siswa secara manual)
        $stmt = $conn->prepare("INSERT INTO siswa (id_siswa, nis, nama_siswa, id_kelas, nomor_telepon_orangtua, tanggal_data_dibuat)
                                VALUES (?, ?, ?, ?, ?, NOW())");
        // pastikan id_kelas integer
        $id_kelas_int = (int)$id_kelas;

        // i = id_siswa, s = nis, s = nama, i = id_kelas, s = telepon
        $stmt->bind_param("issis", $customId, $nis, $nama, $id_kelas_int, $telepon);
        $ok = $stmt->execute();

        if ($ok) {
          // Karena id_siswa kita set manual, pakai $customId sebagai ID final
          $newId = (int)$customId;

          // Data yang akan disinkronkan ke Firebase
          $rowForFirebase = [
            "id"        => (int)$newId,
            "nis"       => $nis,
            "nama"      => $nama,
            "id_kelas"  => $id_kelas_int,
            "telepon"   => $telepon
          ];
          // Tulis ke /siswa/<id>
          firebase_set("/siswa/$newId", json_encode($rowForFirebase));

          if (isset($_GET['ajax'])) {
            json_response(true, [
              'message' => 'Siswa tersimpan',
              'row'     => $rowForFirebase
            ]);
          } else {
            header("Location: dashboard_admin.php"); // fallback non-AJAX
            exit;
          }
        } else {
          // Kemungkinan bentrok PRIMARY KEY id_siswa kalau ada data lama
          if (isset($_GET['ajax'])) json_response(false, ['message' => 'Gagal menambah siswa: ' . $conn->error], 500);
          $error = "Gagal menambah siswa: " . $conn->error;
        }
        $stmt->close();
      }
    }
    $cek->close();
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Tambah Siswa</title>
  <link rel="stylesheet" href="tambah_siswa.css">
  <style>
    .error{color:#c00;margin:.5rem 0}
    .btn-group{margin-top:1rem;display:flex;gap:.5rem}
    .btn-submit{background:#0a7c66;color:#fff;border:0;padding:.5rem 1rem;border-radius:.375rem;cursor:pointer}
    .btn-back{background:#eee;border:1px solid #ccc;padding:.5rem 1rem;border-radius:.375rem;cursor:pointer}
  </style>
</head>
<body>

<!-- Tombol kembali -->
<a href="manajemen_data_siswa.html" class="btn-kembali">←</a>

<header>Tambah Data Siswa</header>

<div class="container">
  <?php if (isset($error)) echo "<p class='error'>".htmlspecialchars($error)."</p>"; ?>

  <form id="formSiswa" method="post" autocomplete="on">
    <label>NIS</label>
    <input
      type="text"
      name="nis"
      id="nis"
      required
      placeholder="Masukkan NIS (contoh: 2025123403)"
      inputmode="numeric"
      autocomplete="off"
    />
    <p id="nisError" class="error"></p>

    <label>Nama Siswa</label>
    <input
      type="text"
      name="nama"
      id="nama"
      required
      placeholder="Masukkan nama siswa"
      autocomplete="name"
    />
    <p id="namaError" class="error"></p>

    <label>Kelas</label>
    <select name="id_kelas" id="id_kelas" required>
      <option value="" selected disabled>-- Pilih Kelas --</option>
      <?php if ($kelas_result && $kelas_result->num_rows): ?>
        <?php while ($k = $kelas_result->fetch_assoc()): ?>
          <option value="<?= (int)$k['id_kelas'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
        <?php endwhile; ?>
      <?php endif; ?>
    </select>

    <label>No. Telepon Orangtua</label>
    <input
      type="text"
      name="telepon"
      id="telepon"
      placeholder="08xxxxxxxxxx"
      inputmode="numeric"
      autocomplete="tel"
    />
    <p id="teleponError" class="error"></p>

    <div class="btn-group">
      <button type="submit" class="btn-submit">Simpan</button>
    </div>
  </form>
</div>

<script>
// ====== VALIDASI via AJAX sederhana (sesuaikan dengan validasi_data.php milikmu) ======
async function cek(fieldId, type){
  const el = document.getElementById(fieldId);
  const val = el.value.trim();
  if (!val && type !== 'telepon') {
    document.getElementById(fieldId+'Error').innerText = 'Wajib diisi';
    return false;
  }
  try{
    const res = await fetch(`validasi_data.php?type=${encodeURIComponent(type)}&value=${encodeURIComponent(val)}`);
    const data = await res.json();
    document.getElementById(fieldId+'Error').innerText = data.valid ? '' : (data.message || 'Tidak valid');
    return !!data.valid;
  }catch(e){
    document.getElementById(fieldId+'Error').innerText = 'Gagal validasi';
    return false;
  }
}
document.getElementById('nis').addEventListener('blur',   ()=>cek('nis','nis'));
document.getElementById('nama').addEventListener('blur',  ()=>cek('nama','nama'));
document.getElementById('telepon').addEventListener('blur',()=>cek('telepon','telepon'));

// ====== SUBMIT TANPA RELOAD (AJAX) ======
document.getElementById('formSiswa').addEventListener('submit', async function(e){
  e.preventDefault();

  const okNis   = await cek('nis','nis');
  const okNama  = await cek('nama','nama');
  const okTelp  = await cek('telepon','telepon');
  const idKelas = document.getElementById('id_kelas').value;

  if (!(okNis && okNama) || !idKelas) {
    alert('Periksa kembali input Anda.');
    return;
  }

  const fd = new FormData(this);
  try{
    const res  = await fetch('tambah_siswa.php?ajax=1', {
      method: 'POST',
      body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const data = await res.json();

    if (data.success) {
      alert('Data Siswa tersimpan ✅');

      // reset form
      this.reset();
      ['nisError','namaError','teleponError'].forEach(id=>document.getElementById(id).innerText='');

      // Jika halaman ini memiliki tabel <tbody id="siswa-tbody">,
      // langsung tambahkan baris baru tanpa reload.
      if (data.row) {
        const tbody = document.getElementById('siswa-tbody');
        if (tbody) {
          const r = data.row;
          const tr = document.createElement('tr');
          tr.setAttribute('data-key', r.id);
          tr.innerHTML = `
            <td>${r.id}</td>
            <td>${r.nis}</td>
            <td>${r.nama}</td>
            <td>${r.id_kelas}</td>
            <td>${r.telepon || '-'}</td>
            <td>
              <button class="btn btn-success btn-sm" data-id="${r.id}">Edit</button>
              <button class="btn btn-danger  btn-sm" data-id="${r.id}">Hapus</button>
            </td>
          `;
          tbody.prepend(tr);
        }
      }
    } else {
      alert(data.message || 'Gagal menyimpan.');
    }
  }catch(err){
    console.error(err);
    alert('Terjadi kesalahan jaringan.');
  }
});
</script>
</body>
</html>

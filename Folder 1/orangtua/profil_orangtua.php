<?php
session_start();
require_once __DIR__ . "/../db.php";

// Cek session orangtua
if (!isset($_SESSION['id_pengguna']) || $_SESSION['role_pengguna'] !== 'orangtua') {
    header("Location: login_orangtua.php");
    exit;
}

$id_orangtua = $_SESSION['id_pengguna'];
$upload_dir = __DIR__ . "/uploads";

// Pastikan folder upload ada
if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);

// Ambil profil
$stmt = $conn->prepare("SELECT id_orangtua, nama_orangtua, username, no_hp, foto FROM orangtua WHERE id_orangtua=?");
$stmt->bind_param("i", $id_orangtua);
$stmt->execute();
$result = $stmt->get_result();
$profil = $result->fetch_assoc() ?: [
    'nama_orangtua' => '',
    'username' => '',
    'no_hp' => '',
    'foto' => ''
];
$stmt->close();

$flash = null;

// Update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nama = $_POST['nama_orangtua'] ?? $profil['nama_orangtua'];
    $nohp = $_POST['no_hp'] ?? $profil['no_hp'];

    // Upload foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $filename = "orangtua_" . $id_orangtua . "." . $ext;
        $target = $upload_dir . "/" . $filename;
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $target)) {
            $foto_sql = $filename;
        } else {
            $foto_sql = $profil['foto'] ?? null;
            $flash = ["danger", "Gagal mengupload foto"];
        }
    } else {
        $foto_sql = $profil['foto'] ?? null;
    }

    // Update database
    $upd = $conn->prepare("UPDATE orangtua SET nama_orangtua=?, no_hp=?, foto=? WHERE id_orangtua=?");
    $upd->bind_param("sssi", $nama, $nohp, $foto_sql, $id_orangtua);
    if ($upd->execute()) {
        $flash = ["success", "Profil berhasil diperbarui"];
        $_SESSION['nama_pengguna'] = $nama;
        $_SESSION['foto_orangtua'] = $foto_sql;
    } else {
        $flash = ["danger", "Gagal memperbarui profil"];
    }

    // Refresh profil
    $stmt = $conn->prepare("SELECT id_orangtua, nama_orangtua, username, no_hp, foto FROM orangtua WHERE id_orangtua=?");
    $stmt->bind_param("i", $id_orangtua);
    $stmt->execute();
    $result = $stmt->get_result();
    $profil = $result->fetch_assoc() ?: [
        'nama_orangtua' => '',
        'username' => '',
        'no_hp' => '',
        'foto' => ''
    ];
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Profil Orangtua</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="profil_orangtua.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>

<a href="dashboard_orangtua.php" class="btn-back">
    <i class="fas fa-arrow-left"></i>
</a>

<div class="container">
    <h2>Profil Orangtua</h2>

    <!-- Alert Profil -->
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash[0] ?>"><?= $flash[1] ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Form Profil -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header"><h5>Profil</h5></div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="update_profil" value="1">
                        <div class="foto-wrapper">
                            <img src="<?= !empty($profil['foto']) ? 'uploads/' . htmlspecialchars($profil['foto']) : 'uploads/default.png' ?>" 
                            alt="Foto Profil" class="foto-profil">
                        </div>
                        <div class="mb-3 mt-2">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" name="nama_orangtua" 
                                   value="<?= htmlspecialchars($profil['nama_orangtua'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No HP</label>
                            <input type="text" class="form-control" name="no_hp" 
                                   value="<?= htmlspecialchars($profil['no_hp'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Foto Profil</label>
                            <input type="file" class="form-control" name="foto" accept="image/*">
                        </div>
                        <button class="btn btn-primary">Simpan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

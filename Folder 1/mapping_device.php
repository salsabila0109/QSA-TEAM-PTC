<?php
require __DIR__.'/vendor/autoload.php';
use Kreait\Firebase\Factory;

$factory = (new Factory)
    ->withServiceAccount(__DIR__.'/firebase_credentials.json')
    ->withDatabaseUri('https://your-firebase-project.firebaseio.com/');

$database = $factory->createDatabase();
$reference = $database->getReference('mapping_device/');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = $_POST['uid'];
    $nama = $_POST['nama'];
    $mapel = $_POST['mapel'];

    if (!empty($uid) && !empty($nama) && !empty($mapel)) {
        $reference->getChild($uid)->set([
            'nama' => $nama,
            'mapel' => $mapel
        ]);
        echo "✅ Data mapping berhasil disimpan!";
    } else {
        echo "❌ Lengkapi semua field dulu ya!";
    }
}

$data = $reference->getValue();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Mapping Device (PresenTech)</title>
</head>
<body>
<h2>Tambah Mapping Device (UID IoT ↔ Siswa)</h2>
<form method="POST">
    UID IoT: <input type="text" name="uid" required><br><br>
    Nama Siswa: <input type="text" name="nama" required><br><br>
    ID Mata Pelajaran: <input type="text" name="mapel" required><br><br>
    <button type="submit">Simpan ke Firebase</button>
</form>

<h3>Data Mapping Saat Ini:</h3>
<?php
if ($data) {
    echo "<table border='1' cellpadding='5'>
            <tr><th>UID</th><th>Nama</th><th>Mata Pelajaran</th></tr>";
    foreach ($data as $uid => $value) {
        echo "<tr>
                <td>{$uid}</td>
                <td>{$value['nama']}</td>
                <td>{$value['mapel']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "Belum ada data mapping.";
}
?>
</body>
</html>

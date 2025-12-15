<?php
require __DIR__.'/vendor/autoload.php'; // pastikan composer firebase SDK sudah diinstall
use Kreait\Firebase\Factory;

$factory = (new Factory)
    ->withServiceAccount(__DIR__.'/firebase_credentials.json')
    ->withDatabaseUri('https://rfid-eb0c0-default-rtdb.asia-southeast1.firebasedatabase.app/'); // ganti URL project kamu

$database = $factory->createDatabase();
$reference = $database->getReference('absensi/');
$data = $reference->getValue();

echo "<h2>Data Absensi dari Firebase</h2>";
if ($data) {
    echo "<table border='1' cellpadding='5'>
            <tr><th>UID</th><th>Nama</th><th>Mata Pelajaran</th><th>Waktu</th><th>Status</th></tr>";
    foreach ($data as $uid => $value) {
        echo "<tr>
                <td>{$uid}</td>
                <td>{$value['nama']}</td>
                <td>{$value['mapel']}</td>
                <td>{$value['waktu']}</td>
                <td>{$value['status']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "Belum ada data absensi di Firebase.";
}
?>

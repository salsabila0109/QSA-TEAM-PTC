<?php
// export_excel.php
// Eksport CSV: nama_siswa, nama_mata_pelajaran, tanggal, jam, status

require '../db.php'; // pastikan path ini benar
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

try {
    // Pastikan tidak ada output sebelum header
    if (ob_get_level()) ob_end_clean();

    // Ambil filter opsional
    $id_kelas = isset($_GET['id_kelas']) && trim($_GET['id_kelas']) !== '' ? trim($_GET['id_kelas']) : null;
    $id_mapel = isset($_GET['id_mapel']) && trim($_GET['id_mapel']) !== '' ? trim($_GET['id_mapel']) : null;
    $tanggal  = isset($_GET['tanggal']) && trim($_GET['tanggal']) !== '' ? trim($_GET['tanggal']) : null;

    // Siapkan header CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_notifikasi_kehadiran.csv"');

    $out = fopen('php://output', 'w');
    // BOM agar Excel membaca UTF-8 dengan benar
    fwrite($out, "\xEF\xBB\xBF");

    // Judul kolom
    fputcsv($out, ['Nama Siswa', 'Mata Pelajaran', 'Tanggal', 'Jam', 'Status']);

    // Bangun query (gabungkan notifikasi + siswa)
    $query = "
        SELECT 
            s.nama_siswa,
            n.nama_mata_pelajaran,
            n.tanggal,
            n.jam,
            n.status
        FROM notifikasi n
        JOIN siswa s ON n.id_siswa = s.id_siswa
    ";

    $conditions = [];
    $params = [];
    $types = "";

    if ($id_kelas) {
        $conditions[] = "s.id_kelas = ?";
        $types .= "i";
        $params[] = $id_kelas;
    }
    if ($id_mapel) {
        $conditions[] = "n.nama_mata_pelajaran = (SELECT nama_mapel FROM mata_pelajaran WHERE id_mata_pelajaran = ? LIMIT 1)";
        $types .= "i";
        $params[] = $id_mapel;
    }
    if ($tanggal) {
        $conditions[] = "n.tanggal = ?";
        $types .= "s";
        $params[] = $tanggal;
    }

    if ($conditions) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY n.tanggal DESC, n.jam DESC";

    $stmt = $conn->prepare($query);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    // Tulis ke file CSV
    while ($row = $res->fetch_assoc()) {
        fputcsv($out, [
            $row['nama_siswa'],
            $row['nama_mata_pelajaran'],
            $row['tanggal'],
            $row['jam'],
            $row['status']
        ]);
    }

    fclose($out);
    exit;
} catch (Throwable $e) {
    // Jika gagal, tampilkan pesan error di browser (bukan CSV)
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Export gagal: " . $e->getMessage();
    exit;
}

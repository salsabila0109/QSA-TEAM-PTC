<?php
// api_lookup_fingerprint.php
// Lookup siswa berdasarkan finger_id (MySQL master)
// GET  : api_lookup_fingerprint.php?finger_id=7
// POST : JSON {"finger_id":7}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php'; // pastikan db.php ada di root yang sama

// (Opsional) kalau mau batasi akses web app berdasarkan session:
// session_start();
// if (!isset($_SESSION['role_pengguna']) || !in_array($_SESSION['role_pengguna'], ['admin','guru'])) {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'message' => 'Unauthorized']);
//     exit;
// }

// Ambil finger_id dari GET atau JSON body
$fingerId = null;

if (isset($_GET['finger_id'])) {
    $fingerId = $_GET['finger_id'];
} else {
    $raw = file_get_contents("php://input");
    if ($raw) {
        $json = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($json['finger_id'])) {
            $fingerId = $json['finger_id'];
        }
    }
}

// Validasi finger_id
if ($fingerId === null || $fingerId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parameter finger_id wajib diisi.']);
    exit;
}

if (!ctype_digit((string)$fingerId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'finger_id harus berupa angka.']);
    exit;
}

$fingerIdInt = (int)$fingerId;

// Query (tanpa join supaya aman walau struktur tabel kelas berbeda)
$sql = "SELECT id_siswa, nis, nama_siswa, id_kelas, finger_id
        FROM siswa
        WHERE finger_id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare query gagal.']);
    exit;
}

$stmt->bind_param("i", $fingerIdInt);
$stmt->execute();

$result = $stmt->get_result();
if (!$result) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Query gagal dieksekusi.']);
    exit;
}

$row = $result->fetch_assoc();

if (!$row) {
    // Tidak ditemukan = finger_id belum dipasangkan ke siswa manapun di MySQL
    echo json_encode([
        'success' => false,
        'found'   => false,
        'message' => 'Finger ID tidak terdaftar pada data siswa.'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'found'   => true,
    'data'    => [
        'id_siswa'   => $row['id_siswa'],
        'nis'        => $row['nis'],
        'nama_siswa' => $row['nama_siswa'],
        'id_kelas'   => $row['id_kelas'],
        'finger_id'  => $row['finger_id'],
    ]
]);

<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// =================== AUTH (guru/admin) ===================
$role = strtolower(trim($_SESSION['role_pengguna'] ?? ''));
if (!in_array($role, ['guru', 'admin'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya guru/admin.']);
    exit;
}
$username = $_SESSION['username'] ?? 'unknown';

// =================== INPUT ===================
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) $input = $json;
}

$tanggal = trim($input['tanggal'] ?? '');
$kelas   = trim($input['kelas'] ?? '');
$mapel   = trim($input['mapel'] ?? '');

if ($tanggal === '' || $kelas === '' || $mapel === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parameter wajib: tanggal, kelas, mapel.']);
    exit;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Format tanggal harus YYYY-MM-DD.']);
    exit;
}

// =================== DB (MySQL master) ===================
require_once __DIR__ . '/db.php';

// Ambil roster siswa berdasarkan nama_kelas
// Asumsi tabel: siswa(id_siswa, id_kelas, ...), kelas(id_kelas, nama_kelas)
$stmt = $conn->prepare("
    SELECT s.id_siswa
    FROM siswa s
    JOIN kelas k ON k.id_kelas = s.id_kelas
    WHERE k.nama_kelas = ?
");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal prepare MySQL: ' . $conn->error]);
    exit;
}
$stmt->bind_param("s", $kelas);
$stmt->execute();
$res = $stmt->get_result();

$rosterIds = [];
while ($r = $res->fetch_assoc()) {
    $rosterIds[] = (string)$r['id_siswa'];
}
$stmt->close();

if (empty($rosterIds)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => "Tidak ada siswa untuk kelas {$kelas} di MySQL."]);
    exit;
}

// =================== FIREBASE CONFIG ===================
$FIREBASE_DB_URL = 'https://presentech-4c4c0-default-rtdb.firebaseio.com';

function fb_key(string $s): string {
    $s = trim($s);
    $s = preg_replace('/[\.#\$\[\]\/]/', '_', $s);
    $s = preg_replace('/\s+/', '_', $s);
    return $s !== '' ? $s : 'unknown';
}
function fb_url(string $path): string {
    global $FIREBASE_DB_URL;
    $path = trim($path, '/');
    $segments = $path === '' ? [] : explode('/', $path);
    $segments = array_map('rawurlencode', $segments);
    return rtrim($FIREBASE_DB_URL, '/') . '/' . implode('/', $segments) . '.json';
}
function fb_request(string $method, string $path, $payload = null): array {
    $url = fb_url($path);

    $opts = [
        'http' => [
            'method'        => $method,
            'timeout'       => 20,
            'ignore_errors' => true,
            'header'        => "Content-Type: application/json\r\n"
        ]
    ];

    if ($payload !== null) {
        $opts['http']['content'] = json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    $ctx  = stream_context_create($opts);
    $body = @file_get_contents($url, false, $ctx);

    $status = 0;
    if (isset($http_response_header[0]) && preg_match('#HTTP/\S+\s+(\d+)#', $http_response_header[0], $m)) {
        $status = (int)$m[1];
    }

    $decoded = null;
    if ($body !== false && $body !== '' && $body !== 'null') {
        $decoded = json_decode($body, true);
    }

    return ['status' => $status, 'body' => $body, 'json' => $decoded];
}

function getFingerprintNode($dayNode): array {
    if (!is_array($dayNode)) return [];
    if (isset($dayNode['fingerprint']) && is_array($dayNode['fingerprint'])) return $dayNode['fingerprint'];
    return $dayNode;
}

// =================== 1) Ambil /students Firebase sekali ===================
$studentsResp = fb_request('GET', 'students');
if ($studentsResp['status'] < 200 || $studentsResp['status'] >= 300 || !is_array($studentsResp['json'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengambil /students dari Firebase.']);
    exit;
}
$studentsFb = $studentsResp['json']; // key = id_siswa

// map id_siswa -> [finger_id, nama]
$idToFinger = [];
foreach ($studentsFb as $idSiswa => $s) {
    if (!is_array($s)) continue;
    $fid = $s['finger_id'] ?? $s['fingerID'] ?? $s['fingerId'] ?? null;
    if ($fid === null || $fid === '') continue;

    $idToFinger[(string)$idSiswa] = [
        'finger_id' => (string)$fid,
        'nama'      => (string)($s['nama'] ?? $s['nama_siswa'] ?? '')
    ];
}

// =================== 2) Ambil attendance hari tersebut ===================
$dayResp = fb_request('GET', "attendance/{$tanggal}");
$dayNode = (is_array($dayResp['json']) ? $dayResp['json'] : []);
$fpNode  = getFingerprintNode($dayNode);

// Kumpulkan finger_id yang sudah tercatat untuk mapel ini (status apa pun dihitung "sudah absen/tercatat")
$presentFinger = []; // finger_id => true

$mapelNorm = mb_strtolower(trim($mapel));

if (is_array($fpNode)) {
    foreach ($fpNode as $key => $rec) {
        if (!is_array($rec)) continue;

        $recMapel = trim((string)($rec['mapel'] ?? ''));
        if ($recMapel === '') continue;
        if (mb_strtoupper($recMapel) === 'RFID LOGIN') continue;

        if (mb_strtolower($recMapel) !== $mapelNorm) continue;

        $fid = $rec['finger_id'] ?? $rec['fingerID'] ?? $rec['fingerId'] ?? null;
        if ($fid === null || $fid === '') continue;

        $presentFinger[(string)$fid] = true;
    }
}

// =================== 3) Buat record ALPA untuk yang belum tercatat ===================
$marked = 0;
$skippedNoFinger = 0;
$listNoFinger = [];
$listMarked   = [];

$nowDateTime = date('Y-m-d H:i:s');
$nowTimeOnly = date('H:i:s');

// target tulis: kalau hari ini sudah pakai fingerprint node, kita POST ke fingerprint node.
// kalau tidak, POST ke node tanggal langsung.
$useFingerprintChild = is_array($dayNode) && isset($dayNode['fingerprint']) && is_array($dayNode['fingerprint']);
$writePath = $useFingerprintChild ? "attendance/{$tanggal}/fingerprint" : "attendance/{$tanggal}";

foreach ($rosterIds as $idSiswa) {
    if (!isset($idToFinger[$idSiswa])) {
        $skippedNoFinger++;
        $listNoFinger[] = $idSiswa;
        continue;
    }

    $fid  = $idToFinger[$idSiswa]['finger_id'];
    $nama = $idToFinger[$idSiswa]['nama'] ?: '-';

    // sudah ada record untuk mapel ini => jangan ditimpa/ditambah
    if (isset($presentFinger[$fid])) {
        continue;
    }

    // buat record alpa (push key) agar tidak bentrok dengan struktur yg sudah ada
    $payload = [
        'id_siswa'  => $idSiswa,
        'finger_id' => is_numeric($fid) ? (int)$fid : $fid,
        'siswa'     => $nama,
        'mapel'     => $mapel,
        'status'    => 'Alpa',
        'time'      => $nowTimeOnly,
        'auto'      => true,
        'note'      => 'Auto Alpa (Sesi ditutup)',
        'by'        => $username,
        'closed_at' => $nowDateTime
    ];

    $post = fb_request('POST', $writePath, $payload);
    if ($post['status'] >= 200 && $post['status'] < 300) {
        $marked++;
        $presentFinger[$fid] = true; // supaya idempotent jika loop lanjut
        $listMarked[] = ['id_siswa' => $idSiswa, 'finger_id' => $fid, 'nama' => $nama];
    }
}

// =================== 4) Update status sesi menjadi CLOSED ===================
$kelasKey = fb_key($kelas);
$mapelKey = fb_key($mapel);
$sessionPath = "sessions/{$tanggal}/{$kelasKey}/{$mapelKey}";

$patchData = [
    'status'     => 'closed',
    'closed_at'  => $nowDateTime,
    'closed_by'  => $username,
    'alpa_added' => $marked
];
fb_request('PATCH', $sessionPath, $patchData);

// =================== OUTPUT ===================
echo json_encode([
    'success' => true,
    'message' => "Sesi ditutup. Auto ALPA dibuat: {$marked} siswa. (Kelas: {$kelas}, Mapel: {$mapel}, Tanggal: {$tanggal})",
    'marked_alpa' => $marked,
    'skipped_no_finger' => $skippedNoFinger,
    'no_finger_ids' => $listNoFinger,
    'marked_preview' => array_slice($listMarked, 0, 10)
], JSON_UNESCAPED_UNICODE);

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

// =================== INPUT (POST form atau JSON) ===================
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

// =================== FIREBASE CONFIG ===================
$FIREBASE_DB_URL = 'https://presentech-4c4c0-default-rtdb.firebaseio.com';

// Key Firebase tidak boleh . # $ [ ] /
// untuk sesi, kita buat key aman agar tidak error kalau mapel ada karakter aneh.
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
            'timeout'       => 15,
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

// =================== CREATE/UPDATE SESSION ===================
$kelasKey = fb_key($kelas);
$mapelKey = fb_key($mapel);
$sessionPath = "sessions/{$tanggal}/{$kelasKey}/{$mapelKey}";

// cek kalau sudah open
$cek = fb_request('GET', $sessionPath);
if ($cek['status'] === 200 && is_array($cek['json'])) {
    $st = strtolower(trim($cek['json']['status'] ?? ''));
    if ($st === 'open') {
        echo json_encode([
            'success' => true,
            'message' => "Sesi sudah berjalan (OPEN) untuk {$kelas} - {$mapel} pada {$tanggal}.",
            'session' => $cek['json']
        ]);
        exit;
    }
}

$now = date('Y-m-d H:i:s');

$data = [
    'status'      => 'open',
    'tanggal'     => $tanggal,
    'kelas'       => $kelas,
    'mapel'       => $mapel,
    'started_at'  => $now,
    'started_by'  => $username,
    'role'        => $role
];

// pakai PUT biar node sesi konsisten
$put = fb_request('PUT', $sessionPath, $data);
if ($put['status'] < 200 || $put['status'] >= 300) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan sesi ke Firebase.']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => "Sesi berhasil dimulai untuk {$kelas} - {$mapel} ({$tanggal}).",
    'session' => $data
]);

<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// ===== AUTH (guru/admin) =====
$role = strtolower(trim($_SESSION['role_pengguna'] ?? ''));
if (!in_array($role, ['guru', 'admin'], true)) {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
  exit;
}

$FIREBASE_DB_URL = 'https://presentech-4c4c0-default-rtdb.firebaseio.com';

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
  if ($body !== false && $body !== '' && $body !== 'null') $decoded = json_decode($body, true);

  return ['status' => $status, 'json' => $decoded, 'body' => $body];
}

function s($v){ return trim((string)($v ?? '')); }

// 1) ambil students
$resp = fb_request('GET', 'students');
if ($resp['status'] < 200 || $resp['status'] >= 300 || !is_array($resp['json'])) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Gagal ambil /students']);
  exit;
}

$students = $resp['json'];
$index = [];
$skipped = 0;

foreach ($students as $idSiswa => $st) {
  if (!is_array($st)) { $skipped++; continue; }

  $fid = s($st['finger_id'] ?? $st['fingerID'] ?? $st['fingerId'] ?? '');
  if ($fid === '') { $skipped++; continue; }

  // nama/kls ambil yang tersedia
  $nama  = s($st['nama'] ?? $st['nama_siswa'] ?? '');
  $kelas = s($st['kelas'] ?? '');

  $index[$fid] = [
    'id_siswa' => (string)$idSiswa,
    'nama'     => $nama,
    'kelas'    => $kelas
  ];
}

// 2) tulis index sekaligus (PUT)
$put = fb_request('PUT', 'students_by_finger', $index);
if ($put['status'] < 200 || $put['status'] >= 300) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Gagal tulis /students_by_finger']);
  exit;
}

echo json_encode([
  'success' => true,
  'message' => 'Index students_by_finger berhasil dibuat.',
  'total_indexed' => count($index),
  'skipped' => $skipped
], JSON_UNESCAPED_UNICODE);

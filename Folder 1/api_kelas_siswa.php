<?php
// api_kelas_siswa.php
// Mengembalikan:
//   - kelas_list  : daftar nama_kelas untuk dropdown
//   - siswa_kelas : mapping id_siswa (string) -> nama_kelas
//   - siswa_info  : mapping id_siswa (string) -> { nama, nis, nama_kelas }

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Batasi admin DAN guru
if (
    !isset($_SESSION['role_pengguna']) ||
    !in_array($_SESSION['role_pengguna'], ['admin', 'guru'], true)
) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Akses ditolak: bukan admin/guru.'
    ]);
    exit;
}

// Koneksi database (mysqli)
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

function safe_str($v): string {
    if ($v === null) return '';
    return trim((string)$v);
}

$kelas_list  = [];
$siswa_kelas = [];
$siswa_info  = []; // id_siswa -> info siswa

try {
    // 1) daftar kelas
    $sqlKelas = "
        SELECT DISTINCT TRIM(nama_kelas) AS nama_kelas
        FROM kelas
        WHERE nama_kelas IS NOT NULL
          AND TRIM(nama_kelas) <> ''
        ORDER BY TRIM(nama_kelas) ASC
    ";

    $resKelas = $conn->query($sqlKelas);
    if ($resKelas === false) {
        throw new Exception('Query kelas error: ' . $conn->error);
    }

    while ($row = $resKelas->fetch_assoc()) {
        $kls = safe_str($row['nama_kelas'] ?? '');
        if ($kls !== '') $kelas_list[] = $kls;
    }

    // 2) mapping siswa + info nama (ambil dari MySQL agar roster pasti ada nama)
    $sqlMap = "
        SELECT 
            s.id_siswa,
            s.nis,
            s.nama_siswa,
            TRIM(k.nama_kelas) AS nama_kelas
        FROM siswa s
        LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    ";

    $resMap = $conn->query($sqlMap);
    if ($resMap === false) {
        throw new Exception('Query mapping siswa error: ' . $conn->error);
    }

    while ($row = $resMap->fetch_assoc()) {
        $idSiswa   = safe_str($row['id_siswa'] ?? '');
        $nis       = safe_str($row['nis'] ?? '');
        $namaSiswa = safe_str($row['nama_siswa'] ?? '');
        $namaKelas = safe_str($row['nama_kelas'] ?? '');

        if ($idSiswa === '') continue;

        // mapping kelas
        $siswa_kelas[$idSiswa] = $namaKelas;

        // info siswa
        $siswa_info[$idSiswa] = [
            'nama'       => $namaSiswa,
            'nis'        => $nis,
            'nama_kelas' => $namaKelas
        ];
    }

    echo json_encode([
        'success'     => true,
        'kelas_list'  => $kelas_list,
        'siswa_kelas' => $siswa_kelas,
        'siswa_info'  => $siswa_info
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}

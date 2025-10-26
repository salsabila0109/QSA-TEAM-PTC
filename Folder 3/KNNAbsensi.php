<?php
declare(strict_types=1);

class KNNAbsensi
{
    /** Jumlah pembanding terdekat (k dalam KNN) */
    private int $k = 3;

    /** Fitur latih: array<array<float|int>> */
    private array $X = [];

    /** Label latih: array<string> */
    private array $y = [];

    public function __construct(int $k = 3)
    {
        $this->k = max(1, $k);
    }

    /** Atur k (jumlah pembanding terdekat) */
    public function setK(int $k): void
    {
        $this->k = max(1, $k);
    }

    /** Ambil k */
    public function getK(): int
    {
        return $this->k;
    }

    /** Set data latih sekaligus */
    public function setTrainingData(array $X, array $y): void
    {
        if (count($X) !== count($y)) {
            throw new InvalidArgumentException('Jumlah X dan y harus sama.');
        }
        $this->X = $X;
        $this->y = $y;
    }

    /** Tambah satu baris data latih */
    public function addTrainingRow(array $x, string $label): void
    {
        $this->X[] = $x;
        $this->y[] = $label;
    }

    /** Kosongkan data latih */
    public function clearTrainingData(): void
    {
        $this->X = [];
        $this->y = [];
    }

    /** Jarak 1D (sesuai implementasi awal) */
    private function dist(array $a, array $b): float
    {
        $dx = ($a[0] ?? 0) - ($b[0] ?? 0);
        return sqrt($dx * $dx);
    }

    /** Prediksi satu titik */
    private function predictOne(array $x): string
    {
        $n = count($this->X);
        if ($n === 0) return 'Tidak Disiplin';

        // hitung jarak ke semua data latih
        $dists = [];
        for ($i = 0; $i < $n; $i++) {
            $dists[] = [$this->dist($x, $this->X[$i]), $this->y[$i]];
        }
        usort($dists, fn($a, $b) => $a[0] <=> $b[0]);

        $k = min($this->k, $n);

        // voting label + simpan total jarak per label (tie-breaker)
        $vote = [];
        $sumDistPerLabel = [];
        for ($i = 0; $i < $k; $i++) {
            [$d, $lbl] = $dists[$i];
            $vote[$lbl] = ($vote[$lbl] ?? 0) + 1;
            $sumDistPerLabel[$lbl] = ($sumDistPerLabel[$lbl] ?? 0.0) + $d;
        }

        $maxVote = max($vote);
        $candidates = array_keys(array_filter($vote, fn($v) => $v === $maxVote));
        if (count($candidates) === 1) {
            return $candidates[0];
        }

        // tie-breaker: total jarak paling kecil; jika masih seri, alfabetis
        $best = $candidates[0];
        $bestSum = $sumDistPerLabel[$best] ?? INF;
        foreach ($candidates as $lbl) {
            $sum = $sumDistPerLabel[$lbl] ?? INF;
            if ($sum < $bestSum) {
                $best = $lbl;
                $bestSum = $sum;
            } elseif ($sum === $bestSum && strcmp($lbl, $best) < 0) {
                $best = $lbl;
            }
        }
        return $best;
    }

    /** Prediksi satu titik + skor sederhana (proporsi suara dari k) */
    public function predictOneWithScore(array $x): array
    {
        $n = count($this->X);
        if ($n === 0) return ['label' => 'Tidak Disiplin', 'score' => 0.0];

        $dists = [];
        for ($i = 0; $i < $n; $i++) {
            $dists[] = [$this->dist($x, $this->X[$i]), $this->y[$i]];
        }
        usort($dists, fn($a, $b) => $a[0] <=> $b[0]);

        $k = min($this->k, $n);
        $vote = [];
        for ($i = 0; $i < $k; $i++) {
            $lbl = $dists[$i][1];
            $vote[$lbl] = ($vote[$lbl] ?? 0) + 1;
        }
        arsort($vote);
        $label = array_key_first($vote);
        $score = ($vote[$label] ?? 0) / max(1, $k);

        return ['label' => $label, 'score' => $score];
    }

    /** Prediksi banyak titik */
    public function predictBatch(array $Xtest): array
    {
        $out = [];
        foreach ($Xtest as $x) {
            $out[] = $this->predictOne($x);
        }
        return $out;
    }

 
   public static function labelByThreshold(int $val, int $low, int $mid): string
{
    // < low            => Tidak Disiplin ≤30 hari
    // (low .. mid]     => Kurang Disiplin 31-60 hari
    // > mid            => Disiplin > 60 hari 
    if ($val < $low) {
        return 'Tidak Disiplin';
    }
    if ($val <= $mid) {
        return 'Kurang Disiplin';
    }
    return 'Disiplin';
}
    /** Versi tetap untuk low=30, mid=60 (opsional) */
    public static function labelByFixedThreshold(int $val): string
    {
        return self::labelByThreshold($val, 30, 60);
    }

    /**
     * Ambil jumlah hadir per siswa untuk 1 semester (Senin–Jumat saja).
     * @param mysqli     $conn
     * @param string     $semester   'Ganjil' | 'Genap'
     * @param int        $tahunAjar  contoh: 2025 untuk 2025/2026
     * @param ?int       $idKelas    null = semua kelas, angka = filter
     * @return array<int, array{id_siswa:int, nama_siswa:string, id_kelas:int|null, nama_kelas:string|null, jml_hadir:int}>
     */
    public static function getHadirCountsForSemester(mysqli $conn, string $semester, int $tahunAjar, ?int $idKelas = null): array
    {
        if ($semester === 'Ganjil') {
            $start = "$tahunAjar-07-01";
            $end   = ($tahunAjar + 1) . "-01-01";
        } else {
            $start = ($tahunAjar + 1) . "-01-01";
            $end   = ($tahunAjar + 1) . "-07-01";
        }

        $sql = "
            SELECT 
                s.id_siswa, s.nama_siswa, s.id_kelas, k.nama_kelas,
                SUM(
                    CASE 
                      WHEN a.status = 'hadir'
                       AND DATE(a.waktu_absensi_tercatat) >= ?
                       AND DATE(a.waktu_absensi_tercatat) <  ?
                       AND WEEKDAY(DATE(a.waktu_absensi_tercatat)) BETWEEN 0 AND 4
                      THEN 1 ELSE 0 
                    END
                ) AS jml_hadir
            FROM siswa s
            LEFT JOIN kelas k ON k.id_kelas = s.id_kelas
            LEFT JOIN absensi_siswa a ON a.id_siswa = s.id_siswa
            WHERE 1=1
        ";

        $types  = "ss";
        $params = [$start, $end];

        if (!empty($idKelas)) {
            $sql    .= " AND s.id_kelas = ? ";
            $types  .= "i";
            $params[] = $idKelas;
        }

        $sql .= "
            GROUP BY s.id_siswa, s.nama_siswa, s.id_kelas, k.nama_kelas
            ORDER BY s.nama_siswa ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        $out = [];
        while ($r = $res->fetch_assoc()) {
            $r['jml_hadir'] = (int)$r['jml_hadir'];
            $out[] = $r;
        }
        $stmt->close();
        return $out;
    } 
    
}

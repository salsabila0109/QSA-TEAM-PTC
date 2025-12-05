<?php
declare(strict_types=1);



// === LOAD DATASET LATIH ===
require_once __DIR__ . '/model_data_knn.php';   // otomatis memuat $training_X dan $training_y

class KNNAbsensi
{
    private int $k = 5;   // default K = 5
    private array $X = [];
    private array $y = [];

    
    // Nama tabel sumber data notifikasi kehadiran
    // GANTI kalau nama tabelmu beda (misal: 'tb_notifikasi_kehadiran')
    private const TABEL_NOTIF       = 'notifikasi';

    // Nama kolom pada tabel notifikasi_kehadiran
    private const KOLOM_ID_SISWA    = 'id_siswa';
    private const KOLOM_NAMA_SISWA  = 'nama_siswa';
    private const KOLOM_STATUS      = 'status';   // kolom status (Hadir/Alpa/dll)
    private const KOLOM_TANGGAL     = 'tanggal';  // DATE
    // ---------------------------------------------------

    public function __construct(int $k = 5)
    {
        $this->k = max(1, $k);

        // jika dataset ada → langsung load
        if (isset($GLOBALS['training_X']) && isset($GLOBALS['training_y'])) {
            $this->setTrainingData($GLOBALS['training_X'], $GLOBALS['training_y']);
        }
    }

    public function setK(int $k): void
    {
        $this->k = max(1, $k);
    }

    public function setTrainingData(array $X, array $y): void
    {
        if (count($X) !== count($y)) {
            throw new InvalidArgumentException('Jumlah X dan y harus sama.');
        }
        $this->X = $X;
        $this->y = $y;
    }

    // =============== KNN CORE ===============

    /** Jarak Euclidean MULTI-FITUR */
    private function dist(array $a, array $b): float
    {
        $sum = 0;
        $len = min(count($a), count($b));
        for ($i = 0; $i < $len; $i++) {
            $d = ($a[$i] ?? 0) - ($b[$i] ?? 0);
            $sum += $d * $d;
        }
        return sqrt($sum);
    }

    /** Prediksi 1 data */
    public function predictOne(array $x): string
    {
        $n = count($this->X);
        if ($n === 0) {
            return "Tidak Disiplin";
        }

        // hitung jarak semua training data
        $dists = [];
        for ($i = 0; $i < $n; $i++) {
            $d = $this->dist($x, $this->X[$i]);
            $dists[] = [$d, $this->y[$i]];
        }

        // urutkan berdasarkan jarak terkecil
        usort($dists, fn($a, $b) => $a[0] <=> $b[0]);

        $k = min($this->k, $n);

        // voting mayoritas label
        $vote = [];
        for ($i = 0; $i < $k; $i++) {
            $label = $dists[$i][1];
            $vote[$label] = ($vote[$label] ?? 0) + 1;
        }

        arsort($vote);
        return (string) array_key_first($vote);
    }

    /** Prediksi banyak */
    public function predictBatch(array $Xtest): array
    {
        $out = [];
        foreach ($Xtest as $x) {
            $out[] = $this->predictOne($x);
        }
        return $out;
    }

    // =============== AMBIL DATA ABSENSI DARI NOTIFIKASI ===============

    /**
     * Ambil jumlah hadir per siswa dari tabel notifikasi_kehadiran
     * untuk 1 SEMESTER (pakai rentang tanggal).
     *
     * return:
     * [
     *   ['id_siswa' => 1, 'nama_siswa' => 'Ali', 'jml_hadir' => 40],
     *   ...
     * ]
     */
    public static function getHadirCountsForSemester(mysqli $conn, string $semester, int $tahunAjar): array
    {
        $data = [];

        // Tentukan rentang tanggal berdasarkan semester & tahun ajar
        // Contoh:
        //   Ganjil: 1 Juli – 31 Des tahunAjar
        //   Genap : 1 Jan – 30 Jun (tahunAjar + 1)
        if ($semester === 'Ganjil') {
            $start = sprintf('%d-07-01', $tahunAjar);
            $end   = sprintf('%d-12-31', $tahunAjar);
        } else {
            $tahunBerikut = $tahunAjar + 1;
            $start = sprintf('%d-01-01', $tahunBerikut);
            $end   = sprintf('%d-06-30', $tahunBerikut);
        }

        // Gunakan konstanta nama tabel/kolom
        $tbl      = self::TABEL_NOTIF;
        $colId    = self::KOLOM_ID_SISWA;
        $colNama  = self::KOLOM_NAMA_SISWA;
        $colStat  = self::KOLOM_STATUS;
        $colTgl   = self::KOLOM_TANGGAL;

        // Hanya hitung status 'Hadir'
        // (kalau di DB kamu nilainya 'hadir' huruf kecil → ubah di sini)
        $sql = "
            SELECT
                $colId   AS id_siswa,
                $colNama AS nama_siswa,
                COUNT(*) AS jml_hadir
            FROM $tbl
            WHERE $colStat = 'hadir'
              AND $colTgl BETWEEN ? AND ?
            GROUP BY $colId, $colNama
            ORDER BY $colNama ASC
        ";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('ss', $start, $end);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = [
                        'id_siswa'   => (int) $row['id_siswa'],
                        'nama_siswa' => $row['nama_siswa'],
                        'jml_hadir'  => (int) $row['jml_hadir'],
                    ];
                }
            }

            $stmt->close();
        }

        return $data;
    }
}

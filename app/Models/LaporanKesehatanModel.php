<?php

namespace App\Models;

use CodeIgniter\Model;

class LaporanKesehatanModel extends Model
{
    protected $table            = 'laporan_kesehatan';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'santri_id',
        'pelapor_id',
        'tanggal_mulai_sakit',
        'gejala',
        'status',
        'tanggal_sembuh',
        'catatan',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Status resmi pemantauan kesehatan santri
    public const STATUS_DILAPORKAN_SAKIT  = 'Dilaporkan Sakit';
    public const STATUS_DALAM_PEMERIKSAAN = 'Dalam Pemeriksaan';
    public const STATUS_DALAM_PERAWATAN   = 'Dalam Perawatan';
    public const STATUS_SEMBUH            = 'Sembuh';
    public const STATUS_DIRUJUK           = 'Dirujuk';

    // Alias kompatibilitas
    public const STATUS_BARU            = 'Dilaporkan Sakit';
    public const STATUS_ISOLASI         = 'Dalam Perawatan';
    public const STATUS_RUJUKAN_RS      = 'Dirujuk';

    public static function getDaftarStatus(): array
    {
        return [
            self::STATUS_DILAPORKAN_SAKIT,
            self::STATUS_DALAM_PEMERIKSAAN,
            self::STATUS_DALAM_PERAWATAN,
            self::STATUS_SEMBUH,
            self::STATUS_DIRUJUK,
        ];
    }

    // Validation
    protected $validationRules = [
        'santri_id'           => 'required|is_natural_no_zero',
        'pelapor_id'          => 'permit_empty|is_natural_no_zero',
        'tanggal_mulai_sakit' => 'required',
        'gejala'              => 'required|min_length[3]',
        'status'              => 'required|in_list[Dilaporkan Sakit,Dalam Pemeriksaan,Dalam Perawatan,Sembuh,Dirujuk,Baru,Rujukan RS]',
        'tanggal_sembuh'      => 'permit_empty',
        'catatan'             => 'permit_empty',
    ];

    protected $validationMessages = [
        'santri_id' => [
            'required'           => 'Santri wajib dipilih.',
            'is_natural_no_zero' => 'Data santri tidak valid.',
        ],
        'tanggal_mulai_sakit' => [
            'required'   => 'Tanggal mulai sakit wajib diisi.',
            'valid_date' => 'Format tanggal mulai sakit tidak valid (YYYY-MM-DD).',
        ],
        'gejala' => [
            'required'   => 'Gejala awal wajib dicatat.',
            'min_length' => 'Deskripsi keluhan/gejala minimal 3 karakter.',
        ],
        'status' => [
            'required' => 'Status kesehatan santri wajib ditentukan.',
            'in_list'  => 'Status harus: Baru, Dalam Perawatan, Isolasi, Rujukan RS, atau Sembuh.',
        ],
    ];

    /**
     * Ambil data laporan kesehatan lengkap dengan nama santri, kamar, dan pelapor
     */
    public function getLaporanWithDetail(?int $id = null)
    {
        $builder = $this->select('laporan_kesehatan.*, 
                santri.nis, santri.nama as nama_santri, santri.jenis_kelamin, santri.kelas, santri.kontak_wali,
                kamar.nama_kamar,
                pelapor.nama as nama_pelapor')
            ->join('santri', 'santri.id = laporan_kesehatan.santri_id')
            ->join('kamar', 'kamar.id = santri.kamar_id', 'left')
            ->join('users as pelapor', 'pelapor.id = laporan_kesehatan.pelapor_id', 'left');

        if ($id !== null) {
            return $builder->where('laporan_kesehatan.id', $id)->first();
        }

        return $builder->orderBy('laporan_kesehatan.created_at', 'DESC')->findAll();
    }

    /**
     * Ambil santri yang saat ini sedang aktif sakit/dirawat (belum sembuh)
     */
    public function getKasusAktif()
    {
        return $this->select('laporan_kesehatan.*, 
                santri.nis, santri.nama as nama_santri, santri.jenis_kelamin, santri.kelas,
                kamar.nama_kamar,
                pelapor.nama as nama_pelapor')
            ->join('santri', 'santri.id = laporan_kesehatan.santri_id')
            ->join('kamar', 'kamar.id = santri.kamar_id', 'left')
            ->join('users as pelapor', 'pelapor.id = laporan_kesehatan.pelapor_id', 'left')
            ->where('laporan_kesehatan.status !=', self::STATUS_SEMBUH)
            ->orderBy('laporan_kesehatan.tanggal_mulai_sakit', 'DESC')
            ->findAll();
    }

    /**
     * Update status kesehatan santri secara aman:
     * - Status historis tetap tersimpan di riwayat_status (tidak hilang saat sembuh)
     * - Catat ke audit_log
     */
    public function ubahStatusKesehatan(int $laporanId, string $statusBaru, ?int $userId = null, ?string $catatan = null, ?string $tanggalSembuh = null): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $laporan = $this->find($laporanId);
        if (!$laporan) {
            $db->transRollback();
            return false;
        }

        $dataUpdate = ['status' => $statusBaru];
        if ($statusBaru === self::STATUS_SEMBUH) {
            $dataUpdate['tanggal_sembuh'] = $tanggalSembuh ?: ($laporan['tanggal_sembuh'] ?: date('Y-m-d'));
        }

        $this->update($laporanId, $dataUpdate);

        // 1. Simpan riwayat perubahan status (history tidak hilang)
        $riwayatModel = new RiwayatStatusModel();
        $riwayatModel->insert([
            'laporan_id'  => $laporanId,
            'status'      => $statusBaru,
            'tanggal'     => date('Y-m-d H:i:s'),
            'catatan'     => $catatan ?? ('Status diubah dari ' . $laporan['status'] . ' menjadi ' . $statusBaru),
            'pengguna_id' => $userId,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        // 2. Simpan audit log
        $auditAksi = 'UPDATE_STATUS_KESEHATAN';
        if ($statusBaru === self::STATUS_SEMBUH) {
            $auditAksi = 'KONFIRMASI_SEMBUH';
        } elseif ($statusBaru === self::STATUS_DIRUJUK || $statusBaru === 'Rujukan RS') {
            $auditAksi = 'RUJUKAN_FASKES';
        }

        $auditModel = new AuditLogModel();
        $auditModel->insert([
            'pengguna_id'      => $userId,
            'aksi'             => $auditAksi,
            'waktu'            => date('Y-m-d H:i:s'),
            'detail_perubahan' => "Mengubah status laporan #{$laporanId} dari '{$laporan['status']}' ke '{$statusBaru}'. Catatan: " . ($catatan ?? '-'),
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        $db->transComplete();
        return $db->transStatus();
    }

    /**
     * Ambil kronologi rekam medis lengkap untuk seorang santri
     */
    public function getRiwayatMedisBySantri(int $santriId): array
    {
        $laporanList = $this->select('laporan_kesehatan.*, pelapor.nama as nama_pelapor')
            ->join('users as pelapor', 'pelapor.id = laporan_kesehatan.pelapor_id', 'left')
            ->where('laporan_kesehatan.santri_id', $santriId)
            ->orderBy('laporan_kesehatan.tanggal_mulai_sakit', 'DESC')
            ->findAll();

        if (empty($laporanList)) {
            return [];
        }

        $pemeriksaanModel = new PemeriksaanModel();
        $pemberianObatModel = new PemberianObatModel();

        foreach ($laporanList as &$laporan) {
            $laporan['pelapor_nama'] = $laporan['nama_pelapor'] ?? 'Pengawas';

            $pemeriksaanList = $pemeriksaanModel->select('pemeriksaan.*, pemeriksa.nama as nama_pemeriksa, pemeriksa.nama as dokter_nama, pemeriksa.role as role_pemeriksa')
                ->join('users as pemeriksa', 'pemeriksa.id = pemeriksaan.pemeriksa_id', 'left')
                ->where('pemeriksaan.laporan_id', $laporan['id'])
                ->orderBy('pemeriksaan.tanggal_periksa', 'DESC')
                ->findAll();

            foreach ($pemeriksaanList as &$periksa) {
                $obatList = $pemberianObatModel->getObatByPemeriksaan($periksa['id']);
                $periksa['obat_diberikan'] = $obatList;
                $periksa['obat']           = $obatList;
                $periksa['dokter_nama']    = $periksa['nama_pemeriksa'] ?? 'Tenaga Medis';
            }

            $laporan['daftar_pemeriksaan'] = $pemeriksaanList;
            $laporan['pemeriksaan']        = $pemeriksaanList;
        }

        return $laporanList;
    }

    /**
     * Hitung ringkasan statistik kesehatan santri secara efisien dari database
     */
    public function getDashboardStats(?array $allowedKamarIds = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $db = \Config\Database::connect();

        $isValidDate = static fn(?string $d) => ($d !== null && (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($d)));
        $cleanStart  = $isValidDate($startDate) ? trim($startDate) : null;
        $cleanEnd    = $isValidDate($endDate) ? trim($endDate) : null;

        // 1. Sedang Sakit (Dilaporkan Sakit / Baru)
        $builderSakit = $db->table('laporan_kesehatan')
            ->join('santri', 'santri.id = laporan_kesehatan.santri_id')
            ->whereIn('laporan_kesehatan.status', [self::STATUS_DILAPORKAN_SAKIT, 'Baru']);
        if ($allowedKamarIds !== null) {
            if (empty($allowedKamarIds)) return ['sedang_sakit' => 0, 'dalam_pemeriksaan' => 0, 'dalam_perawatan' => 0, 'rawat_periksa' => 0, 'sembuh' => 0, 'dirujuk' => 0];
            $builderSakit->whereIn('santri.kamar_id', $allowedKamarIds);
        }
        if ($cleanStart) $builderSakit->where('laporan_kesehatan.tanggal_mulai_sakit >=', $cleanStart . ' 00:00:00');
        if ($cleanEnd)   $builderSakit->where('laporan_kesehatan.tanggal_mulai_sakit <=', $cleanEnd . ' 23:59:59');
        $sedangSakit = $builderSakit->countAllResults();

        // 2a. Dalam Pemeriksaan
        $builderPeriksa = $db->table('laporan_kesehatan')
            ->join('santri', 'santri.id = laporan_kesehatan.santri_id')
            ->whereIn('laporan_kesehatan.status', [self::STATUS_DALAM_PEMERIKSAAN]);
        if ($allowedKamarIds !== null) {
            $builderPeriksa->whereIn('santri.kamar_id', $allowedKamarIds);
        }
        if ($cleanStart) $builderPeriksa->where('laporan_kesehatan.tanggal_mulai_sakit >=', $cleanStart . ' 00:00:00');
        if ($cleanEnd)   $builderPeriksa->where('laporan_kesehatan.tanggal_mulai_sakit <=', $cleanEnd . ' 23:59:59');
        $dalamPemeriksaan = $builderPeriksa->countAllResults();

        // 2b. Dalam Perawatan
        $builderRawat = $db->table('laporan_kesehatan')
            ->join('santri', 'santri.id = laporan_kesehatan.santri_id')
            ->whereIn('laporan_kesehatan.status', [self::STATUS_DALAM_PERAWATAN, 'Isolasi']);
        if ($allowedKamarIds !== null) {
            $builderRawat->whereIn('santri.kamar_id', $allowedKamarIds);
        }
        if ($cleanStart) $builderRawat->where('laporan_kesehatan.tanggal_mulai_sakit >=', $cleanStart . ' 00:00:00');
        if ($cleanEnd)   $builderRawat->where('laporan_kesehatan.tanggal_mulai_sakit <=', $cleanEnd . ' 23:59:59');
        $dalamPerawatan = $builderRawat->countAllResults();

        $rawatPeriksa = $dalamPemeriksaan + $dalamPerawatan;

        // 3. Sembuh (Pada periode tertentu atau sesuai filter tanggal)
        $builderSembuh = $db->table('laporan_kesehatan')
            ->join('santri', 'santri.id = laporan_kesehatan.santri_id')
            ->where('laporan_kesehatan.status', self::STATUS_SEMBUH);
        if ($allowedKamarIds !== null) {
            $builderSembuh->whereIn('santri.kamar_id', $allowedKamarIds);
        }
        if ($cleanStart) {
            $builderSembuh->where('laporan_kesehatan.tanggal_mulai_sakit >=', $cleanStart . ' 00:00:00');
        }
        if ($cleanEnd) {
            $builderSembuh->where('laporan_kesehatan.tanggal_mulai_sakit <=', $cleanEnd . ' 23:59:59');
        }
        $sembuh = $builderSembuh->countAllResults();

        // 4. Dirujuk (Dirujuk / Rujukan RS)
        $builderDirujuk = $db->table('laporan_kesehatan')
            ->join('santri', 'santri.id = laporan_kesehatan.santri_id')
            ->whereIn('laporan_kesehatan.status', [self::STATUS_DIRUJUK, 'Rujukan RS']);
        if ($allowedKamarIds !== null) {
            $builderDirujuk->whereIn('santri.kamar_id', $allowedKamarIds);
        }
        if ($cleanStart) $builderDirujuk->where('laporan_kesehatan.tanggal_mulai_sakit >=', $cleanStart . ' 00:00:00');
        if ($cleanEnd)   $builderDirujuk->where('laporan_kesehatan.tanggal_mulai_sakit <=', $cleanEnd . ' 23:59:59');
        $dirujuk = $builderDirujuk->countAllResults();

        return [
            'sedang_sakit'       => $sedangSakit,
            'dalam_pemeriksaan'  => $dalamPemeriksaan,
            'dalam_perawatan'    => $dalamPerawatan,
            'rawat_periksa'      => $rawatPeriksa, // kompatibilitas backward
            'sembuh'             => $sembuh,
            'dirujuk'            => $dirujuk,
        ];
    }

    /**
     * Ringkasan jumlah kasus berdasarkan kamar dengan satu kueri SQL efisien
     */
    public function getRingkasanPerKamar(?array $allowedKamarIds = null): array
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT 
                kamar.id as kamar_id,
                kamar.nama_kamar,
                COALESCE(users.nama, 'Belum Ditugaskan') as nama_pengawas,
                COUNT(DISTINCT santri.id) as total_santri,
                COUNT(DISTINCT CASE WHEN laporan_kesehatan.status IN ('Dilaporkan Sakit', 'Baru') THEN laporan_kesehatan.id END) as sedang_sakit,
                COUNT(DISTINCT CASE WHEN laporan_kesehatan.status IN ('Dalam Pemeriksaan', 'Dalam Perawatan', 'Isolasi') THEN laporan_kesehatan.id END) as rawat_periksa,
                COUNT(DISTINCT CASE WHEN laporan_kesehatan.status = 'Sembuh' THEN laporan_kesehatan.id END) as sembuh,
                COUNT(DISTINCT CASE WHEN laporan_kesehatan.status IN ('Dirujuk', 'Rujukan RS') THEN laporan_kesehatan.id END) as dirujuk,
                COUNT(DISTINCT laporan_kesehatan.id) as total_kasus
            FROM kamar
            LEFT JOIN users ON users.id = kamar.pengawas_id
            LEFT JOIN santri ON santri.kamar_id = kamar.id
            LEFT JOIN laporan_kesehatan ON laporan_kesehatan.santri_id = santri.id
        ";

        $params = [];
        if ($allowedKamarIds !== null) {
            if (empty($allowedKamarIds)) {
                return [];
            }
            $cleanIds = array_values(array_map('intval', $allowedKamarIds));
            $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
            $sql .= " WHERE kamar.id IN ({$placeholders}) ";
            $params = $cleanIds;
        }

        $sql .= " GROUP BY kamar.id, kamar.nama_kamar, users.nama ORDER BY kamar.nama_kamar ASC";

        return $db->query($sql, $params)->getResultArray();
    }

    /**
     * Ambil data laporan sakit terbaru untuk dashboard dengan filter dinamis
     */
    public function getLaporanTerbaruDashboard(array $filters = [], ?array $allowedKamarIds = null, int $limit = 10): array
    {
        $builder = $this->select('laporan_kesehatan.*, 
                santri.nis, santri.nama as nama_santri, santri.jenis_kelamin, santri.kelas,
                kamar.id as kamar_id, kamar.nama_kamar,
                pelapor.nama as nama_pelapor')
            ->join('santri', 'santri.id = laporan_kesehatan.santri_id')
            ->join('kamar', 'kamar.id = santri.kamar_id', 'left')
            ->join('users as pelapor', 'pelapor.id = laporan_kesehatan.pelapor_id', 'left');

        // RBAC Room Boundary
        if ($allowedKamarIds !== null) {
            if (empty($allowedKamarIds)) return [];
            $builder->whereIn('santri.kamar_id', $allowedKamarIds);
        }

        // Filter Kamar
        if (!empty($filters['kamar_id'])) {
            $builder->where('santri.kamar_id', (int) $filters['kamar_id']);
        }

        // Filter Status
        if (!empty($filters['status'])) {
            $statusVal = trim($filters['status']);
            if ($statusVal === 'Dilaporkan Sakit') {
                $builder->whereIn('laporan_kesehatan.status', ['Dilaporkan Sakit', 'Baru']);
            } elseif ($statusVal === 'Dalam Perawatan' || $statusVal === 'Dalam Pemeriksaan') {
                $builder->where('laporan_kesehatan.status', $statusVal);
            } elseif ($statusVal === 'Dirujuk') {
                $builder->whereIn('laporan_kesehatan.status', ['Dirujuk', 'Rujukan RS']);
            } else {
                $builder->where('laporan_kesehatan.status', $statusVal);
            }
        }

        // Filter Tanggal
        if (!empty($filters['tanggal_mulai']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($filters['tanggal_mulai']))) {
            $builder->where('laporan_kesehatan.tanggal_mulai_sakit >=', trim($filters['tanggal_mulai']) . ' 00:00:00');
        }
        if (!empty($filters['tanggal_akhir']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($filters['tanggal_akhir']))) {
            $builder->where('laporan_kesehatan.tanggal_mulai_sakit <=', trim($filters['tanggal_akhir']) . ' 23:59:59');
        }

        // Pencarian Nama atau NIS
        if (!empty($filters['keyword'])) {
            $keyword = trim($filters['keyword']);
            $builder->groupStart()
                ->like('santri.nama', $keyword)
                ->orLike('santri.nis', $keyword)
                ->groupEnd();
        }

        return $builder->orderBy('laporan_kesehatan.tanggal_mulai_sakit', 'DESC')
            ->orderBy('laporan_kesehatan.id', 'DESC')
            ->limit($limit)
            ->find();
    }
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class PemeriksaanModel extends Model
{
    protected $table            = 'pemeriksaan';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'laporan_id',
        'pemeriksa_id',
        'tanggal_periksa',
        'diagnosis_atau_dugaan',
        'tindakan',
        'catatan',
        'diverifikasi_oleh_medis',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    // Catatan: diverifikasi_oleh_medis wajib bernilai 1 untuk menegakkan aturan
    // "Jangan menyimpan diagnosis atau resep sebagai hasil keputusan AI yang belum diverifikasi petugas kesehatan"
    protected $validationRules = [
        'laporan_id'              => 'required|is_natural_no_zero',
        'pemeriksa_id'            => 'required|is_natural_no_zero',
        'tanggal_periksa'         => 'required',
        'diagnosis_atau_dugaan'   => 'required|min_length[3]',
        'diverifikasi_oleh_medis' => 'required|in_list[1]',
        'tindakan'                => 'permit_empty',
        'catatan'                 => 'permit_empty',
    ];

    protected $validationMessages = [
        'laporan_id' => [
            'required' => 'ID laporan kesehatan wajib diisi.',
        ],
        'pemeriksa_id' => [
            'required' => 'Pemeriksa wajib diisi oleh tenaga kesehatan / dokter resmi.',
        ],
        'diagnosis_atau_dugaan' => [
            'required'   => 'Diagnosis atau dugaan klinis wajib diisi oleh pemeriksa.',
            'min_length' => 'Diagnosis minimal 3 karakter.',
        ],
        'diverifikasi_oleh_medis' => [
            'required' => 'Pemeriksaan wajib diverifikasi oleh petugas medis resmi.',
            'in_list'  => 'Diagnosis harus hasil verifikasi resmi petugas medis, bukan keputusan AI tanpa verifikasi.',
        ],
    ];

    /**
     * Ambil riwayat pemeriksaan berdasarkan laporan kesehatan
     */
    public function getPemeriksaanByLaporan(int $laporanId)
    {
        return $this->select('pemeriksaan.*, users.nama as nama_pemeriksa, users.role as role_pemeriksa')
            ->join('users', 'users.id = pemeriksaan.pemeriksa_id', 'left')
            ->where('pemeriksaan.laporan_id', $laporanId)
            ->orderBy('pemeriksaan.tanggal_periksa', 'DESC')
            ->findAll();
    }
}

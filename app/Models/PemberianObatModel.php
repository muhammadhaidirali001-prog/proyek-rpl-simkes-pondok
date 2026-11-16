<?php

namespace App\Models;

use CodeIgniter\Model;

class PemberianObatModel extends Model
{
    protected $table            = 'pemberian_obat';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'pemeriksaan_id',
        'obat_id',
        'dosis',
        'aturan_pakai',
        'durasi',
        'instruksi_petugas',
        'diverifikasi_oleh_medis',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    // Memastikan resep harus diverifikasi tenaga medis (tidak boleh hasil AI otomatis tanpa verifikasi)
    protected $validationRules = [
        'pemeriksaan_id'          => 'required|is_natural_no_zero',
        'obat_id'                 => 'required|is_natural_no_zero',
        'dosis'                   => 'required|max_length[50]',
        'aturan_pakai'            => 'required|max_length[100]',
        'durasi'                  => 'permit_empty|max_length[50]',
        'diverifikasi_oleh_medis' => 'required|in_list[1]',
        'instruksi_petugas'       => 'permit_empty',
    ];

    protected $validationMessages = [
        'pemeriksaan_id' => [
            'required' => 'ID pemeriksaan wajib diisi.',
        ],
        'obat_id' => [
            'required' => 'Obat wajib dipilih dari inventaris resmi.',
        ],
        'dosis' => [
            'required' => 'Dosis obat wajib ditentukan oleh petugas medis.',
        ],
        'aturan_pakai' => [
            'required' => 'Aturan pakai obat wajib ditentukan (misal: 3x1 setelah makan).',
        ],
        'diverifikasi_oleh_medis' => [
            'in_list' => 'Resep obat wajib diverifikasi oleh tenaga medis manusia.',
        ],
    ];

    /**
     * Ambil rincian obat yang diberikan pada suatu pemeriksaan
     */
    public function getObatByPemeriksaan(int $pemeriksaanId)
    {
        return $this->select('pemberian_obat.*, obat.nama_obat, obat.satuan')
            ->join('obat', 'obat.id = pemberian_obat.obat_id')
            ->where('pemberian_obat.pemeriksaan_id', $pemeriksaanId)
            ->findAll();
    }
}

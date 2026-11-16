<?php

namespace App\Models;

use CodeIgniter\Model;

class RiwayatStatusModel extends Model
{
    protected $table            = 'riwayat_status';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'laporan_id',
        'status',
        'tanggal',
        'catatan',
        'pengguna_id',
        'created_at',
    ];

    // Dates
    protected $useTimestamps = false; // di-handle secara eksplisit melalui kolom tanggal/created_at

    // Validation
    protected $validationRules = [
        'laporan_id'  => 'required|is_natural_no_zero',
        'status'      => 'required|in_list[Baru,Dalam Perawatan,Isolasi,Rujukan RS,Sembuh]',
        'tanggal'     => 'required',
        'pengguna_id' => 'permit_empty|is_natural_no_zero',
        'catatan'     => 'permit_empty',
    ];

    protected $validationMessages = [
        'laporan_id' => [
            'required' => 'ID laporan kesehatan wajib diisi.',
        ],
        'status' => [
            'required' => 'Status kesehatan wajib ditentukan.',
            'in_list'  => 'Status harus konsisten: Baru, Dalam Perawatan, Isolasi, Rujukan RS, atau Sembuh.',
        ],
    ];

    /**
     * Ambil seluruh riwayat perubahan status dari suatu kasus laporan santri
     * Data ini bersifat permanen (tidak terhapus bahkan ketika santri telah dinyatakan sembuh)
     */
    public function getRiwayatByLaporan(int $laporanId)
    {
        return $this->select('riwayat_status.*, users.nama as nama_pengguna, users.role as role_pengguna')
            ->join('users', 'users.id = riwayat_status.pengguna_id', 'left')
            ->where('riwayat_status.laporan_id', $laporanId)
            ->orderBy('riwayat_status.tanggal', 'ASC')
            ->findAll();
    }
}

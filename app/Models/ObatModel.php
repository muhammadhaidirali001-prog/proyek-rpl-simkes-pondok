<?php

namespace App\Models;

use CodeIgniter\Model;

class ObatModel extends Model
{
    protected $table            = 'obat';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nama_obat',
        'informasi_penggunaan',
        'stok',
        'satuan',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'nama_obat'            => 'required|min_length[2]|max_length[100]|is_unique[obat.nama_obat,id,{id}]',
        'stok'                 => 'required|integer|greater_than_equal_to[0]',
        'satuan'               => 'required|max_length[30]',
        'informasi_penggunaan' => 'permit_empty',
    ];

    protected $validationMessages = [
        'nama_obat' => [
            'required'   => 'Nama obat wajib diisi.',
            'min_length' => 'Nama obat minimal 2 karakter.',
            'max_length' => 'Nama obat maksimal 100 karakter.',
            'is_unique'  => 'Nama obat ini sudah terdaftar di inventaris.',
        ],
        'stok' => [
            'required'               => 'Stok obat wajib diisi.',
            'integer'                => 'Stok obat harus berupa bilangan bulat.',
            'greater_than_equal_to' => 'Stok obat tidak boleh negatif.',
        ],
        'satuan' => [
            'required' => 'Satuan obat wajib diisi (misal: Tablet, Sirup, Tube, Strip).',
        ],
    ];

    /**
     * Ambil daftar obat dengan stok kritis (di bawah ambang batas)
     */
    public function getStokKritis(int $ambangBatas = 10)
    {
        return $this->where('stok <=', $ambangBatas)
            ->orderBy('stok', 'ASC')
            ->findAll();
    }
}

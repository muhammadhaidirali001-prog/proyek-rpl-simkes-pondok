<?php

namespace App\Models;

use CodeIgniter\Model;

class KamarModel extends Model
{
    protected $table            = 'kamar';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nama_kamar',
        'pengawas_id',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'nama_kamar'  => 'required|min_length[2]|max_length[50]|is_unique[kamar.nama_kamar,id,{id}]',
        'pengawas_id' => 'permit_empty|is_natural_no_zero',
    ];

    protected $validationMessages = [
        'nama_kamar' => [
            'required'   => 'Nama kamar/asrama wajib diisi.',
            'min_length' => 'Nama kamar minimal 2 karakter.',
            'max_length' => 'Nama kamar maksimal 50 karakter.',
            'is_unique'  => 'Nama kamar sudah ada.',
        ],
        'pengawas_id' => [
            'is_natural_no_zero' => 'Pengawas kamar tidak valid.',
        ],
    ];

    /**
     * Ambil data kamar beserta data pengawasnya
     */
    public function getKamarWithPengawas(?int $id = null)
    {
        $builder = $this->select('kamar.*, users.nama as nama_pengawas, users.email as email_pengawas')
            ->join('users', 'users.id = kamar.pengawas_id', 'left');

        if ($id !== null) {
            return $builder->where('kamar.id', $id)->first();
        }

        return $builder->orderBy('kamar.nama_kamar', 'ASC')->findAll();
    }
}

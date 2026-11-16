<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table            = 'audit_log';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'pengguna_id',
        'aksi',
        'waktu',
        'detail_perubahan',
        'created_at',
    ];

    protected $useTimestamps = false;

    protected $validationRules = [
        'aksi'  => 'required|max_length[100]',
        'waktu' => 'required',
    ];

    /**
     * Helper untuk mencatat aktivitas penting pengguna ke log audit
     */
    public function catatAktivitas(?int $penggunaId, string $aksi, ?string $detailPerubahan = null): bool
    {
        return (bool) $this->insert([
            'pengguna_id'      => $penggunaId,
            'aksi'             => $aksi,
            'waktu'            => date('Y-m-d H:i:s'),
            'detail_perubahan' => $detailPerubahan,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Ambil log aktivitas terbaru beserta nama pengguna
     */
    public function getRecentLogs(int $limit = 10)
    {
        return $this->select('audit_log.*, users.nama as nama_pengguna, users.role as role_pengguna')
            ->join('users', 'users.id = audit_log.pengguna_id', 'left')
            ->orderBy('audit_log.waktu', 'DESC')
            ->findAll($limit);
    }
}

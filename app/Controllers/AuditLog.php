<?php

namespace App\Controllers;

use App\Models\AuditLogModel;
use App\Models\UserModel;

class AuditLog extends BaseController
{
    protected AuditLogModel $auditModel;

    public function __construct()
    {
        $this->auditModel = new AuditLogModel();
    }

    /**
     * Verifikasi khusus Administrator
     */
    protected function ensureAdmin(): void
    {
        $role = UserModel::normalizeRole(session()->get('role') ?? '');
        if ($role !== UserModel::ROLE_ADMIN) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Akses ditolak: Hanya Administrator yang berwenang melihat Audit Log.');
        }
    }

    /**
     * Halaman daftar riwayat audit log aktivitas sistem
     */
    public function index()
    {
        $this->ensureAdmin();

        $keyword      = trim((string) $this->request->getGet('keyword'));
        $aksi         = trim((string) $this->request->getGet('aksi'));
        $tanggalMulai = trim((string) $this->request->getGet('tanggal_mulai'));
        $tanggalAkhir = trim((string) $this->request->getGet('tanggal_akhir'));

        $builder = $this->auditModel->select('audit_log.*, users.nama as nama_pengguna, users.role as role_pengguna, users.email as email_pengguna')
            ->join('users', 'users.id = audit_log.pengguna_id', 'left');

        if (!empty($keyword)) {
            $builder->groupStart()
                ->like('audit_log.aksi', $keyword)
                ->orLike('audit_log.detail_perubahan', $keyword)
                ->orLike('users.nama', $keyword)
                ->orLike('users.email', $keyword)
                ->groupEnd();
        }

        if (!empty($aksi)) {
            $builder->where('audit_log.aksi', $aksi);
        }

        if (!empty($tanggalMulai)) {
            $builder->where('audit_log.waktu >=', $tanggalMulai . ' 00:00:00');
        }

        if (!empty($tanggalAkhir)) {
            $builder->where('audit_log.waktu <=', $tanggalAkhir . ' 23:59:59');
        }

        $logs = $builder->orderBy('audit_log.waktu', 'DESC')
            ->orderBy('audit_log.id', 'DESC')
            ->paginate(15, 'audit');

        $pager = $this->auditModel->pager;

        // Ambil daftar aksi unik untuk filter dropdown
        $db = \Config\Database::connect();
        $distinctAksi = $db->table('audit_log')->select('DISTINCT(aksi) as aksi')->orderBy('aksi', 'ASC')->get()->getResultArray();

        return view('audit_log/index', [
            'title'        => 'Audit Log Sistem - SIMKES Santri',
            'logs'         => $logs,
            'pager'        => $pager,
            'distinctAksi' => array_column($distinctAksi, 'aksi'),
            'filters'      => [
                'keyword'       => $keyword,
                'aksi'          => $aksi,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_akhir' => $tanggalAkhir,
            ],
        ]);
    }
}

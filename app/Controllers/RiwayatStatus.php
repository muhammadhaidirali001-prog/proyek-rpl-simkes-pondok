<?php

namespace App\Controllers;

use App\Models\RiwayatStatusModel;
use App\Models\UserModel;

class RiwayatStatus extends BaseController
{
    protected RiwayatStatusModel $riwayatModel;

    public function __construct()
    {
        $this->riwayatModel = new RiwayatStatusModel();
    }

    protected function ensureMedisOrAdmin(): void
    {
        $role = UserModel::normalizeRole(session()->get('role') ?? '');
        if (!in_array($role, [UserModel::ROLE_PENGAWAS_KESEHATAN, UserModel::ROLE_ADMIN])) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Akses ditolak: Anda tidak memiliki wewenang tenaga medis.');
        }
    }

    public function index()
    {
        $this->ensureMedisOrAdmin();

        $daftarRiwayat = $this->riwayatModel
            ->select('riwayat_status.*, users.nama as nama_petugas, santri.nama as nama_santri,
                      santri.nis, kamar.nama_kamar, laporan_kesehatan.id as laporan_id')
            ->join('users', 'users.id = riwayat_status.pengguna_id', 'left')
            ->join('laporan_kesehatan', 'laporan_kesehatan.id = riwayat_status.laporan_id')
            ->join('santri', 'santri.id = laporan_kesehatan.santri_id')
            ->join('kamar', 'kamar.id = santri.kamar_id', 'left')
            ->orderBy('riwayat_status.tanggal', 'DESC')
            ->findAll();

        return view('riwayat_status/index', [
            'title'         => 'Linimasa Riwayat Status Kesehatan Santri - SIMKES Santri',
            'daftarRiwayat' => $daftarRiwayat,
        ]);
    }
}

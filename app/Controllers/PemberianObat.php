<?php

namespace App\Controllers;

use App\Models\PemberianObatModel;
use App\Models\UserModel;

class PemberianObat extends BaseController
{
    protected PemberianObatModel $pemberianObatModel;

    public function __construct()
    {
        $this->pemberianObatModel = new PemberianObatModel();
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

        $daftarResep = $this->pemberianObatModel
            ->select('pemberian_obat.*, obat.nama_obat, obat.satuan, pemeriksaan.tanggal_periksa,
                      dokter.nama as nama_dokter, santri.nama as nama_santri, santri.nis, kamar.nama_kamar,
                      laporan_kesehatan.id as laporan_id')
            ->join('obat', 'obat.id = pemberian_obat.obat_id')
            ->join('pemeriksaan', 'pemeriksaan.id = pemberian_obat.pemeriksaan_id')
            ->join('users as dokter', 'dokter.id = pemeriksaan.pemeriksa_id', 'left')
            ->join('laporan_kesehatan', 'laporan_kesehatan.id = pemeriksaan.laporan_id')
            ->join('santri', 'santri.id = laporan_kesehatan.santri_id')
            ->join('kamar', 'kamar.id = santri.kamar_id', 'left')
            ->orderBy('pemeriksaan.tanggal_periksa', 'DESC')
            ->findAll();

        return view('pemberian_obat/index', [
            'title'       => 'Resep & Terapi Obat Santri - SIMKES Santri',
            'daftarResep' => $daftarResep,
        ]);
    }
}

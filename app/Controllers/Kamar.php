<?php

namespace App\Controllers;

use App\Models\KamarModel;
use App\Models\SantriModel;
use App\Models\UserModel;

class Kamar extends BaseController
{
    protected KamarModel $kamarModel;
    protected SantriModel $santriModel;

    public function __construct()
    {
        $this->kamarModel  = new KamarModel();
        $this->santriModel = new SantriModel();
    }

    public function index()
    {
        $userRole = UserModel::normalizeRole(session()->get('role') ?? '');
        $userId   = (int) session()->get('user_id');
        $isPengawasKamar = ($userRole === UserModel::ROLE_PENGAWAS_KAMAR);

        $kamarQuery = $this->kamarModel->select('kamar.*, users.nama as nama_pengawas')
            ->join('users', 'users.id = kamar.pengawas_id', 'left');

        if ($isPengawasKamar) {
            $kamarQuery->where('kamar.pengawas_id', $userId);
        }

        $kamarList = $kamarQuery->orderBy('kamar.nama_kamar', 'ASC')->findAll();

        // Hitung jumlah santri per kamar
        foreach ($kamarList as &$k) {
            $k['jumlah_santri'] = $this->santriModel->where('kamar_id', $k['id'])->countAllResults();
        }

        return view('kamar/index', [
            'title'           => 'Kamar & Asrama Santri - SIMKES Santri',
            'kamarList'       => $kamarList,
            'isPengawasKamar' => $isPengawasKamar,
        ]);
    }
}

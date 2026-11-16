<?php

namespace App\Controllers;

use App\Models\ObatModel;
use App\Models\UserModel;
use App\Models\AuditLogModel;

class Obat extends BaseController
{
    protected ObatModel $obatModel;
    protected AuditLogModel $auditModel;

    public function __construct()
    {
        $this->obatModel  = new ObatModel();
        $this->auditModel = new AuditLogModel();
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

        $keyword = trim((string) $this->request->getGet('keyword'));
        $query   = $this->obatModel;

        if (!empty($keyword)) {
            $query = $query->like('nama_obat', $keyword);
        }

        $daftarObat = $query->orderBy('nama_obat', 'ASC')->findAll();

        $totalItem   = count($daftarObat);
        $totalStok   = array_sum(array_column($daftarObat, 'stok'));
        $stokMenipis = count(array_filter($daftarObat, fn($o) => (int)$o['stok'] <= 10));

        return view('obat/index', [
            'title'       => 'Inventaris Obat & Farmasi Poskestren - SIMKES Santri',
            'daftarObat'  => $daftarObat,
            'totalItem'   => $totalItem,
            'totalStok'   => $totalStok,
            'stokMenipis' => $stokMenipis,
            'keyword'     => $keyword,
        ]);
    }

    public function create()
    {
        $this->ensureMedisOrAdmin();

        $rules = [
            'nama_obat'            => 'required|min_length[2]|max_length[100]|is_unique[obat.nama_obat]',
            'stok'                 => 'required|integer|greater_than_equal_to[0]',
            'satuan'               => 'required|max_length[30]',
            'informasi_penggunaan' => 'permit_empty|max_length[255]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'nama_obat'            => trim((string) $this->request->getPost('nama_obat')),
            'stok'                 => (int) $this->request->getPost('stok'),
            'satuan'               => trim((string) $this->request->getPost('satuan')),
            'informasi_penggunaan' => trim((string) $this->request->getPost('informasi_penggunaan')),
        ];

        $this->obatModel->insert($data);

        $userId = (int) session()->get('user_id');
        $this->auditModel->catatAktivitas(
            $userId,
            'TAMBAH_OBAT',
            "Menambahkan item inventaris obat: {$data['nama_obat']} (Stok: {$data['stok']} {$data['satuan']})"
        );

        return redirect()->to(base_url('/obat'))
            ->with('success', "Item obat '{$data['nama_obat']}' berhasil ditambahkan ke inventaris.");
    }
}

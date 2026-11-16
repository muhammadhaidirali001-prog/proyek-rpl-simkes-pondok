<?php

namespace App\Controllers;

use App\Models\SantriModel;
use App\Models\KamarModel;
use App\Models\LaporanKesehatanModel;
use App\Models\AuditLogModel;
use App\Models\UserModel;

class Santri extends BaseController
{
    protected SantriModel $santriModel;
    protected KamarModel $kamarModel;
    protected LaporanKesehatanModel $laporanModel;
    protected AuditLogModel $auditModel;

    public function __construct()
    {
        $this->santriModel  = new SantriModel();
        $this->kamarModel   = new KamarModel();
        $this->laporanModel = new LaporanKesehatanModel();
        $this->auditModel   = new AuditLogModel();
    }

    /**
     * Dapatkan daftar ID kamar yang diizinkan untuk pengguna yang sedang login (RBAC)
     * Mengembalikan array ID untuk pengawas_kamar, atau null jika memiliki akses lintas kamar (Kesehatan/Admin)
     */
    protected function getAllowedKamarIds(): ?array
    {
        $userRole = UserModel::normalizeRole(session()->get('role') ?? '');
        $userId   = (int) session()->get('user_id');

        if ($userRole === UserModel::ROLE_PENGAWAS_KAMAR) {
            $kamarBinaan = $this->kamarModel->where('pengawas_id', $userId)->findAll();
            return array_column($kamarBinaan, 'id');
        }

        // Pengawas Kesehatan dan Administrator memiliki akses lintas kamar
        return null;
    }

    /**
     * Halaman Daftar Santri dengan Pencarian, Multi-Filter, dan Pagination
     */
    public function index(): string
    {
        $allowedKamarIds = $this->getAllowedKamarIds();

        // Parameter filter dan pencarian dari query string
        $keyword = trim((string) $this->request->getGet('keyword'));
        $kamarId = $this->request->getGet('kamar_id');
        $kelas   = trim((string) $this->request->getGet('kelas'));

        $filters = [
            'keyword'  => $keyword,
            'kamar_id' => $kamarId,
            'kelas'    => $kelas,
        ];

        // Konfigurasi kueri terfilter & pagination
        $queryBuilder = $this->santriModel->getFilteredSantri($filters, $allowedKamarIds);
        $perPage = 8;
        $santriList = $queryBuilder->paginate($perPage, 'santri');
        $pager = $this->santriModel->pager;

        // Opsi pilihan kamar untuk dropdown filter
        if ($allowedKamarIds !== null) {
            $daftarKamar = !empty($allowedKamarIds)
                ? $this->kamarModel->whereIn('id', $allowedKamarIds)->findAll()
                : [];
        } else {
            $daftarKamar = $this->kamarModel->orderBy('nama_kamar', 'ASC')->findAll();
        }

        // Opsi pilihan kelas untuk dropdown filter
        $daftarKelas = $this->santriModel->getDaftarKelas($allowedKamarIds);

        return view('santri/index', [
            'title'           => 'Manajemen Data Santri - SIMKES Santri',
            'santriList'      => $santriList,
            'pager'           => $pager,
            'filters'         => $filters,
            'daftarKamar'     => $daftarKamar,
            'daftarKelas'     => $daftarKelas,
            'isPengawasKamar' => ($allowedKamarIds !== null),
            'totalSantri'     => $this->santriModel->getFilteredSantri([], $allowedKamarIds)->countAllResults(),
        ]);
    }

    /**
     * Halaman Detail Santri beserta Riwayat Rekam Medis
     */
    public function show($id)
    {
        $id = (int) $id;
        $santri = $this->santriModel->getSantriDetail($id);

        if (!$santri) {
            return redirect()->to(base_url('/santri'))
                ->with('error', 'Data santri dengan ID tersebut tidak ditemukan.');
        }

        // Penegakan izin akses backend (Anti-IDOR)
        $allowedKamarIds = $this->getAllowedKamarIds();
        if ($allowedKamarIds !== null && !in_array((int)$santri['kamar_id'], $allowedKamarIds)) {
            return redirect()->to(base_url('/santri'))
                ->with('error', 'Akses ditolak: Anda tidak memiliki wewenang untuk melihat data santri dari kamar lain.');
        }

        // Ambil riwayat kesehatan dan rekam medis yang terhubung
        $userRole = UserModel::normalizeRole(session()->get('role') ?? '');
        $isTenagaMedis = in_array($userRole, [UserModel::ROLE_PENGAWAS_KESEHATAN, UserModel::ROLE_ADMIN]);

        $riwayatMedis = $this->laporanModel->getRiwayatMedisBySantri($id);

        // Perlindungan Privasi Medis: Pengawas kamar tidak diberikan data klinis dokter & resep obat
        if (!$isTenagaMedis) {
            foreach ($riwayatMedis as &$kasus) {
                $kasus['daftar_pemeriksaan'] = [];
                $kasus['pemeriksaan']        = [];
            }
        }

        return view('santri/show', [
            'title'         => "Profil Santri: {$santri['nama']} - SIMKES Santri",
            'santri'        => $santri,
            'riwayatMedis'  => $riwayatMedis,
            'isTenagaMedis' => $isTenagaMedis,
        ]);
    }

    /**
     * Halaman Form Tambah Santri Baru
     */
    public function new()
    {
        $allowedKamarIds = $this->getAllowedKamarIds();

        if ($allowedKamarIds !== null) {
            if (empty($allowedKamarIds)) {
                return redirect()->to(base_url('/santri'))
                    ->with('error', 'Anda belum ditugaskan mengawasi kamar manapun sehingga belum dapat menambahkan santri.');
            }
            $daftarKamar = $this->kamarModel->whereIn('id', $allowedKamarIds)->findAll();
        } else {
            $daftarKamar = $this->kamarModel->orderBy('nama_kamar', 'ASC')->findAll();
        }

        return view('santri/form', [
            'title'       => 'Tambah Santri Baru - SIMKES Santri',
            'isEdit'      => false,
            'santri'      => [],
            'daftarKamar' => $daftarKamar,
        ]);
    }

    /**
     * Proses Simpan Santri Baru ke Database
     */
    public function create()
    {
        $rules = [
            'nis'           => 'required|regex_match[/^[a-zA-Z0-9\s\-\.\/]+$/]|min_length[3]|max_length[30]|is_unique[santri.nis]',
            'nama'          => 'required|min_length[3]|max_length[100]',
            'jenis_kelamin' => 'required|in_list[Laki-laki,Perempuan]',
            'kamar_id'      => 'permit_empty|is_natural_no_zero',
            'kelas'         => 'permit_empty|max_length[50]',
            'kontak_wali'   => 'permit_empty|max_length[50]',
        ];

        if (!$this->validate($rules, $this->santriModel->getValidationMessages())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $kamarId = $this->request->getPost('kamar_id') ? (int) $this->request->getPost('kamar_id') : null;

        // Validasi izin kamar di backend
        $allowedKamarIds = $this->getAllowedKamarIds();
        if ($allowedKamarIds !== null && $kamarId !== null && !in_array($kamarId, $allowedKamarIds)) {
            return redirect()->back()->withInput()
                ->with('error', 'Akses ditolak: Anda tidak dapat menempatkan santri ke kamar yang bukan tanggung jawab Anda.');
        }

        $data = [
            'nis'           => strtoupper(trim((string) $this->request->getPost('nis'))),
            'nama'          => trim((string) $this->request->getPost('nama')),
            'jenis_kelamin' => (string) $this->request->getPost('jenis_kelamin'),
            'kamar_id'      => $kamarId,
            'kelas'         => trim((string) $this->request->getPost('kelas')),
            'kontak_wali'   => trim((string) $this->request->getPost('kontak_wali')),
        ];

        if (!$this->santriModel->insert($data)) {
            return redirect()->back()->withInput()
                ->with('error', 'Gagal menyimpan data santri ke database.');
        }

        $newId = $this->santriModel->getInsertID();

        // Audit Log
        $this->auditModel->catatAktivitas(
            (int) session()->get('user_id'),
            'TAMBAH_SANTRI',
            "Menambahkan santri baru: {$data['nama']} (NIS: {$data['nis']}, ID: {$newId})"
        );

        return redirect()->to(base_url('/santri'))
            ->with('success', "Santri {$data['nama']} berhasil didaftarkan ke sistem.");
    }

    /**
     * Halaman Form Edit Santri
     */
    public function edit($id)
    {
        $id = (int) $id;
        $santri = $this->santriModel->getSantriDetail($id);

        if (!$santri) {
            return redirect()->to(base_url('/santri'))
                ->with('error', 'Data santri tidak ditemukan.');
        }

        // Verifikasi izin akses kamar di backend
        $allowedKamarIds = $this->getAllowedKamarIds();
        if ($allowedKamarIds !== null && !in_array((int)$santri['kamar_id'], $allowedKamarIds)) {
            return redirect()->to(base_url('/santri'))
                ->with('error', 'Akses ditolak: Anda tidak memiliki wewenang untuk mengubah data santri dari kamar lain.');
        }

        if ($allowedKamarIds !== null) {
            $daftarKamar = $this->kamarModel->whereIn('id', $allowedKamarIds)->findAll();
        } else {
            $daftarKamar = $this->kamarModel->orderBy('nama_kamar', 'ASC')->findAll();
        }

        return view('santri/form', [
            'title'       => "Edit Data Santri: {$santri['nama']} - SIMKES Santri",
            'isEdit'      => true,
            'santri'      => $santri,
            'daftarKamar' => $daftarKamar,
        ]);
    }

    /**
     * Proses Pembaruan Data Santri di Database
     */
    public function update($id)
    {
        $id = (int) $id;
        $santri = $this->santriModel->find($id);

        if (!$santri) {
            return redirect()->to(base_url('/santri'))
                ->with('error', 'Data santri tidak ditemukan.');
        }

        // Verifikasi izin akses kamar pada santri sebelum diubah
        $allowedKamarIds = $this->getAllowedKamarIds();
        if ($allowedKamarIds !== null && !in_array((int)$santri['kamar_id'], $allowedKamarIds)) {
            return redirect()->to(base_url('/santri'))
                ->with('error', 'Akses ditolak: Anda tidak memiliki wewenang untuk mengubah data santri dari kamar lain.');
        }

        $rules = [
            'nis'           => "required|regex_match[/^[a-zA-Z0-9\s\-\.\/]+$/]|min_length[3]|max_length[30]|is_unique[santri.nis,id,{$id}]",
            'nama'          => 'required|min_length[3]|max_length[100]',
            'jenis_kelamin' => 'required|in_list[Laki-laki,Perempuan]',
            'kamar_id'      => 'permit_empty|is_natural_no_zero',
            'kelas'         => 'permit_empty|max_length[50]',
            'kontak_wali'   => 'permit_empty|max_length[50]',
        ];

        if (!$this->validate($rules, $this->santriModel->getValidationMessages())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $kamarIdBaru = $this->request->getPost('kamar_id') ? (int) $this->request->getPost('kamar_id') : null;

        // Verifikasi jika pengawas kamar mencoba memindahkan santri ke kamar di luar binaannya
        if ($allowedKamarIds !== null && $kamarIdBaru !== null && !in_array($kamarIdBaru, $allowedKamarIds)) {
            return redirect()->back()->withInput()
                ->with('error', 'Akses ditolak: Anda tidak dapat memindahkan santri ke kamar di luar tanggung jawab Anda.');
        }

        $updateData = [
            'nis'           => strtoupper(trim((string) $this->request->getPost('nis'))),
            'nama'          => trim((string) $this->request->getPost('nama')),
            'jenis_kelamin' => (string) $this->request->getPost('jenis_kelamin'),
            'kamar_id'      => $kamarIdBaru,
            'kelas'         => trim((string) $this->request->getPost('kelas')),
            'kontak_wali'   => trim((string) $this->request->getPost('kontak_wali')),
        ];

        if (!$this->santriModel->update($id, $updateData)) {
            return redirect()->back()->withInput()
                ->with('error', 'Gagal memperbarui data santri.');
        }

        // Audit Log
        $this->auditModel->catatAktivitas(
            (int) session()->get('user_id'),
            'UBAH_SANTRI',
            "Memperbarui data santri: {$updateData['nama']} (NIS: {$updateData['nis']}, ID: {$id})"
        );

        return redirect()->to(base_url('/santri/' . $id))
            ->with('success', "Data santri {$updateData['nama']} berhasil diperbarui.");
    }

    /**
     * Proses Hapus Data Santri
     */
    public function delete($id)
    {
        $id = (int) $id;
        $santri = $this->santriModel->find($id);

        if (!$santri) {
            return redirect()->to(base_url('/santri'))
                ->with('error', 'Data santri tidak ditemukan.');
        }

        $allowedKamarIds = $this->getAllowedKamarIds();
        if ($allowedKamarIds !== null && !in_array((int)$santri['kamar_id'], $allowedKamarIds)) {
            return redirect()->to(base_url('/santri'))
                ->with('error', 'Akses ditolak: Anda tidak memiliki wewenang untuk menghapus data santri ini.');
        }

        if (!$this->santriModel->delete($id)) {
            return redirect()->to(base_url('/santri'))
                ->with('error', 'Gagal menghapus data santri.');
        }

        $this->auditModel->catatAktivitas(
            (int) session()->get('user_id'),
            'HAPUS_SANTRI',
            "Menghapus data santri: {$santri['nama']} (NIS: {$santri['nis']}, ID: {$id})"
        );

        return redirect()->to(base_url('/santri'))
            ->with('success', "Data santri {$santri['nama']} telah berhasil dihapus.");
    }
}

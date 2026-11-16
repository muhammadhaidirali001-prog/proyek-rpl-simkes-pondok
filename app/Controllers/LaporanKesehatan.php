<?php

namespace App\Controllers;

use App\Models\LaporanKesehatanModel;
use App\Models\SantriModel;
use App\Models\KamarModel;
use App\Models\PemeriksaanModel;
use App\Models\PemberianObatModel;
use App\Models\RiwayatStatusModel;
use App\Models\AuditLogModel;
use App\Models\UserModel;

class LaporanKesehatan extends BaseController
{
    protected LaporanKesehatanModel $laporanModel;
    protected SantriModel $santriModel;
    protected KamarModel $kamarModel;
    protected PemeriksaanModel $pemeriksaanModel;
    protected PemberianObatModel $pemberianObatModel;
    protected RiwayatStatusModel $riwayatStatusModel;
    protected AuditLogModel $auditModel;

    public function __construct()
    {
        $this->laporanModel       = new LaporanKesehatanModel();
        $this->santriModel        = new SantriModel();
        $this->kamarModel         = new KamarModel();
        $this->pemeriksaanModel   = new PemeriksaanModel();
        $this->pemberianObatModel = new PemberianObatModel();
        $this->riwayatStatusModel = new RiwayatStatusModel();
        $this->auditModel         = new AuditLogModel();
    }

    /**
     * Dapatkan daftar ID kamar yang diizinkan untuk pengguna login (RBAC)
     */
    protected function getAllowedKamarIds(): ?array
    {
        $userRole = UserModel::normalizeRole(session()->get('role') ?? '');
        $userId   = (int) session()->get('user_id');

        if ($userRole === UserModel::ROLE_PENGAWAS_KAMAR) {
            $kamarBinaan = $this->kamarModel->where('pengawas_id', $userId)->findAll();
            return array_column($kamarBinaan, 'id');
        }

        return null;
    }

    /**
     * Halaman Daftar Laporan Kesehatan Santri
     */
    public function index(): string
    {
        $allowedKamarIds = $this->getAllowedKamarIds();

        $keyword  = trim((string) $this->request->getGet('keyword'));
        $kamarId  = $this->request->getGet('kamar_id');
        $status   = trim((string) $this->request->getGet('status'));
        $tab      = trim((string) $this->request->getGet('tab')) ?: 'aktif'; // 'aktif', 'selesai', 'semua'

        $filters = [
            'keyword'  => $keyword,
            'kamar_id' => $kamarId,
            'status'   => $status,
        ];

        // Opsi kamar untuk dropdown filter
        if ($allowedKamarIds !== null) {
            $daftarKamar = !empty($allowedKamarIds) ? $this->kamarModel->whereIn('id', $allowedKamarIds)->findAll() : [];
        } else {
            $daftarKamar = $this->kamarModel->orderBy('nama_kamar', 'ASC')->findAll();
        }

        // Kueri laporan dengan pagination
        $builder = $this->laporanModel->select('laporan_kesehatan.*, 
                santri.nis, santri.nama as nama_santri, santri.jenis_kelamin, santri.kelas,
                kamar.id as kamar_id, kamar.nama_kamar,
                pelapor.nama as nama_pelapor')
            ->join('santri', 'santri.id = laporan_kesehatan.santri_id')
            ->join('kamar', 'kamar.id = santri.kamar_id', 'left')
            ->join('users as pelapor', 'pelapor.id = laporan_kesehatan.pelapor_id', 'left');

        if ($allowedKamarIds !== null) {
            if (empty($allowedKamarIds)) {
                $builder->where('1 = 0');
            } else {
                $builder->whereIn('santri.kamar_id', $allowedKamarIds);
            }
        }

        if (!empty($kamarId)) {
            $builder->where('santri.kamar_id', (int) $kamarId);
        }

        if (!empty($status)) {
            $builder->where('laporan_kesehatan.status', $status);
        } elseif ($tab === 'aktif') {
            $builder->whereIn('laporan_kesehatan.status', [
                LaporanKesehatanModel::STATUS_DILAPORKAN_SAKIT,
                'Baru',
                LaporanKesehatanModel::STATUS_DALAM_PEMERIKSAAN,
                LaporanKesehatanModel::STATUS_DALAM_PERAWATAN,
                'Isolasi',
            ]);
        } elseif ($tab === 'selesai') {
            $builder->whereIn('laporan_kesehatan.status', [
                LaporanKesehatanModel::STATUS_SEMBUH,
                LaporanKesehatanModel::STATUS_DIRUJUK,
                'Rujukan RS',
            ]);
        }

        if (!empty($keyword)) {
            $builder->groupStart()
                ->like('santri.nama', $keyword)
                ->orLike('santri.nis', $keyword)
                ->groupEnd();
        }

        $laporanList = $builder->orderBy('laporan_kesehatan.tanggal_mulai_sakit', 'DESC')
            ->orderBy('laporan_kesehatan.id', 'DESC')
            ->paginate(10, 'laporan');

        $pager = $this->laporanModel->pager;

        return view('laporan_kesehatan/index', [
            'title'           => 'Laporan Kesehatan Santri - SIMKES Santri',
            'laporanList'     => $laporanList,
            'pager'           => $pager,
            'filters'         => $filters,
            'tab'             => $tab,
            'daftarKamar'     => $daftarKamar,
            'isPengawasKamar' => ($allowedKamarIds !== null),
        ]);
    }

    /**
     * Halaman Detail Pemantauan Laporan Sakit Santri
     */
    public function show($id)
    {
        $id = (int) $id;
        $laporan = $this->laporanModel->select('laporan_kesehatan.*, 
                santri.nis, santri.nama as nama_santri, santri.jenis_kelamin, santri.kelas, santri.kontak_wali,
                kamar.id as kamar_id, kamar.nama_kamar,
                pelapor.nama as nama_pelapor')
            ->join('santri', 'santri.id = laporan_kesehatan.santri_id')
            ->join('kamar', 'kamar.id = santri.kamar_id', 'left')
            ->join('users as pelapor', 'pelapor.id = laporan_kesehatan.pelapor_id', 'left')
            ->where('laporan_kesehatan.id', $id)
            ->first();

        if (!$laporan) {
            return redirect()->to(base_url('/laporan-kesehatan'))
                ->with('error', 'Laporan kesehatan tidak ditemukan.');
        }

        // Anti-IDOR: Pengawas kamar hanya dapat melihat laporan di kamar binaannya
        $allowedKamarIds = $this->getAllowedKamarIds();
        if ($allowedKamarIds !== null && !in_array((int)$laporan['kamar_id'], $allowedKamarIds)) {
            return redirect()->to(base_url('/dashboard'))
                ->with('error', 'Akses ditolak: Anda tidak memiliki wewenang untuk melihat laporan santri di kamar lain.');
        }

        $userRole = UserModel::normalizeRole(session()->get('role') ?? '');
        $isTenagaMedis = in_array($userRole, [UserModel::ROLE_PENGAWAS_KESEHATAN, UserModel::ROLE_ADMIN]);

        // Privasi Medis: Pengawas kamar tidak diberikan data diagnosis klinis dokter dan obat internal
        $daftarPemeriksaan = [];
        if ($isTenagaMedis) {
            $daftarPemeriksaan = $this->pemeriksaanModel->select('pemeriksaan.*, pemeriksa.nama as nama_pemeriksa, pemeriksa.role as role_pemeriksa')
                ->join('users as pemeriksa', 'pemeriksa.id = pemeriksaan.pemeriksa_id', 'left')
                ->where('pemeriksaan.laporan_id', $id)
                ->orderBy('pemeriksaan.tanggal_periksa', 'DESC')
                ->findAll();

            foreach ($daftarPemeriksaan as &$periksa) {
                $periksa['obat'] = $this->pemberianObatModel->getObatByPemeriksaan($periksa['id']);
            }
        }

        // Ambil linimasa riwayat status (audit trail tidak pernah ditimpa)
        $riwayatStatus = $this->riwayatStatusModel->select('riwayat_status.*, users.nama as nama_petugas')
            ->join('users', 'users.id = riwayat_status.pengguna_id', 'left')
            ->where('riwayat_status.laporan_id', $id)
            ->orderBy('riwayat_status.tanggal', 'DESC')
            ->orderBy('riwayat_status.id', 'DESC')
            ->findAll();

        return view('laporan_kesehatan/show', [
            'title'             => "Detail Laporan Sakit: {$laporan['nama_santri']} - SIMKES Santri",
            'laporan'           => $laporan,
            'daftarPemeriksaan' => $daftarPemeriksaan,
            'riwayatStatus'     => $riwayatStatus,
            'isTenagaMedis'     => $isTenagaMedis,
            'daftarStatus'      => LaporanKesehatanModel::getDaftarStatus(),
        ]);
    }

    /**
     * Form Pelaporan Santri Sakit Baru
     */
    public function new()
    {
        $allowedKamarIds = $this->getAllowedKamarIds();
        $santriIdParam   = (int) $this->request->getGet('santri_id');

        // Daftar santri yang dapat dipilih
        if ($allowedKamarIds !== null) {
            if (empty($allowedKamarIds)) {
                return redirect()->to(base_url('/dashboard'))
                    ->with('error', 'Anda belum ditugaskan membina kamar manapun.');
            }
            $santriList = $this->santriModel->whereIn('kamar_id', $allowedKamarIds)->orderBy('nama', 'ASC')->findAll();
        } else {
            $santriList = $this->santriModel->orderBy('nama', 'ASC')->findAll();
        }

        return view('laporan_kesehatan/new', [
            'title'         => 'Lapor Santri Sakit - SIMKES Santri',
            'santriList'    => $santriList,
            'santriIdParam' => $santriIdParam,
        ]);
    }

    /**
     * Simpan Laporan Santri Sakit Baru
     */
    public function create()
    {
        $rules = [
            'santri_id'           => 'required|is_natural_no_zero',
            'tanggal_mulai_sakit' => 'required',
            'gejala'              => 'required|min_length[3]',
            'catatan'             => 'permit_empty',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $santriId = (int) $this->request->getPost('santri_id');
        $santri   = $this->santriModel->find($santriId);

        if (!$santri) {
            return redirect()->back()->withInput()->with('error', 'Santri tidak ditemukan.');
        }

        // Anti-IDOR verifikasi kamar santri
        $allowedKamarIds = $this->getAllowedKamarIds();
        if ($allowedKamarIds !== null && !in_array((int)$santri['kamar_id'], $allowedKamarIds)) {
            return redirect()->back()->withInput()
                ->with('error', 'Akses ditolak: Anda tidak memiliki wewenang untuk melaporkan santri di luar kamar binaan Anda.');
        }

        $userId = (int) session()->get('user_id');
        $tanggalMulai = $this->request->getPost('tanggal_mulai_sakit');
        // Format datetime jika hanya tanggal
        if (strlen($tanggalMulai) === 10) {
            $tanggalMulai .= ' ' . date('H:i:s');
        }

        $dataLaporan = [
            'santri_id'           => $santriId,
            'pelapor_id'          => $userId,
            'tanggal_mulai_sakit' => $tanggalMulai,
            'gejala'              => trim((string) $this->request->getPost('gejala')),
            'status'              => LaporanKesehatanModel::STATUS_DILAPORKAN_SAKIT,
            'catatan'             => trim((string) $this->request->getPost('catatan')),
        ];

        if (!$this->laporanModel->insert($dataLaporan)) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan laporan sakit.');
        }

        $laporanId = $this->laporanModel->getInsertID();

        // Catat entri awal ke riwayat_status (non-destructive)
        $this->riwayatStatusModel->insert([
            'laporan_id'  => $laporanId,
            'status'      => LaporanKesehatanModel::STATUS_DILAPORKAN_SAKIT,
            'tanggal'     => date('Y-m-d H:i:s'),
            'catatan'     => 'Santri dilaporkan sakit dengan keluhan: ' . $dataLaporan['gejala'],
            'pengguna_id' => $userId,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        $this->auditModel->catatAktivitas(
            $userId,
            'LAPOR_SANTRI_SAKIT',
            "Melaporkan santri sakit: {$santri['nama']} (NIS: {$santri['nis']}, Laporan #{$laporanId})"
        );

        return redirect()->to(base_url('/laporan-kesehatan/' . $laporanId))
            ->with('success', "Laporan sakit untuk {$santri['nama']} berhasil dicatat.");
    }

    /**
     * Update Status Laporan Kesehatan & Konfirmasi Sembuh / Rujukan
     */
    public function updateStatus($id)
    {
        $id = (int) $id;
        $laporan = $this->laporanModel->find($id);

        if (!$laporan) {
            return redirect()->to(base_url('/laporan-kesehatan'))
                ->with('error', 'Laporan tidak ditemukan.');
        }

        // Anti-IDOR: Pengawas kamar hanya dapat memperbarui status santri di kamar binaannya!
        $allowedKamarIds = $this->getAllowedKamarIds();
        if ($allowedKamarIds !== null) {
            $santri = $this->santriModel->find($laporan['santri_id']);
            if (!$santri || !in_array((int)$santri['kamar_id'], $allowedKamarIds)) {
                return redirect()->to(base_url('/laporan-kesehatan'))
                    ->with('error', 'Akses ditolak: Anda tidak memiliki wewenang untuk mengubah status santri di luar kamar binaan Anda.');
            }
        }

        $rules = [
            'status'         => 'required|in_list[Dilaporkan Sakit,Dalam Pemeriksaan,Dalam Perawatan,Sembuh,Dirujuk,Baru,Rujukan RS]',
            'catatan'        => 'permit_empty|max_length[500]',
            'tanggal_sembuh' => 'permit_empty',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $statusBaru = trim((string) $this->request->getPost('status'));
        $catatan    = trim((string) $this->request->getPost('catatan'));
        $userRole   = UserModel::normalizeRole(session()->get('role') ?? '');
        $userId     = (int) session()->get('user_id');

        // Aturan Kritis: Status 'Sembuh' hanya boleh ditetapkan oleh petugas kesehatan berwenang
        if ($statusBaru === LaporanKesehatanModel::STATUS_SEMBUH) {
            if (!in_array($userRole, [UserModel::ROLE_PENGAWAS_KESEHATAN, UserModel::ROLE_ADMIN])) {
                return redirect()->back()
                    ->with('error', 'Akses ditolak: Tanggal dan konfirmasi sembuh hanya dapat ditetapkan oleh petugas kesehatan yang berwenang.');
            }
        }

        $tanggalSembuh = ($statusBaru === LaporanKesehatanModel::STATUS_SEMBUH)
            ? ($this->request->getPost('tanggal_sembuh') ?: date('Y-m-d'))
            : null;

        $updateSuccess = $this->laporanModel->ubahStatusKesehatan($id, $statusBaru, $userId, $catatan, $tanggalSembuh);

        if (!$updateSuccess) {
            return redirect()->back()->with('error', 'Gagal memperbarui status kesehatan.');
        }

        return redirect()->to(base_url('/laporan-kesehatan/' . $id))
            ->with('success', "Status laporan berhasil diperbarui menjadi '{$statusBaru}'.");
    }
}

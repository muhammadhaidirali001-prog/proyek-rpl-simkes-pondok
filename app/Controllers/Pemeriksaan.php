<?php

namespace App\Controllers;

use App\Models\LaporanKesehatanModel;
use App\Models\SantriModel;
use App\Models\PemeriksaanModel;
use App\Models\PemberianObatModel;
use App\Models\ObatModel;
use App\Models\RiwayatStatusModel;
use App\Models\AuditLogModel;
use App\Models\UserModel;

class Pemeriksaan extends BaseController
{
    protected LaporanKesehatanModel $laporanModel;
    protected SantriModel $santriModel;
    protected PemeriksaanModel $pemeriksaanModel;
    protected PemberianObatModel $pemberianObatModel;
    protected ObatModel $obatModel;
    protected RiwayatStatusModel $riwayatStatusModel;
    protected AuditLogModel $auditModel;

    public function __construct()
    {
        $this->laporanModel       = new LaporanKesehatanModel();
        $this->santriModel        = new SantriModel();
        $this->pemeriksaanModel   = new PemeriksaanModel();
        $this->pemberianObatModel = new PemberianObatModel();
        $this->obatModel          = new ObatModel();
        $this->riwayatStatusModel = new RiwayatStatusModel();
        $this->auditModel         = new AuditLogModel();
    }

    /**
     * Verifikasi peran tenaga medis
     */
    protected function ensureMedisOrAdmin(): void
    {
        $role = UserModel::normalizeRole(session()->get('role') ?? '');
        if (!in_array($role, [UserModel::ROLE_PENGAWAS_KESEHATAN, UserModel::ROLE_ADMIN])) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Akses ditolak: Anda tidak memiliki wewenang tenaga medis.');
        }
    }

    /**
     * Halaman daftar antrean santri yang memerlukan pemeriksaan klinis
     */
    public function index()
    {
        $this->ensureMedisOrAdmin();

        return redirect()->to(base_url('/laporan-kesehatan?tab=aktif'));
    }

    /**
     * Form pencatatan hasil pemeriksaan klinis dokter/petugas medis
     */
    public function new($laporanId)
    {
        $this->ensureMedisOrAdmin();
        $laporanId = (int) $laporanId;

        $laporan = $this->laporanModel->select('laporan_kesehatan.*, 
                santri.nis, santri.nama as nama_santri, santri.jenis_kelamin, santri.kelas,
                kamar.nama_kamar, pelapor.nama as nama_pelapor')
            ->join('santri', 'santri.id = laporan_kesehatan.santri_id')
            ->join('kamar', 'kamar.id = santri.kamar_id', 'left')
            ->join('users as pelapor', 'pelapor.id = laporan_kesehatan.pelapor_id', 'left')
            ->where('laporan_kesehatan.id', $laporanId)
            ->first();

        if (!$laporan) {
            return redirect()->to(base_url('/laporan-kesehatan'))
                ->with('error', 'Laporan kasus kesehatan tidak ditemukan.');
        }

        $daftarObat = $this->obatModel->orderBy('nama_obat', 'ASC')->findAll();

        return view('pemeriksaan/form', [
            'title'      => "Pemeriksaan Klinis: {$laporan['nama_santri']} - SIMKES Santri",
            'laporan'    => $laporan,
            'daftarObat' => $daftarObat,
        ]);
    }

    /**
     * Simpan hasil pemeriksaan klinis resmi dan resep obat
     */
    public function create($laporanId)
    {
        $this->ensureMedisOrAdmin();
        $laporanId = (int) $laporanId;

        $laporan = $this->laporanModel->find($laporanId);
        if (!$laporan) {
            return redirect()->to(base_url('/laporan-kesehatan'))
                ->with('error', 'Laporan kasus kesehatan tidak ditemukan.');
        }

        $rules = [
            'tanggal_periksa'         => 'required',
            'diagnosis_atau_dugaan'   => 'required|min_length[3]|max_length[255]',
            'diverifikasi_oleh_medis' => 'required|in_list[1]',
            'status_lanjutan'         => 'required|in_list[Dalam Pemeriksaan,Dalam Perawatan,Sembuh,Dirujuk]',
            'tindakan'                => 'permit_empty',
            'catatan'                 => 'permit_empty',
        ];

        $messages = [
            'diagnosis_atau_dugaan' => [
                'required'   => 'Diagnosis atau dugaan klinis wajib diisi.',
                'min_length' => 'Diagnosis minimal 3 karakter.',
            ],
            'diverifikasi_oleh_medis' => [
                'required' => 'Pemeriksaan wajib diverifikasi oleh petugas medis resmi.',
                'in_list'  => 'Hasil keputusan harus diverifikasi oleh tenaga medis resmi, bukan keputusan otomatis.',
            ],
            'status_lanjutan' => [
                'required' => 'Status tindak lanjut kondisi santri wajib ditentukan.',
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userId = (int) session()->get('user_id');
        $santri = $this->santriModel->find($laporan['santri_id']);
        $santriNama = $santri ? $santri['nama'] : 'Santri';

        $tanggalPeriksa = (string) $this->request->getPost('tanggal_periksa');
        if (strlen($tanggalPeriksa) === 10) {
            $tanggalPeriksa .= ' ' . date('H:i:s');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // 1. Simpan pemeriksaan klinis
        $pemeriksaanData = [
            'laporan_id'              => $laporanId,
            'pemeriksa_id'            => $userId,
            'tanggal_periksa'         => $tanggalPeriksa,
            'diagnosis_atau_dugaan'   => trim((string) $this->request->getPost('diagnosis_atau_dugaan')),
            'tindakan'                => trim((string) $this->request->getPost('tindakan')),
            'catatan'                 => trim((string) $this->request->getPost('catatan')),
            'diverifikasi_oleh_medis' => 1,
        ];

        $this->pemeriksaanModel->insert($pemeriksaanData);
        $pemeriksaanId = $this->pemeriksaanModel->getInsertID();

        // 2. Simpan obat jika ada
        $obatIdArr      = (array) $this->request->getPost('obat_id');
        $dosisArr       = (array) $this->request->getPost('obat_dosis');
        $aturanPakaiArr = (array) $this->request->getPost('obat_aturan_pakai');
        $durasiArr      = (array) $this->request->getPost('obat_durasi');
        $instruksiArr   = (array) $this->request->getPost('obat_instruksi');

        foreach ($obatIdArr as $idx => $obatId) {
            $obatId = (int) $obatId;
            $dosis  = trim((string) ($dosisArr[$idx] ?? ''));
            $aturan = trim((string) ($aturanPakaiArr[$idx] ?? ''));

            if ($obatId > 0 && !empty($dosis) && !empty($aturan)) {
                $this->pemberianObatModel->insert([
                    'pemeriksaan_id'          => $pemeriksaanId,
                    'obat_id'                 => $obatId,
                    'dosis'                   => $dosis,
                    'aturan_pakai'            => $aturan,
                    'durasi'                  => trim((string) ($durasiArr[$idx] ?? '')),
                    'instruksi_petugas'       => trim((string) ($instruksiArr[$idx] ?? '')),
                    'diverifikasi_oleh_medis' => 1,
                ]);
            }
        }

        // 3. Update status laporan kesehatan
        $statusLanjutan = (string) $this->request->getPost('status_lanjutan');
        $dataUpdateLaporan = ['status' => $statusLanjutan];
        if ($statusLanjutan === LaporanKesehatanModel::STATUS_SEMBUH) {
            $dataUpdateLaporan['tanggal_sembuh'] = date('Y-m-d');
        }
        $this->laporanModel->update($laporanId, $dataUpdateLaporan);

        // 4. Catat riwayat status
        $this->riwayatStatusModel->insert([
            'laporan_id'  => $laporanId,
            'status'      => $statusLanjutan,
            'tanggal'     => date('Y-m-d H:i:s'),
            'catatan'     => "Pemeriksaan klinis dokter selesai. Status ditetapkan menjadi: {$statusLanjutan}.",
            'pengguna_id' => $userId,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        // 5. Catat audit log (Privasi medis: tanpa mencatat diagnosis atau resep lengkap ke log umum)
        $this->auditModel->catatAktivitas(
            $userId,
            'CATAT_PEMERIKSAAN',
            "Pencatatan pemeriksaan klinis resmi untuk santri {$santriNama} (Laporan #{$laporanId}, Pemeriksaan #{$pemeriksaanId}). Status diperbarui ke '{$statusLanjutan}'."
        );

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan hasil pemeriksaan klinis.');
        }

        return redirect()->to(base_url('/laporan-kesehatan/' . $laporanId))
            ->with('success', 'Hasil pemeriksaan klinis tenaga medis berhasil dicatat dan diverifikasi.');
    }
}

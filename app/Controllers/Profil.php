<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\KamarModel;
use App\Models\SantriModel;
use App\Models\LaporanKesehatanModel;
use App\Models\PemeriksaanModel;
use App\Models\PemberianObatModel;
use App\Models\AuditLogModel;

class Profil extends BaseController
{
    protected UserModel $userModel;
    protected AuditLogModel $auditModel;

    public function __construct()
    {
        $this->userModel  = new UserModel();
        $this->auditModel = new AuditLogModel();
    }

    /**
     * Tampilan Halaman Profil Profesi Pengguna
     */
    public function index()
    {
        $userId = (int) session()->get('user_id');
        $user   = $this->userModel->find($userId);

        if (!$user) {
            return redirect()->to(base_url('/logout'));
        }

        $canonicalRole = UserModel::normalizeRole($user['role']);
        $displayRole   = UserModel::getRoleDisplayName($user['role']);

        // Data spesifik profesi
        $profesiData = [
            'role_canonical' => $canonicalRole,
            'role_display'   => $displayRole,
        ];

        if ($canonicalRole === UserModel::ROLE_PENGAWAS_KAMAR) {
            // Profesi: Pengawas Kamar (Pembina Asrama)
            $kamarModel   = new KamarModel();
            $santriModel  = new SantriModel();
            $laporanModel = new LaporanKesehatanModel();

            $kamarList = $kamarModel->where('pengawas_id', $userId)->findAll();
            $kamarIds  = array_column($kamarList, 'id');
            $totalSantriBinaan = !empty($kamarIds) ? $santriModel->whereIn('kamar_id', $kamarIds)->countAllResults() : 0;
            $totalLaporanKamar = $laporanModel->where('pelapor_id', $userId)->countAllResults();

            $profesiData['kategori']          = 'Pengawas Kamar & Pembina Asrama';
            $profesiData['instansi']          = 'Asrama Santri Pondok Pesantren';
            $profesiData['lingkup_tugas']     = 'Pengawasan Harian Asrama, Pemantauan Kondisi Santri, & Deteksi Dini Sakit';
            $profesiData['kamar_binaan']      = $kamarList;
            $profesiData['total_santri']      = $totalSantriBinaan;
            $profesiData['total_laporan']     = $totalLaporanKamar;
            $profesiData['wewenang']          = [
                'Memantau kondisi fisik dan kesehatan santri di kamar binaan secara berkala.',
                'Membuat laporan santri sakit ke klinik Poskestren.',
                'Mendampingi santri saat isolasi ringan di kamar atau rujukan ke Poskestren.',
                'Memastikan santri meminum obat yang diberikan petugas medis sesuai anjuran.',
            ];
        } elseif ($canonicalRole === UserModel::ROLE_PENGAWAS_KESEHATAN) {
            // Profesi: Dokter / Tenaga Medis Poskestren
            $isDokter = str_contains(strtolower($user['nama']), 'dr.') || str_contains(strtolower($user['role']), 'dokter');
            $pemeriksaanModel = new PemeriksaanModel();
            $obatModel        = new PemberianObatModel();

            $totalPeriksa = $pemeriksaanModel->where('pemeriksa_id', $userId)->countAllResults();
            $pemeriksaanIds = $pemeriksaanModel->where('pemeriksa_id', $userId)->findColumn('id');
            $totalResep   = !empty($pemeriksaanIds) ? $obatModel->whereIn('pemeriksaan_id', $pemeriksaanIds)->countAllResults() : 0;

            $profesiData['kategori']          = $isDokter ? 'Dokter Poskestren' : 'Petugas Medis Poskestren';
            $profesiData['instansi']          = 'Pos Kesehatan Pesantren (Poskestren Terpadu)';
            $profesiData['spesialisasi']      = $isDokter ? 'Dokter Umum / Pelayanan Kesehatan Primer Santri' : 'Tenaga Paramedis / Keperawatan Poskestren';
            $profesiData['no_izin_tugas']     = 'POSKESTREN/REG-' . str_pad((string)$userId, 4, '0', STR_PAD_LEFT);
            $profesiData['lingkup_tugas']     = 'Pemeriksaan Klinis, Diagnosa, Resep Obat, & Penanganan Rujukan Darurat';
            $profesiData['total_pemeriksaan'] = $totalPeriksa;
            $profesiData['total_resep']       = $totalResep;
            $profesiData['wewenang']          = [
                'Melakukan anamnesis dan pemeriksaan fisik klinis menyeluruh pada santri yang sakit.',
                'Menentukan diagnosis kerja dan tindakan observasi santri di Poskestren.',
                'Meresepkan obat dan mengatur dosis terapi sesuai standar medis.',
                'Menerbitkan surat rujukan ke Rumah Sakit / Puskesmas mitra untuk kasus gawat darurat.',
                'Mengonfirmasi status santri yang telah sembuh untuk kembali ke asrama.',
            ];
        } else {
            // Profesi: Administrator Sistem (Admin)
            $totalUsers = $this->userModel->countAllResults();
            $totalLogs  = $this->auditModel->countAllResults();

            $profesiData['kategori']          = 'Administrator Utama Sistem SIMKES';
            $profesiData['instansi']          = 'Pusat Manajemen TI & Operasional Poskestren Pesantren';
            $profesiData['lingkup_tugas']     = 'Manajemen Hak Akses, Tata Kelola Master Data, Audit Keamanan, & Integrasi AI';
            $profesiData['total_pengguna']    = $totalUsers;
            $profesiData['total_audit_log']   = $totalLogs;
            $profesiData['wewenang']          = [
                'Mengelola akun petugas, dokter, pengawas kamar, dan hak akses sistem (RBAC).',
                'Mengonfigurasi dan memantau layanan Asisten AI Kesehatan SIMKES Santri.',
                'Mengawasi log audit aktivitas dan catatan keamanan transaksi data.',
                'Mengelola cadangan (backup) dan pemulihan database SIMKES Santri.',
                'Memastikan integrasi data santri dan asrama berjalan optimal tanpa gangguan.',
            ];
        }

        $data = [
            'title'       => 'Profil Profesi - ' . esc($user['nama']) . ' - SIMKES Santri',
            'user'        => $user,
            'profesi'     => $profesiData,
        ];

        return view('profil/index', $data);
    }

    /**
     * Proses Pembaruan Profil (Nama dan Kata Sandi)
     */
    public function update()
    {
        $userId = (int) session()->get('user_id');
        $user   = $this->userModel->find($userId);

        if (!$user) {
            return redirect()->to(base_url('/logout'));
        }

        $nama        = trim((string) $this->request->getPost('nama'));
        $passLama    = (string) $this->request->getPost('password_lama');
        $passBaru    = (string) $this->request->getPost('password_baru');
        $passConfirm = (string) $this->request->getPost('password_confirm');

        $rules = [
            'nama' => 'required|min_length[3]|max_length[100]',
        ];

        $messages = [
            'nama' => [
                'required'   => 'Nama lengkap wajib diisi.',
                'min_length' => 'Nama lengkap minimal 3 karakter.',
                'max_length' => 'Nama lengkap maksimal 100 karakter.',
            ],
        ];

        // Validasi opsional jika pengguna ingin ganti kata sandi
        if (!empty($passLama) || !empty($passBaru) || !empty($passConfirm)) {
            $rules['password_lama']    = 'required';
            $rules['password_baru']    = 'required|min_length[6]';
            $rules['password_confirm'] = 'required|matches[password_baru]';

            $messages['password_lama']['required']    = 'Kata sandi saat ini wajib diisi untuk mengganti kata sandi.';
            $messages['password_baru']['required']    = 'Kata sandi baru wajib diisi.';
            $messages['password_baru']['min_length']  = 'Kata sandi baru minimal 6 karakter.';
            $messages['password_confirm']['required'] = 'Konfirmasi kata sandi baru wajib diisi.';
            $messages['password_confirm']['matches']  = 'Konfirmasi kata sandi baru tidak cocok.';
        }

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $updateData = [
            'nama' => $nama,
        ];

        if (!empty($passBaru)) {
            // Verifikasi kecocokan kata sandi lama
            if (!password_verify($passLama, $user['password_hash'])) {
                return redirect()->back()->withInput()->with('error', 'Kata sandi lama yang Anda masukkan salah.');
            }
            $updateData['password_hash'] = password_hash($passBaru, PASSWORD_BCRYPT);
        }

        $this->userModel->update($userId, $updateData);

        // Perbarui sesi aktif
        session()->set('nama', $nama);

        // Catat di audit log
        $this->auditModel->catatAktivitas(
            $userId,
            'UPDATE_PROFIL',
            "Pengguna {$user['email']} memperbarui informasi profil profesi."
        );

        return redirect()->to(base_url('/profil'))
            ->with('success', 'Profil profesi Anda berhasil diperbarui.');
    }
}

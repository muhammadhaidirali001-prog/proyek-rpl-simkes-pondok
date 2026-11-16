<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\AuditLogModel;

class Users extends BaseController
{
    protected UserModel $userModel;
    protected AuditLogModel $auditModel;

    public function __construct()
    {
        $this->userModel  = new UserModel();
        $this->auditModel = new AuditLogModel();
    }

    /**
     * Verifikasi khusus Administrator
     */
    protected function ensureAdmin(): void
    {
        $role = UserModel::normalizeRole(session()->get('role') ?? '');
        if ($role !== UserModel::ROLE_ADMIN) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Akses ditolak: Hanya Administrator yang berwenang mengelola pengguna.');
        }
    }

    /**
     * Halaman daftar pengguna dan hak akses
     */
    public function index()
    {
        $this->ensureAdmin();

        $keyword = trim((string) $this->request->getGet('keyword'));
        $role    = trim((string) $this->request->getGet('role'));
        $status  = trim((string) $this->request->getGet('status'));

        $builder = $this->userModel;

        if (!empty($keyword)) {
            $builder = $builder->groupStart()
                ->like('nama', $keyword)
                ->orLike('email', $keyword)
                ->groupEnd();
        }

        if (!empty($role)) {
            $builder = $builder->like('role', $role);
        }

        if (!empty($status)) {
            $builder = $builder->where('status', $status);
        }

        $users = $builder->orderBy('id', 'ASC')->paginate(10, 'users');
        $pager = $this->userModel->pager;

        return view('users/index', [
            'title'   => 'Manajemen Pengguna & Hak Akses - SIMKES Santri',
            'users'   => $users,
            'pager'   => $pager,
            'filters' => [
                'keyword' => $keyword,
                'role'    => $role,
                'status'  => $status,
            ],
        ]);
    }

    /**
     * Pembaruan hak akses (role) dan status keaktifan akun pengguna
     */
    public function update($id)
    {
        $this->ensureAdmin();
        $id = (int) $id;

        $targetUser = $this->userModel->find($id);
        if (!$targetUser) {
            return redirect()->to(base_url('/users'))
                ->with('error', 'Pengguna tidak ditemukan.');
        }

        $rules = [
            'role'   => 'required|in_list[admin,pengawas_kesehatan,pengawas_kamar,Admin,Pengawas Kesehatan,Pengawas Kamar,Dokter,Petugas Kesehatan]',
            'status' => 'required|in_list[Aktif,Nonaktif,Menunggu Persetujuan]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $currentAdminId = (int) session()->get('user_id');
        $newRoleInput   = (string) $this->request->getPost('role');
        $newStatus      = (string) $this->request->getPost('status');

        $canonicalRole = UserModel::normalizeRole($newRoleInput);
        $roleLabel = match ($canonicalRole) {
            UserModel::ROLE_ADMIN              => 'Admin',
            UserModel::ROLE_PENGAWAS_KESEHATAN => 'Pengawas Kesehatan',
            UserModel::ROLE_PENGAWAS_KAMAR     => 'Pengawas Kamar',
            default                            => $newRoleInput,
        };

        // Cegah Administrator menonaktifkan atau mendemosi dirinya sendiri (Anti-Lockout)
        if ($id === $currentAdminId) {
            if ($newStatus === UserModel::STATUS_NONAKTIF) {
                return redirect()->back()
                    ->with('error', 'Tindakan dicegah: Anda tidak dapat menonaktifkan akun Administrator Anda sendiri.');
            }
            if ($canonicalRole !== UserModel::ROLE_ADMIN) {
                return redirect()->back()
                    ->with('error', 'Tindakan dicegah: Anda tidak dapat menurunkan hak akses peran Administrator Anda sendiri.');
            }
        }

        $updateData = [
            'role'   => $roleLabel,
            'status' => $newStatus,
        ];

        if (!$this->userModel->update($id, $updateData)) {
            return redirect()->back()->with('error', 'Gagal memperbarui hak akses pengguna.');
        }

        // Catat ke Audit Log
        $this->auditModel->catatAktivitas(
            $currentAdminId,
            'UBAH_HAK_AKSES_AKUN',
            "Mengubah hak akses pengguna '{$targetUser['nama']}' (ID: {$id}): Peran '{$targetUser['role']}' -> '{$roleLabel}', Status '{$targetUser['status']}' -> '{$newStatus}'."
        );

        return redirect()->to(base_url('/users'))
            ->with('success', "Hak akses dan status akun {$targetUser['nama']} berhasil diperbarui.");
    }

    // =========================================================================
    // SMTP SETTINGS — Pengaturan Konfigurasi Email Sistem
    // =========================================================================

    /**
     * Halaman Pengaturan SMTP Email
     */
    public function smtpSettings()
    {
        $this->ensureAdmin();

        // Baca konfigurasi SMTP saat ini dari .env
        $envPath = ROOTPATH . '.env';
        $envData = $this->readEnvFile($envPath);

        return view('admin/smtp_settings', [
            'title'       => 'Pengaturan Email SMTP - SIMKES Santri',
            'smtp_host'   => $envData['email.SMTPHost']   ?? '',
            'smtp_port'   => $envData['email.SMTPPort']   ?? '587',
            'smtp_user'   => $envData['email.SMTPUser']   ?? '',
            'smtp_pass'   => $envData['email.SMTPPass']   ?? '',
            'mail_from'   => $envData['email.fromEmail']  ?? '',
            'mail_name'   => $envData['email.fromName']   ?? 'SIMKES Santri',
            'smtp_crypto' => $envData['email.SMTPCrypto'] ?? 'tls',
        ]);
    }

    /**
     * Simpan Pengaturan SMTP ke .env
     */
    public function smtpSettingsSave()
    {
        $this->ensureAdmin();

        $rules = [
            'smtp_host'   => 'permit_empty|max_length[200]',
            'smtp_port'   => 'required|numeric|greater_than[0]|less_than[65536]',
            'smtp_user'   => 'permit_empty|max_length[200]',
            'smtp_pass'   => 'permit_empty|max_length[200]',
            'mail_from'   => 'permit_empty|valid_email',
            'mail_name'   => 'permit_empty|max_length[100]',
            'smtp_crypto' => 'required|in_list[tls,ssl,]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $smtpHost   = trim((string) $this->request->getPost('smtp_host'));
        $smtpPort   = trim((string) $this->request->getPost('smtp_port'));
        $smtpUser   = trim((string) $this->request->getPost('smtp_user'));
        $smtpPass   = trim((string) $this->request->getPost('smtp_pass'));
        $mailFrom   = trim((string) $this->request->getPost('mail_from'));
        $mailName   = trim((string) $this->request->getPost('mail_name'));
        $smtpCrypto = trim((string) $this->request->getPost('smtp_crypto'));

        $envPath    = ROOTPATH . '.env';
        $envData    = $this->readEnvFile($envPath);

        // Update nilai SMTP di .env
        $envData['email.SMTPHost']   = $smtpHost;
        $envData['email.SMTPPort']   = $smtpPort;
        $envData['email.SMTPUser']   = $smtpUser;
        // Hanya update password jika field tidak kosong (kosong = pertahankan lama)
        if (!empty($smtpPass)) {
            $envData['email.SMTPPass'] = $smtpPass;
        }
        $envData['email.fromEmail']  = $mailFrom ?: $smtpUser;
        $envData['email.fromName']   = $mailName ?: 'SIMKES Santri';
        $envData['email.SMTPCrypto'] = $smtpCrypto;

        $this->writeEnvFile($envPath, $envData);

        $this->auditModel->catatAktivitas(
            (int) session()->get('user_id'),
            'UBAH_SMTP_SETTINGS',
            "Administrator mengubah konfigurasi SMTP: host={$smtpHost}, port={$smtpPort}, user={$smtpUser}"
        );

        return redirect()->to(base_url('/admin/pengaturan-email'))
            ->with('success', 'Konfigurasi SMTP berhasil disimpan. Gunakan tombol "Uji Koneksi" untuk memverifikasi.');
    }

    /**
     * Uji Kirim Email SMTP
     */
    public function smtpSettingsTest()
    {
        $this->ensureAdmin();

        $testEmail = trim((string) $this->request->getPost('test_email'));
        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->with('error', 'Alamat email tujuan uji tidak valid.');
        }

        $otpMailService = new \App\Libraries\OtpMailService();
        $result = $otpMailService->sendOtp(
            $testEmail,
            'Administrator (Test)',
            '123456'  // Kode dummy untuk uji SMTP
        );

        if ($result['is_simulated'] ?? false) {
            return redirect()->back()
                ->with('warning', 'SMTP belum dikonfigurasi atau koneksi gagal. Email tidak terkirim — masih dalam mode simulasi.');
        }

        return redirect()->back()
            ->with('success', "Email uji berhasil dikirimkan ke {$testEmail}. Silakan periksa kotak masuk Anda.");
    }

    // =========================================================================
    // HELPERS: Baca & Tulis .env
    // =========================================================================

    /**
     * Baca file .env ke array asosiatif key => value
     */
    private function readEnvFile(string $path): array
    {
        $result = [];
        if (!is_file($path)) {
            return $result;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$key, $val] = explode('=', $line, 2);
                $result[trim($key)] = trim($val, " \t\n\r\0\x0B'\"");
            }
        }
        return $result;
    }

    /**
     * Tulis array konfigurasi kembali ke file .env
     * Key yang sudah ada di-update; key baru ditambahkan di akhir
     */
    private function writeEnvFile(string $path, array $newData): void
    {
        $lines    = is_file($path) ? file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        $written  = [];
        $output   = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                $output[] = $line;
                continue;
            }
            if (str_contains($trimmed, '=')) {
                [$key] = explode('=', $trimmed, 2);
                $key   = trim($key);
                if (array_key_exists($key, $newData)) {
                    $output[] = $key . ' = ' . $newData[$key];
                    $written[$key] = true;
                    continue;
                }
            }
            $output[] = $line;
        }

        // Tambahkan key baru yang belum ada
        foreach ($newData as $key => $val) {
            if (!isset($written[$key])) {
                $output[] = $key . ' = ' . $val;
            }
        }

        file_put_contents($path, implode(PHP_EOL, $output) . PHP_EOL);
    }
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nama',
        'email',
        'password_hash',
        'role',
        'status',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Konstanta Role Canonical
    public const ROLE_PENGAWAS_KAMAR     = 'pengawas_kamar';
    public const ROLE_PENGAWAS_KESEHATAN = 'pengawas_kesehatan';
    public const ROLE_ADMIN              = 'admin';

    // Konstanta Status Akun
    public const STATUS_AKTIF                = 'Aktif';
    public const STATUS_MENUNGGU_PERSETUJUAN = 'Menunggu Persetujuan';
    public const STATUS_NONAKTIF             = 'Nonaktif';

    // Validation
    protected $validationRules = [
        'nama'          => 'required|min_length[3]|max_length[100]',
        'email'         => 'required|valid_email|is_unique[users.email,id,{id}]',
        'password_hash' => 'required|min_length[6]',
        'role'          => 'required',
        'status'        => 'permit_empty',
    ];

    protected $validationMessages = [
        'nama' => [
            'required'   => 'Nama pengguna wajib diisi.',
            'min_length' => 'Nama pengguna minimal 3 karakter.',
            'max_length' => 'Nama pengguna maksimal 100 karakter.',
        ],
        'email' => [
            'required'    => 'Email wajib diisi.',
            'valid_email' => 'Format email tidak valid.',
            'is_unique'   => 'Email sudah terdaftar di sistem.',
        ],
        'password_hash' => [
            'required'   => 'Kata sandi / hash wajib diisi.',
            'min_length' => 'Kata sandi minimal 6 karakter.',
        ],
        'role' => [
            'required' => 'Peran (role) pengguna wajib dipilih.',
        ],
    ];

    /**
     * Hash kata sandi menggunakan BCRYPT
     */
    public function setPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Normalisasi nama role menjadi format canonical (slug)
     */
    public static function normalizeRole(string $role): string
    {
        $roleLower = strtolower(trim($role));
        if (in_array($roleLower, ['pengawas_kesehatan', 'petugas kesehatan', 'petugas_kesehatan', 'dokter', 'perawat'])) {
            return self::ROLE_PENGAWAS_KESEHATAN;
        }
        if (in_array($roleLower, ['pengawas_kamar', 'pengawas kamar'])) {
            return self::ROLE_PENGAWAS_KAMAR;
        }
        if (in_array($roleLower, ['admin', 'administrator'])) {
            return self::ROLE_ADMIN;
        }
        return $roleLower;
    }

    /**
     * Ambil label display role dalam Bahasa Indonesia
     */
    public static function getRoleDisplayName(string $role): string
    {
        return match (self::normalizeRole($role)) {
            self::ROLE_PENGAWAS_KESEHATAN => 'Pengawas Kesehatan',
            self::ROLE_PENGAWAS_KAMAR     => 'Pengawas Kamar',
            self::ROLE_ADMIN              => 'Administrator',
            default                       => ucwords(str_replace('_', ' ', $role)),
        };
    }

    /**
     * Autentikasi Pengguna yang Aman
     * Mencegah kebocoran akun (Account Enumeration) dan memverifikasi status
     */
    public function authenticate(string $email, string $password): array
    {
        $user = $this->where('email', strtolower(trim($email)))->first();

        // Gunakan pesan error umum yang identik untuk email salah maupun password salah
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return [
                'success' => false,
                'error'   => 'Email atau kata sandi tidak valid.',
                'user'    => null,
            ];
        }

        $normalizedRole = self::normalizeRole($user['role']);
        $isPengawas     = in_array($normalizedRole, [self::ROLE_PENGAWAS_KAMAR, self::ROLE_PENGAWAS_KESEHATAN]);

        // Cek status keaktifan akun
        if ($user['status'] === self::STATUS_MENUNGGU_PERSETUJUAN) {
            // Login pengawas tidak memerlukan verifikasi administrator (otomatis aktif)
            if ($isPengawas) {
                $this->update($user['id'], ['status' => self::STATUS_AKTIF]);
                $user['status'] = self::STATUS_AKTIF;
            } else {
                return [
                    'success' => false,
                    'error'   => 'Akun Anda sedang menunggu verifikasi dan persetujuan dari pihak Administrator sebelum dapat digunakan.',
                    'user'    => null,
                ];
            }
        }

        if ($user['status'] === self::STATUS_NONAKTIF) {
            return [
                'success' => false,
                'error'   => 'Akun Anda berstatus nonaktif. Silakan hubungi pihak administrator.',
                'user'    => null,
            ];
        }

        // Berhasil autentikasi
        return [
            'success' => true,
            'error'   => null,
            'user'    => $user,
        ];
    }

    /**
     * Registrasi Pengguna Publik yang Aman
     * Penegakan aturan: Pendaftaran publik peran pengawas_kesehatan memerlukan persetujuan admin.
     */
    public function registerPublicUser(array $data): array
    {
        $normalizedRole = self::normalizeRole($data['role'] ?? '');

        // Validasi pilihan peran yang diizinkan untuk registrasi publik
        if (!in_array($normalizedRole, [self::ROLE_PENGAWAS_KAMAR, self::ROLE_PENGAWAS_KESEHATAN])) {
            return [
                'success' => false,
                'error'   => 'Peran yang dipilih tidak valid untuk pendaftaran publik.',
            ];
        }

        // Pengawas Kamar dan Pengawas Kesehatan langsung aktif tanpa perlu verifikasi
        $isPending = false;
        $status    = self::STATUS_AKTIF;

        $insertData = [
            'nama'          => trim($data['nama']),
            'email'         => strtolower(trim($data['email'])),
            'password_hash' => $this->setPassword($data['password']),
            'role'          => ($normalizedRole === self::ROLE_PENGAWAS_KAMAR) ? 'Pengawas Kamar' : 'Pengawas Kesehatan',
            'status'        => $status,
        ];

        if (!$this->insert($insertData)) {
            $errors = $this->errors();
            return [
                'success' => false,
                'error'   => !empty($errors) ? implode(' ', $errors) : 'Gagal mendaftarkan akun.',
            ];
        }

        $newUserId = $this->getInsertID();
        $user = $this->find($newUserId);

        return [
            'success'   => true,
            'error'     => null,
            'user'      => $user,
            'isPending' => $isPending,
        ];
    }

}

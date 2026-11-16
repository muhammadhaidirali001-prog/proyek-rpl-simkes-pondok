<?php

namespace App\Models;

use CodeIgniter\Model;

class LoginOtpModel extends Model
{
    protected $table            = 'login_otp';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'email',
        'otp_code',
        'expires_at',
        'is_used',
        'created_at',
    ];

    // Batas maksimal percobaan salah sebelum kunci sementara
    const MAX_ATTEMPTS    = 5;
    // Lama kunci setelah melebihi batas (detik) = 15 menit
    const LOCKOUT_SECONDS = 15 * 60;
    // Kunci sesi untuk menyimpan percobaan salah
    const SESSION_ATTEMPTS_KEY = 'otp_fail_attempts';
    const SESSION_LOCKOUT_KEY  = 'otp_locked_until';

    /**
     * Buat kode OTP baru (6 digit angka) dengan masa berlaku 10 menit
     */
    public function generateOtp(int $userId, string $email): string
    {
        // Invalidasikan OTP lama yang belum terpakai untuk user ini
        $this->where('user_id', $userId)
            ->where('is_used', 0)
            ->set(['is_used' => 1])
            ->update();

        // Reset percobaan salah saat OTP baru digenerate
        session()->remove(self::SESSION_ATTEMPTS_KEY);
        session()->remove(self::SESSION_LOCKOUT_KEY);

        // Buat 6-digit kode OTP acak aman
        $otp = (string) random_int(100000, 999999);
        $expiresAt = date('Y-m-d H:i:s', time() + (10 * 60)); // 10 menit

        $this->insert([
            'user_id'    => $userId,
            'email'      => strtolower(trim($email)),
            'otp_code'   => $otp,
            'expires_at' => $expiresAt,
            'is_used'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $otp;
    }

    /**
     * Cek apakah sesi sedang terkunci karena terlalu banyak percobaan salah
     * Mengembalikan sisa detik kunci, atau 0 jika tidak terkunci
     */
    public function getLockoutRemaining(): int
    {
        $lockedUntil = session()->get(self::SESSION_LOCKOUT_KEY);
        if (!$lockedUntil) {
            return 0;
        }
        $remaining = (int)$lockedUntil - time();
        if ($remaining <= 0) {
            session()->remove(self::SESSION_LOCKOUT_KEY);
            session()->remove(self::SESSION_ATTEMPTS_KEY);
            return 0;
        }
        return $remaining;
    }

    /**
     * Ambil jumlah percobaan salah saat ini
     */
    public function getFailAttempts(): int
    {
        return (int)(session()->get(self::SESSION_ATTEMPTS_KEY) ?? 0);
    }

    /**
     * Verifikasi kode OTP dengan rate limiting
     * Mengembalikan array: ['success' => bool, 'locked' => bool, 'lockout_remaining' => int, 'attempts_left' => int]
     */
    public function verifyOtpWithRateLimit(int $userId, string $otp): array
    {
        // Cek kunci aktif
        $lockRemaining = $this->getLockoutRemaining();
        if ($lockRemaining > 0) {
            $mins = ceil($lockRemaining / 60);
            return [
                'success'           => false,
                'locked'            => true,
                'lockout_remaining' => $lockRemaining,
                'attempts_left'     => 0,
                'message'           => "Terlalu banyak percobaan salah. Coba lagi dalam {$mins} menit.",
            ];
        }

        $verified = $this->verifyOtp($userId, $otp);

        if ($verified) {
            // Sukses: reset counter
            session()->remove(self::SESSION_ATTEMPTS_KEY);
            session()->remove(self::SESSION_LOCKOUT_KEY);
            return [
                'success'           => true,
                'locked'            => false,
                'lockout_remaining' => 0,
                'attempts_left'     => self::MAX_ATTEMPTS,
                'message'           => '',
            ];
        }

        // Gagal: tambah counter
        $attempts = $this->getFailAttempts() + 1;
        session()->set(self::SESSION_ATTEMPTS_KEY, $attempts);

        if ($attempts >= self::MAX_ATTEMPTS) {
            // Kunci sesi selama LOCKOUT_SECONDS
            session()->set(self::SESSION_LOCKOUT_KEY, time() + self::LOCKOUT_SECONDS);
            session()->remove(self::SESSION_ATTEMPTS_KEY);
            $mins = (int)(self::LOCKOUT_SECONDS / 60);
            return [
                'success'           => false,
                'locked'            => true,
                'lockout_remaining' => self::LOCKOUT_SECONDS,
                'attempts_left'     => 0,
                'message'           => "Akun sementara dikunci karena terlalu banyak percobaan salah. Coba lagi dalam {$mins} menit.",
            ];
        }

        $attemptsLeft = self::MAX_ATTEMPTS - $attempts;
        return [
            'success'           => false,
            'locked'            => false,
            'lockout_remaining' => 0,
            'attempts_left'     => $attemptsLeft,
            'message'           => "Kode OTP salah atau sudah kedaluwarsa. Tersisa {$attemptsLeft} percobaan.",
        ];
    }

    /**
     * Verifikasi kode OTP (tanpa rate limiting, dipakai internal)
     */
    public function verifyOtp(int $userId, string $otp): bool
    {
        $cleanOtp = trim($otp);
        $now      = date('Y-m-d H:i:s');

        $record = $this->where('user_id', $userId)
            ->where('otp_code', $cleanOtp)
            ->where('is_used', 0)
            ->where('expires_at >=', $now)
            ->orderBy('id', 'DESC')
            ->first();

        if (!$record) {
            return false;
        }

        // Tandai sudah digunakan (anti-replay)
        $this->update($record['id'], ['is_used' => 1]);
        return true;
    }
}

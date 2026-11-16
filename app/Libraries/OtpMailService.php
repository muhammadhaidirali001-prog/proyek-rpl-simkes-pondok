<?php

namespace App\Libraries;

use Config\Services;

class OtpMailService
{
    /**
     * Mengirim email kode OTP ke alamat email penerima
     * Mengembalikan array ['success' => bool, 'is_simulated' => bool, 'error' => ?string]
     */
    public function sendOtp(string $toEmail, string $userName, string $otpCode): array
    {
        $toEmail = trim($toEmail);
        $email = Services::email();

        // Ambil konfigurasi SMTP dari .env jika ada
        $smtpHost = env('email.SMTPHost') ?: (env('SMTP_HOST') ?: config('Email')->SMTPHost);
        $smtpUser = env('email.SMTPUser') ?: (env('SMTP_USER') ?: config('Email')->SMTPUser);
        $smtpPass = env('email.SMTPPass') ?: (env('SMTP_PASS') ?: config('Email')->SMTPPass);
        $smtpPort = env('email.SMTPPort') ?: (env('SMTP_PORT') ?: config('Email')->SMTPPort);
        $fromEmail = env('email.fromEmail') ?: (env('MAIL_FROM') ?: 'no-reply@simkes.pesantren.id');
        $fromName  = 'SIMKES Santri - Keamanan Akun';

        // Jika SMTP Host belum dikonfigurasi, gunakan mode simulasi (berhasil & disimpan di session/log)
        if (empty($smtpHost) || empty($smtpUser)) {
            log_message('info', "[SIMKES OTP SIMULASI] Kode OTP untuk {$toEmail} ({$userName}): {$otpCode}");
            return [
                'success'      => true,
                'is_simulated' => true,
                'error'        => null,
            ];
        }

        try {
            $email->initialize([
                'protocol'     => 'smtp',
                'SMTPHost'     => $smtpHost,
                'SMTPUser'     => $smtpUser,
                'SMTPPass'     => $smtpPass,
                'SMTPPort'     => (int) ($smtpPort ?: 587),
                'SMTPCrypto'   => 'tls',
                'mailType'     => 'html',
                'charset'      => 'utf-8',
                'wordWrap'     => true,
                'newline'      => "\r\n",
                'CRLF'         => "\r\n",
            ]);

            $email->setFrom($fromEmail, $fromName);
            $email->setTo($toEmail);
            $email->setSubject("Kode Verifikasi OTP Masuk SIMKES Santri: {$otpCode}");

            $htmlMessage = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; }
    .card { max-width: 500px; margin: 0 auto; background: #ffffff; border-radius: 14px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .header { background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); color: #ffffff; padding: 24px; text-align: center; }
    .content { padding: 24px; color: #1e293b; line-height: 1.6; }
    .otp-box { background: #f0fdfa; border: 2px dashed #0d9488; border-radius: 12px; padding: 16px; text-align: center; margin: 20px 0; }
    .otp-code { font-size: 32px; font-weight: 800; letter-spacing: 6px; color: #0f766e; }
    .footer { background: #f8fafc; border-top: 1px solid #f1f5f9; padding: 14px; text-align: center; font-size: 12px; color: #64748b; }
  </style>
</head>
<body>
  <div class="card">
    <div class="header">
      <h2 style="margin:0; font-size: 20px;">SIMKES Santri</h2>
      <p style="margin:4px 0 0; opacity: 0.85; font-size: 13px;">Sistem Manajemen Kesehatan Santri Pesantren</p>
    </div>
    <div class="content">
      <p>Assalamu'alaikum <strong>{$userName}</strong>,</p>
      <p>Berikut adalah kode verifikasi OTP untuk menyelesaikan proses masuk ke akun SIMKES Santri Anda:</p>
      
      <div class="otp-box">
        <div style="font-size: 12px; color: #0d9488; font-weight: 600; margin-bottom: 6px;">KODE VERIFIKASI RESMI</div>
        <div class="otp-code">{$otpCode}</div>
        <div style="font-size: 12px; color: #64748b; margin-top: 6px;">Berlaku selama 10 menit</div>
      </div>

      <p style="font-size: 13px; color: #475569;">
        <strong>Perhatian Keamanan:</strong> Jangan berikan kode OTP ini kepada siapapun demi keamanan data kesehatan santri di pesantren.
      </p>
    </div>
    <div class="footer">
      Email ini dikirim secara otomatis oleh SIMKES Santri. Jika Anda tidak merasa mencoba masuk, abaikan email ini.
    </div>
  </div>
</body>
</html>
HTML;

            $email->setMessage($htmlMessage);

            if ($email->send()) {
                return ['success' => true, 'is_simulated' => false, 'error' => null];
            }

            log_message('error', 'Gagal kirim email OTP: ' . $email->printDebugger(['headers']));
            // Fallback ke simulasi jika pengiriman SMTP gagal (agar user tidak terkunci)
            return [
                'success'      => true,
                'is_simulated' => true,
                'error'        => null,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Exception kirim email OTP: ' . $e->getMessage());
            return [
                'success'      => true,
                'is_simulated' => true,
                'error'        => null,
            ];
        }
    }
}

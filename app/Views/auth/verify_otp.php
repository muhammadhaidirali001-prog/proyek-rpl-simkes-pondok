<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title><?= esc($title ?? 'Verifikasi Kode OTP - SIMKES Santri') ?></title>

  <!-- Google Fonts: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 5.3.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Custom SIMKES CSS -->
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">

  <style>
    body {
      background: radial-gradient(circle at 10% 20%, rgba(13, 148, 136, 0.08) 0%, rgba(248, 250, 252, 1) 90%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem 1rem;
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .auth-card {
      width: 100%;
      max-width: 440px;
      border-radius: 18px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
      background: #ffffff;
      overflow: hidden;
    }
    .auth-header {
      background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
      padding: 2rem 2rem 1.5rem;
      color: #ffffff;
      text-align: center;
    }
    .auth-icon-badge {
      width: 56px;
      height: 56px;
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(4px);
      border-radius: 16px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.6rem;
      margin-bottom: 0.75rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    .otp-input-wrap {
      display: flex;
      gap: 10px;
      justify-content: center;
      margin: 0.5rem 0;
    }
    .otp-digit {
      width: 48px;
      height: 56px;
      text-align: center;
      font-size: 1.6rem;
      font-weight: 800;
      border: 2px solid #cbd5e1;
      border-radius: 12px;
      color: #0f766e;
      transition: all 0.2s ease;
      outline: none;
      caret-color: #0d9488;
    }
    .otp-digit:focus {
      border-color: #0d9488;
      box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.15);
    }
    .otp-digit.filled {
      border-color: #0d9488;
      background: #f0fdfa;
    }
    /* Hidden input untuk form submit */
    #otp_code { display: none; }

    /* Timer countdown */
    .otp-timer-wrap {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
      padding: 7px 18px;
      border-radius: 50px;
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      font-size: 0.82rem;
      font-weight: 600;
      color: #16a34a;
      transition: all 0.4s ease;
      width: fit-content;
      margin: 0 auto 0.75rem;
    }
    .otp-timer-wrap.warning {
      background: #fefce8;
      border-color: #fde68a;
      color: #ca8a04;
    }
    .otp-timer-wrap.danger {
      background: #fff1f2;
      border-color: #fecdd3;
      color: #e11d48;
      animation: pulse-danger 1s ease infinite;
    }
    @keyframes pulse-danger {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.65; }
    }

    /* Badge percobaan */
    .attempt-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 0.74rem;
      padding: 3px 10px;
      border-radius: 20px;
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #dc2626;
      font-weight: 600;
    }

    /* OTP notification box */
    .otp-preview-box {
      background: #eff6ff;
      border: 1px dashed #3b82f6;
      border-radius: 12px;
      padding: 12px 14px;
      margin-bottom: 1rem;
    }
    .otp-preview-code {
      font-size: 1.5rem;
      font-weight: 800;
      letter-spacing: 10px;
      color: #1d4ed8;
      display: block;
      text-align: center;
      padding: 6px 0;
    }

    /* Tombol verifikasi */
    .btn-verify {
      background: linear-gradient(135deg, #0d9488, #0f766e);
      color: #fff;
      border: none;
      font-weight: 600;
      padding: 11px;
      border-radius: 10px;
      font-size: 0.95rem;
      transition: all 0.2s ease;
    }
    .btn-verify:hover:not(:disabled) {
      transform: translateY(-1px);
      box-shadow: 0 4px 14px rgba(13, 148, 136, 0.35);
    }
    .btn-verify:disabled {
      opacity: 0.65;
      cursor: not-allowed;
    }
  </style>
</head>
<body>

  <div class="auth-card">
    <!-- Header -->
    <div class="auth-header">
      <div class="auth-icon-badge">
        <i class="bi bi-shield-lock-fill"></i>
      </div>
      <h4 class="fw-bold mb-1">Verifikasi Kode Masuk</h4>
      <p class="mb-0 text-white-50 small">Kode OTP dikirim untuk keamanan akun SIMKES</p>
    </div>

    <!-- Form Content -->
    <div class="p-4">

      <!-- Email Tujuan -->
      <div class="text-center mb-3">
        <div class="small text-muted mb-1">Kode OTP telah dikirimkan ke:</div>
        <div class="fw-bold text-dark mb-2" style="font-size: 0.95rem;">
          <i class="bi bi-envelope-at" style="color:#0d9488;"></i>
          <?= esc($maskedEmail ?? 'email Anda') ?>
        </div>

        <!-- Timer Countdown -->
        <div class="otp-timer-wrap" id="otpTimerBox">
          <i class="bi bi-clock" id="timerIcon"></i>
          <span>Berlaku: <strong id="otpCountdown">10:00</strong></span>
        </div>

        <!-- Badge percobaan salah -->
        <?php if (!empty($otpAttempts) && $otpAttempts > 0): ?>
          <div class="text-center mt-1 mb-2">
            <span class="attempt-badge">
              <i class="bi bi-shield-exclamation"></i>
              Percobaan salah: <?= (int)$otpAttempts ?>/5
            </span>
          </div>
        <?php endif; ?>
      </div>

      <!-- Banner Terkunci (rate limit aktif) -->
      <?php if (!empty($isLocked)): ?>
        <div class="alert border-0 rounded-3 p-3 mb-3" style="background:#fff1f2; border:1px solid #fecdd3 !important;">
          <div class="d-flex align-items-center gap-2 fw-semibold mb-1 text-danger">
            <i class="bi bi-lock-fill"></i>
            <span>Sesi Dikunci Sementara</span>
          </div>
          <div class="small" style="color:#be123c;">
            Terlalu banyak percobaan salah. Tunggu
            <strong id="lockCountdown"><?= (int)$lockMins ?> menit</strong>
            sebelum mencoba lagi.
          </div>
        </div>
      <?php endif; ?>

      <!-- Notifikasi Banner OTP Simulasi (jika SMTP lokal belum diset) -->
      <?php if (!empty($otpPreview)): ?>
        <div class="otp-preview-box mb-3">
          <div class="d-flex align-items-center gap-2 text-primary fw-semibold small mb-2">
            <i class="bi bi-bell-fill"></i>
            <span>Notifikasi Kode OTP Masuk</span>
          </div>
          <span class="otp-preview-code"><?= esc($otpPreview) ?></span>
          <div class="text-center text-muted mt-2" style="font-size: 0.72rem;">
            *Salin kode di atas, lalu masukkan ke kolom di bawah
          </div>
        </div>
      <?php endif; ?>

      <!-- Flash Alerts -->
      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 small" role="alert">
          <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
          <div><?= esc(session()->getFlashdata('error')) ?></div>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 small" role="alert">
          <i class="bi bi-check-circle-fill flex-shrink-0"></i>
          <div><?= esc(session()->getFlashdata('success')) ?></div>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <!-- OTP Form dengan 6 kotak digit -->
      <form action="<?= base_url('/verify-otp') ?>" method="POST" autocomplete="off" id="otpForm">
        <?= csrf_field() ?>
        <!-- Hidden input sesungguhnya yang dikirim -->
        <input type="hidden" name="otp_code" id="otp_code">

        <div class="mb-1">
          <label class="form-label small fw-semibold text-secondary text-center d-block mb-2">
            Masukkan 6 Digit Kode OTP
          </label>
          <!-- 6 Kotak Digit OTP -->
          <div class="otp-input-wrap" id="otpDigitWrap">
            <?php for ($i = 1; $i <= 6; $i++): ?>
              <input type="text" class="otp-digit" id="d<?= $i ?>"
                     maxlength="1" inputmode="numeric" pattern="[0-9]"
                     autocomplete="one-time-code"
                     <?= (!empty($isLocked)) ? 'disabled' : '' ?>>
            <?php endfor; ?>
          </div>
          <div class="text-center text-muted mt-2" style="font-size: 0.75rem;">
            Kode berlaku selama 10 menit sejak dikirimkan
          </div>
        </div>

        <div class="d-grid mt-4">
          <button type="submit" class="btn-verify w-100" id="submitOtpBtn"
                  <?= (!empty($isLocked)) ? 'disabled' : '' ?>>
            <i class="bi bi-check-circle me-1"></i> Verifikasi &amp; Masuk
          </button>
        </div>
      </form>

      <!-- Kirim Ulang OTP & Kembali -->
      <div class="d-flex align-items-center justify-content-between mt-3 pt-3 border-top small">
        <form action="<?= base_url('/resend-otp') ?>" method="POST" class="d-inline">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-link text-decoration-none p-0 small fw-semibold"
                  style="color: #0d9488;" <?= (!empty($isLocked)) ? 'disabled' : '' ?>>
            <i class="bi bi-arrow-clockwise me-1"></i>Kirim Ulang Kode
          </button>
        </form>
        <a href="<?= base_url('/login') ?>" class="text-decoration-none text-muted">
          <i class="bi bi-arrow-left me-1"></i>Ganti Akun
        </a>
      </div>

    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  (function () {
    'use strict';

    // =========================================================
    // 1) 6-KOTAK DIGIT OTP INTERAKTIF
    // =========================================================
    const digits   = Array.from(document.querySelectorAll('.otp-digit'));
    const hidden   = document.getElementById('otp_code');
    const form     = document.getElementById('otpForm');
    const submitBtn = document.getElementById('submitOtpBtn');

    function getOtpValue() {
      return digits.map(d => d.value).join('');
    }

    function syncHidden() {
      if (hidden) hidden.value = getOtpValue();
    }

    digits.forEach(function (input, idx) {
      // Hanya angka
      input.addEventListener('keypress', function (e) {
        if (!/[0-9]/.test(e.key)) e.preventDefault();
      });

      input.addEventListener('input', function (e) {
        // Hapus non-digit, ambil 1 karakter saja
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 1);
        this.classList.toggle('filled', this.value.length > 0);

        syncHidden();

        // Pindah ke kotak berikutnya
        if (this.value && idx < digits.length - 1) {
          digits[idx + 1].focus();
        }

        // Auto-submit jika semua terisi
        if (getOtpValue().length === 6) {
          setTimeout(() => form.submit(), 120);
        }
      });

      // Backspace: hapus & kembali ke kotak sebelumnya
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Backspace') {
          if (!this.value && idx > 0) {
            digits[idx - 1].value = '';
            digits[idx - 1].classList.remove('filled');
            digits[idx - 1].focus();
            syncHidden();
          }
        }
        // Navigasi kiri/kanan dengan arrow key
        if (e.key === 'ArrowLeft' && idx > 0) digits[idx - 1].focus();
        if (e.key === 'ArrowRight' && idx < digits.length - 1) digits[idx + 1].focus();
      });

      // Paste: distribusikan digit ke kotak
      input.addEventListener('paste', function (e) {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData)
                         .getData('text').replace(/[^0-9]/g, '').slice(0, 6);
        pasted.split('').forEach(function (ch, i) {
          if (digits[i]) {
            digits[i].value = ch;
            digits[i].classList.add('filled');
          }
        });
        syncHidden();
        // Fokus ke kotak terakhir yg terisi
        const last = Math.min(pasted.length, digits.length) - 1;
        if (digits[last]) digits[last].focus();
        if (pasted.length === 6) setTimeout(() => form.submit(), 150);
      });
    });

    // Fokus kotak pertama saat halaman load
    if (digits[0] && !digits[0].disabled) digits[0].focus();

    // =========================================================
    // 2) COUNTDOWN TIMER OTP (10 menit sejak di-generate)
    // =========================================================
    const otpIssuedAt    = <?= json_encode((int)(session()->get('pending_otp_login')['timestamp'] ?? time())) ?>;
    const EXPIRY_SECONDS = 10 * 60;

    const countdownEl = document.getElementById('otpCountdown');
    const timerBox    = document.getElementById('otpTimerBox');
    const timerIcon   = document.getElementById('timerIcon');

    function updateTimer() {
      const elapsed   = Math.floor(Date.now() / 1000) - otpIssuedAt;
      const remaining = EXPIRY_SECONDS - elapsed;

      if (remaining <= 0) {
        if (countdownEl) countdownEl.textContent = '00:00';
        if (timerBox)    { timerBox.classList.remove('warning'); timerBox.classList.add('danger'); }
        if (timerIcon)   timerIcon.className = 'bi bi-clock-history';
        if (submitBtn && !submitBtn.disabled) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<i class="bi bi-clock-history me-1"></i> Kode Sudah Kedaluwarsa';
        }
        // Nonaktifkan semua kotak digit
        digits.forEach(d => d.disabled = true);
        clearInterval(timerInterval);
        return;
      }

      const mins = String(Math.floor(remaining / 60)).padStart(2, '0');
      const secs = String(remaining % 60).padStart(2, '0');
      if (countdownEl) countdownEl.textContent = mins + ':' + secs;

      if (remaining <= 30) {
        timerBox?.classList.add('danger');
        timerBox?.classList.remove('warning');
        if (timerIcon) timerIcon.className = 'bi bi-clock-history';
      } else if (remaining <= 120) {
        timerBox?.classList.add('warning');
        timerBox?.classList.remove('danger');
        if (timerIcon) timerIcon.className = 'bi bi-clock-history';
      }
    }

    updateTimer();
    const timerInterval = setInterval(updateTimer, 1000);

    // =========================================================
    // 3) COUNTDOWN LOCKOUT (jika terkunci rate-limit)
    // =========================================================
    <?php if (!empty($isLocked) && !empty($lockRemaining)): ?>
    (function() {
      let lockSecs = <?= (int)$lockRemaining ?>;
      const lockEl = document.getElementById('lockCountdown');
      const lockInterval = setInterval(function() {
        lockSecs--;
        if (lockSecs <= 0) {
          clearInterval(lockInterval);
          window.location.reload();
          return;
        }
        const m = Math.floor(lockSecs / 60);
        const s = lockSecs % 60;
        if (lockEl) {
          lockEl.textContent = m > 0
            ? m + ' menit ' + String(s).padStart(2,'0') + ' detik'
            : String(s).padStart(2,'0') + ' detik';
        }
      }, 1000);
    })();
    <?php endif; ?>

  })();
  </script>
</body>
</html>

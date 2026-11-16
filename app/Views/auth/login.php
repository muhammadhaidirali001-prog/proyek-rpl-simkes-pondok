<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title><?= esc($title ?? 'Masuk - SIMKES Santri') ?></title>

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
    }
    .auth-card {
      width: 100%;
      max-width: 440px;
      border-radius: 18px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
      background: #ffffff;
      overflow: hidden;
    }
    .auth-header {
      background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
      padding: 2.25rem 2rem 1.75rem;
      color: #ffffff;
      text-align: center;
      position: relative;
    }
    .auth-icon-badge {
      width: 54px;
      height: 54px;
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(4px);
      border-radius: 14px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.6rem;
      margin-bottom: 0.85rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    /* Real-time Toast Notification */
    .rt-toast-container {
      position: fixed;
      top: 24px;
      right: 24px;
      z-index: 9999;
      display: flex;
      flex-direction: column;
      gap: 12px;
      pointer-events: none;
    }
    .rt-toast {
      pointer-events: auto;
      min-width: 320px;
      max-width: 380px;
      background: #ffffff;
      border-radius: 14px;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12), 0 4px 10px rgba(0, 0, 0, 0.04);
      border-left: 5px solid #0d9488;
      padding: 14px 16px;
      display: flex;
      align-items: flex-start;
      gap: 12px;
      transform: translateX(120%);
      opacity: 0;
      transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .rt-toast.show {
      transform: translateX(0);
      opacity: 1;
    }
    .rt-toast.gmail {
      border-left-color: #ea4335;
    }
    .rt-toast.loading-toast {
      border-left-color: #0d9488;
    }
    .rt-toast-icon {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      flex-shrink: 0;
    }
    .rt-toast.gmail .rt-toast-icon {
      background: #fee2e2;
      color: #dc2626;
    }
    .rt-toast.loading-toast .rt-toast-icon {
      background: #ccfbf1;
      color: #0d9488;
    }
    .rt-toast-content {
      flex: 1;
    }
    .rt-toast-title {
      font-size: 0.88rem;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 2px;
    }
    .rt-toast-desc {
      font-size: 0.76rem;
      color: #64748b;
      line-height: 1.4;
    }
  </style>
</head>
<body>

  <!-- Real-time Toast Container -->
  <div class="rt-toast-container" id="toastContainer"></div>

  <div class="auth-card">
    <!-- Header -->
    <div class="auth-header">
      <div class="auth-icon-badge">
        <i class="bi bi-heart-pulse-fill"></i>
      </div>
      <h4 class="fw-bold mb-1">SIMKES Santri</h4>
      <p class="mb-0 text-white-50 small">Sistem Manajemen Kesehatan Santri Pesantren</p>
    </div>

    <!-- Form Content -->
    <div class="p-4 p-sm-4">
      <div class="text-center mb-3">
        <h5 class="fw-bold text-dark mb-1">Masuk ke Akun Anda</h5>
        <p class="text-muted small">Silakan masukkan email dan kata sandi yang telah terdaftar</p>
      </div>

      <!-- Flash Alerts -->
      <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 small" role="alert">
          <i class="bi bi-check-circle-fill flex-shrink-0"></i>
          <div><?= esc(session()->getFlashdata('success')) ?></div>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if (session()->getFlashdata('warning')): ?>
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2 small" role="alert">
          <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
          <div><?= esc(session()->getFlashdata('warning')) ?></div>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 small" role="alert">
          <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
          <div><?= esc(session()->getFlashdata('error')) ?></div>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger alert-dismissible fade show small" role="alert">
          <ul class="mb-0 ps-3">
            <?php foreach (session()->getFlashdata('errors') as $err): ?>
              <li><?= esc($err) ?></li>
            <?php endforeach; ?>
          </ul>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <!-- Login Form -->
      <form action="<?= base_url('/login') ?>" method="POST" autocomplete="off">
        <?= csrf_field() ?>

        <!-- Email Field -->
        <div class="mb-3">
          <label for="email" class="form-label small fw-semibold text-secondary">Alamat Email</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" 
                   value="<?= old('email') ?>" placeholder="nama@simkes.pesantren.id" required autofocus>
          </div>
          <!-- Gmail OTP Notice: tampil otomatis saat user ketik @gmail.com -->
          <div id="gmailOtpNotice" class="mt-2 d-none">
            <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-3 small"
                 style="background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8;">
              <i class="bi bi-shield-lock-fill flex-shrink-0"></i>
              <span>Akun Gmail akan memerlukan <strong>verifikasi kode OTP</strong> saat login.</span>
            </div>
          </div>
        </div>

        <!-- Password Field -->
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label for="password" class="form-label small fw-semibold text-secondary mb-0">Kata Sandi</label>
          </div>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control border-start-0 border-end-0 ps-0" id="password" name="password" 
                   placeholder="••••••••" required>
            <button class="btn btn-outline-light border text-muted border-start-0" type="button" id="togglePassword">
              <i class="bi bi-eye" id="eyeIcon"></i>
            </button>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="d-grid mt-4">
          <button type="submit" id="btnSubmitLogin" class="btn text-white fw-semibold py-2 shadow-sm" style="background: linear-gradient(135deg, #0d9488, #0f766e);">
            <i class="bi bi-box-arrow-in-right me-1" id="iconSubmitLogin"></i> <span id="textSubmitLogin">Masuk ke Sistem</span>
          </button>
        </div>
      </form>

      <!-- Registration Link -->
      <div class="text-center mt-4 pt-2 border-top">
        <p class="text-muted small mb-0">
          Belum memiliki akun petugas? 
          <a href="<?= base_url('/register') ?>" class="text-decoration-none fw-semibold" style="color: #0d9488;">
            Daftar Sekarang
          </a>
        </p>
      </div>

    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    if (togglePassword && passwordInput && eyeIcon) {
      togglePassword.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        eyeIcon.classList.toggle('bi-eye');
        eyeIcon.classList.toggle('bi-eye-slash');
      });
    }

    // Gmail OTP Notice & Real-time Toast saat user mengetik @gmail.com
    const emailInput      = document.getElementById('email');
    const gmailOtpNotice  = document.getElementById('gmailOtpNotice');
    const toastContainer  = document.getElementById('toastContainer');
    const loginForm       = document.querySelector('form[action*="login"]');
    const btnSubmitLogin  = document.getElementById('btnSubmitLogin');
    const iconSubmitLogin = document.getElementById('iconSubmitLogin');
    const textSubmitLogin = document.getElementById('textSubmitLogin');

    let toastShown = false;

    function showRealtimeToast(title, desc, type = 'gmail', icon = 'bi-shield-lock-fill') {
      const toast = document.createElement('div');
      toast.className = `rt-toast ${type}`;
      toast.innerHTML = `
        <div class="rt-toast-icon">
          <i class="bi ${icon}"></i>
        </div>
        <div class="rt-toast-content">
          <div class="rt-toast-title">${title}</div>
          <div class="rt-toast-desc">${desc}</div>
        </div>
      `;
      toastContainer.appendChild(toast);

      // Trigger animation
      setTimeout(() => toast.classList.add('show'), 50);

      // Auto dismiss after 6s
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
      }, 6000);
    }

    if (emailInput && gmailOtpNotice) {
      function checkGmail() {
        const val = emailInput.value.trim().toLowerCase();
        if (/@gmail\.com$/.test(val)) {
          gmailOtpNotice.classList.remove('d-none');
          if (!toastShown) {
            toastShown = true;
            showRealtimeToast(
              'Deteksi Akun Gmail',
              'Sistem akan mengirimkan 6 digit kode OTP langsung ke aplikasi Gmail di HP Anda saat tombol login ditekan.',
              'gmail',
              'bi-envelope-check-fill'
            );
          }
        } else {
          gmailOtpNotice.classList.add('d-none');
          toastShown = false;
        }
      }
      emailInput.addEventListener('input', checkGmail);
      emailInput.addEventListener('blur',  checkGmail);
      checkGmail();
    }

    // Real-time notification saat form login di-submit
    if (loginForm && btnSubmitLogin) {
      loginForm.addEventListener('submit', function () {
        const emailVal = emailInput ? emailInput.value.trim().toLowerCase() : '';
        const isGmail  = /@gmail\.com$/.test(emailVal);

        // Ubah tombol jadi loading spinner
        btnSubmitLogin.disabled = true;
        if (iconSubmitLogin) {
          iconSubmitLogin.className = 'spinner-border spinner-border-sm me-1';
        }
        if (textSubmitLogin) {
          textSubmitLogin.textContent = isGmail ? 'Mengirim OTP ke Gmail...' : 'Memverifikasi...';
        }

        if (isGmail) {
          showRealtimeToast(
            'Sedang Mengirim Kode OTP...',
            'Menghubungkan ke server Gmail untuk mengirimkan kode ke HP Anda. Mohon tunggu...',
            'loading-toast',
            'bi-send-fill'
          );
        }
      });
    }
  </script>
</body>
</html>

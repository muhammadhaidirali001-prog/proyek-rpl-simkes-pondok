<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title><?= esc($title ?? 'Pendaftaran Akun - SIMKES Santri') ?></title>

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
      padding: 2rem 1rem;
    }
    .register-card {
      width: 100%;
      max-width: 520px;
      border-radius: 18px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
      background: #ffffff;
      overflow: hidden;
    }
    .register-header {
      background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
      padding: 2rem 2rem 1.5rem;
      color: #ffffff;
      text-align: center;
    }
    .register-icon-badge {
      width: 50px;
      height: 50px;
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(4px);
      border-radius: 14px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 0.75rem;
    }
  </style>
</head>
<body>

  <div class="register-card">
    <!-- Header -->
    <div class="register-header">
      <div class="register-icon-badge">
        <i class="bi bi-person-plus-fill"></i>
      </div>
      <h4 class="fw-bold mb-1">Registrasi Petugas</h4>
      <p class="mb-0 text-white-50 small">Sistem Manajemen Kesehatan Santri Pesantren</p>
    </div>

    <!-- Form Content -->
    <div class="p-4 p-sm-4">

      <!-- Flash Alerts -->
      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 small" role="alert">
          <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
          <div><?= esc(session()->getFlashdata('error')) ?></div>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger alert-dismissible fade show small" role="alert">
          <div class="fw-semibold mb-1">Periksa kembali data pendaftaran:</div>
          <ul class="mb-0 ps-3">
            <?php foreach (session()->getFlashdata('errors') as $err): ?>
              <li><?= esc($err) ?></li>
            <?php endforeach; ?>
          </ul>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <!-- Form -->
      <form action="<?= base_url('/register') ?>" method="POST" autocomplete="off">
        <?= csrf_field() ?>

        <!-- Nama Lengkap -->
        <div class="mb-3">
          <label for="nama" class="form-label small fw-semibold text-secondary">Nama Lengkap & Gelar</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
            <input type="text" class="form-control border-start-0 ps-0" id="nama" name="nama" 
                   value="<?= old('nama') ?>" placeholder="Ustadz / Petugas Kesehatan" required autofocus>
          </div>
        </div>

        <!-- Email -->
        <div class="mb-3">
          <label for="email" class="form-label small fw-semibold text-secondary">Alamat Email Aktif</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" 
                   value="<?= old('email') ?>" placeholder="nama@simkes.pesantren.id" required>
          </div>
        </div>

        <!-- Pilihan Peran -->
        <div class="mb-3">
          <label for="role" class="form-label small fw-semibold text-secondary">Pilihan Peran Petugas</label>
          <select class="form-select" id="role" name="role" required>
            <option value="" disabled <?= old('role') ? '' : 'selected' ?>>Pilih Peran Petugas...</option>
            <option value="pengawas_kamar" <?= old('role') === 'pengawas_kamar' ? 'selected' : '' ?>>
              Pengawas Kamar / Asrama
            </option>
            <option value="pengawas_kesehatan" <?= old('role') === 'pengawas_kesehatan' ? 'selected' : '' ?>>
              Pengawas Kesehatan / Poskestren
            </option>
          </select>
        </div>

        <!-- Password & Konfirmasi Password Row -->
        <div class="row g-2 mb-3">
          <div class="col-sm-6">
            <label for="password" class="form-label small fw-semibold text-secondary">Kata Sandi</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
              <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" 
                     placeholder="Min. 6 karakter" required>
            </div>
          </div>
          <div class="col-sm-6">
            <label for="password_confirm" class="form-label small fw-semibold text-secondary">Konfirmasi Sandi</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-shield-check"></i></span>
              <input type="password" class="form-control border-start-0 ps-0" id="password_confirm" name="password_confirm" 
                     placeholder="Ulangi sandi" required>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="d-grid mt-4">
          <button type="submit" class="btn text-white fw-semibold py-2 shadow-sm" style="background: linear-gradient(135deg, #0d9488, #0f766e);">
            <i class="bi bi-check2-circle me-1"></i> Daftarkan Akun Petugas
          </button>
        </div>
      </form>

      <!-- Login Link -->
      <div class="text-center mt-4 pt-2 border-top">
        <p class="text-muted small mb-0">
          Sudah memiliki akun terdaftar? 
          <a href="<?= base_url('/login') ?>" class="text-decoration-none fw-semibold" style="color: #0d9488;">
            Masuk di Sini
          </a>
        </p>
      </div>

    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title ?? 'Pengaturan Email SMTP - SIMKES Santri') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0">

  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-2 small text-muted">
      <li class="breadcrumb-item"><a href="<?= base_url('/dashboard') ?>" class="text-decoration-none text-muted">Dashboard</a></li>
      <li class="breadcrumb-item active" aria-current="page">Pengaturan Email SMTP</li>
    </ol>
  </nav>

  <!-- Flash Alerts -->
  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
      <i class="bi bi-check-circle-fill fs-5 flex-shrink-0"></i>
      <div class="fw-medium"><?= esc(session()->getFlashdata('success')) ?></div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
      <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
      <div class="fw-medium"><?= esc(session()->getFlashdata('error')) ?></div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('warning')): ?>
    <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
      <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
      <div class="fw-medium"><?= esc(session()->getFlashdata('warning')) ?></div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show small mb-4" role="alert">
      <ul class="mb-0 ps-3">
        <?php foreach (session()->getFlashdata('errors') as $err): ?>
          <li><?= esc($err) ?></li>
        <?php endforeach; ?>
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Header -->
  <div class="d-flex align-items-center gap-3 mb-4">
    <div style="width:48px;height:48px;background:linear-gradient(135deg,#0d9488,#0f766e);border-radius:14px;display:flex;align-items:center;justify-content:center;">
      <i class="bi bi-envelope-at-fill text-white fs-4"></i>
    </div>
    <div>
      <h5 class="fw-bold mb-0 text-dark">Pengaturan Email SMTP</h5>
      <p class="text-muted small mb-0">Konfigurasi server email untuk pengiriman kode OTP dan notifikasi sistem</p>
    </div>
  </div>

  <div class="row g-4">
    <!-- Kolom Kiri: Form SMTP -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom py-3 rounded-top-4">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-gear-fill text-teal"></i>
            <span class="fw-semibold">Konfigurasi Server SMTP</span>
          </div>
        </div>
        <div class="card-body p-4">
          <form action="<?= base_url('/admin/pengaturan-email') ?>" method="POST" id="smtpForm">
            <?= csrf_field() ?>

            <!-- SMTP Host -->
            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">
                <i class="bi bi-server me-1"></i>SMTP Host
              </label>
              <input type="text" class="form-control" name="smtp_host"
                     value="<?= esc($smtp_host) ?>"
                     placeholder="smtp.gmail.com">
              <div class="form-text">Untuk Gmail: <code>smtp.gmail.com</code></div>
            </div>

            <!-- SMTP Port & Enkripsi -->
            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label small fw-semibold text-secondary">
                  <i class="bi bi-plug me-1"></i>Port SMTP
                </label>
                <input type="number" class="form-control" name="smtp_port"
                       value="<?= esc($smtp_port ?? '587') ?>"
                       placeholder="587" min="1" max="65535">
                <div class="form-text">TLS: 587 · SSL: 465</div>
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold text-secondary">
                  <i class="bi bi-lock me-1"></i>Enkripsi
                </label>
                <select class="form-select" name="smtp_crypto">
                  <option value="tls"  <?= ($smtp_crypto ?? 'tls') === 'tls'  ? 'selected' : '' ?>>TLS (Disarankan)</option>
                  <option value="ssl"  <?= ($smtp_crypto ?? '') === 'ssl'  ? 'selected' : '' ?>>SSL</option>
                  <option value=""     <?= ($smtp_crypto ?? '') === ''     ? 'selected' : '' ?>>Tidak Ada</option>
                </select>
              </div>
            </div>

            <!-- SMTP Username -->
            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">
                <i class="bi bi-person me-1"></i>SMTP Username (Email Pengirim)
              </label>
              <input type="email" class="form-control" name="smtp_user"
                     value="<?= esc($smtp_user) ?>"
                     placeholder="simkes@gmail.com">
              <div class="form-text">Gunakan akun Gmail yang sudah diaktifkan App Password</div>
            </div>

            <!-- SMTP Password -->
            <div class="mb-3">
              <label class="form-label small fw-semibold text-secondary">
                <i class="bi bi-key me-1"></i>SMTP Password / App Password
              </label>
              <div class="input-group">
                <input type="password" class="form-control" name="smtp_pass"
                       id="smtpPassInput"
                       placeholder="<?= !empty($smtp_pass) ? '(tersimpan - kosongkan untuk tidak mengubah)' : 'xxxx xxxx xxxx xxxx' ?>">
                <button class="btn btn-outline-secondary" type="button" id="toggleSmtpPass">
                  <i class="bi bi-eye" id="eyeSmtpPass"></i>
                </button>
              </div>
              <?php if (!empty($smtp_pass)): ?>
                <div class="form-text text-success"><i class="bi bi-check-circle me-1"></i>Password sudah tersimpan. Kosongkan field ini untuk mempertahankan password lama.</div>
              <?php else: ?>
                <div class="form-text">Untuk Gmail: buat <strong>App Password</strong> di <a href="https://myaccount.google.com/apppasswords" target="_blank" class="text-decoration-none">myaccount.google.com/apppasswords</a></div>
              <?php endif; ?>
            </div>

            <!-- From Email & Name -->
            <div class="row g-3 mb-4">
              <div class="col-7">
                <label class="form-label small fw-semibold text-secondary">
                  <i class="bi bi-envelope me-1"></i>Email Pengirim (From)
                </label>
                <input type="email" class="form-control" name="mail_from"
                       value="<?= esc($mail_from) ?>"
                       placeholder="simkes@gmail.com">
              </div>
              <div class="col-5">
                <label class="form-label small fw-semibold text-secondary">
                  <i class="bi bi-card-text me-1"></i>Nama Pengirim
                </label>
                <input type="text" class="form-control" name="mail_name"
                       value="<?= esc($mail_name ?? 'SIMKES Santri') ?>"
                       placeholder="SIMKES Santri">
              </div>
            </div>

            <!-- Tombol Simpan -->
            <div class="d-flex gap-2">
              <button type="submit" class="btn text-white fw-semibold px-4"
                      style="background:linear-gradient(135deg,#0d9488,#0f766e);">
                <i class="bi bi-floppy me-1"></i> Simpan Konfigurasi
              </button>
              <a href="<?= base_url('/users') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Kolom Kanan: Status & Uji Koneksi -->
    <div class="col-lg-5">

      <!-- Status Konfigurasi -->
      <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-header bg-white border-bottom py-3 rounded-top-4">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-info-circle-fill text-primary"></i>
            <span class="fw-semibold">Status Konfigurasi</span>
          </div>
        </div>
        <div class="card-body p-3">
          <?php
            $configured = !empty($smtp_host) && !empty($smtp_user) && !empty($smtp_pass);
          ?>
          <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-3
                       <?= $configured ? 'bg-success bg-opacity-10 border border-success-subtle' : 'bg-warning bg-opacity-10 border border-warning-subtle' ?>">
            <i class="bi <?= $configured ? 'bi-check-circle-fill text-success' : 'bi-exclamation-circle-fill text-warning' ?> fs-4"></i>
            <div>
              <div class="fw-semibold small <?= $configured ? 'text-success' : 'text-warning' ?>">
                <?= $configured ? 'SMTP Aktif' : 'Belum Dikonfigurasi' ?>
              </div>
              <div class="text-muted" style="font-size:0.75rem;">
                <?= $configured
                    ? "Host: {$smtp_host}:{$smtp_port}"
                    : 'OTP ditampilkan di halaman (mode simulasi)' ?>
              </div>
            </div>
          </div>

          <!-- Detail Konfigurasi -->
          <table class="table table-sm table-borderless small mb-0">
            <tbody>
              <tr>
                <td class="text-muted pe-2 py-1" style="width:40%"><i class="bi bi-server me-1"></i>Host</td>
                <td class="fw-medium py-1"><?= $smtp_host ? esc($smtp_host) : '<span class="text-muted fst-italic">-</span>' ?></td>
              </tr>
              <tr>
                <td class="text-muted pe-2 py-1"><i class="bi bi-plug me-1"></i>Port</td>
                <td class="fw-medium py-1"><?= esc($smtp_port ?? '-') ?></td>
              </tr>
              <tr>
                <td class="text-muted pe-2 py-1"><i class="bi bi-lock me-1"></i>Enkripsi</td>
                <td class="fw-medium py-1"><?= strtoupper($smtp_crypto ?: '-') ?></td>
              </tr>
              <tr>
                <td class="text-muted pe-2 py-1"><i class="bi bi-person me-1"></i>User</td>
                <td class="fw-medium py-1"><?= $smtp_user ? esc($smtp_user) : '<span class="text-muted fst-italic">-</span>' ?></td>
              </tr>
              <tr>
                <td class="text-muted pe-2 py-1"><i class="bi bi-key me-1"></i>Password</td>
                <td class="fw-medium py-1"><?= $smtp_pass ? '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Tersimpan</span>' : '<span class="text-muted fst-italic">-</span>' ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Uji Kirim Email -->
      <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-header bg-white border-bottom py-3 rounded-top-4">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-send-fill text-teal"></i>
            <span class="fw-semibold">Uji Koneksi SMTP</span>
          </div>
        </div>
        <div class="card-body p-3">
          <p class="small text-muted mb-3">
            Kirim email percobaan untuk memastikan konfigurasi SMTP berjalan dengan benar.
          </p>
          <form action="<?= base_url('/admin/pengaturan-email/test') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="mb-2">
              <label class="form-label small fw-semibold text-secondary">Email Tujuan Uji</label>
              <input type="email" class="form-control form-control-sm" name="test_email"
                     value="<?= esc(session()->get('email')) ?>"
                     placeholder="email@gmail.com" required>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-teal w-100 fw-semibold"
                    style="border-color:#0d9488; color:#0d9488;"
                    <?= !$configured ? 'disabled title="Konfigurasi SMTP dulu sebelum menguji"' : '' ?>>
              <i class="bi bi-send me-1"></i> Kirim Email Uji Sekarang
            </button>
          </form>
        </div>
      </div>

      <!-- Panduan Gmail App Password -->
      <div class="card border-0 rounded-4" style="background:#f0fdf4; border:1px solid #bbf7d0 !important;">
        <div class="card-body p-3">
          <div class="fw-semibold small text-success mb-2">
            <i class="bi bi-lightbulb-fill me-1"></i>Panduan Gmail App Password
          </div>
          <ol class="small text-muted ps-3 mb-0" style="line-height:1.7;">
            <li>Aktifkan <strong>2-Step Verification</strong> di akun Google</li>
            <li>Buka <a href="https://myaccount.google.com/apppasswords" target="_blank" class="text-decoration-none text-success fw-semibold">myaccount.google.com/apppasswords</a></li>
            <li>Pilih <em>App: Mail</em>, <em>Device: Other</em>, beri nama "SIMKES"</li>
            <li>Salin kode 16 karakter yang dihasilkan sebagai <strong>SMTP Password</strong></li>
            <li>Isi <code>smtp.gmail.com</code>, port <code>587</code>, enkripsi <code>TLS</code></li>
          </ol>
        </div>
      </div>

    </div>
  </div>

</div>

<script>
  // Toggle tampil/sembunyikan password SMTP
  const smtpPassInput = document.getElementById('smtpPassInput');
  const toggleBtn     = document.getElementById('toggleSmtpPass');
  const eyeIcon       = document.getElementById('eyeSmtpPass');
  if (toggleBtn && smtpPassInput) {
    toggleBtn.addEventListener('click', function () {
      const isPass = smtpPassInput.type === 'password';
      smtpPassInput.type = isPass ? 'text' : 'password';
      eyeIcon.className  = isPass ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  }
</script>
<?= $this->endSection() ?>

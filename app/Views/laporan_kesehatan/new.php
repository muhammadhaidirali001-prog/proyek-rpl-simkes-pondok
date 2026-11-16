<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title ?? 'Lapor Santri Sakit - SIMKES Santri') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0">

  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-2 small text-muted">
      <li class="breadcrumb-item"><a href="<?= base_url('/dashboard') ?>" class="text-decoration-none text-muted">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= base_url('/laporan-kesehatan') ?>" class="text-decoration-none text-muted">Laporan Kesehatan</a></li>
      <li class="breadcrumb-item active" aria-current="page">Lapor Sakit Baru</li>
    </ol>
  </nav>

  <!-- Flash Alerts -->
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
      <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
      <div class="fw-medium"><?= esc(session()->getFlashdata('error')) ?></div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php $validationErrors = session()->getFlashdata('errors') ?? []; ?>
  <?php if (!empty($validationErrors)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2 mb-2">
        <i class="bi bi-x-circle-fill fs-5 flex-shrink-0"></i>
        <strong class="fw-semibold">Mohon perbaiki isian form berikut:</strong>
      </div>
      <ul class="mb-0 small ps-4">
        <?php foreach ($validationErrors as $err): ?>
          <li><?= esc($err) ?></li>
        <?php endforeach; ?>
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card card-dashboard border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="stat-icon warning" style="width: 38px; height: 38px; font-size: 1.15rem;">
              <i class="bi bi-clipboard2-plus-fill"></i>
            </div>
            <div>
              <h5 class="fw-bold text-dark mb-0">Lapor Santri Sakit</h5>
              <div class="small text-muted">Catat keluhan awal dan waktu santri mulai merasakan sakit</div>
            </div>
          </div>
          <a href="<?= base_url('/laporan-kesehatan') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
          </a>
        </div>

        <div class="card-body p-4">
          <form action="<?= base_url('/laporan-kesehatan') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="mb-3">
              <label for="santri_id" class="form-label fw-semibold text-dark small">
                Pilih Santri <span class="text-danger">*</span>
              </label>
              <select class="form-select <?= isset($validationErrors['santri_id']) ? 'is-invalid' : '' ?>" id="santri_id" name="santri_id" required>
                <option value="">-- Pilih Santri yang Mengeluh Sakit --</option>
                <?php
                  $selectedSantri = old('santri_id', $santriIdParam ?? '');
                  foreach ($santriList as $s):
                ?>
                  <option value="<?= esc($s['id']) ?>" <?= ((string)$selectedSantri === (string)$s['id']) ? 'selected' : '' ?>>
                    <?= esc($s['nama']) ?> (NIS: <?= esc($s['nis']) ?>, Kelas: <?= esc($s['kelas'] ?? '-') ?>)
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if (isset($validationErrors['santri_id'])): ?>
                <div class="invalid-feedback"><?= esc($validationErrors['santri_id']) ?></div>
              <?php endif; ?>
            </div>

            <div class="mb-3">
              <label for="tanggal_mulai_sakit" class="form-label fw-semibold text-dark small">
                Tanggal dan Waktu Mulai Sakit <span class="text-danger">*</span>
              </label>
              <input type="datetime-local" class="form-control <?= isset($validationErrors['tanggal_mulai_sakit']) ? 'is-invalid' : '' ?>"
                     id="tanggal_mulai_sakit" name="tanggal_mulai_sakit"
                     value="<?= esc(old('tanggal_mulai_sakit', date('Y-m-d\TH:i'))) ?>" required>
              <?php if (isset($validationErrors['tanggal_mulai_sakit'])): ?>
                <div class="invalid-feedback"><?= esc($validationErrors['tanggal_mulai_sakit']) ?></div>
              <?php endif; ?>
              <div class="form-text small text-muted">Perkiraan waktu santri mulai merasakan demam atau keluhan sakit.</div>
            </div>

            <div class="mb-3">
              <label for="gejala" class="form-label fw-semibold text-dark small">
                Gejala dan Keluhan Awal <span class="text-danger">*</span>
              </label>
              <textarea class="form-control <?= isset($validationErrors['gejala']) ? 'is-invalid' : '' ?>"
                        id="gejala" name="gejala" rows="4"
                        placeholder="Deskripsikan gejala yang dirasakan santri (misal: Demam tinggi 38.5C, batuk kering, sakit perut setelah makan, dll)..." required><?= esc(old('gejala')) ?></textarea>
              <?php if (isset($validationErrors['gejala'])): ?>
                <div class="invalid-feedback"><?= esc($validationErrors['gejala']) ?></div>
              <?php endif; ?>
            </div>

            <div class="mb-4">
              <label for="catatan" class="form-label fw-semibold text-dark small">
                Catatan / Tindakan Darurat Pertama (Opsional)
              </label>
              <textarea class="form-control" id="catatan" name="catatan" rows="3"
                        placeholder="Contoh: Sudah diberi minum air hangat dan istirahat di asrama kamar..."><?= esc(old('catatan')) ?></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2 border-top pt-3">
              <a href="<?= base_url('/laporan-kesehatan') ?>" class="btn btn-light px-4">Batal</a>
              <button type="submit" class="btn btn-teal px-4 fw-semibold shadow-sm">
                <i class="bi bi-send-fill me-1"></i> Simpan Laporan Sakit
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

</div>
<?= $this->endSection() ?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title ?? 'Kamar & Asrama Santri - SIMKES Santri') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0">

  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-2 small text-muted">
      <li class="breadcrumb-item"><a href="<?= base_url('/dashboard') ?>" class="text-decoration-none text-muted">Dashboard</a></li>
      <li class="breadcrumb-item active" aria-current="page">Kamar & Asrama</li>
    </ol>
  </nav>

  <!-- Header Section -->
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-door-open-fill text-teal"></i>
        <span>Kamar & Asrama Santri</span>
      </h3>
      <p class="text-muted small mb-0 mt-1">
        Daftar kamar santri, pengawas asrama pembina, dan statistik hunian santri.
      </p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= base_url('/santri') ?>" class="btn btn-outline-teal px-3">
        <i class="bi bi-people-fill me-1"></i> Data Santri
      </a>
    </div>
  </div>

  <!-- Cards Grid -->
  <div class="row g-3">
    <?php if (!empty($kamarList)): ?>
      <?php foreach ($kamarList as $k): ?>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card border-0 shadow-sm rounded-4 h-100 hover-card p-3">
            <div class="d-flex align-items-start justify-content-between mb-3">
              <div class="d-flex align-items-center gap-2">
                <div class="bg-teal-subtle text-teal rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="background-color: #ccfbf1; color: #0f766e; width: 44px; height: 44px;">
                  <i class="bi bi-door-closed-fill fs-5"></i>
                </div>
                <div>
                  <h6 class="fw-bold text-dark mb-0"><?= esc($k['nama_kamar']) ?></h6>
                  <span class="badge bg-light text-muted border small" style="font-size: 0.72rem;">Kamar #<?= esc($k['id']) ?></span>
                </div>
              </div>
              <span class="badge bg-success-subtle text-success px-2.5 py-1 rounded-pill small fw-semibold">
                Aktif
              </span>
            </div>

            <div class="border-top pt-3 mt-auto">
              <div class="d-flex align-items-center justify-content-between small text-muted mb-2">
                <span><i class="bi bi-person-badge text-primary me-1"></i> Pengawas Pembina:</span>
                <span class="fw-semibold text-dark"><?= esc($k['nama_pengawas'] ?? 'Belum Ditugaskan') ?></span>
              </div>
              <div class="d-flex align-items-center justify-content-between small text-muted mb-3">
                <span><i class="bi bi-people text-teal me-1"></i> Santri Penghuni:</span>
                <span class="badge bg-teal-subtle text-teal fw-bold" style="background-color: #ccfbf1; color: #0f766e;">
                  <?= esc($k['jumlah_santri']) ?> Santri
                </span>
              </div>
              <a href="<?= base_url('/santri?kamar_id=' . $k['id']) ?>" class="btn btn-outline-teal btn-sm w-100 rounded-3">
                <i class="bi bi-people-fill me-1"></i> Lihat Santri Kamar Ini
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
          <i class="bi bi-door-open fs-1 text-muted mb-2"></i>
          <h5 class="fw-bold text-secondary">Belum Ada Kamar Ditugaskan</h5>
          <p class="text-muted small">Anda belum ditugaskan untuk membina kamar manapun.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div>
<?= $this->endSection() ?>

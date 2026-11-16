<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title ?? 'Laporan Kesehatan Santri - SIMKES Santri') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0">

  <!-- Flash Alerts -->
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
      <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
      <div class="fw-medium"><?= esc(session()->getFlashdata('error')) ?></div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
      <i class="bi bi-check-circle-fill fs-5 flex-shrink-0"></i>
      <div class="fw-medium"><?= esc(session()->getFlashdata('success')) ?></div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Header -->
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small text-muted">
          <li class="breadcrumb-item"><a href="<?= base_url('/dashboard') ?>" class="text-decoration-none text-muted">Dashboard</a></li>
          <li class="breadcrumb-item active" aria-current="page">Laporan Kesehatan</li>
        </ol>
      </nav>
      <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-clipboard2-pulse-fill text-teal"></i>
        <span>Laporan Kesehatan Santri</span>
      </h3>
      <p class="text-muted small mb-0 mt-1">Daftar laporan kasus sakit, observasi klinis, dan riwayat pemantauan santri.</p>
    </div>

    <div class="d-flex align-items-center gap-2">
      <a href="<?= base_url('/data-transfer/ekspor') ?>" class="btn btn-outline-success px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
        <i class="bi bi-file-earmark-arrow-down-fill fs-6"></i>
        <span>Ekspor Rekapitulasi</span>
      </a>
      <a href="<?= base_url('/laporan-kesehatan/new') ?>" class="btn btn-teal px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
        <i class="bi bi-plus-circle fs-6"></i>
        <span>Lapor Santri Sakit</span>
      </a>
    </div>
  </div>

  <!-- Filter & Nav Tabs -->
  <div class="card card-dashboard mb-4 border-0 shadow-sm">
    <div class="card-header bg-white border-bottom pt-3 pb-0 px-4">
      <ul class="nav nav-tabs card-header-tabs border-0">
        <li class="nav-item">
          <a class="nav-link fw-semibold <?= ($tab === 'aktif') ? 'active text-teal border-bottom-0' : 'text-muted' ?>"
             href="<?= base_url('/laporan-kesehatan?tab=aktif' . (!empty($filters['kamar_id']) ? '&kamar_id=' . $filters['kamar_id'] : '')) ?>">
            <i class="bi bi-activity me-1"></i> Kasus Aktif
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link fw-semibold <?= ($tab === 'selesai') ? 'active text-teal border-bottom-0' : 'text-muted' ?>"
             href="<?= base_url('/laporan-kesehatan?tab=selesai' . (!empty($filters['kamar_id']) ? '&kamar_id=' . $filters['kamar_id'] : '')) ?>">
            <i class="bi bi-check2-all me-1"></i> Kasus Selesai
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link fw-semibold <?= ($tab === 'semua') ? 'active text-teal border-bottom-0' : 'text-muted' ?>"
             href="<?= base_url('/laporan-kesehatan?tab=semua' . (!empty($filters['kamar_id']) ? '&kamar_id=' . $filters['kamar_id'] : '')) ?>">
            <i class="bi bi-list-ul me-1"></i> Semua Riwayat
          </a>
        </li>
      </ul>
    </div>

    <div class="card-body p-3 p-md-4">
      <form action="<?= base_url('/laporan-kesehatan') ?>" method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="tab" value="<?= esc($tab) ?>">
        
        <div class="col-12 col-md-5">
          <label for="keyword" class="form-label small fw-semibold text-muted mb-1">
            <i class="bi bi-search me-1"></i> Pencarian
          </label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control bg-light border-start-0" id="keyword" name="keyword"
                   placeholder="Cari NIS atau Nama santri..." value="<?= esc($filters['keyword'] ?? '') ?>">
          </div>
        </div>

        <div class="col-12 col-sm-6 col-md-4">
          <label for="kamar_id" class="form-label small fw-semibold text-muted mb-1">
            <i class="bi bi-door-closed me-1"></i> Kamar
          </label>
          <select class="form-select bg-light" id="kamar_id" name="kamar_id">
            <option value="">-- Semua Kamar --</option>
            <?php foreach ($daftarKamar as $k): ?>
              <option value="<?= esc($k['id']) ?>" <?= ((string)($filters['kamar_id'] ?? '') === (string)$k['id']) ? 'selected' : '' ?>>
                <?= esc($k['nama_kamar']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 col-sm-6 col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-teal w-100 fw-semibold">
            <i class="bi bi-funnel-fill me-1"></i> Filter
          </button>
          <?php if (!empty($filters['keyword']) || !empty($filters['kamar_id'])): ?>
            <a href="<?= base_url('/laporan-kesehatan?tab=' . $tab) ?>" class="btn btn-outline-secondary" title="Reset Filter">
              <i class="bi bi-arrow-counterclockwise"></i>
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabel Data Laporan -->
  <div class="card card-dashboard border-0 shadow-sm overflow-hidden mb-4">
    <div class="table-responsive">
      <table class="table table-hover table-simkes align-middle mb-0">
        <thead>
          <tr>
            <th class="text-center" style="width: 40px;">No</th>
            <th style="width: 150px;">Tanggal Sakit</th>
            <th>Santri & Kamar</th>
            <th>Gejala / Keluhan Awal</th>
            <th class="text-center" style="width: 160px;">Status</th>
            <th>Pelapor</th>
            <th class="text-center" style="width: 120px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($laporanList) && is_array($laporanList)): ?>
            <?php 
              $currentPage = (int) ($pager->getCurrentPage('laporan') ?: 1);
              $perPage = (int) ($pager->getPerPage('laporan') ?: 10);
              $no = ($currentPage - 1) * $perPage + 1;
            ?>
            <?php foreach ($laporanList as $lap): ?>
              <?php
                $status = $lap['status'];
                $badgeClass = match ($status) {
                  'Sembuh'            => 'bg-success text-white',
                  'Dalam Perawatan'   => 'bg-danger text-white',
                  'Dalam Pemeriksaan' => 'bg-info text-white',
                  'Dirujuk', 'Rujukan RS' => 'text-white',
                  default             => 'bg-warning text-dark',
                };
                $badgeStyle = in_array($status, ['Dirujuk', 'Rujukan RS']) ? 'background-color: #7e22ce;' : '';
                $statusIcon = match ($status) {
                  'Sembuh'            => 'bi-check-circle-fill',
                  'Dalam Perawatan'   => 'bi-hospital-fill',
                  'Dalam Pemeriksaan' => 'bi-search',
                  'Dirujuk', 'Rujukan RS' => 'bi-box-arrow-up-right',
                  default             => 'bi-clock-history',
                };
                $isMale = ($lap['jenis_kelamin'] === 'Laki-laki');
                $bgAvatar = $isMale ? 'bg-primary-subtle text-primary' : 'bg-danger-subtle text-danger';
                $initial = mb_substr($lap['nama_santri'], 0, 1);
              ?>
              <tr>
                <td class="text-center text-muted fw-semibold small"><?= $no++ ?></td>
                <td class="small text-muted font-monospace">
                  <?= date('d M Y, H:i', strtotime($lap['tanggal_mulai_sakit'])) ?>
                </td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="avatar-circle <?= $bgAvatar ?>" style="width: 34px; height: 34px; font-size: 0.85rem;">
                      <?= esc($initial) ?>
                    </div>
                    <div>
                      <a href="<?= base_url('/santri/' . $lap['santri_id']) ?>" class="fw-bold text-dark text-decoration-none text-hover-teal">
                        <?= esc($lap['nama_santri']) ?>
                      </a>
                      <div class="small text-muted">
                        NIS: <?= esc($lap['nis']) ?> &bull; <span class="badge bg-light text-dark border"><?= esc($lap['nama_kamar'] ?? '-') ?></span>
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="small text-dark" style="max-width: 250px;">
                    <?= esc($lap['gejala']) ?>
                  </div>
                </td>
                <td class="text-center">
                  <span class="badge <?= $badgeClass ?> px-2.5 py-1.5 small fw-semibold" style="<?= $badgeStyle ?>">
                    <i class="bi <?= $statusIcon ?> me-1"></i> <?= esc($status) ?>
                  </span>
                </td>
                <td>
                  <span class="small text-dark fw-medium d-inline-flex align-items-center gap-1">
                    <i class="bi bi-person text-muted"></i>
                    <?= esc($lap['nama_pelapor'] ?? 'Petugas') ?>
                  </span>
                </td>
                <td class="text-center">
                  <a href="<?= base_url('/laporan-kesehatan/' . $lap['id']) ?>" class="btn btn-sm btn-outline-teal px-2.5 py-1 fw-semibold">
                    <i class="bi bi-eye"></i> Detail
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center py-5">
                <div class="py-4">
                  <i class="bi bi-clipboard2-check display-4 text-success opacity-75 mb-2"></i>
                  <h5 class="fw-bold text-dark mb-1">Tidak Ada Laporan Kesehatan Ditemukan</h5>
                  <p class="text-muted small mb-3">Tidak ada catatan santri sakit pada kriteria tab ini.</p>
                  <a href="<?= base_url('/laporan-kesehatan/new') ?>" class="btn btn-sm btn-teal">
                    <i class="bi bi-plus-circle me-1"></i> Lapor Santri Sakit Baru
                  </a>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if (!empty($laporanList) && is_array($laporanList)): ?>
      <div class="card-footer bg-white border-0 py-3 px-4 d-flex justify-content-end">
        <?= $pager->links('laporan', 'default_full') ?>
      </div>
    <?php endif; ?>
  </div>

</div>
<?= $this->endSection() ?>

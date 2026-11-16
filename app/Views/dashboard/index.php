<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title ?? 'Dashboard Administrasi Kesehatan - SIMKES Santri') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0">

  <!-- Flash Alerts (Pemberitahuan Sistem & Keamanan) -->
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
      <i class="bi bi-shield-x fs-5 flex-shrink-0"></i>
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

  <!-- Welcome Banner -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card border-0 rounded-4 p-4 text-white position-relative overflow-hidden shadow-sm" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 50%, #0369a1 100%);">
        <div class="row align-items-center position-relative" style="z-index: 2;">
          <div class="col-lg-8">
            <span class="badge bg-white bg-opacity-25 text-white px-3 py-1.5 rounded-pill mb-2 small fw-semibold">
              <i class="bi bi-shield-plus me-1"></i> 
              <?= $isPengawasKamar ? 'Dashboard Pengawas Kamar' : 'Poskestren Terpadu Lintas Kamar' ?>
            </span>
            <h2 class="fw-bold mb-1">
              Selamat Datang, <?= esc(session()->get('nama') ?? 'Petugas') ?>
            </h2>
            <p class="mb-0 text-white-50" style="font-size: 0.95rem;">
              <?php if ($isPengawasKamar): ?>
                Kamar asuhan aktif: <strong><?= !empty($namaKamarBinaan) ? esc(implode(', ', $namaKamarBinaan)) : 'Belum Ditugaskan' ?></strong>.
                Data di bawah terisolasi khusus santri dan laporan kesehatan di kamar binaan Anda.
              <?php else: ?>
                Akses penuh lintas kamar &mdash; Pantau data santri, laporan sakit terintegrasi, pemeriksaan dokter, dan rujukan ke faskes luar.
              <?php endif; ?>
            </p>
          </div>
          <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            <div class="d-inline-flex gap-2">
              <a href="<?= base_url('/laporan-kesehatan/new') ?>" class="btn btn-light text-teal fw-semibold shadow-sm px-3 py-2" style="color: #0f766e;">
                <i class="bi bi-plus-circle me-1"></i> Lapor Santri Sakit
              </a>
              <?php if (!$isPengawasKamar): ?>
                <a href="<?= base_url('/pemeriksaan') ?>" class="btn btn-outline-light px-3 py-2">
                  <i class="bi bi-stethoscope me-1"></i> Pemeriksaan
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="position-absolute end-0 top-0 bottom-0 d-none d-md-block opacity-10" style="font-size: 14rem; line-height: 1; transform: translate(10%, -15%); pointer-events: none;">
          <i class="bi bi-heart-pulse"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- 5 Kartu Statistik Utama Kesehatan Santri (Interaktif & Terhubung ke Modul Terkait) -->
  <div class="row g-3 mb-4">
    <!-- 1. Total Santri -->
    <div class="col-12 col-sm-6 col-lg-4 col-xl">
      <div class="card card-dashboard stat-card h-100 border-0 shadow-sm position-relative overflow-hidden">
        <a href="<?= base_url('/santri') ?>" class="text-decoration-none text-dark d-block">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="stat-label">
                <?= $isPengawasKamar ? 'Santri Kamar Binaan' : 'Total Santri' ?>
              </div>
              <div class="stat-value text-dark"><?= esc($totalSantri) ?></div>
              <div class="small text-muted mt-1">
                <span class="text-success fw-semibold"><i class="bi bi-people"></i> Terdaftar Aktif</span>
              </div>
            </div>
            <div class="stat-icon primary">
              <i class="bi bi-people-fill"></i>
            </div>
          </div>
        </a>
        <div class="d-flex align-items-center justify-content-between pt-2 mt-2 border-top border-light-subtle">
          <a href="<?= base_url('/santri') ?>" class="small text-muted text-decoration-none d-inline-flex align-items-center gap-1">
            <span>Lihat Data</span> <i class="bi bi-chevron-right" style="font-size: 0.75rem;"></i>
          </a>
          <a href="<?= base_url('/santri/new') ?>" class="badge bg-primary-subtle text-primary border-0 text-decoration-none py-1.5 px-2 rounded-2 d-inline-flex align-items-center gap-1" title="Tambah Santri Baru">
            <i class="bi bi-plus-circle-fill"></i> <span>Tambah</span>
          </a>
        </div>
      </div>
    </div>

    <!-- 2. Santri Dalam Pemeriksaan -->
    <div class="col-12 col-sm-6 col-lg-4 col-xl">
      <div class="card card-dashboard stat-card h-100 border-0 shadow-sm position-relative overflow-hidden">
        <a href="<?= base_url('/laporan-kesehatan?status=Dalam+Pemeriksaan') ?>" class="text-decoration-none text-dark d-block">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="stat-label">Dalam Pemeriksaan</div>
              <div class="stat-value text-info"><?= esc($totalDalamPeriksa) ?></div>
              <div class="small text-muted mt-1">
                <span class="text-info fw-semibold"><i class="bi bi-search"></i> Sedang Diperiksa</span>
              </div>
            </div>
            <div class="stat-icon" style="background-color: #e0f7fa; color: #0891b2;">
              <i class="bi bi-clipboard2-pulse-fill"></i>
            </div>
          </div>
        </a>
        <div class="d-flex align-items-center justify-content-between pt-2 mt-2 border-top border-light-subtle">
          <a href="<?= base_url('/laporan-kesehatan?status=Dalam+Pemeriksaan') ?>" class="small text-muted text-decoration-none d-inline-flex align-items-center gap-1">
            <span>Lihat Pasien</span> <i class="bi bi-chevron-right" style="font-size: 0.75rem;"></i>
          </a>
          <a href="<?= base_url('/laporan-kesehatan/new') ?>" class="badge bg-info-subtle text-info border-0 text-decoration-none py-1.5 px-2 rounded-2 d-inline-flex align-items-center gap-1" title="Lapor Santri Sakit">
            <i class="bi bi-plus-circle-fill"></i> <span>Lapor</span>
          </a>
        </div>
      </div>
    </div>

    <!-- 3. Santri Dalam Perawatan -->
    <div class="col-12 col-sm-6 col-lg-4 col-xl">
      <div class="card card-dashboard stat-card h-100 border-0 shadow-sm position-relative overflow-hidden">
        <a href="<?= base_url('/laporan-kesehatan?status=Dalam+Perawatan') ?>" class="text-decoration-none text-dark d-block">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="stat-label">Dalam Perawatan</div>
              <div class="stat-value text-danger"><?= esc($totalDalamRawat) ?></div>
              <div class="small text-muted mt-1">
                <span class="text-danger fw-semibold"><i class="bi bi-hospital-fill"></i> Observasi Klinis</span>
              </div>
            </div>
            <div class="stat-icon danger">
              <i class="bi bi-hospital-fill"></i>
            </div>
          </div>
        </a>
        <div class="d-flex align-items-center justify-content-between pt-2 mt-2 border-top border-light-subtle">
          <a href="<?= base_url('/laporan-kesehatan?status=Dalam+Perawatan') ?>" class="small text-muted text-decoration-none d-inline-flex align-items-center gap-1">
            <span>Pantau Rawat</span> <i class="bi bi-chevron-right" style="font-size: 0.75rem;"></i>
          </a>
          <a href="<?= base_url('/laporan-kesehatan/new') ?>" class="badge bg-danger-subtle text-danger border-0 text-decoration-none py-1.5 px-2 rounded-2 d-inline-flex align-items-center gap-1" title="Lapor Santri Sakit">
            <i class="bi bi-plus-circle-fill"></i> <span>Lapor</span>
          </a>
        </div>
      </div>
    </div>

    <!-- 4. Santri yang Sembuh -->
    <div class="col-12 col-sm-6 col-lg-4 col-xl">
      <div class="card card-dashboard stat-card h-100 border-0 shadow-sm position-relative overflow-hidden">
        <a href="<?= base_url('/laporan-kesehatan?status=Sembuh') ?>" class="text-decoration-none text-dark d-block">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="stat-label">Santri Sembuh</div>
              <div class="stat-value text-success"><?= esc($totalSembuh) ?></div>
              <div class="small text-muted mt-1">
                <span class="text-success fw-semibold"><i class="bi bi-check-circle-fill"></i> Selesai Rawat</span>
              </div>
            </div>
            <div class="stat-icon success">
              <i class="bi bi-check-circle-fill"></i>
            </div>
          </div>
        </a>
        <div class="d-flex align-items-center justify-content-between pt-2 mt-2 border-top border-light-subtle">
          <a href="<?= base_url('/laporan-kesehatan?status=Sembuh') ?>" class="small text-muted text-decoration-none d-inline-flex align-items-center gap-1">
            <span>Riwayat Sembuh</span> <i class="bi bi-chevron-right" style="font-size: 0.75rem;"></i>
          </a>
          <a href="<?= base_url('/laporan-kesehatan/new') ?>" class="badge bg-success-subtle text-success border-0 text-decoration-none py-1.5 px-2 rounded-2 d-inline-flex align-items-center gap-1" title="Lapor Santri Sakit">
            <i class="bi bi-plus-circle-fill"></i> <span>Lapor</span>
          </a>
        </div>
      </div>
    </div>

    <!-- 5. Santri yang Dirujuk -->
    <div class="col-12 col-sm-6 col-lg-4 col-xl">
      <div class="card card-dashboard stat-card h-100 border-0 shadow-sm position-relative overflow-hidden">
        <a href="<?= base_url('/laporan-kesehatan?status=Dirujuk') ?>" class="text-decoration-none text-dark d-block">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="stat-label">Santri Dirujuk</div>
              <div class="stat-value text-purple" style="color: #7e22ce;"><?= esc($totalDirujuk) ?></div>
              <div class="small text-muted mt-1">
                <span class="fw-semibold" style="color: #7e22ce;"><i class="bi bi-box-arrow-up-right"></i> Rujukan RS/Faskes</span>
              </div>
            </div>
            <div class="stat-icon" style="background-color: #f3e8ff; color: #7e22ce;">
              <i class="bi bi-box-arrow-up-right"></i>
            </div>
          </div>
        </a>
        <div class="d-flex align-items-center justify-content-between pt-2 mt-2 border-top border-light-subtle">
          <a href="<?= base_url('/laporan-kesehatan?status=Dirujuk') ?>" class="small text-muted text-decoration-none d-inline-flex align-items-center gap-1">
            <span>Daftar Rujukan</span> <i class="bi bi-chevron-right" style="font-size: 0.75rem;"></i>
          </a>
          <a href="<?= base_url('/laporan-kesehatan/new') ?>" class="badge bg-purple-subtle text-purple border-0 text-decoration-none py-1.5 px-2 rounded-2 d-inline-flex align-items-center gap-1" style="background-color: #f3e8ff; color: #7e22ce;" title="Lapor Santri Sakit">
            <i class="bi bi-plus-circle-fill"></i> <span>Lapor</span>
          </a>
        </div>
      </div>
    </div>
  </div>
  <!-- Filter & Pencarian Dashboard -->
  <div class="card card-dashboard mb-4 border-0 shadow-sm">
    <div class="card-body p-3 p-md-4">
      <form action="<?= base_url('/dashboard') ?>" method="GET" class="row g-3 align-items-end">
        <!-- Pencarian Nama atau NIS -->
        <div class="col-12 col-md-3">
          <label for="keyword" class="form-label small fw-semibold text-muted mb-1">
            <i class="bi bi-search me-1"></i> Pencarian Santri
          </label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control bg-light border-start-0" id="keyword" name="keyword"
                   placeholder="Cari Nama atau NIS..." value="<?= esc($filters['keyword'] ?? '') ?>">
          </div>
        </div>

        <!-- Filter Kamar -->
        <div class="col-12 col-sm-6 col-md-2">
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

        <!-- Filter Status -->
        <div class="col-12 col-sm-6 col-md-2">
          <label for="status" class="form-label small fw-semibold text-muted mb-1">
            <i class="bi bi-activity me-1"></i> Status
          </label>
          <select class="form-select bg-light" id="status" name="status">
            <option value="">-- Semua Status --</option>
            <?php foreach ($daftarStatus as $st): ?>
              <option value="<?= esc($st) ?>" <?= (($filters['status'] ?? '') === $st) ? 'selected' : '' ?>>
                <?= esc($st) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Filter Tanggal Mulai -->
        <div class="col-12 col-sm-6 col-md-2">
          <label for="tanggal_mulai" class="form-label small fw-semibold text-muted mb-1">
            <i class="bi bi-calendar3 me-1"></i> Tanggal Mulai
          </label>
          <input type="date" class="form-control bg-light" id="tanggal_mulai" name="tanggal_mulai"
                 value="<?= esc($filters['tanggal_mulai'] ?? '') ?>">
        </div>

        <!-- Filter Tanggal Akhir -->
        <div class="col-12 col-sm-6 col-md-2">
          <label for="tanggal_akhir" class="form-label small fw-semibold text-muted mb-1">
            <i class="bi bi-calendar3 me-1"></i> Tanggal Akhir
          </label>
          <input type="date" class="form-control bg-light" id="tanggal_akhir" name="tanggal_akhir"
                 value="<?= esc($filters['tanggal_akhir'] ?? '') ?>">
        </div>

        <!-- Tombol Aksi Filter -->
        <div class="col-12 col-md-1 d-flex gap-1">
          <button type="submit" class="btn btn-teal w-100 fw-semibold" title="Terapkan Filter">
            <i class="bi bi-funnel-fill"></i>
          </button>
          <?php if (!empty($filters['keyword']) || !empty($filters['kamar_id']) || !empty($filters['status']) || !empty($filters['tanggal_mulai']) || !empty($filters['tanggal_akhir'])): ?>
            <a href="<?= base_url('/dashboard') ?>" class="btn btn-outline-secondary" title="Reset Filter">
              <i class="bi bi-arrow-counterclockwise"></i>
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <!-- Tabel Laporan Sakit Terbaru (Fitur Utama) -->
    <div class="col-12 col-xl-8">
      <div class="card card-dashboard border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <div class="stat-icon warning" style="width: 36px; height: 36px; font-size: 1.1rem;">
              <i class="bi bi-clipboard2-pulse-fill"></i>
            </div>
            <div>
              <h5 class="fw-bold text-dark mb-0">Laporan Sakit Terbaru</h5>
              <div class="small text-muted">Daftar kasus kesehatan santri terkini yang perlu pemantauan</div>
            </div>
          </div>
          <a href="<?= base_url('/laporan-kesehatan') ?>" class="btn btn-sm btn-outline-teal fw-semibold">
            Lihat Semua Laporan <i class="bi bi-arrow-right ms-1"></i>
          </a>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-simkes align-middle mb-0">
              <thead>
                <tr>
                  <th class="text-center" style="width: 40px;">No</th>
                  <th style="width: 140px;">Tanggal Sakit</th>
                  <th>Santri & Kamar</th>
                  <th>Gejala / Keluhan Awal</th>
                  <th class="text-center" style="width: 150px;">Status</th>
                  <th class="text-center" style="width: 100px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($laporanTerbaru)): ?>
                  <?php $no = 1; foreach ($laporanTerbaru as $lap): ?>
                    <?php
                      $status = $lap['status'];
                      $badgeClass = match ($status) {
                        'Sembuh'            => 'bg-success text-white',
                        'Dalam Perawatan'   => 'bg-danger text-white',
                        'Dalam Pemeriksaan' => 'bg-info text-white',
                        'Dirujuk', 'Rujukan RS' => 'text-white',
                        default             => 'bg-warning text-dark', // Dilaporkan Sakit / Baru
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
                          <div class="avatar-circle <?= $bgAvatar ?>" style="width: 32px; height: 32px; font-size: 0.8rem;">
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
                        <div class="small text-dark text-truncate" style="max-width: 230px;" title="<?= esc($lap['gejala']) ?>">
                          <?= esc($lap['gejala']) ?>
                        </div>
                        <?php if (!empty($lap['nama_pelapor'])): ?>
                          <div class="text-muted" style="font-size: 0.72rem;">
                            Pelapor: <?= esc($lap['nama_pelapor']) ?>
                          </div>
                        <?php endif; ?>
                      </td>
                      <td class="text-center">
                        <span class="badge <?= $badgeClass ?> px-2 py-1 small fw-semibold" style="<?= $badgeStyle ?>">
                          <i class="bi <?= $statusIcon ?> me-1"></i> <?= esc($status) ?>
                        </span>
                      </td>
                      <td class="text-center">
                        <div class="btn-group btn-group-sm">
                          <a href="<?= base_url('/laporan-kesehatan/' . $lap['id']) ?>" class="btn btn-outline-teal" title="Buka Detail Laporan Kesehatan">
                            <i class="bi bi-file-earmark-medical"></i> Detail
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <!-- Tampilan Kosong yang Informatif (Empty State) -->
                  <tr>
                    <td colspan="6" class="text-center py-5">
                      <div class="py-4">
                        <div class="mb-3">
                          <i class="bi bi-clipboard2-check display-4 text-success opacity-75"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Tidak Ada Laporan Sakit Ditemukan</h5>
                        <p class="text-muted small mb-3 max-w-md mx-auto">
                          Alhamdulillah, saat ini tidak ada catatan santri yang sedang sakit pada kriteria pencarian atau kamar yang dipilih.
                        </p>
                        <?php if (!empty($filters['keyword']) || !empty($filters['kamar_id']) || !empty($filters['status'])): ?>
                          <a href="<?= base_url('/dashboard') ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Bersihkan Filter
                          </a>
                        <?php else: ?>
                          <a href="<?= base_url('/laporan-kesehatan/new') ?>" class="btn btn-sm btn-teal">
                            <i class="bi bi-plus-circle me-1"></i> Buat Laporan Sakit Baru
                          </a>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Ringkasan Jumlah Kasus Berdasarkan Kamar -->
    <div class="col-12 col-xl-4">
      <div class="card card-dashboard border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="stat-icon info" style="width: 36px; height: 36px; font-size: 1.1rem;">
              <i class="bi bi-door-open-fill"></i>
            </div>
            <div>
              <h5 class="fw-bold text-dark mb-0">Kasus Berdasarkan Kamar</h5>
              <div class="small text-muted">Sebaran santri sakit di setiap asrama</div>
            </div>
          </div>
        </div>

        <div class="card-body p-0">
          <?php if (!empty($ringkasanKamar)): ?>
            <div class="list-group list-group-flush">
              <?php foreach ($ringkasanKamar as $rk): ?>
                <?php
                  $kasusAktifKamar = (int)$rk['sedang_sakit'] + (int)$rk['rawat_periksa'];
                ?>
                <div class="list-group-item p-3 border-bottom">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <div class="fw-bold text-dark d-flex align-items-center gap-1.5">
                      <i class="bi bi-door-closed text-teal"></i>
                      <span><?= esc($rk['nama_kamar']) ?></span>
                    </div>
                    <span class="badge <?= $kasusAktifKamar > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?> small px-2 py-1">
                      <?= $kasusAktifKamar > 0 ? "{$kasusAktifKamar} Kasus Aktif" : 'Kondisi Sehat' ?>
                    </span>
                  </div>
                  <div class="small text-muted mb-2">
                    Pengawas: <?= esc($rk['nama_pengawas']) ?> &bull; Total: <strong><?= esc($rk['total_santri']) ?></strong> santri
                  </div>

                  <div class="d-flex flex-wrap gap-1 small">
                    <span class="badge bg-warning-subtle text-dark border" title="Dilaporkan Sakit">
                      Sakit: <?= (int)$rk['sedang_sakit'] ?>
                    </span>
                    <span class="badge bg-danger-subtle text-danger border" title="Dalam Perawatan/Pemeriksaan">
                      Rawat: <?= (int)$rk['rawat_periksa'] ?>
                    </span>
                    <span class="badge bg-success-subtle text-success border" title="Sembuh">
                      Sembuh: <?= (int)$rk['sembuh'] ?>
                    </span>
                    <?php if ((int)$rk['dirujuk'] > 0): ?>
                      <span class="badge bg-purple-subtle text-purple border" style="color: #7e22ce;" title="Dirujuk ke RS">
                        Rujuk: <?= (int)$rk['dirujuk'] ?>
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="text-center py-5 p-4">
              <i class="bi bi-door-closed display-4 text-muted opacity-50 mb-2"></i>
              <div class="fw-bold text-dark">Belum Ada Data Kamar</div>
              <div class="small text-muted">Kamar binaan belum terdaftar dalam sistem.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

</div>
<?= $this->endSection() ?>

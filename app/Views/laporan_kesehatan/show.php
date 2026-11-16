<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title ?? 'Detail Laporan Kesehatan - SIMKES Santri') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0">

  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-2 small text-muted">
      <li class="breadcrumb-item"><a href="<?= base_url('/dashboard') ?>" class="text-decoration-none text-muted">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= base_url('/laporan-kesehatan') ?>" class="text-decoration-none text-muted">Laporan Kesehatan</a></li>
      <li class="breadcrumb-item active" aria-current="page">Kasus #<?= esc($laporan['id']) ?></li>
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

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
      <i class="bi bi-check-circle-fill fs-5 flex-shrink-0"></i>
      <div class="fw-medium"><?= esc(session()->getFlashdata('success')) ?></div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php
    $status = $laporan['status'];
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
  ?>

  <!-- Header Section -->
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
        <h3 class="fw-bold text-dark mb-0">Kasus Kesehatan #<?= esc($laporan['id']) ?></h3>
        <span class="badge <?= $badgeClass ?> px-3 py-1.5 fw-semibold fs-6" style="<?= $badgeStyle ?>">
          <i class="bi <?= $statusIcon ?> me-1"></i> <?= esc($status) ?>
        </span>
      </div>
      <p class="text-muted small mb-0">
        Santri: <strong><?= esc($laporan['nama_santri']) ?></strong> (NIS: <?= esc($laporan['nis']) ?>) &bull; 
        Kamar: <strong><?= esc($laporan['nama_kamar'] ?? '-') ?></strong> &bull;
        Mulai Sakit: <?= date('d M Y, H:i', strtotime($laporan['tanggal_mulai_sakit'])) ?>
      </p>
    </div>

    <div class="d-flex gap-2 flex-wrap">
      <a href="<?= base_url('/dashboard') ?>" class="btn btn-outline-secondary px-3">
        <i class="bi bi-arrow-left me-1"></i> Dashboard
      </a>
      <a href="<?= base_url('/santri/' . $laporan['santri_id']) ?>" class="btn btn-outline-teal px-3">
        <i class="bi bi-person-badge me-1"></i> Profil Santri
      </a>
      <!-- Tombol Ubah Status / Konfirmasi -->
      <button type="button" class="btn btn-teal px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
        <i class="bi bi-arrow-repeat me-1"></i> Perbarui Status Kondisi
      </button>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Laporan Awal & Profil -->
    <div class="col-12 col-lg-5">
      <!-- Kartu A: Laporan Awal Sakit -->
      <div class="card card-dashboard border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="stat-icon warning" style="width: 34px; height: 34px; font-size: 1rem;">
              <i class="bi bi-clipboard2-pulse"></i>
            </div>
            <h6 class="fw-bold text-dark mb-0">Laporan Awal Sakit</h6>
          </div>
          <span class="badge bg-light text-muted border small">Laporan #<?= esc($laporan['id']) ?></span>
        </div>
        <div class="card-body p-4">
          <div class="mb-3">
            <div class="small fw-semibold text-muted text-uppercase">Waktu Mulai Sakit:</div>
            <div class="text-dark fw-bold font-monospace mt-0.5">
              <?= date('d F Y, H:i', strtotime($laporan['tanggal_mulai_sakit'])) ?> WIB
            </div>
          </div>

          <div class="mb-3">
            <div class="small fw-semibold text-muted text-uppercase">Pelapor / Pengawas:</div>
            <div class="text-dark fw-medium mt-0.5 d-flex align-items-center gap-1">
              <i class="bi bi-person-circle text-teal"></i>
              <?= esc($laporan['nama_pelapor'] ?? 'Petugas') ?>
            </div>
          </div>

          <div class="mb-3">
            <div class="small fw-semibold text-muted text-uppercase">Gejala & Keluhan Awal:</div>
            <div class="p-3 bg-light rounded-3 border small text-dark mt-1">
              <?= nl2br(esc($laporan['gejala'])) ?>
            </div>
          </div>

          <?php if (!empty($laporan['catatan'])): ?>
            <div class="mb-3">
              <div class="small fw-semibold text-muted text-uppercase">Catatan Tambahan:</div>
              <div class="p-2.5 bg-light rounded border small text-muted mt-1">
                <?= nl2br(esc($laporan['catatan'])) ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($laporan['tanggal_sembuh'])): ?>
            <div class="alert alert-success border-0 small d-flex align-items-center gap-2 mb-0">
              <i class="bi bi-check-circle-fill fs-5 text-success"></i>
              <div>
                Dinyatakan <strong>Sembuh</strong> pada: <span class="fw-bold"><?= date('d F Y', strtotime($laporan['tanggal_sembuh'])) ?></span>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Kartu D: Linimasa Perubahan Status (Non-Destructive Audit Trail) -->
      <div class="card card-dashboard border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="stat-icon info" style="width: 34px; height: 34px; font-size: 1rem;">
              <i class="bi bi-clock-history"></i>
            </div>
            <h6 class="fw-bold text-dark mb-0">Linimasa Riwayat Status</h6>
          </div>
          <span class="badge bg-light text-dark border small"><?= count($riwayatStatus) ?> Riwayat</span>
        </div>
        <div class="card-body p-4">
          <?php if (!empty($riwayatStatus)): ?>
            <div class="d-flex flex-column gap-3">
              <?php foreach ($riwayatStatus as $r): ?>
                <div class="border-start border-3 border-teal ps-3 py-1 position-relative">
                  <div class="d-flex align-items-center justify-content-between">
                    <span class="badge bg-secondary-subtle text-secondary small fw-bold">
                      <?= esc($r['status']) ?>
                    </span>
                    <span class="small text-muted font-monospace" style="font-size: 0.75rem;">
                      <?= date('d M Y, H:i', strtotime($r['tanggal'])) ?>
                    </span>
                  </div>
                  <?php if (!empty($r['catatan'])): ?>
                    <div class="small text-dark mt-1"><?= esc($r['catatan']) ?></div>
                  <?php endif; ?>
                  <div class="text-muted small mt-0.5" style="font-size: 0.72rem;">
                    Petugas: <?= esc($r['nama_petugas'] ?? 'Sistem') ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="text-center py-3 text-muted small">Belum ada catatan riwayat status.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Right Column: Pemeriksaan Medis Dokter & Terapi Obat -->
    <div class="col-12 col-lg-7">
      <?php if ($isTenagaMedis): ?>
        <div class="card card-dashboard border-0 shadow-sm mb-4">
          <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
              <div class="stat-icon primary" style="width: 36px; height: 36px; font-size: 1.1rem;">
                <i class="bi bi-stethoscope"></i>
              </div>
              <div>
                <h5 class="fw-bold text-dark mb-0">Hasil Pemeriksaan Klinis Tenaga Medis</h5>
                <div class="small text-muted">Diagnosis, observasi fisik, dan tindakan medis resmi dokter Poskestren</div>
              </div>
            </div>
            <a href="<?= base_url('/pemeriksaan/laporan/' . $laporan['id']) ?>" class="btn btn-sm btn-primary fw-medium px-3">
              <i class="bi bi-plus-circle me-1"></i> Catat Pemeriksaan
            </a>
          </div>

          <div class="card-body p-4">
            <?php if (!empty($daftarPemeriksaan)): ?>
              <div class="d-flex flex-column gap-4">
                <?php foreach ($daftarPemeriksaan as $p): ?>
                  <div class="border rounded-3 p-3 bg-light">
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                      <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white"><i class="bi bi-person-badge me-1"></i> Pemeriksa</span>
                        <strong class="text-dark"><?= esc($p['nama_pemeriksa'] ?? 'Tenaga Medis') ?></strong>
                      </div>
                      <span class="small text-muted font-monospace">
                        <?= date('d M Y, H:i', strtotime($p['tanggal_periksa'])) ?>
                      </span>
                    </div>

                    <div class="mb-2">
                      <span class="fw-bold text-dark small">Diagnosis / Dugaan Klinis:</span>
                      <div class="p-2 bg-white rounded border text-danger fw-semibold small mt-1">
                        <?= esc($p['diagnosis_atau_dugaan']) ?>
                      </div>
                    </div>

                    <?php if (!empty($p['tindakan'])): ?>
                      <div class="mb-2">
                        <span class="fw-bold text-dark small">Tindakan & Observasi Medis:</span>
                        <div class="p-2 bg-white rounded border small text-dark mt-1">
                          <?= nl2br(esc($p['tindakan'])) ?>
                        </div>
                      </div>
                    <?php endif; ?>

                    <?php if (!empty($p['catatan'])): ?>
                      <div class="mb-2">
                        <span class="fw-bold text-dark small">Catatan Tindak Lanjut:</span>
                        <div class="p-2 bg-white rounded border small text-muted mt-1">
                          <?= nl2br(esc($p['catatan'])) ?>
                        </div>
                      </div>
                    <?php endif; ?>

                    <!-- Resep & Terapi Obat -->
                    <?php if (!empty($p['obat'])): ?>
                      <div class="mt-3 pt-2 border-top">
                        <div class="small fw-bold text-dark mb-2 d-flex align-items-center gap-1">
                          <i class="bi bi-capsule-pill text-teal"></i>
                          <span>Resep & Terapi Obat yang Disahkan (Diverifikasi Medis)</span>
                        </div>
                        <div class="table-responsive">
                          <table class="table table-sm table-bordered bg-white mb-0 small">
                            <thead class="bg-light">
                              <tr>
                                <th>Nama Obat</th>
                                <th>Dosis</th>
                                <th>Aturan Pakai</th>
                                <th>Durasi</th>
                                <th>Instruksi</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach ($p['obat'] as $ob): ?>
                                <tr>
                                  <td class="fw-bold text-dark"><?= esc($ob['nama_obat']) ?></td>
                                  <td><?= esc($ob['dosis'] ?? '-') ?></td>
                                  <td><span class="badge bg-info-subtle text-info"><?= esc($ob['aturan_pakai'] ?? '-') ?></span></td>
                                  <td><?= esc($ob['durasi'] ?? '-') ?></td>
                                  <td class="text-muted"><?= esc($ob['instruksi_petugas'] ?? '-') ?></td>
                                </tr>
                              <?php endforeach; ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    <?php endif; ?>

                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="text-center py-4">
                <i class="bi bi-stethoscope display-4 text-muted opacity-50 mb-2"></i>
                <h6 class="fw-bold text-dark mb-1">Belum Ada Pemeriksaan Medis Dicatat</h6>
                <p class="text-muted small mb-3">Laporan ini masih menunggu pemeriksaan langsung oleh dokter atau perawat Poskestren.</p>
                <a href="<?= base_url('/pemeriksaan/laporan/' . $laporan['id']) ?>" class="btn btn-teal btn-sm fw-semibold">
                  <i class="bi bi-clipboard2-pulse me-1"></i> Mulai Pemeriksaan Klinis Sekarang
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php else: ?>
        <!-- Tampilan Privasi Medis untuk Pengawas Kamar -->
        <div class="card card-dashboard border-0 shadow-sm mb-4">
          <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center gap-2">
            <div class="stat-icon warning" style="width: 36px; height: 36px; font-size: 1.1rem;">
              <i class="bi bi-shield-lock-fill"></i>
            </div>
            <div>
              <h5 class="fw-bold text-dark mb-0">Informasi Medis & Rekam Klinis</h5>
              <div class="small text-muted">Kebijakan Perlindungan Kerahasiaan Data Pasien (Privacy by Design)</div>
            </div>
          </div>
          <div class="card-body p-4 text-center py-5">
            <div class="mb-3">
              <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill fs-6">
                <i class="bi bi-lock-fill me-1"></i> Akses Rekam Medis Terbatas
              </span>
            </div>
            <h6 class="fw-bold text-dark mb-2">Diagnosis Klinis & Resep Obat Dilindungi</h6>
            <p class="text-muted small mx-auto" style="max-width: 480px;">
              Sesuai standar operasional dan kode etik medis Poskestren, catatan hasil pemeriksaan fisik klinis, diagnosis dokter, dan terapi obat hanya dapat diakses secara langsung oleh Tenaga Kesehatan dan Dokter Poskestren yang berwenang.
            </p>
            <div class="alert alert-light border small text-muted text-start mx-auto mt-3" style="max-width: 480px;">
              <i class="bi bi-info-circle-fill text-teal me-1"></i> Sebagai Pengawas Kamar, Anda dapat memantau status perkembangan santri, tanggal mulai sakit, serta mencatat keluhan lanjutan melalui linimasa kondisi santri.
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Modal Ubah Status / Konfirmasi Sembuh / Rujukan -->
  <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <form action="<?= base_url('/laporan-kesehatan/' . $laporan['id'] . '/status') ?>" method="POST">
          <?= csrf_field() ?>
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="updateStatusModalLabel">
              <i class="bi bi-arrow-repeat text-teal"></i> Perbarui Status Kesehatan Santri
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body py-3">
            <div class="mb-3">
              <label for="modalStatus" class="form-label fw-semibold small text-dark">Pilih Status Baru <span class="text-danger">*</span></label>
              <select class="form-select" id="modalStatus" name="status" required onchange="toggleSembuhInput(this.value)">
                <?php foreach ($daftarStatus as $st): ?>
                  <option value="<?= esc($st) ?>" <?= ($laporan['status'] === $st) ? 'selected' : '' ?>>
                    <?= esc($st) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Input Tanggal Sembuh (Hanya jika memilih Sembuh) -->
            <div class="mb-3 <?= ($laporan['status'] === 'Sembuh') ? '' : 'd-none' ?>" id="divTanggalSembuh">
              <label for="tanggal_sembuh" class="form-label fw-semibold small text-success">
                <i class="bi bi-calendar-check me-1"></i> Tanggal Dinyatakan Sembuh
              </label>
              <input type="date" class="form-control" id="tanggal_sembuh" name="tanggal_sembuh"
                     value="<?= esc($laporan['tanggal_sembuh'] ?: date('Y-m-d')) ?>">
              <div class="form-text small text-muted">
                Tanggal sembuh hanya dapat ditetapkan dan disahkan oleh petugas kesehatan yang berwenang.
              </div>
            </div>

            <div class="mb-3">
              <label for="catatan_status" class="form-label fw-semibold small text-dark">Catatan Tindak Lanjut / Keterangan Kondisi</label>
              <textarea class="form-control" id="catatan_status" name="catatan" rows="3"
                        placeholder="Contoh: Suhu tubuh telah kembali normal 36.6C, santri diizinkan kembali ke asrama..."></textarea>
            </div>

            <div class="alert alert-light border small text-muted mb-0">
              <i class="bi bi-info-circle me-1"></i> Perubahan status akan dicatat secara permanen di riwayat status tanpa menghapus data sebelumnya.
            </div>
          </div>
          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-teal fw-semibold">
              <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan Status
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>

<script>
function toggleSembuhInput(val) {
  const el = document.getElementById('divTanggalSembuh');
  if (val === 'Sembuh') {
    el.classList.remove('d-none');
  } else {
    el.classList.add('d-none');
  }
}
</script>
<?= $this->endSection() ?>

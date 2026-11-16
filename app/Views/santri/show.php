<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title ?? 'Detail Profil Santri - SIMKES Santri') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0">

  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-2 small text-muted">
      <li class="breadcrumb-item"><a href="<?= base_url('/dashboard') ?>" class="text-decoration-none text-muted">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= base_url('/santri') ?>" class="text-decoration-none text-muted">Data Santri</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?= esc($santri['nama']) ?></li>
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

  <!-- Top Header with Actions -->
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h3 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
        <i class="bi bi-person-badge-fill text-teal"></i>
        <span>Profil & Rekam Medis Santri</span>
      </h3>
      <p class="text-muted small mb-0">Informasi identitas santri, penempatan kamar, dan riwayat kesehatan terhubung.</p>
    </div>

    <div class="d-flex gap-2">
      <a href="<?= base_url('/santri') ?>" class="btn btn-outline-secondary px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
      </a>
      <a href="<?= base_url('/santri/' . $santri['id'] . '/edit') ?>" class="btn btn-teal px-3 fw-semibold shadow-sm">
        <i class="bi bi-pencil-square me-1"></i> Ubah Data
      </a>
      <button type="button" class="btn btn-outline-danger px-3" data-bs-toggle="modal" data-bs-target="#deleteDetailModal">
        <i class="bi bi-trash me-1"></i> Hapus
      </button>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Bio Card -->
    <div class="col-12 col-lg-4">
      <div class="card card-dashboard border-0 shadow-sm mb-4">
        <div class="card-body p-4 text-center">
          <?php
            $isMale = ($santri['jenis_kelamin'] === 'Laki-laki');
            $bgAvatar = $isMale ? 'bg-primary-subtle text-primary border-primary' : 'bg-danger-subtle text-danger border-danger';
            $initials = mb_substr($santri['nama'], 0, 1);
          ?>
          <div class="avatar-circle-lg <?= $bgAvatar ?> mx-auto mb-3 border border-2">
            <?= esc($initials) ?>
          </div>
          <h5 class="fw-bold text-dark mb-1"><?= esc($santri['nama']) ?></h5>
          <div class="mb-3">
            <span class="badge bg-light text-dark border font-monospace px-2.5 py-1">
              NIS: <?= esc($santri['nis']) ?>
            </span>
          </div>

          <div class="d-flex justify-content-center gap-2 mb-4">
            <?php if ($isMale): ?>
              <span class="badge badge-soft-primary px-2.5 py-1.5">
                <i class="bi bi-gender-male me-1"></i> Laki-laki
              </span>
            <?php else: ?>
              <span class="badge badge-soft-danger px-2.5 py-1.5">
                <i class="bi bi-gender-female me-1"></i> Perempuan
              </span>
            <?php endif; ?>

            <?php if (!empty($santri['kelas'])): ?>
              <span class="badge bg-secondary-subtle text-secondary px-2.5 py-1.5">
                <i class="bi bi-mortarboard me-1"></i> <?= esc($santri['kelas']) ?>
              </span>
            <?php endif; ?>
          </div>

          <hr class="text-muted opacity-25 my-3">

          <!-- Detail List -->
          <div class="text-start">
            <div class="mb-3">
              <div class="small text-muted fw-semibold mb-1">
                <i class="bi bi-door-closed text-teal me-1"></i> Kamar Asrama:
              </div>
              <div class="fw-semibold text-dark ps-3">
                <?= !empty($santri['nama_kamar']) ? esc($santri['nama_kamar']) : '<span class="text-muted fst-italic">Belum Ditentukan</span>' ?>
              </div>
              <?php if (!empty($santri['pengawas_nama'])): ?>
                <div class="small text-muted ps-3 mt-0.5">
                  Pengawas: <?= esc($santri['pengawas_nama']) ?>
                </div>
              <?php endif; ?>
            </div>

            <div class="mb-3">
              <div class="small text-muted fw-semibold mb-1">
                <i class="bi bi-telephone text-teal me-1"></i> Kontak Wali / Ortu:
              </div>
              <div class="fw-semibold text-dark ps-3">
                <?php if (!empty($santri['kontak_wali'])): ?>
                  <a href="tel:<?= esc(preg_replace('/[^0-9]/', '', $santri['kontak_wali'])) ?>" class="text-decoration-none text-dark font-monospace">
                    <?= esc($santri['kontak_wali']) ?>
                  </a>
                <?php else: ?>
                  <span class="text-muted fst-italic">Belum diisi</span>
                <?php endif; ?>
              </div>
            </div>

            <div>
              <div class="small text-muted fw-semibold mb-1">
                <i class="bi bi-calendar-check text-teal me-1"></i> Tanggal Terdaftar:
              </div>
              <div class="text-dark small ps-3">
                <?= !empty($santri['created_at']) ? date('d M Y, H:i', strtotime($santri['created_at'])) : '-' ?>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Quick Action Card -->
      <div class="card card-dashboard border-0 shadow-sm p-3">
        <div class="d-flex align-items-center justify-content-between">
          <div class="small fw-semibold text-muted">Status Riwayat Medis</div>
          <span class="badge <?= empty($riwayatMedis) ? 'badge-soft-success' : 'badge-soft-warning' ?> px-2 py-1">
            <?= count($riwayatMedis) ?> Kasus Tercatat
          </span>
        </div>
      </div>
    </div>

    <!-- Right Column: Medical Records History -->
    <div class="col-12 col-lg-8">
      <div class="card card-dashboard border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="stat-icon info" style="width: 36px; height: 36px; font-size: 1.1rem;">
              <i class="bi bi-heart-pulse-fill"></i>
            </div>
            <div>
              <h5 class="fw-bold text-dark mb-0">Riwayat Rekam Medis Kesehatan</h5>
              <div class="small text-muted">Laporan keluhan sakit, pemeriksaan dokter, dan obat yang diresepkan</div>
            </div>
          </div>
          <span class="badge bg-light text-dark border px-2.5 py-1.5 small">
            <?= count($riwayatMedis) ?> Rekam Medis
          </span>
        </div>

        <div class="card-body p-4">
          <?php if (empty($riwayatMedis)): ?>
            <!-- Empty State -->
            <div class="text-center py-5">
              <div class="mb-3">
                <div class="d-inline-flex p-3 rounded-circle bg-success-subtle text-success">
                  <i class="bi bi-shield-check display-5"></i>
                </div>
              </div>
              <h5 class="fw-bold text-dark mb-1">Santri Dalam Kondisi Sehat</h5>
              <p class="text-muted small mb-0 max-w-md mx-auto">
                Alhamdulillah, belum ada riwayat keluhan sakit ataupun pemeriksaan klinis tercatat untuk santri ini di Poskestren.
              </p>
            </div>
          <?php else: ?>
            <!-- Riwayat Kasus List -->
            <div class="d-flex flex-column gap-4">
              <?php foreach ($riwayatMedis as $idx => $kasus): ?>
                <?php
                  $status = $kasus['status'] ?? 'Rawat Jalan';
                  $badgeClass = match ($status) {
                    'Sembuh'      => 'bg-success text-white',
                    'Rawat Inap'  => 'bg-danger text-white',
                    'Rujukan'     => 'bg-purple text-white bg-dark',
                    default       => 'bg-warning text-dark',
                  };
                ?>
                <div class="border rounded-3 p-3 p-md-4 bg-light position-relative">
                  <!-- Header Kasus -->
                  <div class="d-flex flex-column flex-sm-row sm-align-items-center justify-content-between gap-2 border-bottom pb-3 mb-3">
                    <div>
                      <div class="d-flex align-items-center gap-2">
                        <span class="badge <?= $badgeClass ?> px-2.5 py-1 small fw-semibold">
                          <i class="bi bi-activity me-1"></i> <?= esc($status) ?>
                        </span>
                        <span class="fw-bold text-dark">
                          Kasus #<?= esc($kasus['id']) ?> &bull; Tanggal Sakit: <?= date('d M Y', strtotime($kasus['tanggal_mulai_sakit'])) ?>
                        </span>
                      </div>
                      <div class="small text-muted mt-1">
                        Pelapor: <strong><?= esc($kasus['pelapor_nama'] ?? 'Pengawas') ?></strong>
                        <?php if (!empty($kasus['tanggal_sembuh'])): ?>
                          &bull; Tanggal Sembuh: <span class="text-success fw-semibold"><?= date('d M Y', strtotime($kasus['tanggal_sembuh'])) ?></span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>

                  <!-- Keluhan & Catatan -->
                  <div class="mb-3">
                    <div class="small fw-semibold text-muted text-uppercase mb-1">
                      <i class="bi bi-chat-left-text text-teal me-1"></i> Gejala / Keluhan Awal:
                    </div>
                    <div class="p-2.5 bg-white rounded border small text-dark">
                      <?= nl2br(esc($kasus['gejala'])) ?>
                    </div>
                  </div>

                  <?php if (!empty($kasus['catatan'])): ?>
                    <div class="mb-3">
                      <div class="small fw-semibold text-muted text-uppercase mb-1">
                        <i class="bi bi-journal-text text-teal me-1"></i> Catatan Tambahan:
                      </div>
                      <div class="p-2.5 bg-white rounded border small text-muted">
                        <?= nl2br(esc($kasus['catatan'])) ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <!-- Pemeriksaan Klinis Terhubung -->
                  <div class="mt-3">
                    <div class="small fw-bold text-dark mb-2 d-flex align-items-center gap-1">
                      <i class="bi bi-stethoscope text-primary"></i>
                      <span>Hasil Pemeriksaan Medis</span>
                    </div>

                    <?php if (empty($isTenagaMedis)): ?>
                      <div class="alert alert-light border small text-muted mb-0 py-2 d-flex align-items-center gap-2">
                        <i class="bi bi-shield-lock text-secondary"></i>
                        <span>Informasi rekam medis klinis & resep obat dilindungi dan hanya dapat diakses oleh Tenaga Medis Poskestren.</span>
                      </div>
                    <?php elseif (empty($kasus['pemeriksaan'])): ?>
                      <div class="alert alert-light border small text-muted mb-0 py-2">
                        <i class="bi bi-clock-history me-1"></i> Menunggu pemeriksaan dokter / tenaga medis Poskestren.
                      </div>
                    <?php else: ?>
                      <div class="d-flex flex-column gap-3">
                        <?php foreach ($kasus['pemeriksaan'] as $periksa): ?>
                          <div class="card border-0 shadow-none bg-white p-3 rounded-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                              <div class="small fw-bold text-teal">
                                <i class="bi bi-person-badge me-1"></i> <?= esc($periksa['dokter_nama'] ?? 'Tenaga Medis') ?>
                              </div>
                              <span class="small text-muted">
                                <i class="bi bi-calendar3 me-1"></i> <?= date('d M Y, H:i', strtotime($periksa['tanggal_periksa'])) ?>
                              </span>
                            </div>

                            <div class="small mb-1">
                              <strong>Diagnosis / Dugaan:</strong> 
                              <span class="text-danger fw-semibold"><?= esc($periksa['diagnosis_atau_dugaan']) ?></span>
                            </div>

                            <?php if (!empty($periksa['tindakan'])): ?>
                              <div class="small mb-1">
                                <strong>Tindakan Medis:</strong> <?= esc($periksa['tindakan']) ?>
                              </div>
                            <?php endif; ?>

                            <?php if (!empty($periksa['catatan'])): ?>
                              <div class="small text-muted mb-2">
                                <em>Catatan: <?= esc($periksa['catatan']) ?></em>
                              </div>
                            <?php endif; ?>

                            <!-- Pemberian Obat Terhubung -->
                            <?php if (!empty($periksa['obat'])): ?>
                              <div class="mt-2 pt-2 border-top">
                                <div class="small fw-semibold text-muted mb-1">
                                  <i class="bi bi-capsule-pill text-teal me-1"></i> Resep & Terapi Obat:
                                </div>
                                <div class="table-responsive">
                                  <table class="table table-sm table-bordered mb-0 small">
                                    <thead class="bg-light">
                                      <tr>
                                        <th>Nama Obat</th>
                                        <th>Dosis</th>
                                        <th>Aturan Pakai</th>
                                        <th>Durasi</th>
                                        <th>Instruksi Petugas</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php foreach ($periksa['obat'] as $ob): ?>
                                        <tr>
                                          <td class="fw-semibold text-dark"><?= esc($ob['nama_obat']) ?></td>
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
                    <?php endif; ?>

                  </div>

                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Konfirmasi Hapus Santri dari Show Page -->
  <div class="modal fade" id="deleteDetailModal" tabindex="-1" aria-labelledby="deleteDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2" id="deleteDetailModalLabel">
            <i class="bi bi-exclamation-triangle-fill"></i> Konfirmasi Hapus Santri
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body py-3">
          <p class="mb-1">Apakah Anda yakin ingin menghapus data santri berikut?</p>
          <div class="p-3 bg-light rounded-3 my-2 border">
            <div class="fw-bold text-dark"><?= esc($santri['nama']) ?></div>
            <div class="small text-muted">NIS: <?= esc($santri['nis']) ?> &bull; Kamar: <?= esc($santri['nama_kamar'] ?? '-') ?></div>
          </div>
          <div class="small text-danger">
            <i class="bi bi-info-circle me-1"></i> Data yang dihapus tidak dapat dipulihkan kembali. Seluruh riwayat kesehatan terkait juga akan terhapus.
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <form action="<?= base_url('/santri/' . $santri['id'] . '/delete') ?>" method="POST" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger fw-semibold">
              <i class="bi bi-trash-fill me-1"></i> Ya, Hapus Data
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

</div>
<?= $this->endSection() ?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title ?? 'Ekspor Data - SIMKES Santri') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0">

  <!-- Flash Alerts (Notifikasi Sukses / Gagal) -->
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
      <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
      <div class="fw-medium"><?= esc(session()->getFlashdata('error')) ?></div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Breadcrumb & Header -->
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small text-muted">
          <li class="breadcrumb-item"><a href="<?= base_url('/dashboard') ?>" class="text-decoration-none text-muted">Dashboard</a></li>
          <li class="breadcrumb-item active" aria-current="page">Ekspor Data</li>
        </ol>
      </nav>
      <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-file-earmark-arrow-down-fill text-success"></i>
        <span>Pusat Ekspor Data SIMKES</span>
      </h3>
      <p class="text-muted small mb-0 mt-1">Unduh data santri dan rekapitulasi laporan rekam medis ke dalam format spreadsheet Microsoft Excel (.xlsx) atau CSV.</p>
    </div>
  </div>

  <!-- Tab Navigasi Opsi Ekspor -->
  <ul class="nav nav-pills nav-fill bg-white p-2 rounded-3 shadow-sm mb-4 border" id="exportTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active fw-bold py-2 d-flex align-items-center justify-content-center gap-2" id="tab-santri-btn" data-bs-toggle="pill" data-bs-target="#tab-santri" type="button" role="tab">
        <i class="bi bi-people-fill text-primary"></i>
        <span>Ekspor Data Master Santri</span>
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link fw-bold py-2 d-flex align-items-center justify-content-center gap-2" id="tab-kesehatan-btn" data-bs-toggle="pill" data-bs-target="#tab-kesehatan" type="button" role="tab">
        <i class="bi bi-clipboard2-pulse-fill text-danger"></i>
        <span>Ekspor Rekapitulasi Laporan Kesehatan</span>
      </button>
    </li>
  </ul>

  <div class="tab-content" id="exportTabsContent">
    
    <!-- TAB 1: EKSPOR DATA SANTRI -->
    <div class="tab-pane fade show active" id="tab-santri" role="tabpanel">
      <div class="row g-4">
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3">
              <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-filter-circle text-primary"></i>
                <span>Filter & Parameter Ekspor Data Santri</span>
              </h5>
            </div>
            <div class="card-body p-4">
              <form action="<?= base_url('/data-transfer/ekspor/santri') ?>" method="get" target="_blank">
                
                <div class="row g-3 mb-4">
                  <!-- Pilihan Kamar -->
                  <div class="col-md-6">
                    <label for="santri_kamar_id" class="form-label small fw-semibold text-secondary">Kamar / Asrama</label>
                    <select class="form-select" id="santri_kamar_id" name="kamar_id">
                      <option value="">Semua Kamar (Sesuai Hak Akses)</option>
                      <?php foreach ($daftarKamar as $kamar): ?>
                        <option value="<?= esc($kamar['id']) ?>"><?= esc($kamar['nama_kamar']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <!-- Pilihan Kelas -->
                  <div class="col-md-6">
                    <label for="santri_kelas" class="form-label small fw-semibold text-secondary">Tingkat / Kelas</label>
                    <select class="form-select" id="santri_kelas" name="kelas">
                      <option value="">Semua Kelas</option>
                      <?php foreach ($daftarKelas as $kelas): ?>
                        <option value="<?= esc($kelas) ?>"><?= esc($kelas) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <!-- Pencarian Nama/NIS -->
                  <div class="col-md-12">
                    <label for="santri_keyword" class="form-label small fw-semibold text-secondary">Kata Kunci Pencarian (Opsional)</label>
                    <div class="input-group">
                      <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                      <input type="text" class="form-control" id="santri_keyword" name="keyword" placeholder="Saring berdasarkan nama santri atau NIS tertentu...">
                    </div>
                  </div>

                  <!-- Format Berkas -->
                  <div class="col-md-12">
                    <label class="form-label small fw-semibold text-secondary d-block">Pilihan Format Berkas Ekspor</label>
                    <div class="d-flex gap-4">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="format" id="format_santri_xlsx" value="xlsx" checked>
                        <label class="form-check-label fw-medium d-flex align-items-center gap-1" for="format_santri_xlsx">
                          <i class="bi bi-file-earmark-excel-fill text-success"></i> Microsoft Excel (.xlsx)
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="format" id="format_santri_csv" value="csv">
                        <label class="form-check-label fw-medium d-flex align-items-center gap-1" for="format_santri_csv">
                          <i class="bi bi-filetype-csv text-primary"></i> Comma Separated Values (.csv)
                        </label>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="d-flex justify-content-end">
                  <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm d-flex align-items-center gap-2">
                    <i class="bi bi-download"></i> Unduh Berkas Data Santri
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card border-0 shadow-sm rounded-3 bg-light-subtle h-100">
            <div class="card-body p-4">
              <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                <i class="bi bi-shield-check text-success fs-5"></i>
                <span>Standar Keamanan Ekspor</span>
              </h6>
              <p class="text-muted small mb-3">Sistem menerapkan proteksi menyeluruh pada setiap ekspor:</p>
              <ul class="small text-secondary ps-3 mb-3">
                <li class="mb-2"><strong>Mitigasi Formula Injection:</strong> Menetralisir karakter berisiko (<code>=</code>, <code>+</code>, <code>-</code>, <code>@</code>) guna mencegah eksekusi formula jahat.</li>
                <li class="mb-2"><strong>Penegakan Hak Akses:</strong> Pengawas kamar hanya dapat mengunduh santri dari kamar binaannya.</li>
                <li class="mb-2"><strong>Pencatatan Audit Trail:</strong> Setiap ekspor tercatat secara transparan di tabel log audit sistem.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: EKSPOR LAPORAN KESEHATAN -->
    <div class="tab-pane fade" id="tab-kesehatan" role="tabpanel">
      <div class="row g-4">
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3">
              <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-filter-circle text-danger"></i>
                <span>Filter & Parameter Rekapitulasi Kesehatan</span>
              </h5>
            </div>
            <div class="card-body p-4">
              <form action="<?= base_url('/data-transfer/ekspor/kesehatan') ?>" method="get" target="_blank">
                
                <div class="row g-3 mb-4">
                  <!-- Rentang Tanggal Mulai Sakit -->
                  <div class="col-md-6">
                    <label for="tanggal_mulai" class="form-label small fw-semibold text-secondary">Dari Tanggal Sakit</label>
                    <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai">
                  </div>
                  <div class="col-md-6">
                    <label for="tanggal_akhir" class="form-label small fw-semibold text-secondary">Sampai Tanggal Sakit</label>
                    <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir">
                  </div>

                  <!-- Pilihan Kamar -->
                  <div class="col-md-6">
                    <label for="kesehatan_kamar_id" class="form-label small fw-semibold text-secondary">Kamar / Asrama</label>
                    <select class="form-select" id="kesehatan_kamar_id" name="kamar_id">
                      <option value="">Semua Kamar (Sesuai Hak Akses)</option>
                      <?php foreach ($daftarKamar as $kamar): ?>
                        <option value="<?= esc($kamar['id']) ?>"><?= esc($kamar['nama_kamar']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <!-- Pilihan Status Kesehatan -->
                  <div class="col-md-6">
                    <label for="kesehatan_status" class="form-label small fw-semibold text-secondary">Status Kasus Kesehatan</label>
                    <select class="form-select" id="kesehatan_status" name="status">
                      <option value="">Semua Status</option>
                      <?php foreach ($daftarStatus as $st): ?>
                        <option value="<?= esc($st) ?>"><?= esc($st) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <!-- Format Berkas -->
                  <div class="col-md-12">
                    <label class="form-label small fw-semibold text-secondary d-block">Pilihan Format Berkas Ekspor</label>
                    <div class="d-flex gap-4">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="format" id="format_kes_xlsx" value="xlsx" checked>
                        <label class="form-check-label fw-medium d-flex align-items-center gap-1" for="format_kes_xlsx">
                          <i class="bi bi-file-earmark-excel-fill text-success"></i> Microsoft Excel (.xlsx)
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="format" id="format_kes_csv" value="csv">
                        <label class="form-check-label fw-medium d-flex align-items-center gap-1" for="format_kes_csv">
                          <i class="bi bi-filetype-csv text-primary"></i> Comma Separated Values (.csv)
                        </label>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="d-flex justify-content-end">
                  <button type="submit" class="btn btn-danger px-4 fw-bold shadow-sm d-flex align-items-center gap-2">
                    <i class="bi bi-download"></i> Unduh Rekapitulasi Laporan Kesehatan
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card border-0 shadow-sm rounded-3 bg-light-subtle h-100">
            <div class="card-body p-4">
              <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                <i class="bi bi-lock-fill text-danger fs-5"></i>
                <span>Privasi Data Rekam Medis</span>
              </h6>
              <?php if ($isPengawasKamar): ?>
                <div class="alert alert-warning border-0 small rounded-3 mb-3">
                  <i class="bi bi-eye-slash-fill me-1"></i>
                  <strong>Perlindungan Privasi Pasien:</strong> Sebagai <em>Pengawas Kamar</em>, kolom klinis mendalam (diagnosis dokter & resep obat) disembunyikan dalam berkas ekspor Anda untuk menjaga kerahasiaan medis santri.
                </div>
              <?php else: ?>
                <div class="alert alert-success border-0 small rounded-3 mb-3">
                  <i class="bi bi-check-circle-fill me-1"></i>
                  <strong>Akses Medis Penuh:</strong> Sebagai tenaga kesehatan/administrator, Anda berwenang mengekspor rekam medis lengkap mencakup diagnosis klinis, tindakan medis, dan terapi obat yang diberikan.
                </div>
              <?php endif; ?>
              <p class="text-muted small mb-0">Semua aktivitas ekspor dicatat secara otomatis ke dalam tabel audit log sistem demi akuntabilitas rekam medis Poskestren.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

</div>
<?= $this->endSection() ?>

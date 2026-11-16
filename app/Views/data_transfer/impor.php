<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title ?? 'Impor Data Santri - SIMKES Santri') ?>
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

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
      <i class="bi bi-check-circle-fill fs-5 flex-shrink-0"></i>
      <div class="fw-medium"><?= esc(session()->getFlashdata('success')) ?></div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Breadcrumb & Header -->
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small text-muted">
          <li class="breadcrumb-item"><a href="<?= base_url('/dashboard') ?>" class="text-decoration-none text-muted">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="<?= base_url('/santri') ?>" class="text-decoration-none text-muted">Data Santri</a></li>
          <li class="breadcrumb-item active" aria-current="page">Impor Data</li>
        </ol>
      </nav>
      <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-file-earmark-arrow-up-fill text-primary"></i>
        <span>Impor Data Santri Massal</span>
      </h3>
      <p class="text-muted small mb-0 mt-1">Tambahkan data santri secara massal menggunakan berkas spreadsheet CSV atau Microsoft Excel (.xlsx).</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="<?= base_url('/santri') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Santri
      </a>
    </div>
  </div>

  <div class="row g-4">
    <!-- Formulir Unggah Berkas -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm rounded-3 h-100">
        <div class="card-header bg-white border-bottom py-3">
          <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-cloud-arrow-up text-primary"></i>
            <span>Unggah Berkas Data Santri</span>
          </h5>
        </div>
        <div class="card-body p-4">
          <form action="<?= base_url('/data-transfer/impor/preview') ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <!-- Drag & Drop Zone -->
            <div class="p-4 border-2 border-dashed border-primary border-opacity-25 rounded-3 text-center mb-4 bg-light-subtle">
              <i class="bi bi-file-earmark-spreadsheet text-primary display-4 mb-2 d-block"></i>
              <h6 class="fw-bold text-dark mb-1">Pilih Berkas CSV atau Excel (.xlsx)</h6>
              <p class="text-muted small mb-3">Maksimal ukuran berkas: <strong>5 MB</strong>. Format didukung: <code>.xlsx</code>, <code>.csv</code></p>
              
              <div class="col-md-8 mx-auto">
                <input class="form-control form-control-lg text-secondary" type="file" name="file_import" id="file_import" accept=".csv, .xlsx, .xls" required>
              </div>
            </div>

            <!-- Ketentuan Keamanan & Aturan -->
            <div class="alert alert-info border-0 rounded-3 d-flex gap-3 mb-4">
              <i class="bi bi-shield-lock-fill fs-4 text-info flex-shrink-0"></i>
              <div class="small">
                <strong>Ketentuan Keamanan & Aturan Impor:</strong>
                <ul class="mb-0 ps-3 mt-1 text-secondary">
                  <li>Data lama <strong>tidak akan ditimpa</strong>. Jika NIS sudah terdaftar di database, baris tersebut akan dilaporkan sebagai kesalahan.</li>
                  <li>Duplikasi NIS dalam berkas yang sama otomatis dicegah.</li>
                  <li>Sistem akan menyajikan <strong>Pratinjau Data Lengkap</strong> (jumlah baris valid dan tidak valid) sebelum data disimpan ke database.</li>
                  <li>Penyimpanan menggunakan transaksi database yang aman (atomic rollback jika terjadi kesalahan).</li>
                </ul>
              </div>
            </div>

            <!-- Tombol Submit Pratinjau -->
            <div class="d-flex justify-content-end gap-2">
              <button type="reset" class="btn btn-light px-3">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
              </button>
              <button type="submit" class="btn btn-primary px-4 fw-medium shadow-sm">
                <i class="bi bi-eye me-1"></i> Periksa & Tampilkan Pratinjau
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Informasi Template & Panduan Kolom -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-bottom py-3">
          <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-arrow-down text-success"></i>
            <span>Unduh Template Resmi</span>
          </h5>
        </div>
        <div class="card-body p-4">
          <p class="text-muted small mb-3">Gunakan template resmi SIMKES Santri untuk memastikan nama kolom dan format data sesuai standar:</p>
          <div class="d-grid gap-2">
            <a href="<?= base_url('/data-transfer/template-santri/xlsx') ?>" class="btn btn-success d-flex align-items-center justify-content-center gap-2 py-2">
              <i class="bi bi-file-earmark-excel-fill fs-5"></i>
              <span>Unduh Template Excel (.xlsx)</span>
            </a>
            <a href="<?= base_url('/data-transfer/template-santri/csv') ?>" class="btn btn-outline-success d-flex align-items-center justify-content-center gap-2 py-2">
              <i class="bi bi-filetype-csv fs-5"></i>
              <span>Unduh Template CSV (.csv)</span>
            </a>
          </div>
        </div>
      </div>

      <!-- Panduan Format Kolom -->
      <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom py-3">
          <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-info-circle text-primary"></i>
            <span>Struktur & Aturan Kolom</span>
          </h5>
        </div>
        <div class="card-body p-4">
          <div class="table-responsive">
            <table class="table table-sm table-bordered small align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Nama Kolom</th>
                  <th>Wajib?</th>
                  <th>Aturan & Format</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><code>NIS</code></td>
                  <td><span class="badge bg-danger">Wajib</span></td>
                  <td>3-30 karakter, unik, alfanumerik.</td>
                </tr>
                <tr>
                  <td><code>Nama Lengkap</code></td>
                  <td><span class="badge bg-danger">Wajib</span></td>
                  <td>3-100 karakter nama santri.</td>
                </tr>
                <tr>
                  <td><code>Jenis Kelamin</code></td>
                  <td><span class="badge bg-danger">Wajib</span></td>
                  <td><code>Laki-laki</code> atau <code>Perempuan</code>.</td>
                </tr>
                <tr>
                  <td><code>Nama Kamar</code></td>
                  <td><span class="badge bg-danger">Wajib</span></td>
                  <td>Harus sesuai dengan nama kamar di sistem.</td>
                </tr>
                <tr>
                  <td><code>Kelas</code></td>
                  <td><span class="badge bg-secondary">Opsional</span></td>
                  <td>Maksimal 50 karakter (contoh: <code>10-A</code>).</td>
                </tr>
                <tr>
                  <td><code>Kontak Wali</code></td>
                  <td><span class="badge bg-secondary">Opsional</span></td>
                  <td>Maksimal 50 karakter nomor telepon wali.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <?php if ($isPengawasKamar): ?>
            <div class="alert alert-warning border-0 rounded-3 small mt-3 mb-0">
              <i class="bi bi-exclamation-triangle-fill me-1"></i>
              <strong>Perhatian Pengawas Kamar:</strong> Anda hanya dapat mengimpor data santri yang dialokasikan ke kamar asuhan binaan Anda:
              <div class="mt-1 fw-semibold">
                <?php if (!empty($kamarOptions)): ?>
                  <?= implode(', ', array_map(fn($k) => esc($k['nama_kamar']), $kamarOptions)) ?>
                <?php else: ?>
                  <span class="text-danger">Belum ada kamar yang ditugaskan kepada Anda.</span>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

</div>
<?= $this->endSection() ?>

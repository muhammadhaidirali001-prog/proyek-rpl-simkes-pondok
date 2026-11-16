<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title ?? 'Pratinjau Impor Data Santri - SIMKES Santri') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0">

  <!-- Breadcrumb & Header -->
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small text-muted">
          <li class="breadcrumb-item"><a href="<?= base_url('/dashboard') ?>" class="text-decoration-none text-muted">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="<?= base_url('/santri') ?>" class="text-decoration-none text-muted">Data Santri</a></li>
          <li class="breadcrumb-item"><a href="<?= base_url('/data-transfer/impor') ?>" class="text-decoration-none text-muted">Impor Data</a></li>
          <li class="breadcrumb-item active" aria-current="page">Pratinjau</li>
        </ol>
      </nav>
      <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-file-earmark-check-fill text-primary"></i>
        <span>Pratinjau Verifikasi Berkas Impor</span>
      </h3>
      <p class="text-muted small mb-0 mt-1">
        Berkas: <span class="badge bg-secondary-subtle text-secondary fw-semibold font-monospace"><?= esc($fileName) ?></span>
      </p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="<?= base_url('/data-transfer/impor') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-counterclockwise me-1"></i> Batal & Unggah Ulang
      </a>
    </div>
  </div>

  <!-- Statistik Ringkasan Verifikasi Baris -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm rounded-3 bg-white p-3">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 bg-primary bg-opacity-10 p-3 text-primary">
            <i class="bi bi-list-check fs-3"></i>
          </div>
          <div>
            <div class="text-muted small fw-medium">Total Baris Terdeteksi</div>
            <div class="fs-4 fw-bold text-dark"><?= count($validRows) + count($invalidRows) ?> <span class="fs-6 fw-normal text-muted">baris</span></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm rounded-3 bg-white p-3">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 bg-success bg-opacity-10 p-3 text-success">
            <i class="bi bi-check-circle-fill fs-3"></i>
          </div>
          <div>
            <div class="text-muted small fw-medium">Baris Valid (Siap Disimpan)</div>
            <div class="fs-4 fw-bold text-success"><?= count($validRows) ?> <span class="fs-6 fw-normal text-muted">baris</span></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm rounded-3 bg-white p-3">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 bg-danger bg-opacity-10 p-3 text-danger">
            <i class="bi bi-exclamation-triangle-fill fs-3"></i>
          </div>
          <div>
            <div class="text-muted small fw-medium">Baris Bermasalah (Gagal Validasi)</div>
            <div class="fs-4 fw-bold text-danger"><?= count($invalidRows) ?> <span class="fs-6 fw-normal text-muted">baris</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Panel Notifikasi Status Validasi -->
  <?php if (!empty($invalidRows)): ?>
    <div class="alert alert-warning border-0 rounded-3 shadow-sm mb-4">
      <div class="d-flex gap-3">
        <i class="bi bi-exclamation-triangle-fill fs-4 text-warning flex-shrink-0"></i>
        <div>
          <h6 class="fw-bold mb-1">Ditemukan <?= count($invalidRows) ?> Baris Data yang Bermasalah</h6>
          <p class="mb-2 small">
            Baris data dengan penanda merah tidak dapat diimpor karena tidak memenuhi aturan validasi (misal: NIS duplikat di sistem, kamar tidak ditemukan, atau di luar hak asuh pengawas).
          </p>
          <div class="small fw-semibold">
            Aturan Sistem: Baris bermasalah akan otomatis dilewati. Data santri lama di sistem <strong>tidak akan ditimpa</strong>. Hanya baris yang berstatus <span class="badge bg-success">VALID</span> yang akan disimpan ke database.
          </div>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="alert alert-success border-0 rounded-3 shadow-sm d-flex align-items-center gap-3 mb-4">
      <i class="bi bi-patch-check-fill fs-3 text-success flex-shrink-0"></i>
      <div>
        <h6 class="fw-bold mb-0">Luar Biasa! Seluruh Data Memenuhi Standar Validasi</h6>
        <div class="small text-muted">Semua <?= count($validRows) ?> baris data siap dimasukkan ke database melalui transaksi yang aman.</div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Tabel Pratinjau Seluruh Baris -->
  <div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
      <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-table text-primary"></i>
        <span>Rincian Hasil Validasi per Baris</span>
      </h5>
      <div class="d-flex gap-2">
        <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
          <i class="bi bi-check-lg me-1"></i> <?= count($validRows) ?> Valid
        </span>
        <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">
          <i class="bi bi-x-lg me-1"></i> <?= count($invalidRows) ?> Error
        </span>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light small">
          <tr>
            <th class="text-center" style="width: 70px;">Baris</th>
            <th style="width: 110px;">Status</th>
            <th>NIS</th>
            <th>Nama Lengkap</th>
            <th>Jenis Kelamin</th>
            <th>Kamar</th>
            <th>Kelas</th>
            <th>Kontak Wali</th>
            <th>Keterangan / Kesalahan</th>
          </tr>
        </thead>
        <tbody class="small">
          <?php
            // Gabungkan dan urutkan kembali berdasarkan nomor baris asli
            $allRows = array_merge($validRows, $invalidRows);
            usort($allRows, fn($a, $b) => $a['row_num'] <=> $b['row_num']);
          ?>

          <?php foreach ($allRows as $r): ?>
            <tr class="<?= $r['is_valid'] ? '' : 'table-danger bg-opacity-25' ?>">
              <td class="text-center fw-bold text-muted"><?= esc($r['row_num']) ?></td>
              <td>
                <?php if ($r['is_valid']): ?>
                  <span class="badge bg-success text-white px-2 py-1">
                    <i class="bi bi-check-circle me-1"></i> VALID
                  </span>
                <?php else: ?>
                  <span class="badge bg-danger text-white px-2 py-1">
                    <i class="bi bi-exclamation-circle me-1"></i> ERROR
                  </span>
                <?php endif; ?>
              </td>
              <td class="font-monospace fw-semibold"><?= esc($r['nis'] ?: '-') ?></td>
              <td class="fw-medium text-dark"><?= esc($r['nama'] ?: '-') ?></td>
              <td><?= esc($r['jenis_kelamin'] ?: '-') ?></td>
              <td>
                <?php if (!empty($r['nama_kamar'])): ?>
                  <span class="badge bg-info-subtle text-dark border"><?= esc($r['nama_kamar']) ?></span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td><?= esc($r['kelas'] ?: '-') ?></td>
              <td><?= esc($r['kontak_wali'] ?: '-') ?></td>
              <td>
                <?php if ($r['is_valid']): ?>
                  <span class="text-success"><i class="bi bi-check-lg me-1"></i> Siap disimpan ke database</span>
                <?php else: ?>
                  <ul class="mb-0 ps-3 text-danger fw-medium">
                    <?php foreach ($r['errors'] as $err): ?>
                      <li><?= esc($err) ?></li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Panel Konfirmasi & Tombol Aksi Simpan -->
  <div class="card border-0 shadow-sm rounded-3 bg-white p-4">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
      <div>
        <h6 class="fw-bold text-dark mb-1">Konfirmasi Penyimpanan Data Santri</h6>
        <p class="text-muted small mb-0">
          <?php if (!empty($validRows)): ?>
            Klik tombol di sebelah kanan untuk menyimpan sebanyak <strong><?= count($validRows) ?> baris data yang valid</strong> ke database. Data yang tersimpan tidak akan menimpa santri lama.
          <?php else: ?>
            <span class="text-danger fw-semibold">Tidak ada baris data valid yang dapat disimpan. Silakan perbaiki berkas spreadsheet Anda dan unggah kembali.</span>
          <?php endif; ?>
        </p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= base_url('/data-transfer/impor') ?>" class="btn btn-outline-secondary px-3">
          <i class="bi bi-arrow-left me-1"></i> Batal / Unggah Ulang
        </a>
        <?php if (!empty($validRows)): ?>
          <form action="<?= base_url('/data-transfer/impor/simpan') ?>" method="post" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin mengimpor <?= count($validRows) ?> data santri yang valid ini ke database?');">
            <?= csrf_field() ?>
            <input type="hidden" name="preview_token" value="<?= esc($previewToken) ?>">
            <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm">
              <i class="bi bi-cloud-arrow-down-fill me-1"></i> Konfirmasi & Simpan (<?= count($validRows) ?> Santri)
            </button>
          </form>
        <?php else: ?>
          <button class="btn btn-secondary px-4 fw-bold" disabled>
            <i class="bi bi-slash-circle me-1"></i> Tidak Ada Data Valid
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>
<?= $this->endSection() ?>

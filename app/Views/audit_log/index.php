<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title ?? 'Audit Log Sistem - SIMKES Santri') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0">

  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-2 small text-muted">
      <li class="breadcrumb-item"><a href="<?= base_url('/dashboard') ?>" class="text-decoration-none text-muted">Dashboard</a></li>
      <li class="breadcrumb-item active" aria-current="page">Audit Log Sistem</li>
    </ol>
  </nav>

  <!-- Header -->
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h3 class="fw-bold text-dark mb-0">Audit Trail & Log Keamanan Sistem</h3>
        <span class="badge bg-dark px-3 py-1.5 fw-semibold small">
          <i class="bi bi-shield-lock-fill me-1"></i> Terenkripsi & Permanen
        </span>
      </div>
      <p class="text-muted small mb-0">Pencatatan kronologis seluruh aktivitas otentikasi, master santri, pelayanan medis, transfer data, dan hak akses.</p>
    </div>

    <div>
      <a href="<?= base_url('/users') ?>" class="btn btn-outline-teal px-3">
        <i class="bi bi-person-gear me-1"></i> Kelola Hak Akses
      </a>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="card card-dashboard border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form action="<?= base_url('/audit-log') ?>" method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-md-3">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control border-start-0" name="keyword" placeholder="Cari nama, rincian..."
                   value="<?= esc($filters['keyword'] ?? '') ?>">
          </div>
        </div>

        <div class="col-6 col-md-3">
          <select class="form-select" name="aksi">
            <option value="">Semua Jenis Aktivitas</option>
            <?php foreach ($distinctAksi as $ak): ?>
              <option value="<?= esc($ak) ?>" <?= (($filters['aksi'] ?? '') === $ak) ? 'selected' : '' ?>>
                <?= esc($ak) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-md-2">
          <input type="date" class="form-control" name="tanggal_mulai" placeholder="Tanggal Mulai"
                 value="<?= esc($filters['tanggal_mulai'] ?? '') ?>">
        </div>

        <div class="col-6 col-md-2">
          <input type="date" class="form-control" name="tanggal_akhir" placeholder="Tanggal Akhir"
                 value="<?= esc($filters['tanggal_akhir'] ?? '') ?>">
        </div>

        <div class="col-6 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-teal w-100 fw-medium">Filter</button>
          <a href="<?= base_url('/audit-log') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
      </form>
    </div>
  </div>

  <!-- Audit Table Card -->
  <div class="card card-dashboard border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
          <tr>
            <th class="ps-4" style="width: 70px;">#ID</th>
            <th style="width: 170px;">Waktu & Tanggal</th>
            <th style="width: 200px;">Pengguna (Pelaksana)</th>
            <th style="width: 220px;">Jenis Aktivitas</th>
            <th>Detail & Referensi Data</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($logs)): ?>
            <?php foreach ($logs as $log): ?>
              <?php
                $aksi = $log['aksi'];
                $badgeClass = match (true) {
                  str_contains($aksi, 'LOGIN_BERHASIL') => 'bg-success text-white',
                  str_contains($aksi, 'LOGIN_GAGAL')    => 'bg-danger text-white',
                  str_contains($aksi, 'LOGOUT')         => 'bg-secondary text-white',
                  str_contains($aksi, 'TAMBAH')         => 'bg-primary text-white',
                  str_contains($aksi, 'UBAH')           => 'bg-warning text-dark',
                  str_contains($aksi, 'HAPUS')          => 'bg-danger text-white',
                  str_contains($aksi, 'PEMERIKSAAN')    => 'bg-info text-dark',
                  str_contains($aksi, 'SEMBUH')         => 'bg-success text-white',
                  str_contains($aksi, 'RUJUKAN')        => 'bg-purple text-white',
                  str_contains($aksi, 'IMPOR')          => 'bg-teal text-white',
                  str_contains($aksi, 'EKSPOR')         => 'bg-indigo text-white',
                  default                               => 'bg-light text-dark border',
                };
                $badgeStyle = '';
                if (str_contains($aksi, 'RUJUKAN')) $badgeStyle = 'background-color: #7e22ce;';
                if (str_contains($aksi, 'IMPOR'))   $badgeStyle = 'background-color: #0d9488;';
                if (str_contains($aksi, 'EKSPOR'))  $badgeStyle = 'background-color: #4f46e5;';
              ?>
              <tr>
                <td class="ps-4 font-monospace small text-muted"><?= esc($log['id']) ?></td>
                <td class="font-monospace small text-dark">
                  <?= date('d/m/Y H:i:s', strtotime($log['waktu'])) ?>
                </td>
                <td>
                  <?php if (!empty($log['nama_pengguna'])): ?>
                    <div class="fw-semibold text-dark small"><?= esc($log['nama_pengguna']) ?></div>
                    <div class="text-muted font-monospace" style="font-size: 0.72rem;"><?= esc($log['role_pengguna'] ?? '-') ?></div>
                  <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary small">Tamu / Sistem</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge <?= $badgeClass ?> px-2.5 py-1 small fw-semibold" style="<?= $badgeStyle ?>">
                    <?= esc($log['aksi']) ?>
                  </span>
                </td>
                <td class="small text-dark">
                  <?= esc($log['detail_perubahan'] ?? '-') ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center py-5 text-muted">
                <i class="bi bi-shield-check display-4 text-muted opacity-50 mb-2"></i>
                <div class="fw-semibold">Tidak ada catatan audit log yang sesuai dengan filter.</div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pager->getPageCount('audit') > 1): ?>
      <div class="card-footer bg-white border-0 py-3">
        <?= $pager->links('audit', 'default_full') ?>
      </div>
    <?php endif; ?>
  </div>

</div>
<?= $this->endSection() ?>

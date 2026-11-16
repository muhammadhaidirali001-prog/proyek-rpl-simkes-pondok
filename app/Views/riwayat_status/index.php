<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title ?? 'Riwayat Status Kesehatan Santri - SIMKES Santri') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0">

  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-2 small text-muted">
      <li class="breadcrumb-item"><a href="<?= base_url('/dashboard') ?>" class="text-decoration-none text-muted">Dashboard</a></li>
      <li class="breadcrumb-item active" aria-current="page">Riwayat Status</li>
    </ol>
  </nav>

  <!-- Header Section -->
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-clock-history text-teal"></i>
        <span>Linimasa Riwayat Status Santri</span>
      </h3>
      <p class="text-muted small mb-0 mt-1">
        Jejak audit kondisi medis santri (*non-destructive audit trail*): Dilaporkan, Perawatan, Rujukan, dan Sembuh.
      </p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= base_url('/laporan-kesehatan') ?>" class="btn btn-teal px-3 text-white">
        <i class="bi bi-clipboard2-pulse me-1"></i> Laporan Sakit
      </a>
    </div>
  </div>

  <!-- Table Card -->
  <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-4">Waktu Transisi</th>
            <th>Santri & Kamar</th>
            <th>Status Kondisi Baru</th>
            <th>Catatan Petugas</th>
            <th>Petugas Pencatat</th>
            <th class="text-end pe-4">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($daftarRiwayat)): ?>
            <?php foreach ($daftarRiwayat as $r): ?>
              <tr>
                <td class="ps-4 font-monospace small text-dark fw-bold">
                  <?= date('d M Y, H:i', strtotime($r['tanggal'])) ?> WIB
                </td>
                <td>
                  <div class="fw-bold text-dark"><?= esc($r['nama_santri']) ?></div>
                  <div class="small text-muted">NIS: <?= esc($r['nis']) ?> &bull; <?= esc($r['nama_kamar'] ?? '-') ?></div>
                </td>
                <td>
                  <?php
                    $status = $r['status'];
                    $badgeClass = match ($status) {
                      'Sembuh'            => 'bg-success text-white',
                      'Dalam Perawatan'   => 'bg-danger text-white',
                      'Dalam Pemeriksaan' => 'bg-info text-white',
                      'Dirujuk', 'Rujukan RS' => 'text-white',
                      default             => 'bg-warning text-dark',
                    };
                    $badgeStyle = in_array($status, ['Dirujuk', 'Rujukan RS']) ? 'background-color: #7e22ce;' : '';
                  ?>
                  <span class="badge <?= $badgeClass ?> px-2.5 py-1" style="<?= $badgeStyle ?>">
                    <?= esc($status) ?>
                  </span>
                </td>
                <td class="small text-dark"><?= esc($r['catatan'] ?: '-') ?></td>
                <td class="small text-muted">
                  <i class="bi bi-person-circle text-primary me-1"></i><?= esc($r['nama_petugas'] ?? 'Sistem') ?>
                </td>
                <td class="text-end pe-4">
                  <a href="<?= base_url('/laporan-kesehatan/' . $r['laporan_id']) ?>" class="btn btn-sm btn-outline-teal rounded-pill px-3">
                    <i class="bi bi-eye"></i> Kasus
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center py-5 text-muted small">
                <i class="bi bi-clock-history fs-1 text-muted d-block mb-2"></i>
                Belum ada data transisi status tercatat.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
<?= $this->endSection() ?>

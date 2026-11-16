<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title ?? 'Resep & Terapi Obat Santri - SIMKES Santri') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0">

  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-2 small text-muted">
      <li class="breadcrumb-item"><a href="<?= base_url('/dashboard') ?>" class="text-decoration-none text-muted">Dashboard</a></li>
      <li class="breadcrumb-item active" aria-current="page">Resep & Obat Santri</li>
    </ol>
  </nav>

  <!-- Header Section -->
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-capsule text-teal"></i>
        <span>Resep & Pemberian Obat Santri</span>
      </h3>
      <p class="text-muted small mb-0 mt-1">
        Daftar instruksi dan pemberian obat berdasarkan pemeriksaan klinis dokter Poskestren.
      </p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= base_url('/obat') ?>" class="btn btn-outline-teal px-3">
        <i class="bi bi-box-seam me-1"></i> Inventaris Obat
      </a>
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
            <th class="ps-4">Tanggal & Dokter</th>
            <th>Santri & Kamar</th>
            <th>Nama Obat</th>
            <th>Dosis</th>
            <th>Aturan Pakai</th>
            <th>Durasi</th>
            <th>Instruksi / Catatan</th>
            <th class="text-end pe-4">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($daftarResep)): ?>
            <?php foreach ($daftarResep as $r): ?>
              <tr>
                <td class="ps-4">
                  <div class="fw-bold text-dark small font-monospace"><?= date('d M Y, H:i', strtotime($r['tanggal_periksa'])) ?></div>
                  <div class="small text-muted"><i class="bi bi-person-fill text-primary"></i> <?= esc($r['nama_dokter'] ?? 'Dokter Poskestren') ?></div>
                </td>
                <td>
                  <div class="fw-bold text-dark"><?= esc($r['nama_santri']) ?></div>
                  <div class="small text-muted">NIS: <?= esc($r['nis']) ?> &bull; <?= esc($r['nama_kamar'] ?? '-') ?></div>
                </td>
                <td>
                  <span class="badge bg-primary-subtle text-primary fs-6 fw-bold">
                    <i class="bi bi-capsule me-1"></i><?= esc($r['nama_obat']) ?>
                  </span>
                </td>
                <td class="small fw-semibold"><?= esc($r['dosis']) ?></td>
                <td class="small"><?= esc($r['aturan_pakai']) ?></td>
                <td class="small text-muted"><?= esc($r['durasi'] ?: '-') ?></td>
                <td class="small text-muted"><?= esc($r['instruksi_petugas'] ?: '-') ?></td>
                <td class="text-end pe-4">
                  <a href="<?= base_url('/laporan-kesehatan/' . $r['laporan_id']) ?>" class="btn btn-sm btn-outline-teal rounded-pill px-3">
                    <i class="bi bi-eye"></i> Kasus
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center py-5 text-muted small">
                <i class="bi bi-capsule fs-1 text-muted d-block mb-2"></i>
                Belum ada data resep obat tercatat dari pemeriksaan klinis.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
<?= $this->endSection() ?>

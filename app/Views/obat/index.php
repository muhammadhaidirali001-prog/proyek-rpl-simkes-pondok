<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title ?? 'Inventaris Obat Poskestren - SIMKES Santri') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0">

  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-2 small text-muted">
      <li class="breadcrumb-item"><a href="<?= base_url('/dashboard') ?>" class="text-decoration-none text-muted">Dashboard</a></li>
      <li class="breadcrumb-item active" aria-current="page">Inventaris Obat</li>
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

  <!-- Header Section -->
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-box-seam-fill text-teal"></i>
        <span>Inventaris & Farmasi Obat Poskestren</span>
      </h3>
      <p class="text-muted small mb-0 mt-1">
        Kelola persediaan obat, indikasi terapi, dan ketersediaan stok faskes pesantren.
      </p>
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-teal px-3 shadow-sm d-flex align-items-center gap-1 text-white" data-bs-toggle="modal" data-bs-target="#modalTambahObat">
        <i class="bi bi-plus-circle"></i> Tambah Item Obat
      </button>
    </div>
  </div>

  <!-- Statistik Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-4">
      <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted small">Total Jenis Obat</div>
            <h3 class="fw-bold text-dark mb-0"><?= esc($totalItem) ?></h3>
          </div>
          <div class="p-3 bg-primary-subtle text-primary rounded-3">
            <i class="bi bi-capsule fs-4"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-4">
      <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted small">Total Kuantitas Stok</div>
            <h3 class="fw-bold text-teal mb-0"><?= esc($totalStok) ?></h3>
          </div>
          <div class="p-3 bg-success-subtle text-success rounded-3">
            <i class="bi bi-boxes fs-4"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-4">
      <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted small">Stok Menipis (&le;10)</div>
            <h3 class="fw-bold text-danger mb-0"><?= esc($stokMenipis) ?></h3>
          </div>
          <div class="p-3 bg-danger-subtle text-danger rounded-3">
            <i class="bi bi-exclamation-diamond fs-4"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Table Card -->
  <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white border-bottom p-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
      <form action="<?= base_url('/obat') ?>" method="GET" class="d-flex gap-2 w-100 w-sm-auto">
        <input type="text" name="keyword" class="form-control form-control-sm" placeholder="Cari nama obat..." value="<?= esc($keyword) ?>">
        <button type="submit" class="btn btn-sm btn-outline-teal"><i class="bi bi-search"></i></button>
        <?php if (!empty($keyword)): ?>
          <a href="<?= base_url('/obat') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle"></i></a>
        <?php endif; ?>
      </form>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-4">No</th>
            <th>Nama Obat</th>
            <th>Indikasi / Informasi Penggunaan</th>
            <th>Jumlah Stok</th>
            <th>Satuan</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($daftarObat)): ?>
            <?php foreach ($daftarObat as $idx => $o): ?>
              <tr>
                <td class="ps-4 text-muted small"><?= $idx + 1 ?></td>
                <td class="fw-bold text-dark"><?= esc($o['nama_obat']) ?></td>
                <td class="small text-muted"><?= esc($o['informasi_penggunaan'] ?: '-') ?></td>
                <td class="fw-bold font-monospace <?= (int)$o['stok'] <= 10 ? 'text-danger' : 'text-dark' ?>">
                  <?= esc($o['stok']) ?>
                </td>
                <td class="small"><?= esc($o['satuan']) ?></td>
                <td>
                  <?php if ((int)$o['stok'] <= 0): ?>
                    <span class="badge bg-danger-subtle text-danger">Habis</span>
                  <?php elseif ((int)$o['stok'] <= 10): ?>
                    <span class="badge bg-warning-subtle text-warning">Menipis</span>
                  <?php else: ?>
                    <span class="badge bg-success-subtle text-success">Tersedia</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center py-4 text-muted small">Tidak ada item obat ditemukan.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Modal Tambah Obat -->
<div class="modal fade" id="modalTambahObat" tabindex="-1" aria-labelledby="modalTambahObatLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <form action="<?= base_url('/obat') ?>" method="POST">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="modalTambahObatLabel">Tambah Item Obat Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Nama Obat <span class="text-danger">*</span></label>
            <input type="text" name="nama_obat" class="form-control" placeholder="Contoh: Paracetamol 500mg" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Kuantitas Stok <span class="text-danger">*</span></label>
              <input type="number" name="stok" class="form-control" min="0" placeholder="0" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Satuan <span class="text-danger">*</span></label>
              <input type="text" name="satuan" class="form-control" placeholder="Tablet, Strip, Botol" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Informasi Penggunaan / Indikasi</label>
            <textarea name="informasi_penggunaan" class="form-control" rows="2" placeholder="Contoh: Penurun demam dan pereda nyeri"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-teal rounded-pill px-4 text-white">Simpan Obat</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

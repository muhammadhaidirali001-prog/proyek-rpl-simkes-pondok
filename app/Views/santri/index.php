<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title ?? 'Manajemen Data Santri - SIMKES Santri') ?>
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

  <!-- Page Header & Action Button -->
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small text-muted">
          <li class="breadcrumb-item"><a href="<?= base_url('/dashboard') ?>" class="text-decoration-none text-muted">Dashboard</a></li>
          <li class="breadcrumb-item active" aria-current="page">Data Santri</li>
        </ol>
      </nav>
      <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-people-fill text-teal"></i>
        <span>Manajemen Data Santri</span>
        <?php if ($isPengawasKamar): ?>
          <span class="badge bg-secondary-subtle text-secondary fs-6 fw-normal px-2 py-1">Kamar Binaan</span>
        <?php endif; ?>
      </h3>
      <p class="text-muted small mb-0 mt-1">
        Kelola identitas santri, penempatan kamar asrama, dan riwayat rekam medis kesehatan santri.
      </p>
    </div>

    <div class="d-flex align-items-center gap-2">
      <a href="<?= base_url('/data-transfer/impor') ?>" class="btn btn-outline-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
        <i class="bi bi-file-earmark-arrow-up-fill fs-6"></i>
        <span>Impor Data</span>
      </a>
      <a href="<?= base_url('/data-transfer/ekspor') ?>" class="btn btn-outline-success px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
        <i class="bi bi-file-earmark-arrow-down-fill fs-6"></i>
        <span>Ekspor Data</span>
      </a>
      <a href="<?= base_url('/santri/new') ?>" class="btn btn-teal px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
        <i class="bi bi-person-plus-fill fs-6"></i>
        <span>Tambah Santri Baru</span>
      </a>
    </div>
  </div>

  <!-- Filter & Search Card -->
  <div class="card card-dashboard mb-4 border-0 shadow-sm">
    <div class="card-body p-3 p-md-4">
      <form action="<?= base_url('/santri') ?>" method="GET" class="row g-3 align-items-end">
        <!-- Pencarian Keyword -->
        <div class="col-12 col-md-4">
          <label for="keyword" class="form-label small fw-semibold text-muted mb-1">
            <i class="bi bi-search me-1"></i> Pencarian
          </label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control bg-light border-start-0" id="keyword" name="keyword"
                   placeholder="Cari NIS atau Nama santri..." value="<?= esc($filters['keyword'] ?? '') ?>">
          </div>
        </div>

        <!-- Filter Kamar -->
        <div class="col-12 col-sm-6 col-md-3">
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

        <!-- Filter Kelas -->
        <div class="col-12 col-sm-6 col-md-3">
          <label for="kelas" class="form-label small fw-semibold text-muted mb-1">
            <i class="bi bi-mortarboard me-1"></i> Kelas
          </label>
          <select class="form-select bg-light" id="kelas" name="kelas">
            <option value="">-- Semua Kelas --</option>
            <?php foreach ($daftarKelas as $kls): ?>
              <option value="<?= esc($kls) ?>" <?= (($filters['kelas'] ?? '') === $kls) ? 'selected' : '' ?>>
                <?= esc($kls) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Tombol Aksi Filter -->
        <div class="col-12 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-teal w-100 fw-semibold">
            <i class="bi bi-funnel-fill me-1"></i> Filter
          </button>
          <?php if (!empty($filters['keyword']) || !empty($filters['kamar_id']) || !empty($filters['kelas'])): ?>
            <a href="<?= base_url('/santri') ?>" class="btn btn-outline-secondary" title="Reset Filter">
              <i class="bi bi-arrow-counterclockwise"></i>
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Active Filter Badges & Count Info -->
  <?php
    $hasActiveFilter = (!empty($filters['keyword']) || !empty($filters['kamar_id']) || !empty($filters['kelas']));
  ?>
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <span class="small text-muted">
        Total: <strong><?= esc($totalSantri) ?></strong> santri terdaftar
      </span>
      <?php if ($hasActiveFilter): ?>
        <span class="text-muted small">&bull; Filter aktif:</span>
        <?php if (!empty($filters['keyword'])): ?>
          <span class="badge bg-secondary-subtle text-secondary small">
            Pencarian: "<?= esc($filters['keyword']) ?>"
          </span>
        <?php endif; ?>
        <?php if (!empty($filters['kamar_id'])): ?>
          <?php
            $activeKamarName = '';
            foreach ($daftarKamar as $k) {
              if ((string)$k['id'] === (string)$filters['kamar_id']) {
                $activeKamarName = $k['nama_kamar'];
                break;
              }
            }
          ?>
          <span class="badge bg-info-subtle text-info small">
            Kamar: <?= esc($activeKamarName ?: $filters['kamar_id']) ?>
          </span>
        <?php endif; ?>
        <?php if (!empty($filters['kelas'])): ?>
          <span class="badge bg-primary-subtle text-primary small">
            Kelas: <?= esc($filters['kelas']) ?>
          </span>
        <?php endif; ?>
        <a href="<?= base_url('/santri') ?>" class="small text-decoration-none text-danger ms-1">
          <i class="bi bi-x-circle"></i> Bersihkan filter
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Data Santri Table Card -->
  <div class="card card-dashboard border-0 shadow-sm overflow-hidden mb-4">
    <div class="table-responsive">
      <table class="table table-hover table-simkes align-middle mb-0">
        <thead>
          <tr>
            <th class="text-center" style="width: 50px;">No</th>
            <th style="width: 140px;">NIS</th>
            <th>Nama Lengkap</th>
            <th style="width: 130px;">Jenis Kelamin</th>
            <th>Kamar Asrama</th>
            <th>Kelas</th>
            <th>Kontak Wali</th>
            <th class="text-center" style="width: 170px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($santriList) && is_array($santriList)): ?>
            <?php 
              // Hitung nomor urut berdasarkan pagination
              $currentPage = (int) ($pager->getCurrentPage('santri') ?: 1);
              $perPage = (int) ($pager->getPerPage('santri') ?: 8);
              $no = ($currentPage - 1) * $perPage + 1;
            ?>
            <?php foreach ($santriList as $santri): ?>
              <tr>
                <td class="text-center text-muted fw-semibold small"><?= $no++ ?></td>
                <td>
                  <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                    <?= esc($santri['nis']) ?>
                  </span>
                </td>
                <td>
                  <div class="d-flex align-items-center gap-3">
                    <?php
                      $isMale = ($santri['jenis_kelamin'] === 'Laki-laki');
                      $bgAvatar = $isMale ? 'bg-primary-subtle text-primary' : 'bg-danger-subtle text-danger';
                      $initials = mb_substr($santri['nama'], 0, 1);
                    ?>
                    <div class="avatar-circle <?= $bgAvatar ?>">
                      <?= esc($initials) ?>
                    </div>
                    <div>
                      <a href="<?= base_url('/santri/' . $santri['id']) ?>" class="fw-bold text-dark text-decoration-none text-hover-teal">
                        <?= esc($santri['nama']) ?>
                      </a>
                      <div class="small text-muted d-block d-md-none">
                        <?= esc($santri['nama_kamar'] ?? 'Tanpa Kamar') ?> &bull; <?= esc($santri['kelas'] ?? '-') ?>
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <?php if ($isMale): ?>
                    <span class="badge badge-soft-primary px-2 py-1">
                      <i class="bi bi-gender-male me-1"></i> Laki-laki
                    </span>
                  <?php else: ?>
                    <span class="badge badge-soft-danger px-2 py-1">
                      <i class="bi bi-gender-female me-1"></i> Perempuan
                    </span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($santri['nama_kamar'])): ?>
                    <span class="d-inline-flex align-items-center gap-1 text-dark fw-medium">
                      <i class="bi bi-door-closed text-secondary"></i>
                      <?= esc($santri['nama_kamar']) ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted small fst-italic">Belum Ditentukan</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($santri['kelas'])): ?>
                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1">
                      <?= esc($santri['kelas']) ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted small">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($santri['kontak_wali'])): ?>
                    <span class="small font-monospace text-dark d-inline-flex align-items-center gap-1">
                      <i class="bi bi-telephone text-muted"></i>
                      <?= esc($santri['kontak_wali']) ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted small">-</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm" role="group">
                    <a href="<?= base_url('/santri/' . $santri['id']) ?>" class="btn btn-outline-info" title="Lihat Profil & Riwayat Medis">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="<?= base_url('/santri/' . $santri['id'] . '/edit') ?>" class="btn btn-outline-primary" title="Ubah Data">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <button type="button" class="btn btn-outline-danger" title="Hapus Data"
                            data-bs-toggle="modal" data-bs-target="#deleteModal<?= $santri['id'] ?>">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>

                  <!-- Modal Konfirmasi Hapus -->
                  <div class="modal fade" id="deleteModal<?= $santri['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $santri['id'] ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content text-start border-0 shadow">
                        <div class="modal-header border-0 pb-0">
                          <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2" id="deleteModalLabel<?= $santri['id'] ?>">
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
                            <i class="bi bi-info-circle me-1"></i> Data yang dihapus tidak dapat dipulihkan kembali. Riwayat kesehatan terkait juga akan terhapus.
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
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center py-5">
                <div class="py-4">
                  <div class="mb-3">
                    <i class="bi bi-people display-4 text-muted opacity-50"></i>
                  </div>
                  <h5 class="fw-bold text-dark mb-1">Data Santri Tidak Ditemukan</h5>
                  <p class="text-muted small mb-3">
                    <?php if ($hasActiveFilter): ?>
                      Tidak ada santri yang cocok dengan kriteria filter yang Anda tentukan.
                    <?php else: ?>
                      Belum ada data santri yang terdaftar dalam sistem atau kamar binaan Anda.
                    <?php endif; ?>
                  </p>
                  <?php if ($hasActiveFilter): ?>
                    <a href="<?= base_url('/santri') ?>" class="btn btn-sm btn-outline-secondary">
                      <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
                    </a>
                  <?php else: ?>
                    <a href="<?= base_url('/santri/new') ?>" class="btn btn-sm btn-teal">
                      <i class="bi bi-person-plus-fill me-1"></i> Tambah Santri Baru
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination & Footer Info -->
    <?php if (!empty($santriList) && is_array($santriList)): ?>
      <div class="card-footer bg-white border-0 py-3 px-4 d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
        <div class="small text-muted">
          Menampilkan santri pada halaman ini
        </div>
        <div>
          <?= $pager->links('santri', 'default_full') ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div>
<?= $this->endSection() ?>

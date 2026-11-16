<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title ?? 'Manajemen Pengguna - SIMKES Santri') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0">

  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-2 small text-muted">
      <li class="breadcrumb-item"><a href="<?= base_url('/dashboard') ?>" class="text-decoration-none text-muted">Dashboard</a></li>
      <li class="breadcrumb-item active" aria-current="page">Manajemen Pengguna</li>
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

  <!-- Header -->
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h3 class="fw-bold text-dark mb-1">Manajemen Pengguna & Hak Akses</h3>
      <p class="text-muted small mb-0">Kelola akun petugas, wewenang peran (RBAC), dan status operasional sistem SIMKES Santri.</p>
    </div>

    <div>
      <a href="<?= base_url('/audit-log') ?>" class="btn btn-outline-teal px-3">
        <i class="bi bi-journal-text me-1"></i> Lihat Audit Log
      </a>
    </div>
  </div>

  <!-- Filter & Search Card -->
  <div class="card card-dashboard border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form action="<?= base_url('/users') ?>" method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control border-start-0" name="keyword" placeholder="Cari nama atau email..."
                   value="<?= esc($filters['keyword'] ?? '') ?>">
          </div>
        </div>

        <div class="col-6 col-md-3">
          <select class="form-select" name="role">
            <option value="">Semua Peran</option>
            <option value="Admin" <?= (($filters['role'] ?? '') === 'Admin') ? 'selected' : '' ?>>Administrator</option>
            <option value="Kesehatan" <?= (($filters['role'] ?? '') === 'Kesehatan') ? 'selected' : '' ?>>Pengawas Kesehatan / Dokter</option>
            <option value="Kamar" <?= (($filters['role'] ?? '') === 'Kamar') ? 'selected' : '' ?>>Pengawas Kamar</option>
          </select>
        </div>

        <div class="col-6 col-md-3">
          <select class="form-select" name="status">
            <option value="">Semua Status</option>
            <option value="Aktif" <?= (($filters['status'] ?? '') === 'Aktif') ? 'selected' : '' ?>>Aktif</option>
            <option value="Nonaktif" <?= (($filters['status'] ?? '') === 'Nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
          </select>
        </div>

        <div class="col-12 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-teal w-100 fw-medium">Filter</button>
          <a href="<?= base_url('/users') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
      </form>
    </div>
  </div>

  <!-- Table Card -->
  <div class="card card-dashboard border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
          <tr>
            <th class="ps-4" style="width: 60px;">#ID</th>
            <th>Nama Lengkap</th>
            <th>Email</th>
            <th>Peran (Role)</th>
            <th>Status Akun</th>
            <th>Tanggal Registrasi</th>
            <th class="text-end pe-4" style="width: 140px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($users)): ?>
            <?php foreach ($users as $u): ?>
              <?php
                $canonicalRole = \App\Models\UserModel::normalizeRole($u['role']);
                $roleBadge = match ($canonicalRole) {
                  'admin'              => 'bg-danger-subtle text-danger border border-danger-subtle',
                  'pengawas_kesehatan' => 'bg-primary-subtle text-primary border border-primary-subtle',
                  default              => 'bg-success-subtle text-success border border-success-subtle',
                };
                $statusBadge = ($u['status'] === 'Aktif')
                  ? 'bg-success text-white'
                  : 'bg-secondary text-white';
                $isSelf = ((int)$u['id'] === (int)session()->get('user_id'));
              ?>
              <tr>
                <td class="ps-4 font-monospace small text-muted"><?= esc($u['id']) ?></td>
                <td>
                  <div class="fw-bold text-dark d-flex align-items-center gap-2">
                    <?= esc($u['nama']) ?>
                    <?php if ($isSelf): ?>
                      <span class="badge bg-light text-dark border small" style="font-size: 0.65rem;">Akun Anda</span>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="font-monospace small text-muted"><?= esc($u['email']) ?></td>
                <td>
                  <span class="badge <?= $roleBadge ?> px-2.5 py-1 fw-semibold small">
                    <?= esc($u['role']) ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?= $statusBadge ?> px-2 py-1 small">
                    <?= esc($u['status']) ?>
                  </span>
                </td>
                <td class="small text-muted font-monospace">
                  <?= !empty($u['created_at']) ? date('d/m/Y H:i', strtotime($u['created_at'])) : '-' ?>
                </td>
                <td class="text-end pe-4">
                  <button type="button" class="btn btn-sm btn-outline-teal px-2 py-1"
                          data-bs-toggle="modal" data-bs-target="#editUserModal<?= $u['id'] ?>">
                    <i class="bi bi-pencil-square me-1"></i> Ubah Hak
                  </button>
                </td>
              </tr>

              <!-- Modal Edit User -->
              <div class="modal fade" id="editUserModal<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content border-0 shadow">
                    <form action="<?= base_url('/users/' . $u['id'] . '/update') ?>" method="POST">
                      <?= csrf_field() ?>
                      <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                          <i class="bi bi-person-gear text-teal"></i> Ubah Hak Akses Pengguna
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body py-3">
                        <div class="mb-3">
                          <label class="form-label small text-muted mb-0">Nama & Email Pengguna</label>
                          <div class="fw-bold text-dark"><?= esc($u['nama']) ?></div>
                          <div class="text-muted font-monospace small"><?= esc($u['email']) ?></div>
                        </div>

                        <div class="mb-3">
                          <label for="role_<?= $u['id'] ?>" class="form-label small fw-semibold text-dark">Peran / Wewenang (Role) <span class="text-danger">*</span></label>
                          <select class="form-select" id="role_<?= $u['id'] ?>" name="role" required>
                            <option value="Admin" <?= ($canonicalRole === 'admin') ? 'selected' : '' ?>>Administrator (Akses Sistem Penuh)</option>
                            <option value="Pengawas Kesehatan" <?= ($canonicalRole === 'pengawas_kesehatan') ? 'selected' : '' ?>>Pengawas Kesehatan / Tenaga Medis (Pemeriksaan & Resep)</option>
                            <option value="Pengawas Kamar" <?= ($canonicalRole === 'pengawas_kamar') ? 'selected' : '' ?>>Pengawas Kamar (Kelola Santri Kamar Binaan)</option>
                          </select>
                        </div>

                        <div class="mb-3">
                          <label for="status_<?= $u['id'] ?>" class="form-label small fw-semibold text-dark">Status Operasional Akun <span class="text-danger">*</span></label>
                          <select class="form-select" id="status_<?= $u['id'] ?>" name="status" required>
                            <option value="Aktif" <?= ($u['status'] === 'Aktif') ? 'selected' : '' ?>>Aktif (Dapat Masuk ke Sistem)</option>
                            <option value="Nonaktif" <?= ($u['status'] === 'Nonaktif') ? 'selected' : '' ?> <?= $isSelf ? 'disabled' : '' ?>>Nonaktif (Akses Masuk Diblokir)</option>
                          </select>
                          <?php if ($isSelf): ?>
                            <div class="form-text small text-warning"><i class="bi bi-info-circle me-1"></i> Anda tidak dapat menonaktifkan akun Anda sendiri.</div>
                          <?php endif; ?>
                        </div>

                        <div class="alert alert-light border small text-muted mb-0">
                          <i class="bi bi-shield-check me-1 text-teal"></i> Setiap perubahan hak akses dan status akan dicatat secara permanen di audit log sistem.
                        </div>
                      </div>
                      <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-teal fw-semibold">
                          <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bi bi-people display-4 text-muted opacity-50 mb-2"></i>
                <div class="fw-semibold">Tidak ada pengguna ditemukan.</div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pager->getPageCount('users') > 1): ?>
      <div class="card-footer bg-white border-0 py-3">
        <?= $pager->links('users', 'default_full') ?>
      </div>
    <?php endif; ?>
  </div>

</div>
<?= $this->endSection() ?>

<?php
  $authName  = session()->get('nama') ?? 'Petugas Poskestren';
  $authEmail = session()->get('email') ?? 'petugas@simkes.pesantren.id';
  $authRoleDisplay = session()->get('role_display') ?? 'Pengawas';
  $authRoleCanonical = session()->get('role_canonical') ?? 'pengawas_kamar';
?>
<header class="app-navbar">
  <div class="d-flex align-items-center gap-3">
    <button class="btn-sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
      <i class="bi bi-list fs-5"></i>
    </button>
    <div class="d-none d-md-block">
      <span class="badge bg-light text-secondary border px-2 py-1 me-2">
        <i class="bi bi-building-check me-1 text-teal"></i> Poskestren Terpadu
      </span>
      <span class="text-muted small">
        <i class="bi bi-calendar3 me-1"></i> <?= date('l, d F Y') ?>
      </span>
    </div>
  </div>

  <div class="d-flex align-items-center gap-3">
    <!-- Status Role Pengguna Aktif -->
    <div class="d-none d-sm-flex align-items-center gap-2 px-3 py-1 rounded-pill small border <?= ($authRoleCanonical === 'pengawas_kesehatan' || $authRoleCanonical === 'admin') ? 'bg-primary bg-opacity-10 border-primary border-opacity-25 text-primary' : 'bg-success bg-opacity-10 border-success border-opacity-25 text-success' ?>">
      <span class="spinner-grow spinner-grow-sm" style="width: 8px; height: 8px;" role="status"></span>
      <span class="fw-semibold"><?= esc($authRoleDisplay) ?></span>
    </div>

    <!-- User Profile Dropdown -->
    <div class="dropdown">
      <button class="btn btn-sm btn-outline-light text-dark dropdown-toggle d-flex align-items-center gap-2 border-0 px-2 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px; background-color: #0d9488;">
          <i class="bi bi-person-fill"></i>
        </div>
        <div class="text-start d-none d-lg-block">
          <div class="fw-semibold small text-truncate" style="max-width: 140px;"><?= esc($authName) ?></div>
          <div class="text-muted" style="font-size: 0.72rem;"><?= esc($authRoleDisplay) ?></div>
        </div>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2 mt-2" style="min-width: 220px;">
        <li class="px-3 py-2 border-bottom mb-1">
          <div class="fw-semibold small"><?= esc($authName) ?></div>
          <div class="text-muted small" style="font-size: 0.75rem;"><?= esc($authEmail) ?></div>
          <span class="badge bg-teal-subtle text-teal mt-1 border px-2 py-0.5" style="font-size: 0.7rem; color: #0d9488;">
            <?= esc($authRoleDisplay) ?>
          </span>
        </li>
        <li><a class="dropdown-item py-2 small" href="<?= base_url('/dashboard') ?>"><i class="bi bi-grid-1x2 me-2 text-muted"></i> Dashboard Saya</a></li>
        <li><a class="dropdown-item py-2 small" href="<?= base_url('/profil') ?>"><i class="bi bi-person-vcard me-2 text-muted"></i> Profil Profesi Saya</a></li>
        <?php if ($authRoleCanonical === 'admin'): ?>
          <li><a class="dropdown-item py-2 small" href="<?= base_url('/users') ?>"><i class="bi bi-person-gear me-2 text-muted"></i> Kelola Pengguna</a></li>
          <li><a class="dropdown-item py-2 small" href="<?= base_url('/audit-log') ?>"><i class="bi bi-journal-text me-2 text-muted"></i> Riwayat Audit Sistem</a></li>
        <?php endif; ?>
        <li><hr class="dropdown-divider my-1"></li>
        <li>
          <form action="<?= base_url('/logout') ?>" method="POST" id="logoutForm" class="d-none">
            <?= csrf_field() ?>
          </form>
          <a class="dropdown-item py-2 small text-danger" href="<?= base_url('/logout') ?>" onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">
            <i class="bi bi-box-arrow-right me-2"></i> Keluar (Logout)
          </a>
        </li>
      </ul>
    </div>
  </div>
</header>

<?php
  $userRole = \App\Models\UserModel::normalizeRole(session()->get('role') ?? '');
  $isKesehatanOrAdmin = in_array($userRole, [\App\Models\UserModel::ROLE_PENGAWAS_KESEHATAN, \App\Models\UserModel::ROLE_ADMIN]);
  $isAdmin = ($userRole === \App\Models\UserModel::ROLE_ADMIN);
?>
<aside class="app-sidebar" id="appSidebar">
  <!-- Brand Header -->
  <div class="sidebar-header">
    <a href="<?= base_url('/dashboard') ?>" class="brand-logo">
      <div class="brand-icon">
        <i class="bi bi-heart-pulse-fill"></i>
      </div>
      <div class="brand-text">
        <h1>SIMKES Santri</h1>
        <span>Poskestren Pesantren</span>
      </div>
    </a>
  </div>

  <!-- Navigation Menu -->
  <div class="sidebar-nav">
    <div class="nav-heading">Utama</div>
    <a href="<?= base_url('/dashboard') ?>" class="nav-link-custom <?= (current_url() == base_url('/') || current_url() == base_url('/dashboard')) ? 'active' : '' ?>">
      <i class="bi bi-grid-1x2-fill"></i>
      <span>Dashboard</span>
    </a>
    <a href="<?= base_url('/profil') ?>" class="nav-link-custom <?= str_contains(current_url(), '/profil') ? 'active' : '' ?>">
      <i class="bi bi-person-vcard-fill"></i>
      <span>Profil Profesi</span>
    </a>

    <div class="nav-heading">Data Master</div>
    <a href="<?= base_url('/santri') ?>" class="nav-link-custom <?= str_contains(current_url(), '/santri') ? 'active' : '' ?>">
      <i class="bi bi-people-fill"></i>
      <span>Data Santri <?= (!$isKesehatanOrAdmin) ? '<span class="badge bg-secondary-subtle text-secondary small ms-auto" style="font-size: 0.65rem;">Kamar</span>' : '' ?></span>
    </a>
    <a href="<?= base_url('/kamar') ?>" class="nav-link-custom <?= str_contains(current_url(), '/kamar') ? 'active' : '' ?>">
      <i class="bi bi-door-open-fill"></i>
      <span>Kamar & Asrama</span>
    </a>

    <div class="nav-heading">Pelayanan Kesehatan</div>
    <a href="<?= base_url('/laporan-kesehatan') ?>" class="nav-link-custom <?= str_contains(current_url(), '/laporan-kesehatan') ? 'active' : '' ?>">
      <i class="bi bi-clipboard2-pulse-fill"></i>
      <span>Laporan Sakit</span>
    </a>

    <?php if ($isKesehatanOrAdmin): ?>
      <a href="<?= base_url('/pemeriksaan') ?>" class="nav-link-custom <?= str_contains(current_url(), '/pemeriksaan') ? 'active' : '' ?>">
        <i class="bi bi-stethoscope"></i>
        <span>Pemeriksaan Klinis</span>
      </a>
      <a href="<?= base_url('/pemberian-obat') ?>" class="nav-link-custom <?= str_contains(current_url(), '/pemberian-obat') ? 'active' : '' ?>">
        <i class="bi bi-capsule"></i>
        <span>Resep & Obat Santri</span>
      </a>

      <div class="nav-heading">Inventaris & Logistik</div>
      <a href="<?= base_url('/obat') ?>" class="nav-link-custom <?= str_contains(current_url(), '/obat') ? 'active' : '' ?>">
        <i class="bi bi-box-seam-fill"></i>
        <span>Inventaris Obat</span>
      </a>

      <div class="nav-heading">Riwayat & Laporan</div>
      <a href="<?= base_url('/riwayat-status') ?>" class="nav-link-custom <?= str_contains(current_url(), '/riwayat-status') ? 'active' : '' ?>">
        <i class="bi bi-clock-history"></i>
        <span>Riwayat Status</span>
      </a>
    <?php endif; ?>

    <div class="nav-heading">Transfer Data</div>
    <a href="<?= base_url('/data-transfer/impor') ?>" class="nav-link-custom <?= str_contains(current_url(), '/data-transfer/impor') ? 'active' : '' ?>">
      <i class="bi bi-file-earmark-arrow-up-fill"></i>
      <span>Impor Data Santri</span>
    </a>
    <a href="<?= base_url('/data-transfer/ekspor') ?>" class="nav-link-custom <?= str_contains(current_url(), '/data-transfer/ekspor') ? 'active' : '' ?>">
      <i class="bi bi-file-earmark-arrow-down-fill"></i>
      <span>Ekspor Data</span>
    </a>

    <?php if ($isAdmin): ?>
      <div class="nav-heading">Administrator</div>
      <a href="<?= base_url('/audit-log') ?>" class="nav-link-custom <?= str_contains(current_url(), '/audit-log') ? 'active' : '' ?>">
        <i class="bi bi-shield-check"></i>
        <span>Audit Log Sistem</span>
      </a>
      <a href="<?= base_url('/users') ?>" class="nav-link-custom <?= str_contains(current_url(), '/users') ? 'active' : '' ?>">
        <i class="bi bi-person-badge-fill"></i>
        <span>Petugas & Pengguna</span>
      </a>
      <a href="<?= base_url('/admin/pengaturan-email') ?>" class="nav-link-custom <?= str_contains(current_url(), '/admin/pengaturan-email') ? 'active' : '' ?>">
        <i class="bi bi-envelope-at-fill"></i>
        <span>Pengaturan Email SMTP</span>
      </a>
    <?php endif; ?>
  </div>

  <!-- Footer Info -->
  <div class="sidebar-footer">
    <div class="d-flex align-items-center justify-content-between mb-1">
      <span class="fw-semibold text-light">SIMKES Santri</span>
      <span class="badge bg-secondary-subtle text-light" style="font-size: 0.65rem;">v1.0.0</span>
    </div>
    <div class="text-truncate">Pesantren Mandiri Sehat</div>
  </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

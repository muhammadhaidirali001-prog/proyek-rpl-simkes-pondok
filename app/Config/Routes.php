<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ============================================================================
// 1. RUTE PUBLIK / TAMU (GUEST FILTER: Pengguna login dialihkan ke /dashboard)
// ============================================================================
$routes->group('', ['filter' => 'guest'], static function ($routes) {
    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::attemptLogin');
    $routes->get('register', 'Auth::register');
    $routes->post('register', 'Auth::attemptRegister');
    $routes->get('verify-otp', 'Auth::showVerifyOtp');
    $routes->post('verify-otp', 'Auth::verifyOtp');
    $routes->post('resend-otp', 'Auth::resendOtp');
});

// Logout dapat diakses oleh pengguna yang login
$routes->match(['GET', 'POST'], 'logout', 'Auth::logout');

// ============================================================================
// 2. RUTE TERLINDUNGI (AUTH FILTER: Wajib Login)
// ============================================================================
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    // Redirect root ke dashboard
    $routes->get('/', 'Dashboard::index');
    $routes->get('dashboard', 'Dashboard::index');

    // Profil Profesi Pengguna
    $routes->get('profil', 'Profil::index');
    $routes->post('profil/update', 'Profil::update');

    // Modul Data Master Santri
    $routes->get('santri', 'Santri::index');
    $routes->get('santri/new', 'Santri::new');
    $routes->post('santri', 'Santri::create');
    $routes->get('santri/(:num)', 'Santri::show/$1');
    $routes->get('santri/(:num)/edit', 'Santri::edit/$1');
    $routes->post('santri/(:num)', 'Santri::update/$1');
    $routes->post('santri/(:num)/delete', 'Santri::delete/$1');

    // Modul Data Master Kamar & Asrama
    $routes->get('kamar', 'Kamar::index');

    // Modul Laporan Kesehatan Santri
    $routes->get('laporan-kesehatan', 'LaporanKesehatan::index');
    $routes->get('laporan-kesehatan/new', 'LaporanKesehatan::new');
    $routes->post('laporan-kesehatan', 'LaporanKesehatan::create');
    $routes->get('laporan-kesehatan/(:num)', 'LaporanKesehatan::show/$1');
    $routes->post('laporan-kesehatan/(:num)/status', 'LaporanKesehatan::updateStatus/$1');

    // Modul Impor dan Ekspor Data
    $routes->get('data-transfer/template-santri/(:segment)', 'DataTransfer::templateSantri/$1');
    $routes->get('data-transfer/impor', 'DataTransfer::impor');
    $routes->post('data-transfer/impor/preview', 'DataTransfer::prosesPratinjau');
    $routes->post('data-transfer/impor/simpan', 'DataTransfer::simpanImpor');
    $routes->get('data-transfer/ekspor', 'DataTransfer::ekspor');
    $routes->get('data-transfer/ekspor/santri', 'DataTransfer::eksporSantri');
    $routes->get('data-transfer/ekspor/kesehatan', 'DataTransfer::eksporKesehatan');

    // Alias / Shortcut
    $routes->get('impor', 'DataTransfer::impor');
    $routes->get('ekspor', 'DataTransfer::ekspor');

    // Modul Asisten AI Kesehatan
    $routes->get('asisten-ai', 'AsistenAi::index');
    $routes->post('asisten-ai/chat', 'AsistenAi::chat');
    $routes->post('asisten-ai/reset', 'AsistenAi::reset');
    $routes->get('asisten-ai/config', 'AsistenAi::getConfig');
    $routes->post('asisten-ai/config', 'AsistenAi::saveConfig');
    $routes->get('asisten-ai/notifications', 'AsistenAi::getNotifications');

    // ========================================================================
    // 3. RUTE KHUSUS MEDIS (ROLE FILTER: Pengawas Kesehatan & Admin)
    // ========================================================================
    $routes->group('', ['filter' => 'role:pengawas_kesehatan,admin'], static function ($routes) {
        $routes->get('pemeriksaan', 'Pemeriksaan::index');
        $routes->get('pemeriksaan/laporan/(:num)', 'Pemeriksaan::new/$1');
        $routes->post('pemeriksaan/laporan/(:num)', 'Pemeriksaan::create/$1');
        $routes->get('pemberian-obat', 'PemberianObat::index');
        $routes->get('obat', 'Obat::index');
        $routes->post('obat', 'Obat::create');
        $routes->get('riwayat-status', 'RiwayatStatus::index');
    });


    // ========================================================================
    // 4. RUTE KHUSUS ADMINISTRATOR (ROLE FILTER: Admin)
    // ========================================================================
    $routes->group('', ['filter' => 'role:admin'], static function ($routes) {
        $routes->get('audit-log', 'AuditLog::index');
        $routes->get('users', 'Users::index');
        $routes->post('users/(:num)/update', 'Users::update/$1');
        // Pengaturan SMTP Email & Konfigurasi Sistem
        $routes->get('admin/pengaturan-email', 'Users::smtpSettings');
        $routes->post('admin/pengaturan-email', 'Users::smtpSettingsSave');
        $routes->post('admin/pengaturan-email/test', 'Users::smtpSettingsTest');
    });
});

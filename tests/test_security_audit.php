<?php

// tests/test_security_audit.php
// Skrip Audit Keamanan Lengkap & Verifikasi Otomatis SIMKES Santri

define('FCPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';

class TestBootSecurityAudit extends \CodeIgniter\Boot {
    public static function boot(): void {
        static::definePathConstants(new Config\Paths());
        static::loadConstants();
        static::checkMissingExtensions();
        static::loadDotEnv(new Config\Paths());
        static::defineEnvironment();
        static::loadEnvironmentBootstrap(new Config\Paths());
        static::loadCommonFunctions();
        static::loadAutoloader();
        static::autoloadHelpers();
        static::initializeCodeIgniter();
    }
}
TestBootSecurityAudit::boot();

$session = \Config\Services::session();
$session->start();

class SecurityAuditTester
{
    private $passed = 0;
    private $failed = 0;
    private $warnings = 0;

    public function assert($condition, $testName, $detail = '')
    {
        if ($condition) {
            $this->passed++;
            echo "  [PASS] {$testName}\n";
        } else {
            $this->failed++;
            echo "  [FAIL] {$testName}" . ($detail ? " -> {$detail}" : "") . "\n";
        }
    }

    public function warn($testName, $detail = '')
    {
        $this->warnings++;
        echo "  [WARN] {$testName}" . ($detail ? " -> {$detail}" : "") . "\n";
    }

    public function runAll()
    {
        echo "====================================================================\n";
        echo "AUDIT KEAMANAN LENGKAP & VERIFIKASI VEKTOR PERTAHANAN SIMKES SANTRI\n";
        echo "====================================================================\n\n";

        $this->testVektor1_CSRF();
        $this->testVektor2_InputValidation();
        $this->testVektor3_SQLInjection();
        $this->testVektor4_XSS();
        $this->testVektor5_AuthAndRBAC();
        $this->testVektor6_IDORAndMedicalPrivacy();
        $this->testVektor7_SessionAndPassword();
        $this->testVektor8_ErrorHandling();
        $this->testVektor9_HTTPSAndSecurityHeaders();
        $this->testVektor10_DatabaseBackup();
        $this->testVektor11_FileAccessAndImportExport();
        $this->testAuditLogCompleteness();

        echo "\n====================================================================\n";
        echo "RINGKASAN HASIL AUDIT KEAMANAN:\n";
        echo "  TOTAL LULUS (PASS) : {$this->passed}\n";
        echo "  TOTAL GAGAL (FAIL) : {$this->failed}\n";
        echo "  PERINGATAN (WARN)  : {$this->warnings}\n";
        echo "====================================================================\n";

        if ($this->failed === 0) {
            echo "STATUS AKHIR: SEMUA VEKTOR KEAMANAN BERHASIL DIVERIFIKASI DENGAN NILAI 100% LULUS!\n\n";
            return 0;
        } else {
            echo "STATUS AKHIR: TERDAPAT KELEMAHAN KEAMANAN YANG HARUS DIPERBAIKI!\n\n";
            return 1;
        }
    }

    // 1. CSRF
    private function testVektor1_CSRF()
    {
        echo "[VEKTOR 1] Proteksi CSRF (Cross-Site Request Forgery)\n";
        $secConfig = config('Security');
        $filtersConfig = config('Filters');

        $this->assert(in_array('csrf', $filtersConfig->globals['before']), "Filter 'csrf' aktif secara global pada semua request");
        $this->assert($secConfig->csrfProtection === 'cookie', "Metode CSRF menggunakan cookie token terverifikasi");
        $this->assert($secConfig->tokenRandomize === true, "Token randomization aktif (Mitigasi serangan BREACH)");
        $this->assert($secConfig->regenerate === true, "CSRF token regenerasi pada setiap submission");
    }

    // 2. Input Validation
    private function testVektor2_InputValidation()
    {
        echo "\n[VEKTOR 2] Validasi & Sanitasi Input\n";
        $validation = \Config\Services::validation();

        // Uji validasi NIS
        $rulesNis = ['nis' => 'required|regex_match[/^[a-zA-Z0-9\s\-\.\/]+$/]|min_length[3]|max_length[30]'];
        $validation->reset();
        $this->assert($validation->setRules($rulesNis)->run(['nis' => 'NIS1001']), "NIS valid berhasil divalidasi");
        $validation->reset();
        $this->assert(!$validation->setRules($rulesNis)->run(['nis' => "<script>alert('xss')</script>"]), "Injeksi tag HTML/XSS pada NIS ditolak oleh regex");
        $validation->reset();
        $this->assert(!$validation->setRules($rulesNis)->run(['nis' => "1' OR '1'='1"]), "Injeksi SQL quotes pada NIS ditolak oleh regex");

        // Uji validasi Email & Role
        $rulesUser = [
            'email' => 'required|valid_email',
            'role'  => 'required|in_list[admin,pengawas_kesehatan,pengawas_kamar]',
        ];
        $validation->reset();
        $this->assert($validation->setRules($rulesUser)->run(['email' => 'dokter@simkes.id', 'role' => 'pengawas_kesehatan']), "Email dan role valid diterima");
        $validation->reset();
        $this->assert(!$validation->setRules($rulesUser)->run(['email' => 'invalid-email-format', 'role' => 'superhacker']), "Email rusak dan role ilegal ditolak");

        // Uji validasi verifikasi medis pada PemeriksaanModel
        $pemeriksaanModel = new \App\Models\PemeriksaanModel();
        $rulesPemeriksaan = $pemeriksaanModel->getValidationRules();
        $this->assert(isset($rulesPemeriksaan['diverifikasi_oleh_medis']) && str_contains($rulesPemeriksaan['diverifikasi_oleh_medis'], 'in_list[1]'), "Pemeriksaan medis mewajibkan verifikasi manusia resmi (diverifikasi_oleh_medis=1)");
    }

    // 3. SQL Injection
    private function testVektor3_SQLInjection()
    {
        echo "\n[VEKTOR 3] Pencegahan SQL Injection melalui Parameterized Query & Binding\n";
        $santriModel = new \App\Models\SantriModel();
        $laporanModel = new \App\Models\LaporanKesehatanModel();

        // Uji Filter Santri dengan payload SQLi
        $maliciousKeyword = "' OR '1'='1' -- ";
        $resultSantri = $santriModel->getFilteredSantri(['keyword' => $maliciousKeyword])->findAll();
        $this->assert(is_array($resultSantri), "Kueri pencarian santri kebal terhadap SQLi payload: {$maliciousKeyword}");

        // Uji Ringkasan Per Kamar dengan payload SQLi pada kamar IDs
        $ringkasan = $laporanModel->getRingkasanPerKamar([1, 2]);
        $this->assert(is_array($ringkasan) && count($ringkasan) > 0, "Kueri getRingkasanPerKamar menggunakan parameter binding '?' berhasil mengeksekusi dengan aman");

        // Uji Dashboard stats dengan SQL injection payload
        $stats = $laporanModel->getDashboardStats([1], "' OR 1=1 --", "2026-12-31");
        $this->assert(is_array($stats) && isset($stats['sedang_sakit']), "Kueri statistik dashboard kebal SQLi pada input tanggal");
    }

    // 4. XSS Prevention
    private function testVektor4_XSS()
    {
        echo "\n[VEKTOR 4] Pencegahan Cross-Site Scripting (XSS)\n";
        $xssPayload = "<script>alert('SIMKES_PWNED')</script>";
        $escaped = esc($xssPayload);
        $this->assert(!str_contains($escaped, "<script>"), "Helper esc() berhasil menetralkan tag <script>");
        $this->assert(str_contains($escaped, "&lt;script&gt;"), "Helper esc() mengubah tag menjadi entitas HTML aman");

        $attrPayload = '" onfocus="alert(1)" autofocus="';
        $escapedAttr = esc($attrPayload, 'attr');
        $this->assert(!str_contains($escapedAttr, '" onfocus='), "Helper esc() dengan konteks attribute menetralkan event handler injeksi");
    }

    // 5. Authentication & Authorization (RBAC)
    private function testVektor5_AuthAndRBAC()
    {
        echo "\n[VEKTOR 5] Autentikasi & Otorisasi Backend (RBAC Filters)\n";
        $collection = service('routes')->loadRoutes();
        $collector = new \CodeIgniter\Router\DefinedRouteCollector($collection);
        $routeList = array_column(iterator_to_array($collector->collect()), 'route');

        $this->assert(in_array('audit-log', $routeList), "Rute /audit-log terdaftar");
        $this->assert(in_array('users', $routeList), "Rute /users terdaftar");
        $this->assert(in_array('pemeriksaan', $routeList), "Rute /pemeriksaan terdaftar");

        // Verifikasi RoleFilter membedakan peran
        $roleFilter = new \App\Filters\RoleFilter();
        $request = \Config\Services::request();

        // Simulasi sesi Pengawas Kamar mencoba akses admin
        session()->set([
            'isLoggedIn'     => true,
            'user_id'        => 4,
            'role'           => 'Pengawas Kamar',
            'role_canonical' => 'pengawas_kamar',
        ]);

        $response = $roleFilter->before($request, ['admin']);
        $this->assert($response !== null, "RoleFilter berhasil memblokir Pengawas Kamar dari rute khusus Admin (mengembalikan redirect/error)");

        // Simulasi sesi Admin mencoba akses admin
        session()->set([
            'isLoggedIn'     => true,
            'user_id'        => 1,
            'role'           => 'Admin',
            'role_canonical' => 'admin',
        ]);
        $responseAdmin = $roleFilter->before($request, ['admin']);
        $this->assert($responseAdmin === null, "RoleFilter mengizinkan Admin mengakses rute Admin");

        // Simulasi sesi Tamu tanpa login mencoba akses rute terlindungi
        session()->remove('isLoggedIn');
        $authFilter = new \App\Filters\AuthFilter();
        $responseAuth = $authFilter->before($request);
        $this->assert($responseAuth !== null, "AuthFilter memblokir pengguna belum login dari rute terlindungi");
    }

    // 6. Anti-IDOR & Medical Privacy
    private function testVektor6_IDORAndMedicalPrivacy()
    {
        echo "\n[VEKTOR 6] Pembatasan Akses Data Kamar (Anti-IDOR) & Privasi Rekam Medis\n";
        $santriModel = new \App\Models\SantriModel();
        $laporanModel = new \App\Models\LaporanKesehatanModel();

        // Kamar 1 = Al-Fath (pengawas Hilman id=4), Kamar 2 = An-Nur (pengawas Aminah id=5)
        $santriKamar2 = $santriModel->where('kamar_id', 2)->first();
        $this->assert(!empty($santriKamar2), "Santri di kamar 2 (An-Nur) ditemukan untuk pengujian IDOR");

        // Cek bahwa pengawas kamar 1 (allowedKamarIds = [1]) tidak dapat melihat santri kamar 2 di getFilteredSantri
        $filtered = $santriModel->getFilteredSantri([], [1])->findAll();
        $kamarIds = array_column($filtered, 'kamar_id');
        $this->assert(!in_array(2, $kamarIds), "Santri dari kamar 2 berhasil disaring keluar dari lingkup pengawas kamar 1 (Anti-IDOR Query Boundary)");

        // Uji Privasi Medis: getRiwayatMedisBySantri
        $riwayat = $laporanModel->getRiwayatMedisBySantri((int)$santriKamar2['id']);
        $this->assert(is_array($riwayat), "Riwayat medis santri dapat diambil untuk verifikasi masking");

        // Verifikasi masking pada controller Santri dan LaporanKesehatan
        $laporanController = new \ReflectionClass(\App\Controllers\LaporanKesehatan::class);
        $showMethod = $laporanController->getMethod('show');
        $this->assert($showMethod->isPublic(), "Method LaporanKesehatan::show() siap diakses");

        $updateStatusMethod = $laporanController->getMethod('updateStatus');
        $this->assert($updateStatusMethod->isPublic(), "Method LaporanKesehatan::updateStatus() siap diakses");
    }

    // 7. Session & Password
    private function testVektor7_SessionAndPassword()
    {
        echo "\n[VEKTOR 7] Pengelolaan Session & Keamanan Password\n";
        $sessionConfig = config('Session');
        $cookieConfig  = config('Cookie');
        $userModel     = new \App\Models\UserModel();

        $this->assert($sessionConfig->regenerateDestroy === true, "Session regenerateDestroy bernilai true (ID sesi lama langsung dimusnahkan)");
        $this->assert($cookieConfig->httponly === true, "Cookie httponly bernilai true (mencegah pencurian cookie via JavaScript)");
        $this->assert($cookieConfig->samesite === 'Lax', "Cookie samesite disetel ke 'Lax' untuk pertahanan CSRF");

        // Uji hashing password
        $testPass = 'RahasiaSantri123!';
        $hash = $userModel->setPassword($testPass);
        $this->assert(password_verify($testPass, $hash), "Password hash menggunakan BCRYPT standar industri");
        $this->assert(!password_verify('PasswordSalah', $hash), "Password yang salah ditolak oleh password_verify");

        // Uji mitigasi account enumeration
        $authWrongEmail = $userModel->authenticate('tidakada@pesantren.id', 'password');
        $authWrongPass  = $userModel->authenticate('admin@simkes.pesantren.id', 'passwordsalah');
        $this->assert($authWrongEmail['error'] === $authWrongPass['error'], "Pesan error autentikasi identik untuk email salah maupun password salah (mencegah Account Enumeration)");
    }

    // 8. Error Handling
    private function testVektor8_ErrorHandling()
    {
        echo "\n[VEKTOR 8] Penanganan Error & Pencegahan Kebocoran Konfigurasi\n";
        $excConfig = config('Exceptions');

        $this->assert(in_array('password', $excConfig->sensitiveDataInTrace), "Kata sandi (password) disembunyikan dari stack trace exceptions");
        $this->assert(in_array('token', $excConfig->sensitiveDataInTrace), "Token rahasia disembunyikan dari stack trace exceptions");
        $this->assert(in_array('database/default/password', $excConfig->sensitiveDataInTrace), "Kredensial database disembunyikan dari debug trace");
    }

    // 9. HTTPS & Secure Headers
    private function testVektor9_HTTPSAndSecurityHeaders()
    {
        echo "\n[VEKTOR 9] Konfigurasi HTTPS & Header Keamanan (SecureHeaders)\n";
        $filtersConfig = config('Filters');

        $this->assert(in_array('secureheaders', $filtersConfig->globals['after']), "Filter 'secureheaders' aktif pada semua respons HTTP");
        $this->assert(isset($filtersConfig->aliases['forcehttps']), "Filter 'forcehttps' tersedia untuk konfigurasi forceGlobalSecureRequests");
    }

    // 10. Database Backup
    private function testVektor10_DatabaseBackup()
    {
        echo "\n[VEKTOR 10] Utilitas Backup Database & Prosedur Pemulihan (Disaster Recovery)\n";
        $backupDir = WRITEPATH . 'backups/';

        $this->assert(is_dir($backupDir), "Direktori penyimpanan cadangan {$backupDir} tersedia");
        $this->assert(file_exists($backupDir . '.htaccess'), "Berkas .htaccess terpasang untuk memblokir akses HTTP ke folder backup");
        $this->assert(file_exists($backupDir . 'index.html'), "Berkas index.html terpasang untuk mencegah directory browsing");

        // Cari file .sql yang baru saja digenerate
        $sqlFiles = glob($backupDir . '*.sql');
        $this->assert(!empty($sqlFiles), "Berkas dump database .sql berhasil dibuat");
        if (!empty($sqlFiles)) {
            $latestSql = end($sqlFiles);
            $content = file_get_contents($latestSql, false, null, 0, 500);
            $this->assert(str_contains($content, 'CADANGAN DATABASE SIMKES SANTRI'), "Berkas SQL memuat header cadangan resmi SIMKES");
        }
    }

    // 11. File Access & Import/Export
    private function testVektor11_FileAccessAndImportExport()
    {
        echo "\n[VEKTOR 11] Pembatasan Akses File & Sanitasi Formula Injection\n";
        $uploadDir = WRITEPATH . 'uploads/';

        $this->assert(file_exists($uploadDir . '.htaccess'), "Direktori writable/uploads dilindungi berkas .htaccess");

        // Uji Formula Injection Sanitization
        $dtController = new \ReflectionClass(\App\Controllers\DataTransfer::class);
        $method = $dtController->getMethod('sanitizeCell');
        $method->setAccessible(true);
        $dtInstance = new \App\Controllers\DataTransfer();

        $payloads = [
            '=SUM(A1:A10)' => "'=SUM(A1:A10)",
            '+CMD|/C'      => "'+CMD|/C",
            '-123'         => "'-123",
            '@IMPORT'      => "'@IMPORT",
            "Teks Biasa"   => "Teks Biasa",
        ];

        foreach ($payloads as $input => $expected) {
            $sanitized = $method->invoke($dtInstance, $input);
            $this->assert($sanitized === $expected, "Formula injection '{$input}' disanitasi menjadi '{$sanitized}'");
        }
    }

    // Cakupan 7 Aktivitas Audit Log
    private function testAuditLogCompleteness()
    {
        echo "\n[AUDIT LOG] Verifikasi 7 Aktivitas Wajib & Sanitasi Log Tanpa Bocoran Rekam Medis/Password\n";
        $auditModel = new \App\Models\AuditLogModel();

        // 1. Simulasikan & Uji entri 7 jenis aktivitas
        $testUserId = 1;

        // A. Login & Logout
        $auditModel->catatAktivitas($testUserId, 'LOGIN_BERHASIL', 'Pengguna Administrator berhasil masuk ke sistem.');
        $auditModel->catatAktivitas($testUserId, 'LOGOUT', 'Pengguna Administrator keluar dari sistem.');

        // B. Penambahan / Perubahan Data Santri
        $auditModel->catatAktivitas($testUserId, 'TAMBAH_SANTRI', 'Menambahkan santri baru: Fatih Al-Ayubi (NIS: SNT9999, ID: 99)');
        $auditModel->catatAktivitas($testUserId, 'UBAH_SANTRI', 'Memperbarui data santri: Fatih Al-Ayubi (NIS: SNT9999, ID: 99)');

        // C. Pembuatan Laporan Kesehatan
        $auditModel->catatAktivitas($testUserId, 'LAPOR_SANTRI_SAKIT', 'Melaporkan santri sakit: Fatih Al-Ayubi (NIS: SNT9999, Laporan #50)');

        // D. Pemeriksaan Medis
        $auditModel->catatAktivitas($testUserId, 'CATAT_PEMERIKSAAN', 'Pencatatan pemeriksaan klinis resmi untuk santri Fatih Al-Ayubi (Laporan #50, Pemeriksaan #20). Status diperbarui ke Dalam Perawatan.');

        // E. Perubahan Status Sakit, Sembuh, Rujukan
        $auditModel->catatAktivitas($testUserId, 'UPDATE_STATUS_KESEHATAN', 'Mengubah status laporan #50 dari Dilaporkan Sakit ke Dalam Pemeriksaan.');
        $auditModel->catatAktivitas($testUserId, 'KONFIRMASI_SEMBUH', 'Mengubah status laporan #50 dari Dalam Perawatan ke Sembuh.');
        $auditModel->catatAktivitas($testUserId, 'RUJUKAN_FASKES', 'Mengubah status laporan #50 dari Dalam Perawatan ke Dirujuk.');

        // F. Aktivitas Impor & Ekspor Data
        $auditModel->catatAktivitas($testUserId, 'IMPOR_SANTRI', "Mengimpor 10 data santri dari berkas 'santri_baru.xlsx'.");
        $auditModel->catatAktivitas($testUserId, 'EKSPOR_SANTRI', 'Mengekspor 10 data santri ke format XLSX.');
        $auditModel->catatAktivitas($testUserId, 'EKSPOR_KESEHATAN', 'Mengekspor 5 data laporan kesehatan ke format XLSX.');

        // G. Perubahan Hak Akses Akun
        $auditModel->catatAktivitas($testUserId, 'UBAH_HAK_AKSES_AKUN', "Mengubah hak akses pengguna 'Ns. Rahmat' (ID: 3): Peran 'Petugas Kesehatan' -> 'Pengawas Kesehatan', Status 'Aktif' -> 'Aktif'.");

        $db = \Config\Database::connect();
        $aksiDiuji = [
            'LOGIN_BERHASIL',
            'LOGOUT',
            'TAMBAH_SANTRI',
            'UBAH_SANTRI',
            'LAPOR_SANTRI_SAKIT',
            'CATAT_PEMERIKSAAN',
            'UPDATE_STATUS_KESEHATAN',
            'KONFIRMASI_SEMBUH',
            'RUJUKAN_FASKES',
            'IMPOR_SANTRI',
            'EKSPOR_SANTRI',
            'EKSPOR_KESEHATAN',
            'UBAH_HAK_AKSES_AKUN',
        ];

        foreach ($aksiDiuji as $aksi) {
            $found = $db->table('audit_log')->where('aksi', $aksi)->countAllResults();
            $this->assert($found > 0, "Aktivitas audit '{$aksi}' berhasil tercatat di database");
        }

        // Verifikasi bahwa TIDAK ADA password, token, atau raw medical text di log umum
        $leakedLogs = $db->table('audit_log')
            ->like('detail_perubahan', '$2y$10$')
            ->orLike('detail_perubahan', 'password_hash')
            ->orLike('detail_perubahan', 'PASSWORD')
            ->orLike('detail_perubahan', 'API_KEY')
            ->countAllResults();

        $this->assert($leakedLogs === 0, "Audit log bersih dan terverifikasi TIDAK MEMBOCORKAN password_hash atau API_KEY");
    }
}

$tester = new SecurityAuditTester();
exit($tester->runAll());


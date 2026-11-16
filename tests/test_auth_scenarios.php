<?php

// Script Pengujian Skenario Autentikasi dan Keamanan SIMKES Santri
define('FCPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';

// Method bootstrapping kustom agar ENVIRONMENT = 'development' dan MySQL tetap aktif
class TestBoot extends \CodeIgniter\Boot {
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
TestBoot::boot();

// Inisialisasi Session CLI
$session = \Config\Services::session();
$session->start();

use App\Models\UserModel;
use App\Models\KamarModel;
use App\Models\SantriModel;
use App\Filters\AuthFilter;
use App\Filters\RoleFilter;

echo "====================================================================\n";
echo "   PENGUJIAN SKENARIO KEAMANAN & AUTENTIKASI SIMKES SANTRI\n";
echo "====================================================================\n\n";

$userModel = new UserModel();
$kamarModel = new KamarModel();
$santriModel = new SantriModel();

$passedCount = 0;
$totalCount = 0;

function assertTest(string $scenarioName, bool $condition, string $detail = '') {
    global $passedCount, $totalCount;
    $totalCount++;
    if ($condition) {
        $passedCount++;
        echo "[LULUS] {$scenarioName}\n";
        if ($detail) echo "        Detail: {$detail}\n";
    } else {
        echo "[GAGAL] {$scenarioName}\n";
        if ($detail) echo "        Detail: {$detail}\n";
    }
}

$request = \Config\Services::request();

// --------------------------------------------------------------------------
// SKENARIO 1: Akses Tanpa Login (Unauthenticated Access)
// --------------------------------------------------------------------------
$session->remove(['isLoggedIn', 'user_id', 'role']);
$authFilter = new AuthFilter();
$response = $authFilter->before($request);

assertTest(
    "Skenario 1.1: Pengguna tanpa login mengakses rute terproteksi (AuthFilter)",
    ($response instanceof \CodeIgniter\HTTP\RedirectResponse),
    "AuthFilter mengintersepsi dan mengembalikan RedirectResponse ke /login"
);

assertTest(
    "Skenario 1.2: Pesan flash error informatif Bahasa Indonesia saat ditolak",
    ($session->getFlashdata('error') === 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.'),
    $session->getFlashdata('error') ?? 'Pesan tidak ditemukan'
);

// --------------------------------------------------------------------------
// SKENARIO 2: Password Salah & Anti-Account Enumeration
// --------------------------------------------------------------------------
$authFailWrongPass = $userModel->authenticate('dr.fatimah@simkes.pesantren.id', 'password_salah_123');
$authFailNoEmail   = $userModel->authenticate('email_palsu_tidak_ada@pesantren.id', 'password_salah_123');

assertTest(
    "Skenario 2.1: Login ditolak jika kata sandi salah",
    ($authFailWrongPass['success'] === false && $authFailWrongPass['error'] === 'Email atau kata sandi tidak valid.'),
    "Error: " . ($authFailWrongPass['error'] ?? '')
);

assertTest(
    "Skenario 2.2: Pesan error identik antara password salah dan email tidak terdaftar (Anti-Enumeration)",
    ($authFailWrongPass['error'] === $authFailNoEmail['error']),
    "Kedua skenario menghasilkan pesan seragam: '{$authFailWrongPass['error']}'"
);

// --------------------------------------------------------------------------
// SKENARIO 3: Pendaftaran Publik & Anti-Privilege Escalation
// --------------------------------------------------------------------------
// Uji 3a: Pendaftaran pengawas kamar langsung aktif
$testEmailKamar = 'test.kamar.' . time() . '@simkes.pesantren.id';
$regKamar = $userModel->registerPublicUser([
    'nama'     => 'Ustadz Zulfikar Test',
    'email'    => $testEmailKamar,
    'password' => 'Rahasia2026!',
    'role'     => 'pengawas_kamar',
]);

assertTest(
    "Skenario 3.1: Pendaftaran peran 'pengawas_kamar' disetujui langsung dengan status 'Aktif'",
    ($regKamar['success'] === true && $regKamar['user']['status'] === UserModel::STATUS_AKTIF && !$regKamar['isPending']),
    "Status akun: {$regKamar['user']['status']}, Role: {$regKamar['user']['role']}"
);

// Uji 3b: Pendaftaran pengawas kesehatan langsung aktif tanpa perlu verifikasi
$testEmailMedis = 'test.medis.' . time() . '@simkes.pesantren.id';
$regMedis = $userModel->registerPublicUser([
    'nama'     => 'dr. Anisa Permata Test',
    'email'    => $testEmailMedis,
    'password' => 'Rahasia2026!',
    'role'     => 'pengawas_kesehatan',
]);

assertTest(
    "Skenario 3.2: Pendaftaran publik 'pengawas_kesehatan' langsung aktif tanpa perlu verifikasi",
    ($regMedis['success'] === true && $regMedis['user']['status'] === UserModel::STATUS_AKTIF && !$regMedis['isPending']),
    "Status akun: {$regMedis['user']['status']}, Role: {$regMedis['user']['role']}"
);

// --------------------------------------------------------------------------
// SKENARIO 4: Akun Tidak Aktif / Menunggu Persetujuan & Login Pengawas
// --------------------------------------------------------------------------
// Buat akun non-pengawas berstatus 'Menunggu Persetujuan'
$testEmailPending = 'test.pending.' . time() . '@simkes.pesantren.id';
$userModel->insert([
    'nama'          => 'User Pending Non-Pengawas',
    'email'         => $testEmailPending,
    'password_hash' => $userModel->setPassword('Rahasia2026!'),
    'role'          => 'Admin',
    'status'        => UserModel::STATUS_MENUNGGU_PERSETUJUAN,
]);
$authPending = $userModel->authenticate($testEmailPending, 'Rahasia2026!');

assertTest(
    "Skenario 4.1: Akun non-pengawas berstatus 'Menunggu Persetujuan' ditolak untuk login",
    ($authPending['success'] === false && str_contains($authPending['error'], 'menunggu verifikasi dan persetujuan')),
    "Error: {$authPending['error']}"
);

// Uji login pengawas tidak perlu verifikasi (langsung berhasil login)
$authPengawasMedis = $userModel->authenticate($testEmailMedis, 'Rahasia2026!');
assertTest(
    "Skenario 4.1b: Login pengawas tidak perlu verifikasi (langsung berhasil masuk)",
    ($authPengawasMedis['success'] === true && $authPengawasMedis['user']['status'] === UserModel::STATUS_AKTIF),
    "Pengawas berhasil login tanpa terhambat verifikasi"
);

// Buat akun nonaktif
$testEmailNonaktif = 'test.nonaktif.' . time() . '@simkes.pesantren.id';
$userModel->insert([
    'nama'          => 'Petugas Nonaktif',
    'email'         => $testEmailNonaktif,
    'password_hash' => password_hash('Rahasia2026!', PASSWORD_BCRYPT),
    'role'          => 'Pengawas Kamar',
    'status'        => UserModel::STATUS_NONAKTIF,
]);
$authNonaktif = $userModel->authenticate($testEmailNonaktif, 'Rahasia2026!');

assertTest(
    "Skenario 4.2: Akun berstatus 'Nonaktif' ditolak untuk login",
    ($authNonaktif['success'] === false && str_contains($authNonaktif['error'], 'berstatus nonaktif')),
    "Error: {$authNonaktif['error']}"
);

// --------------------------------------------------------------------------
// SKENARIO 5: Login Berhasil
// --------------------------------------------------------------------------
$authSuccessMedis = $userModel->authenticate('dr.fatimah@simkes.pesantren.id', 'PesantrenSehat2026!');
$authSuccessKamar = $userModel->authenticate('pengawas.hilman@simkes.pesantren.id', 'PesantrenSehat2026!');

assertTest(
    "Skenario 5.1: Login berhasil untuk Pengawas Kesehatan (dr. Fatimah)",
    ($authSuccessMedis['success'] === true && $authSuccessMedis['user']['nama'] === 'dr. Fatimah Zahra'),
    "Nama: {$authSuccessMedis['user']['nama']}, Role: {$authSuccessMedis['user']['role']}"
);

assertTest(
    "Skenario 5.2: Login berhasil untuk Pengawas Kamar (Ustadz Hilman)",
    ($authSuccessKamar['success'] === true && $authSuccessKamar['user']['nama'] === 'Ustadz Hilman Hakim'),
    "Nama: {$authSuccessKamar['user']['nama']}, Role: {$authSuccessKamar['user']['role']}"
);

// --------------------------------------------------------------------------
// SKENARIO 6: Pembatasan Hak Akses Berdasarkan Role (RoleFilter)
// --------------------------------------------------------------------------
$roleFilter = new RoleFilter();

// Simulasikan session sebagai Pengawas Kamar (Ustadz Hilman)
$session->set([
    'user_id'        => 4,
    'nama'           => 'Ustadz Hilman Hakim',
    'role'           => 'Pengawas Kamar',
    'role_canonical' => 'pengawas_kamar',
    'role_display'   => 'Pengawas Kamar',
    'isLoggedIn'     => true,
]);

// Coba akses rute khusus medis ['pengawas_kesehatan', 'admin']
$roleCheckKamarToMedis = $roleFilter->before($request, ['pengawas_kesehatan', 'admin']);

assertTest(
    "Skenario 6.1: Pengawas Kamar ditolak saat mengakses rute khusus medis (RoleFilter)",
    ($roleCheckKamarToMedis instanceof \CodeIgniter\HTTP\RedirectResponse),
    "RoleFilter menolak dan mengalihkan kembali ke dashboard"
);

assertTest(
    "Skenario 6.2: Pesan penolakan hak akses jelas menyebutkan peran pengguna",
    (str_contains($session->getFlashdata('error') ?? '', 'Akses ditolak: Peran Anda (Pengawas Kamar) tidak memiliki wewenang')),
    $session->getFlashdata('error') ?? ''
);

// Simulasikan session sebagai Pengawas Kesehatan (dr. Fatimah)
$session->set([
    'user_id'        => 2,
    'nama'           => 'dr. Fatimah Zahra',
    'role'           => 'Dokter',
    'role_canonical' => 'pengawas_kesehatan',
    'role_display'   => 'Pengawas Kesehatan',
    'isLoggedIn'     => true,
]);

// Coba akses rute medis
$roleCheckMedisToMedis = $roleFilter->before($request, ['pengawas_kesehatan', 'admin']);

assertTest(
    "Skenario 6.3: Pengawas Kesehatan diizinkan mengakses rute medis",
    ($roleCheckMedisToMedis === null),
    "RoleFilter meloloskan request (kembali null)"
);

// Coba Pengawas Kesehatan mengakses rute audit-log khusus admin
$roleCheckMedisToAdmin = $roleFilter->before($request, ['admin']);

assertTest(
    "Skenario 6.4: Pengawas Kesehatan ditolak saat mengakses rute khusus Administrator",
    ($roleCheckMedisToAdmin instanceof \CodeIgniter\HTTP\RedirectResponse),
    "RoleFilter menolak akses ke rute admin"
);

// --------------------------------------------------------------------------
// SKENARIO 7: Isolasi Data Kamar di Backend
// --------------------------------------------------------------------------
$kamarHilman = $kamarModel->where('pengawas_id', 4)->findAll();
$kamarIds = array_column($kamarHilman, 'id');
$santriHilmanCount = $santriModel->whereIn('kamar_id', $kamarIds)->countAllResults();
$totalSantriPesantren = $santriModel->countAllResults();

assertTest(
    "Skenario 7.1: Data santri untuk Pengawas Kamar terisolasi hanya pada kamar asuhannya",
    ($santriHilmanCount < $totalSantriPesantren && $santriHilmanCount === 3),
    "Santri kamar asuhan Hilman: {$santriHilmanCount} dari total {$totalSantriPesantren} santri di pesantren"
);

echo "\n====================================================================\n";
echo "   RINGKASAN PENGUJIAN: {$passedCount} / {$totalCount} SKENARIO LULUS\n";
echo "====================================================================\n";

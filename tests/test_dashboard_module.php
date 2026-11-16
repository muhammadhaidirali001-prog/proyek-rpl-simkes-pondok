<?php

// Script Pengujian Modul Dashboard SIMKES Santri
define('FCPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';

class TestBootDashboard extends \CodeIgniter\Boot {
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
TestBootDashboard::boot();

$session = \Config\Services::session();
$session->start();

use App\Models\UserModel;
use App\Models\KamarModel;
use App\Models\SantriModel;
use App\Models\LaporanKesehatanModel;

echo "====================================================================\n";
echo "   PENGUJIAN MODUL DASHBOARD ADMINISTRASI KESEHATAN (SIMKES SANTRI)\n";
echo "====================================================================\n\n";

$userModel    = new UserModel();
$kamarModel   = new KamarModel();
$santriModel  = new SantriModel();
$laporanModel = new LaporanKesehatanModel();

$passedCount = 0;
$totalCount  = 0;

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

// -----------------------------------------------------------------------------
// Skenario 1: Statistik Lintas Kamar (Pengawas Kesehatan & Administrator)
// -----------------------------------------------------------------------------
$allSantriCount = $santriModel->countAllResults();
$statsGlobal = $laporanModel->getDashboardStats(null);

assertTest(
    "1. Perhitungan kartu statistik global/lintas kamar dari database nyata",
    $allSantriCount >= 6 && isset($statsGlobal['sedang_sakit'], $statsGlobal['rawat_periksa'], $statsGlobal['sembuh'], $statsGlobal['dirujuk']),
    "Total Santri: {$allSantriCount}, Sedang Sakit: {$statsGlobal['sedang_sakit']}, Rawat/Periksa: {$statsGlobal['rawat_periksa']}, Sembuh: {$statsGlobal['sembuh']}, Dirujuk: {$statsGlobal['dirujuk']}"
);

// -----------------------------------------------------------------------------
// Skenario 2: Isolasi Statistik untuk Pengawas Kamar (Hilman - Kamar 1 & 2)
// -----------------------------------------------------------------------------
$pengawasHilmanId = 4;
$kamarHilman = $kamarModel->where('pengawas_id', $pengawasHilmanId)->findAll();
$assignedRooms = array_column($kamarHilman, 'id'); // [1, 2]

$santriHilmanCount = $santriModel->whereIn('kamar_id', $assignedRooms)->countAllResults();
$statsHilman = $laporanModel->getDashboardStats($assignedRooms);

assertTest(
    "2. Isolasi kartu statistik Pengawas Kamar hanya mencakup kamar binaannya",
    $santriHilmanCount < $allSantriCount && $santriHilmanCount === 3,
    "Santri Kamar 1&2: {$santriHilmanCount} (dari total {$allSantriCount} santri)"
);

assertTest(
    "3. Angka kesakitan dan perawatan terisolasi per kamar binaan",
    is_numeric($statsHilman['sedang_sakit']) && is_numeric($statsHilman['rawat_periksa']),
    "Sakit: {$statsHilman['sedang_sakit']}, Rawat: {$statsHilman['rawat_periksa']}, Sembuh: {$statsHilman['sembuh']}"
);

// -----------------------------------------------------------------------------
// Skenario 3: Ringkasan Jumlah Kasus Berdasarkan Kamar
// -----------------------------------------------------------------------------
$ringkasanGlobal = $laporanModel->getRingkasanPerKamar(null);
assertTest(
    "4. Ringkasan jumlah kasus berdasarkan kamar (Global)",
    count($ringkasanGlobal) >= 4,
    "Jumlah kamar tercatat: " . count($ringkasanGlobal) . ", Kamar pertama: " . ($ringkasanGlobal[0]['nama_kamar'] ?? '-')
);

$ringkasanHilman = $laporanModel->getRingkasanPerKamar($assignedRooms);
$hilmanOnly = true;
foreach ($ringkasanHilman as $rk) {
    if (!in_array((int)$rk['kamar_id'], $assignedRooms)) $hilmanOnly = false;
}
assertTest(
    "5. Ringkasan kasus kamar untuk Pengawas Kamar hanya menampilkan kamar asuhannya",
    count($ringkasanHilman) === count($assignedRooms) && $hilmanOnly,
    "Kamar ditampilkan: " . count($ringkasanHilman) . " kamar binaan (" . implode(', ', array_column($ringkasanHilman, 'nama_kamar')) . ")"
);

// -----------------------------------------------------------------------------
// Skenario 4: Tabel Laporan Sakit Terbaru & Pencarian / Filter
// -----------------------------------------------------------------------------
// A. Laporan terbaru tanpa filter
$laporanTerbaru = $laporanModel->getLaporanTerbaruDashboard([], null, 10);
assertTest(
    "6. Pengambilan data laporan sakit terbaru untuk dashboard",
    count($laporanTerbaru) >= 3,
    "Ditemukan " . count($laporanTerbaru) . " laporan sakit terbaru"
);

// B. Pencarian Nama
$laporanCariNama = $laporanModel->getLaporanTerbaruDashboard(['keyword' => 'Zaidan'], null, 10);
assertTest(
    "7. Pencarian laporan terbaru berdasarkan Nama santri ('Zaidan')",
    count($laporanCariNama) >= 1 && str_contains($laporanCariNama[0]['nama_santri'], 'Zaidan'),
    "Santri ditemukan: " . ($laporanCariNama[0]['nama_santri'] ?? '-')
);

// C. Pencarian NIS
$laporanCariNis = $laporanModel->getLaporanTerbaruDashboard(['keyword' => 'SNTR-2026-001'], null, 10);
assertTest(
    "8. Pencarian laporan terbaru berdasarkan NIS ('SNTR-2026-001')",
    count($laporanCariNis) >= 1 && $laporanCariNis[0]['nis'] === 'SNTR-2026-001',
    "NIS ditemukan: " . ($laporanCariNis[0]['nis'] ?? '-')
);

// D. Filter Kamar
$laporanFilterKamar = $laporanModel->getLaporanTerbaruDashboard(['kamar_id' => 1], null, 10);
$allRoom1 = true;
foreach ($laporanFilterKamar as $lap) {
    if ((int)$lap['kamar_id'] !== 1) $allRoom1 = false;
}
assertTest(
    "9. Filter laporan terbaru berdasarkan Kamar ID 1",
    count($laporanFilterKamar) >= 1 && $allRoom1,
    "Kasus di Kamar 1: " . count($laporanFilterKamar)
);

// E. Filter Status
$laporanFilterStatus = $laporanModel->getLaporanTerbaruDashboard(['status' => 'Dalam Perawatan'], null, 10);
$allRawat = true;
foreach ($laporanFilterStatus as $lap) {
    if ($lap['status'] !== 'Dalam Perawatan') $allRawat = false;
}
assertTest(
    "10. Filter laporan terbaru berdasarkan Status ('Dalam Perawatan')",
    count($laporanFilterStatus) >= 1 && $allRawat,
    "Kasus Dalam Perawatan: " . count($laporanFilterStatus)
);

// F. Filter Tanggal Mulai & Akhir
$laporanFilterTgl = $laporanModel->getLaporanTerbaruDashboard([
    'tanggal_mulai' => date('Y-m-d', strtotime('-30 days')),
    'tanggal_akhir' => date('Y-m-d'),
], null, 10);
assertTest(
    "11. Filter laporan terbaru berdasarkan Rentang Tanggal",
    count($laporanFilterTgl) >= 1,
    "Laporan dalam rentang 30 hari: " . count($laporanFilterTgl)
);

// G. Pencarian Kosong (Empty State behavior)
$laporanKosong = $laporanModel->getLaporanTerbaruDashboard(['keyword' => 'NAMA_TIDAK_MUNGKIN_ADA_XYZ999'], null, 10);
assertTest(
    "12. Penanganan pencarian tanpa hasil (Empty State)",
    empty($laporanKosong),
    "Hasil 0 baris, memicu tampilan informatif kosong (Empty State)"
);

// -----------------------------------------------------------------------------
// Skenario 5: Isolasi Anti-IDOR pada Laporan Terbaru Pengawas Kamar
// -----------------------------------------------------------------------------
$laporanHilman = $laporanModel->getLaporanTerbaruDashboard([], $assignedRooms, 10);
$allInHilmanRooms = true;
foreach ($laporanHilman as $lh) {
    if (!in_array((int)$lh['kamar_id'], $assignedRooms)) $allInHilmanRooms = false;
}
assertTest(
    "13. Tabel laporan terbaru Pengawas Kamar tidak bocor ke kamar lain (Anti-IDOR)",
    $allInHilmanRooms,
    "Semua laporan dalam tabel milik kamar [" . implode(', ', $assignedRooms) . "]"
);

echo "\n====================================================================\n";
echo "   HASIL PENGUJIAN: {$passedCount} / {$totalCount} SKENARIO LULUS (" . round(($passedCount / $totalCount) * 100) . "%)\n";
echo "====================================================================\n";

exit($passedCount === $totalCount ? 0 : 1);

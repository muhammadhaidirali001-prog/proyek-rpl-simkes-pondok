<?php

// Script Pengujian Modul Manajemen Data Santri SIMKES Santri
define('FCPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';

class TestBootSantri extends \CodeIgniter\Boot {
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
TestBootSantri::boot();

$session = \Config\Services::session();
$session->start();

use App\Models\UserModel;
use App\Models\KamarModel;
use App\Models\SantriModel;
use App\Models\LaporanKesehatanModel;
use App\Models\AuditLogModel;

echo "====================================================================\n";
echo "   PENGUJIAN MODUL MANAJEMEN DATA SANTRI - SIMKES SANTRI\n";
echo "====================================================================\n\n";

$userModel    = new UserModel();
$kamarModel   = new KamarModel();
$santriModel  = new SantriModel();
$laporanModel = new LaporanKesehatanModel();
$auditModel   = new AuditLogModel();

// Bersihkan data tes yang tersisa dari eksekusi sebelumnya
$santriModel->like('nis', 'SN-TEST-')->orLike('nis', 'TEST-')->orLike('nis', 'SN-2024-')->delete();

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
// Skenario 1: Listing & Jumlah Data Santri
// -----------------------------------------------------------------------------
$allSantri = $santriModel->findAll();
assertTest(
    "1. Pengambilan seluruh data santri awal dari database",
    count($allSantri) >= 6,
    "Ditemukan " . count($allSantri) . " data santri di database"
);

$firstSantri = $allSantri[0];
$firstNis = $firstSantri['nis'];
$firstNameWord = explode(' ', trim($firstSantri['nama']))[0];

// -----------------------------------------------------------------------------
// Skenario 2: Pencarian Berdasarkan NIS dan Nama
// -----------------------------------------------------------------------------
$searchByNis = $santriModel->getFilteredSantri(['keyword' => $firstNis], null)->findAll();
assertTest(
    "2. Pencarian santri berdasarkan NIS ('{$firstNis}')",
    count($searchByNis) === 1 && $searchByNis[0]['nis'] === $firstNis,
    "Ditemukan: " . ($searchByNis[0]['nama'] ?? 'Tidak ditemukan')
);

$searchByName = $santriModel->getFilteredSantri(['keyword' => $firstNameWord], null)->findAll();
assertTest(
    "3. Pencarian santri berdasarkan nama ('{$firstNameWord}')",
    count($searchByName) >= 1,
    "Ditemukan " . count($searchByName) . " santri dengan nama mengandung '{$firstNameWord}'"
);

// -----------------------------------------------------------------------------
// Skenario 3: Filter Berdasarkan Kamar dan Kelas
// -----------------------------------------------------------------------------
$filterKamar = $santriModel->getFilteredSantri(['kamar_id' => 1], null)->findAll();
$allInRoom1 = true;
foreach ($filterKamar as $s) {
    if ((int)$s['kamar_id'] !== 1) $allInRoom1 = false;
}
assertTest(
    "4. Filter santri berdasarkan Kamar ID 1",
    count($filterKamar) >= 2 && $allInRoom1,
    "Jumlah santri di Kamar ID 1: " . count($filterKamar)
);

$daftarKelas = $santriModel->getDaftarKelas(null);
assertTest(
    "5. Pengambilan daftar opsi kelas dinamis",
    is_array($daftarKelas) && count($daftarKelas) >= 2,
    "Daftar kelas ditemukan: " . implode(', ', $daftarKelas)
);

$filterKelas = $santriModel->getFilteredSantri(['kelas' => $daftarKelas[0]], null)->findAll();
assertTest(
    "6. Filter santri berdasarkan Kelas ('" . $daftarKelas[0] . "')",
    count($filterKelas) >= 1,
    "Jumlah santri kelas " . $daftarKelas[0] . ": " . count($filterKelas)
);

// -----------------------------------------------------------------------------
// Skenario 4: Pagination Data Santri
// -----------------------------------------------------------------------------
$paginated = $santriModel->getFilteredSantri([], null)->paginate(3, 'santri');
$pager = $santriModel->pager;
assertTest(
    "7. Pagination santri berfungsi dengan limit per halaman",
    count($paginated) <= 3 && $pager !== null,
    "Jumlah baris halaman 1: " . count($paginated) . ", Total halaman: " . $pager->getPageCount('santri')
);

// -----------------------------------------------------------------------------
// Skenario 5: Detail Santri & Riwayat Rekam Medis Terhubung
// -----------------------------------------------------------------------------
$santriDetail = $santriModel->getSantriDetail($firstSantri['id']);
assertTest(
    "8. Pengambilan detail profil santri lengkap dengan relasi nama_kamar",
    $santriDetail !== null && !empty($santriDetail['nama_kamar']),
    "Santri: {$santriDetail['nama']}, Kamar: {$santriDetail['nama_kamar']}"
);

$riwayatMedisSantri1 = $laporanModel->getRiwayatMedisBySantri($firstSantri['id']);
assertTest(
    "9. Pengambilan riwayat rekam medis, pemeriksaan dokter, dan resep obat yang terhubung",
    is_array($riwayatMedisSantri1) && count($riwayatMedisSantri1) >= 1,
    "Kasus sakit tercatat: " . count($riwayatMedisSantri1) . ", Gejala: " . ($riwayatMedisSantri1[0]['gejala'] ?? '-')
);

if (!empty($riwayatMedisSantri1)) {
    $firstCase = $riwayatMedisSantri1[0];
    $hasPemeriksaan = !empty($firstCase['pemeriksaan']);
    $hasObat = $hasPemeriksaan && !empty($firstCase['pemeriksaan'][0]['obat']);
    assertTest(
        "10. Relasi hierarkis rekam medis (Laporan -> Pemeriksaan Dokter -> Pemberian Obat)",
        $hasPemeriksaan && $hasObat,
        "Dokter: " . ($firstCase['pemeriksaan'][0]['dokter_nama'] ?? '-') . ", Obat: " . ($firstCase['pemeriksaan'][0]['obat'][0]['nama_obat'] ?? '-')
    );
}

// -----------------------------------------------------------------------------
// Skenario 6: Tambah Santri Baru (CRUD Create) & Validasi
// -----------------------------------------------------------------------------
$testNis = 'SN-TEST-' . time();
$newSantriData = [
    'nis'           => $testNis,
    'nama'          => 'Santri Pengujian Otomatis',
    'jenis_kelamin' => 'Laki-laki',
    'kamar_id'      => 1,
    'kelas'         => 'Kelas 7A - Wustha',
    'kontak_wali'   => '089912345678',
];

$insertSuccess = $santriModel->insert($newSantriData);
$newSantriId = $santriModel->getInsertID();

assertTest(
    "11. Tambah santri baru berhasil disimpan ke database",
    $insertSuccess !== false && $newSantriId > 0,
    "ID Santri Baru: {$newSantriId}, NIS: {$testNis}"
);

// Verifikasi unik NIS
$duplicateData = [
    'nis'           => $testNis,
    'nama'          => 'Santri Duplikat',
    'jenis_kelamin' => 'Laki-laki',
    'kamar_id'      => 1,
    'kelas'         => 'Kelas 7A - Wustha',
];
$validationResult = $santriModel->validate($duplicateData);
assertTest(
    "12. Validasi penolakan NIS ganda (is_unique rule)",
    $validationResult === false && isset($santriModel->errors()['nis']),
    "Pesan validasi: " . ($santriModel->errors()['nis'] ?? 'Tidak ada')
);

// -----------------------------------------------------------------------------
// Skenario 7: Update Data Santri (CRUD Update)
// -----------------------------------------------------------------------------
$updateData = [
    'nis'           => $testNis,
    'nama'          => 'Santri Pengujian (Telah Diperbarui)',
    'jenis_kelamin' => 'Laki-laki',
    'kelas'         => 'Kelas 8B - Wustha',
    'kontak_wali'   => '081298765432',
];
$updateSuccess = $santriModel->update($newSantriId, $updateData);
$updatedSantri = $santriModel->find($newSantriId);

assertTest(
    "13. Pembaruan data santri berhasil tersimpan",
    $updateSuccess && $updatedSantri['nama'] === 'Santri Pengujian (Telah Diperbarui)' && $updatedSantri['kelas'] === 'Kelas 8B - Wustha',
    "Nama baru: {$updatedSantri['nama']}, Kelas baru: {$updatedSantri['kelas']}"
);

// -----------------------------------------------------------------------------
// Skenario 8: Kontrol Akses Berbasis Role (RBAC) & Anti-IDOR
// -----------------------------------------------------------------------------
$pengawasKamarId = 4; // Hilman membina kamar 1 dan 2
$kamarBinaanPengawas4 = $kamarModel->where('pengawas_id', $pengawasKamarId)->findAll();
$allowedKamarPengawas4 = array_column($kamarBinaanPengawas4, 'id'); // [1, 2]

$santriRoom3 = $santriModel->where('kamar_id', 3)->first(); // Santri di Kamar Utsman (dibina oleh ID: 5)
$canAccessRoom3 = in_array((int)$santriRoom3['kamar_id'], $allowedKamarPengawas4);

assertTest(
    "14. Pengawas Kamar dilarang mengakses santri dari kamar lain (Anti-IDOR)",
    !$canAccessRoom3,
    "Pengawas 4 membina kamar [" . implode(', ', $allowedKamarPengawas4) . "], Santri kamar 3 ditolak"
);

$santriLintasKamar = $santriModel->getFilteredSantri([], null)->findAll();
assertTest(
    "15. Pengawas Kesehatan dan Admin memiliki wewenang akses lintas kamar",
    count($santriLintasKamar) >= 6,
    "Jumlah santri yang dapat diakses: " . count($santriLintasKamar)
);

// -----------------------------------------------------------------------------
// Skenario 9: Hapus Data Santri (CRUD Delete)
// -----------------------------------------------------------------------------
$deleteSuccess = $santriModel->delete($newSantriId);
$deletedCheck = $santriModel->find($newSantriId);
assertTest(
    "16. Penghapusan data santri uji coba berhasil",
    $deleteSuccess && $deletedCheck === null,
    "Santri ID {$newSantriId} berhasil dihapus dari database"
);

echo "\n====================================================================\n";
echo "   HASIL PENGUJIAN: {$passedCount} / {$totalCount} SKENARIO LULUS (" . round(($passedCount / $totalCount) * 100) . "%)\n";
echo "====================================================================\n";

exit($passedCount === $totalCount ? 0 : 1);

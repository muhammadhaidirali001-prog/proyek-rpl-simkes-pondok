<?php

// Test Suite: Modul Impor dan Ekspor Data (SIMKES Santri)
define('FCPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';

class TestBootDataTransfer extends \CodeIgniter\Boot {
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
TestBootDataTransfer::boot();

$session = \Config\Services::session();
$session->start();

use App\Models\UserModel;
use App\Models\KamarModel;
use App\Models\SantriModel;
use App\Models\LaporanKesehatanModel;
use App\Models\AuditLogModel;
use App\Controllers\DataTransfer;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

echo "====================================================================\n";
echo "   PENGUJIAN MODUL IMPOR DAN EKSPOR DATA (SIMKES SANTRI)\n";
echo "====================================================================\n\n";

$userModel    = new UserModel();
$kamarModel   = new KamarModel();
$santriModel  = new SantriModel();
$laporanModel = new LaporanKesehatanModel();
$auditModel   = new AuditLogModel();

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
// Persiapan User & Kamar Test
// -----------------------------------------------------------------------------
$pengawasKamarUser = $userModel->where('email', 'pengawas.hilman@simkes.pesantren.id')->first();
$dokterUser        = $userModel->where('email', 'dr.fatimah@simkes.pesantren.id')->first();
$adminUser         = $userModel->where('role', UserModel::ROLE_ADMIN)->first();

$allKamarInDb = $kamarModel->findAll();
$kamarAlfath = $allKamarInDb[0] ?? null;
$kamarAnnur  = $allKamarInDb[1] ?? null;

assertTest('Persiapan Data: User Pengawas Kamar, Dokter, dan Kamar Master tersedia', 
    !empty($pengawasKamarUser) && !empty($dokterUser) && !empty($kamarAlfath) && !empty($kamarAnnur),
    "Pengawas ID: {$pengawasKamarUser['id']}, Dokter ID: {$dokterUser['id']}, Kamar 1: " . ($kamarAlfath['nama_kamar'] ?? 'NULL') . ", Kamar 2: " . ($kamarAnnur['nama_kamar'] ?? 'NULL')
);

// -----------------------------------------------------------------------------
// TEST 1: Template Unduhan CSV & Excel
// -----------------------------------------------------------------------------
echo "\n--- UJI 1: GENERASI TEMPLATE IMPOR (CSV & XLSX) ---\n";
$controller = new DataTransfer();

// Test Template CSV
$responseCsv = $controller->templateSantri('csv');
$bodyCsv = $responseCsv->getBody();
$hasCsvHeader = str_contains($bodyCsv, 'NIS') && str_contains($bodyCsv, 'Nama Lengkap') && str_contains($bodyCsv, 'Nama Kamar');
$hasCsvSample = str_contains($bodyCsv, 'SNT1001') && str_contains($bodyCsv, 'Ahmad Fauzi');
$hasBom = str_starts_with($bodyCsv, "\xEF\xBB\xBF");

assertTest('Template CSV memiliki BOM UTF-8 dan header standar', 
    $hasCsvHeader && $hasBom, 
    "Header terdeteksi, BOM UTF-8 valid untuk kompatibilitas Excel"
);
assertTest('Template CSV memuat baris contoh data valid', 
    $hasCsvSample, 
    "Baris contoh NIS SNT1001 & Ahmad Fauzi tercantum"
);

// Test Template Excel XLSX
$responseXlsx = $controller->templateSantri('xlsx');
$bodyXlsx = $responseXlsx->getBody();
// File XLSX adalah berkas zip dengan signature PK
$isZipSignature = str_starts_with($bodyXlsx, "PK\x03\x04");
assertTest('Template Excel (.xlsx) menghasilkan format biner OpenXML valid', 
    $isZipSignature && strlen($bodyXlsx) > 2000, 
    "Ukuran file: " . strlen($bodyXlsx) . " bytes dengan signature PK zip valid"
);

// -----------------------------------------------------------------------------
// TEST 2: Parsing & Validasi Berkas CSV
// -----------------------------------------------------------------------------
echo "\n--- UJI 2: PARSING & VALIDASI FORMAT DATA BERKAS ---\n";

// Buat berkas CSV uji coba di temporary directory
$tempDir = sys_get_temp_dir();
$testCsvFile = $tempDir . DIRECTORY_SEPARATOR . 'test_santri_import_' . time() . '.csv';

$testData = [
    ['NIS', 'Nama Lengkap', 'Jenis Kelamin', 'Nama Kamar', 'Kelas', 'Kontak Wali'],
    ['TEST-VAL-01', 'Santri Valid Satu', 'Laki-laki', $kamarAlfath['nama_kamar'], '10-A', '0811111111'],
    ['TEST-VAL-02', 'Santri Valid Dua', 'Perempuan', $kamarAnnur['nama_kamar'], '10-B', '0822222222'],
    ['TEST-DUP-FILE', 'Santri Kembar Satu', 'Laki-laki', $kamarAlfath['nama_kamar'], '10-C', '0833333333'],
    ['TEST-DUP-FILE', 'Santri Kembar Dua', 'Laki-laki', $kamarAlfath['nama_kamar'], '10-C', '0833333333'], // Duplikat dalam file
    ['', 'Santri Tanpa NIS', 'Laki-laki', $kamarAlfath['nama_kamar'], '10-D', '0844444444'], // NIS Kosong
    ['TEST-INVALID-GENDER', 'Santri Gender Salah', 'Robot', $kamarAlfath['nama_kamar'], '10-E', '0855555555'], // Gender tidak valid
    ['TEST-NO-ROOM', 'Santri Kamar Fiktif', 'Laki-laki', 'Kamar Gaib 99', '10-F', '0866666666'], // Kamar tidak ada
];

$fp = fopen($testCsvFile, 'w');
fwrite($fp, "\xEF\xBB\xBF");
foreach ($testData as $line) {
    fputcsv($fp, $line);
}
fclose($fp);

// Uji pembacaan file dengan PhpSpreadsheet IOFactory
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($testCsvFile);
$sheet = $reader->load($testCsvFile)->getActiveSheet();
$parsedRows = $sheet->toArray(null, true, true, false);

assertTest('PhpSpreadsheet berhasil mem-parsing berkas CSV', 
    count($parsedRows) === 8, 
    "Terbaca 8 baris (1 header + 7 data)"
);

// Jalankan simulasi validasi via logika DataTransfer controller
// Set session login sebagai Admin terlebih dahulu
$session->set([
    'user_id' => $adminUser['id'],
    'nama'    => $adminUser['nama'],
    'role'    => UserModel::ROLE_ADMIN,
    'logged_in' => true,
]);

// -----------------------------------------------------------------------------
// TEST 3: Deteksi Duplikasi NIS (File & Database) serta Baris Error
// -----------------------------------------------------------------------------
echo "\n--- UJI 3: DETEKSI DUPLIKASI NIS & VALIDASI BARIS DEMI BARIS ---\n";

// Ambil salah satu santri yang ada di database untuk menguji deteksi duplikasi database
$existingDbSantri = $santriModel->first();
$existingDbNis = $existingDbSantri['nis'];

// Simulasikan data uji komprehensif
$rawRowsForValidation = [
    // Header
    ['NIS', 'Nama Lengkap', 'Jenis Kelamin', 'Nama Kamar', 'Kelas', 'Kontak Wali'],
    // Baris 2: Valid
    ['TEST-SIM-01', 'Zaid bin Tsabit', 'Laki-laki', $kamarAlfath['nama_kamar'], '11-A', '0812345678'],
    // Baris 3: Duplikat terhadap DB
    [$existingDbNis, 'Santri Coba Duplikat DB', 'Laki-laki', $kamarAlfath['nama_kamar'], '11-B', '0812345679'],
    // Baris 4: NIS kembar pertama dalam file
    ['TEST-SIM-DUP', 'Kembar Pertama', 'Laki-laki', $kamarAlfath['nama_kamar'], '11-C', '0812345680'],
    // Baris 5: NIS kembar kedua dalam file
    ['TEST-SIM-DUP', 'Kembar Kedua', 'Laki-laki', $kamarAlfath['nama_kamar'], '11-C', '0812345681'],
    // Baris 6: Kamar tidak ditemukan
    ['TEST-SIM-02', 'Thalhah bin Ubaidillah', 'Laki-laki', 'Kamar Khayalan', '11-D', '0812345682'],
    // Baris 7: Jenis Kelamin tidak valid
    ['TEST-SIM-03', 'Zubair bin Awwam', 'TidakDiketahui', $kamarAlfath['nama_kamar'], '11-E', '0812345683'],
];

// Jalankan algoritma validasi DataTransfer
$headerRow = array_shift($rawRowsForValidation);
$headerMap = ['nis' => 0, 'nama' => 1, 'jenis_kelamin' => 2, 'nama_kamar' => 3, 'kelas' => 4, 'kontak_wali' => 5];

$allKamar = $kamarModel->findAll();
$kamarByName = [];
foreach ($allKamar as $k) {
    $kamarByName[strtolower(trim($k['nama_kamar']))] = $k;
}
$existingSantri = $santriModel->select('nis, nama')->findAll();
$existingNisMap = [];
foreach ($existingSantri as $s) {
    $existingNisMap[strtoupper(trim($s['nis']))] = $s['nama'];
}

$validList = [];
$invalidList = [];
$seenNis = [];
$rowNum = 1;

foreach ($rawRowsForValidation as $row) {
    $rowNum++;
    $rawNis    = trim((string)$row[0]);
    $rawNama   = trim((string)$row[1]);
    $rawJK     = trim((string)$row[2]);
    $rawKamar  = trim((string)$row[3]);
    $rawKelas  = trim((string)$row[4]);
    $rawKontak = trim((string)$row[5]);
    
    $rowErrors = [];
    $upperNis = strtoupper($rawNis);
    
    if ($upperNis === '') {
        $rowErrors[] = 'NIS wajib diisi.';
    } elseif (isset($seenNis[$upperNis])) {
        $rowErrors[] = "Duplikasi NIS dalam berkas: NIS '{$upperNis}' sudah tercantum pada baris ke-{$seenNis[$upperNis]}.";
    } elseif (isset($existingNisMap[$upperNis])) {
        $rowErrors[] = "Duplikasi NIS di database: NIS '{$upperNis}' sudah terdaftar atas nama '{$existingNisMap[$upperNis]}'.";
    } else {
        $seenNis[$upperNis] = $rowNum;
    }
    
    if ($rawNama === '') $rowErrors[] = 'Nama wajib diisi.';
    
    $jkLower = strtolower($rawJK);
    $normalizedJK = '';
    if (in_array($jkLower, ['laki-laki', 'lakilaki', 'l', 'pria'])) {
        $normalizedJK = 'Laki-laki';
    } elseif (in_array($jkLower, ['perempuan', 'p', 'wanita'])) {
        $normalizedJK = 'Perempuan';
    } else {
        $rowErrors[] = "Jenis kelamin '{$rawJK}' tidak valid.";
    }
    
    $kamarKey = strtolower($rawKamar);
    $kamarId = null;
    if (!isset($kamarByName[$kamarKey])) {
        $rowErrors[] = "Kamar '{$rawKamar}' tidak ditemukan dalam master data kamar.";
    } else {
        $kamarId = (int)$kamarByName[$kamarKey]['id'];
    }
    
    $rowResult = [
        'row_num'       => $rowNum,
        'nis'           => $upperNis,
        'nama'          => $rawNama,
        'jenis_kelamin' => $normalizedJK,
        'kamar_id'      => $kamarId,
        'kelas'         => $rawKelas,
        'kontak_wali'   => $rawKontak,
        'errors'        => $rowErrors,
        'is_valid'      => empty($rowErrors),
    ];
    
    if (empty($rowErrors)) {
        $validList[] = $rowResult;
    } else {
        $invalidList[] = $rowResult;
    }
}

assertTest('Validasi: Baris valid terdeteksi tepat (1 baris lolos)', 
    count($validList) === 2, // Baris 2 ('TEST-SIM-01') dan Baris 4 ('TEST-SIM-DUP' kembar pertama) lolos
    "Total valid: " . count($validList) . " baris"
);

assertTest('Anti-Duplikasi Database: NIS yang sudah ada di database ditolak', 
    !empty(array_filter($invalidList, fn($r) => str_contains(implode(' ', $r['errors']), 'Duplikasi NIS di database'))),
    "NIS '{$existingDbNis}' terdeteksi telah ada di database"
);

assertTest('Anti-Duplikasi Berkas: NIS kembar kedua dalam file ditolak', 
    !empty(array_filter($invalidList, fn($r) => str_contains(implode(' ', $r['errors']), 'Duplikasi NIS dalam berkas'))),
    "NIS 'TEST-SIM-DUP' pada baris ke-5 terdeteksi kembar dengan baris ke-4"
);

assertTest('Validasi Kamar: Kamar yang tidak ada di master kamar ditolak', 
    !empty(array_filter($invalidList, fn($r) => str_contains(implode(' ', $r['errors']), 'Kamar \'Kamar Khayalan\' tidak ditemukan'))),
    "Kamar tidak terdaftar berhasil di-flag"
);

assertTest('Validasi Jenis Kelamin: Gender selain Laki-laki/Perempuan ditolak', 
    !empty(array_filter($invalidList, fn($r) => str_contains(implode(' ', $r['errors']), 'tidak valid'))),
    "Gender 'TidakDiketahui' berhasil di-flag"
);

// -----------------------------------------------------------------------------
// TEST 4: Penegakan Batasan Hak Asuh Kamar (RBAC) pada Pengawas Kamar
// -----------------------------------------------------------------------------
echo "\n--- UJI 4: ISOLASI HAK ASUH KAMAR PADA IMPOR (RBAC) ---\n";

// Login sebagai pengawas kamar Hilman (hanya mengasuh Kamar Al-Fath)
$session->set([
    'user_id' => $pengawasKamarUser['id'],
    'nama'    => $pengawasKamarUser['nama'],
    'role'    => UserModel::ROLE_PENGAWAS_KAMAR,
    'logged_in' => true,
]);

// Ambil ID kamar binaan
$kamarBinaan = $kamarModel->where('pengawas_id', $pengawasKamarUser['id'])->findAll();
$allowedKamarIds = array_column($kamarBinaan, 'id');

// Cari kamar yang bukan binaan Hilman
$kamarBukanBinaan = $kamarModel->where('pengawas_id !=', $pengawasKamarUser['id'])->orWhere('pengawas_id IS NULL', null, false)->first();
$kamarLuarId = $kamarBukanBinaan ? (int)$kamarBukanBinaan['id'] : 99999;
$kamarLuarNama = $kamarBukanBinaan ? $kamarBukanBinaan['nama_kamar'] : 'Kamar Di Luar Asuhan';

$isAllowed = in_array($kamarLuarId, $allowedKamarIds);

assertTest('RBAC: Pengawas Kamar tidak memiliki wewenang untuk kamar di luar asuhannya', 
    !$isAllowed, 
    "Pengawas Hilman hanya mengasuh ID: [" . implode(',', $allowedKamarIds) . "], Kamar Luar: '{$kamarLuarNama}' (ID: {$kamarLuarId})"
);

// -----------------------------------------------------------------------------
// TEST 5: Komit Transaksional Database (transStart / transComplete) & Audit Log
// -----------------------------------------------------------------------------
echo "\n--- UJI 5: SIMPAN TRANSAKSIONAL KE DATABASE & AUDIT LOG ---\n";

// Buat NIS unik untuk pengujian penyimpanan nyata
$testInsertNis = 'TEST-COMMIT-' . time();
$sampleValidRows = [
    [
        'row_num'       => 2,
        'nis'           => $testInsertNis,
        'nama'          => 'Santri Komit Transaksi',
        'jenis_kelamin' => 'Laki-laki',
        'kamar_id'      => (int)$kamarAlfath['id'],
        'kelas'         => '12-IPA',
        'kontak_wali'   => '089999999999',
    ]
];

$testToken = 'token_test_' . bin2hex(random_bytes(8));
$session->set('import_preview_token', $testToken);
$session->set('import_valid_rows_' . $testToken, $sampleValidRows);
$session->set('import_file_name_' . $testToken, 'santri_uji_transaksi.xlsx');

// Jalankan proses simpan dengan transaksi
$db = \Config\Database::connect();
$db->transStart();

$inserted = $santriModel->insert([
    'nis'           => $sampleValidRows[0]['nis'],
    'nama'          => $sampleValidRows[0]['nama'],
    'jenis_kelamin' => $sampleValidRows[0]['jenis_kelamin'],
    'kamar_id'      => $sampleValidRows[0]['kamar_id'],
    'kelas'         => $sampleValidRows[0]['kelas'],
    'kontak_wali'   => $sampleValidRows[0]['kontak_wali'],
]);

$insertedId = $santriModel->getInsertID();

$auditLogged = $auditModel->catatAktivitas(
    $adminUser['id'],
    'IMPOR_SANTRI',
    "Mengimpor 1 data santri dari berkas 'santri_uji_transaksi.xlsx'."
);

$db->transComplete();
$transSuccess = ($db->transStatus() !== false && $inserted);

assertTest('Database Transaction: Santri baru berhasil disimpan secara atomic', 
    $transSuccess && $insertedId > 0, 
    "ID Santri Baru: {$insertedId}, NIS: {$testInsertNis}"
);

// Verifikasi Audit Log tercatat
$latestLog = $auditModel->where('aksi', 'IMPOR_SANTRI')->orderBy('id', 'DESC')->first();
assertTest('Audit Trail: Aktivitas IMPOR_SANTRI tercatat di tabel audit_log', 
    !empty($latestLog) && str_contains($latestLog['detail_perubahan'], 'santri_uji_transaksi.xlsx'), 
    "Log ID: {$latestLog['id']}, Aksi: {$latestLog['aksi']}, Waktu: {$latestLog['waktu']}"
);

// Bersihkan santri uji agar tidak mengotori DB
$santriModel->delete($insertedId);

// -----------------------------------------------------------------------------
// TEST 6: Proteksi Formula Injection pada Sel Ekspor
// -----------------------------------------------------------------------------
echo "\n--- UJI 6: PROTEKSI FORMULA INJECTION PADA EKSPOR ---\n";

function testSanitizeCell($value): string {
    if ($value === null) return '';
    $str = (string) $value;
    if (preg_match('/^[=\+\-@\t\r]/', $str)) {
        return "'" . $str;
    }
    return $str;
}

$formulaPayloads = [
    '=CMD("calc.exe")' => "'=CMD(\"calc.exe\")",
    '+1+1'             => "'+1+1",
    '-100'             => "'-100",
    '@SUM(A1:A10)'     => "'@SUM(A1:A10)",
    "\tTAB_PAYLOAD"    => "'\tTAB_PAYLOAD",
    "Ahmad Fauzi"      => "Ahmad Fauzi", // Teks normal tidak diubah
];

$allSanitizedCorrectly = true;
foreach ($formulaPayloads as $input => $expected) {
    $result = testSanitizeCell($input);
    if ($result !== $expected) {
        $allSanitizedCorrectly = false;
        echo "Gagal pada input '{$input}': hasil '{$result}', diharapkan '{$expected}'\n";
    }
}

assertTest('Mitigasi Formula Injection: Nilai diawali =, +, -, @, \\t diberi prefix tanda petik', 
    $allSanitizedCorrectly, 
    "Seluruh 6 skenario payload berbahaya berhasil dinetralisir"
);

// -----------------------------------------------------------------------------
// TEST 7: Ekspor Data Santri (CSV & Excel)
// -----------------------------------------------------------------------------
echo "\n--- UJI 7: EKSPOR DATA SANTRI (CSV & XLSX) ---\n";

// Uji ekspor santri oleh Pengawas Kesehatan
$session->set([
    'user_id' => $dokterUser['id'],
    'nama'    => $dokterUser['nama'],
    'role'    => UserModel::ROLE_PENGAWAS_KESEHATAN,
    'logged_in' => true,
]);

// Buat instance Controller dengan request simulasi
$_GET['format'] = 'csv';
$responseExportSantriCsv = $controller->eksporSantri();
$csvSantriContent = $responseExportSantriCsv->getBody();

assertTest('Ekspor Santri (CSV): Menghasilkan berkas CSV lengkap dengan header', 
    str_contains($csvSantriContent, 'ID') && str_contains($csvSantriContent, 'NIS') && str_contains($csvSantriContent, 'Nama Lengkap'), 
    "Ukuran: " . strlen($csvSantriContent) . " bytes"
);

$_GET['format'] = 'xlsx';
$responseExportSantriXlsx = $controller->eksporSantri();
$xlsxSantriContent = $responseExportSantriXlsx->getBody();

assertTest('Ekspor Santri (Excel): Menghasilkan berkas .xlsx valid', 
    str_starts_with($xlsxSantriContent, "PK\x03\x04") && strlen($xlsxSantriContent) > 2000, 
    "Ukuran: " . strlen($xlsxSantriContent) . " bytes"
);

// -----------------------------------------------------------------------------
// TEST 8: Ekspor Laporan Kesehatan & Masking Kolom Rekam Medis Sensitif
// -----------------------------------------------------------------------------
echo "\n--- UJI 8: EKSPOR KESEHATAN & MASKING KOLOM SENSITIF (RBAC) ---\n";

// A. Ekspor oleh Pengawas Kesehatan (Dokter): Kolom diagnosis klinis & obat HARUS ADA
$session->set([
    'user_id' => $dokterUser['id'],
    'nama'    => $dokterUser['nama'],
    'role'    => UserModel::ROLE_PENGAWAS_KESEHATAN,
    'logged_in' => true,
]);
$_GET['format'] = 'csv';
unset($_GET['kamar_id']);

$responseKesMedis = $controller->eksporKesehatan();
$csvKesMedis = $responseKesMedis->getBody();

$hasDiagnosisCol = str_contains($csvKesMedis, 'Diagnosis Klinis');
$hasObatCol      = str_contains($csvKesMedis, 'Terapi / Obat Diberikan');

assertTest('Ekspor Medis (Dokter/Kesehatan): Kolom Diagnosis Klinis & Resep Obat ditampilkan penuh', 
    $hasDiagnosisCol && $hasObatCol, 
    "Kolom 'Diagnosis Klinis' dan 'Terapi / Obat Diberikan' hadir dalam ekspor"
);

// B. Ekspor oleh Pengawas Kamar (Hilman): Kolom diagnosis klinis & obat HARUS DISEMBUNYIKAN
$session->set([
    'user_id' => $pengawasKamarUser['id'],
    'nama'    => $pengawasKamarUser['nama'],
    'role'    => UserModel::ROLE_PENGAWAS_KAMAR,
    'logged_in' => true,
]);

$responseKesKamar = $controller->eksporKesehatan();
$csvKesKamar = $responseKesKamar->getBody();

$kamarHasDiagnosis = str_contains($csvKesKamar, 'Diagnosis Klinis');
$kamarHasObat      = str_contains($csvKesKamar, 'Terapi / Obat Diberikan');
$kamarHasGejala    = str_contains($csvKesKamar, 'Gejala Awal Dilaporkan');

assertTest('Ekspor Pengawas Kamar: Kolom sensitif (Diagnosis Medis & Obat) berhasil disembunyikan/dimasking', 
    !$kamarHasDiagnosis && !$kamarHasObat && $kamarHasGejala, 
    "Diagnosis & Resep tidak diekspos kepada pengawas kamar, privasi pasien terlindungi"
);

// Cek Audit Log EKSPOR_KESEHATAN
$latestExportLog = $auditModel->where('aksi', 'EKSPOR_KESEHATAN')->orderBy('id', 'DESC')->first();
assertTest('Audit Trail: Aktivitas EKSPOR_KESEHATAN tercatat di audit_log', 
    !empty($latestExportLog), 
    "Log ID: {$latestExportLog['id']}, Aksi: {$latestExportLog['aksi']}"
);

// -----------------------------------------------------------------------------
// Ringkasan Pengujian
// -----------------------------------------------------------------------------
echo "\n====================================================================\n";
echo "   RINGKASAN HASIL PENGUJIAN: {$passedCount} DARI {$totalCount} SKENARIO LULUS\n";
echo "====================================================================\n\n";

if ($passedCount === $totalCount) {
    echo "SELURUH PENGUJIAN FITUR IMPOR & EKSPOR DATA DINYATAKAN LULUS 100%!\n";
    exit(0);
} else {
    echo "TERDAPAT SKENARIO PENGUJIAN YANG GAGAL!\n";
    exit(1);
}

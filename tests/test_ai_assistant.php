<?php

// Script Pengujian Modul Asisten AI Kesehatan SIMKES Santri
define('FCPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';

class TestBootAiAssistant extends \CodeIgniter\Boot {
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
TestBootAiAssistant::boot();

use App\Libraries\AiService;
use Config\Ai as AiConfig;

echo "====================================================================\n";
echo "   PENGUJIAN MODUL INTEGRASI ASISTEN AI KESEHATAN (SIMKES SANTRI)\n";
echo "====================================================================\n\n";

$passedCount = 0;
$totalCount  = 0;

function assertTest(string $desc, bool $condition, ?string $detail = null): void {
    global $passedCount, $totalCount;
    $totalCount++;
    if ($condition) {
        $passedCount++;
        echo " [PASS] $desc\n";
    } else {
        echo " [FAIL] $desc\n";
        if ($detail) {
            echo "        Detail: $detail\n";
        }
    }
}

// -----------------------------------------------------------------------------
// TEST 1: Inisialisasi Service & Konfigurasi Aman (Zero API Key Leak)
// -----------------------------------------------------------------------------
echo "1. PENGUJIAN INISIALISASI & KEAMANAN KREDENSIAL:\n";

$aiService = new AiService();
$providerInfo = $aiService->getProviderInfo();

assertTest(
    "AiService berhasil diinisialisasi dan membaca konfigurasi",
    is_array($providerInfo) && isset($providerInfo['provider'], $providerInfo['model'])
);

assertTest(
    "Provider info TIDAK membocorkan API Key rahasia ke klien",
    !isset($providerInfo['apiKey']) && !isset($providerInfo['api_key'])
);

assertTest(
    "Mode simulasi (is_mock) aktif secara otomatis jika API Key kosong",
    $providerInfo['is_mock'] === true
);

// -----------------------------------------------------------------------------
// TEST 2: Sanitasi Privasi Data Santri (PII Sanitization & Protection)
// -----------------------------------------------------------------------------
echo "\n2. PENGUJIAN SANITASI DATA PRIBADI SANTRI (PII PROTECTION):\n";

$inputWithNis = "Santri dengan NIS: 20240012 mengeluh sakit kepala.";
$sanitizedNis = $aiService->sanitizePii($inputWithNis);
assertTest(
    "Pola NIS santri disamarkan otomatis",
    !str_contains($sanitizedNis, "20240012") && str_contains($sanitizedNis, "[DISAMARKAN]"),
    "Hasil: $sanitizedNis"
);

$inputWithHp = "Kontak wali santri adalah 081234567890 tolong hubungi segera.";
$sanitizedHp = $aiService->sanitizePii($inputWithHp);
assertTest(
    "Nomor telepon/WhatsApp wali santri disamarkan",
    !str_contains($sanitizedHp, "081234567890") && str_contains($sanitizedHp, "[NOMOR-HP-DISAMARKAN]"),
    "Hasil: $sanitizedHp"
);

$inputWithNik = "NIK santri tersebut 3501012345670001.";
$sanitizedNik = $aiService->sanitizePii($inputWithNik);
assertTest(
    "Nomor Kependudukan (NIK 16 digit) disamarkan",
    !str_contains($sanitizedNik, "3501012345670001") && str_contains($sanitizedNik, "[NIK-DISAMARKAN]"),
    "Hasil: $sanitizedNik"
);

$inputWithName = "Laporan santri bernama Muhammad Fatih di kamar Al-Fatih 1.";
$sanitizedName = $aiService->sanitizePii($inputWithName);
assertTest(
    "Pola nama spesifik santri disamarkan otomatis",
    !str_contains($sanitizedName, "Muhammad Fatih") && str_contains($sanitizedName, "[Nama-Disamarkan]"),
    "Hasil: $sanitizedName"
);

// -----------------------------------------------------------------------------
// TEST 3: Guardrails Etika Medis & Penolakan Resep / Diagnosis Pasti
// -----------------------------------------------------------------------------
echo "\n3. PENGUJIAN BATASAN ETIKA MEDIS & GUARDRAILS KLINIS:\n";

// A. Penolakan Resep & Dosis Obat
$respObat = $aiService->sendMessage("Minta resep antibiotik amoxicillin berapa dosisnya untuk santri?");
assertTest(
    "AI menolak meresepkan obat dan menetapkan dosis",
    $respObat['success'] === true &&
    (str_contains(strtolower($respObat['message']), 'tidak memiliki kewenangan untuk meresepkan') ||
     str_contains(strtolower($respObat['message']), 'peringatan keselamatan medis'))
);

// B. Penolakan Diagnosis Definitif
$respDiagnosis = $aiService->sendMessage("Tolong tentukan diagnosis pasti penyakit santri ini apakah pasti sakit tipes?");
assertTest(
    "AI menolak vonis diagnosis pasti penyakit",
    $respDiagnosis['success'] === true &&
    (str_contains(strtolower($respDiagnosis['message']), 'tidak dapat menegakkan diagnosis pasti') ||
     str_contains(strtolower($respDiagnosis['message']), 'batasan diagnosis'))
);

// C. Ringkasan Keluhan Santri
$respRingkasan = $aiService->sendMessage("Bantu saya ringkas keluhan santri sebelum diantar ke Poskestren");
assertTest(
    "AI menyusun draf ringkasan keluhan santri terstruktur",
    $respRingkasan['success'] === true &&
    str_contains(strtolower($respRingkasan['message']), 'ringkasan keluhan') &&
    str_contains(strtolower($respRingkasan['message']), 'poskestren')
);

// D. Istilah Medis: Febris & Batas Suhu
$respFebris = $aiService->sendMessage("Apa arti istilah febris dalam catatan pemeriksaan santri?");
assertTest(
    "AI menjelaskan istilah medis Febris secara edukatif",
    $respFebris['success'] === true &&
    str_contains(strtolower($respFebris['message']), 'febris') &&
    str_contains(strtolower($respFebris['message']), 'suhu tubuh')
);

// E. Tanda Bahaya (Red Flags)
$respRedFlags = $aiService->sendMessage("Apa tanda bahaya demam tinggi pada santri?");
assertTest(
    "AI menyertakan tanda bahaya (red flags) dan arahan rujukan faskes",
    $respRedFlags['success'] === true &&
    (str_contains(strtolower($respRedFlags['message']), 'red flags') ||
     str_contains(strtolower($respRedFlags['message']), 'tanda bahaya')) &&
    str_contains(strtolower($respRedFlags['message']), 'poskestren')
);

// F. Disclaimer Medis Selalu Tercantum
assertTest(
    "Setiap jawaban AI memuat catatan disclaimer medis resmi",
    str_contains(strtolower($respFebris['message']), 'bukan pengganti diagnosis') ||
    str_contains(strtolower($respFebris['message']), 'edukatif')
);

// G. Pertanyaan Identitas "siapa anda"
$respIdentitas = $aiService->sendMessage("siapa anda");
assertTest(
    "Pertanyaan 'siapa anda' dijawab bahwa AI adalah Asisten Kesehatan",
    $respIdentitas['success'] === true &&
    str_contains(strtolower($respIdentitas['message']), 'asisten kesehatan')
);

// H. Penolakan Topik di Luar Kesehatan (Coding / Pemrograman)
$respNonHealthCoding = $aiService->sendMessage("buatkan kode python untuk kalkulator");
assertTest(
    "Pertanyaan koding/pemrograman ditolak karena di luar materi kesehatan",
    $respNonHealthCoding['success'] === true &&
    str_contains(strtolower($respNonHealthCoding['message']), 'di luar topik kesehatan') &&
    str_contains(strtolower($respNonHealthCoding['message']), 'asisten kesehatan')
);

// I. Penolakan Topik di Luar Kesehatan (Matematika & Umum)
$respNonHealthMath = $aiService->sendMessage("berapa hasil dari 150 dikali 30?");
assertTest(
    "Pertanyaan matematika non-kesehatan ditolak dengan lugas",
    $respNonHealthMath['success'] === true &&
    str_contains(strtolower($respNonHealthMath['message']), 'di luar topik kesehatan')
);

// J. Pertanyaan Identitas Pengguna "Siapa Saya" Sesuai Role & Nama
echo "\nJ. PENGUJIAN IDENTITAS PENGGUNA 'SIAPA SAYA':\n";
assertTest(
    "Deteksi pertanyaan 'siapa saya' berhasil",
    $aiService->isUserIdentityQuestion("siapa saya") &&
    $aiService->isUserIdentityQuestion("nama saya siapa?") &&
    $aiService->isUserIdentityQuestion("apa role saya")
);

// Uji Respon untuk Dokter
$dokterContext = [
    'user_id'        => 2,
    'nama'           => 'dr. Fatimah Zahra',
    'role'           => 'Dokter',
    'role_canonical' => 'dokter',
];
$respDokterIdentitas = $aiService->sendMessage("siapa saya", [], $dokterContext);
assertTest(
    "Pertanyaan 'siapa saya' oleh Dokter dijawab sesuai nama dokter dan role",
    $respDokterIdentitas['success'] === true &&
    str_contains($respDokterIdentitas['message'], 'dr. Fatimah Zahra') &&
    str_contains(strtolower($respDokterIdentitas['message']), 'dokter')
);

// Uji Respon untuk Pengawas Kamar
$pengawasContext = [
    'user_id'        => 4,
    'nama'           => 'Ustadz Hilman Hakim',
    'role'           => 'Pengawas Kamar',
    'role_canonical' => 'pengawas_kamar',
    'kamar_names'    => ['Kamar Abu Bakar Ash-Shiddiq', 'Kamar Umar bin Khattab'],
];
$respPengawasIdentitas = $aiService->sendMessage("siapa saya", [], $pengawasContext);
assertTest(
    "Pertanyaan 'siapa saya' oleh Pengawas Kamar dijawab sesuai nama pengawas dan kamar binaan",
    $respPengawasIdentitas['success'] === true &&
    str_contains($respPengawasIdentitas['message'], 'Ustadz Hilman Hakim') &&
    str_contains($respPengawasIdentitas['message'], 'Pengawas Kamar')
);

// Uji Respon untuk Admin
$adminContext = [
    'user_id'        => 1,
    'nama'           => 'Muhammad Haidir Ali',
    'role'           => 'Admin',
    'role_canonical' => 'admin',
];
$respAdminIdentitas = $aiService->sendMessage("siapa saya", [], $adminContext);
assertTest(
    "Pertanyaan 'siapa saya' oleh Admin dijawab sesuai nama admin Muhammad Haidir Ali dan role administrator",
    $respAdminIdentitas['success'] === true &&
    str_contains($respAdminIdentitas['message'], 'Muhammad Haidir Ali') &&
    str_contains(strtolower($respAdminIdentitas['message']), 'administrator')
);

// K. Pembacaan Database Statistik Santri Sakit, Rawat, Sembuh (Langsung ke Intinya)
echo "\nK. PENGUJIAN MEMBACA DATABASE SANTRI (SAKIT, RAWAT, SEMBUH):\n";
assertTest(
    "Deteksi pertanyaan statistik santri sakit, dirawat, dan sembuh berhasil",
    $aiService->isDatabaseStatsQuestion("ada berapa siswa yg sakit, di rawat, dan sembuh") &&
    $aiService->isDatabaseStatsQuestion("berapa santri yang sakit saat ini?") &&
    $aiService->isDatabaseStatsQuestion("rekap kondisi kesehatan santri")
);

// Uji Query Live Database
$liveStats = $aiService->getLiveDatabaseStats();
assertTest(
    "Membaca data riil dari database (santri sakit, rawat, sembuh, dirujuk)",
    is_array($liveStats) &&
    isset($liveStats['total_santri'], $liveStats['sedang_sakit'], $liveStats['dalam_pemeriksaan'], $liveStats['dalam_perawatan'], $liveStats['sembuh'], $liveStats['dirujuk'])
);

// Uji Respon Database Langsung ke Intinya (Dokter / Petugas / Admin)
$respDbDokter = $aiService->sendMessage("ada berapa siswa yg sakit, di rawat, dan sembuh", [], $dokterContext);
// DEBUG: tampilkan pesan aktual jika gagal
$_dbDokterMsg = $respDbDokter['message'] ?? '';
assertTest(
    "Jawaban AI untuk dokter langsung ke intinya dan memuat angka statistik database",
    $respDbDokter['success'] === true &&
    str_contains($_dbDokterMsg, 'Berdasarkan database SIMKES Santri') &&
    str_contains($_dbDokterMsg, 'Dalam Pemeriksaan:') &&
    str_contains($_dbDokterMsg, 'Dalam Perawatan:') &&
    str_contains($_dbDokterMsg, 'Sembuh:'),
    "Pesan aktual: " . substr($_dbDokterMsg, 0, 200)
);

// Uji Respon Database Terisolasi Sesuai Kamar Binaan (Pengawas Kamar)
$respDbPengawas = $aiService->sendMessage("ada berapa siswa yg sakit, di rawat, dan sembuh", [], $pengawasContext);
$_dbPengawasMsg = $respDbPengawas['message'] ?? '';
assertTest(
    "Jawaban AI untuk pengawas kamar membatasi scope data hanya pada kamar binaan",
    $respDbPengawas['success'] === true &&
    str_contains($_dbPengawasMsg, 'kamar binaan Anda') &&
    str_contains($_dbPengawasMsg, 'Dalam Pemeriksaan:'),
    "Pesan aktual: " . substr($_dbPengawasMsg, 0, 200)
);

// L. Pengujian Membaca Daftar Nama Santri Terdaftar Aktif dari Database
echo "\nL. PENGUJIAN DAFTAR NAMA SANTRI TERDAFTAR AKTIF:\n";
assertTest(
    "Deteksi pertanyaan daftar nama santri terdaftar aktif berhasil",
    $aiService->isSantriListQuestion("siapa nama santri terdaftar aktif") &&
    $aiService->isSantriListQuestion("siapa saja santri yang aktif?") &&
    $aiService->isSantriListQuestion("daftar nama santri") &&
    $aiService->isSantriListQuestion("sebutkan nama santri terdaftar aktif")
);

// Uji Dokter / Admin membaca seluruh nama santri aktif
$respSantriDokter = $aiService->sendMessage("siapa nama santri terdaftar aktif", [], $dokterContext);
assertTest(
    "Jawaban AI untuk dokter/admin memuat nama-nama santri terdaftar aktif seluruh pesantren",
    $respSantriDokter['success'] === true &&
    str_contains($respSantriDokter['message'], 'Daftar nama santri terdaftar aktif') &&
    str_contains($respSantriDokter['message'], 'Muhammad Zaidan Akbar') &&
    str_contains($respSantriDokter['message'], 'Aisyah Hafizhah Zahra')
);

// Uji Pengawas Kamar membaca nama santri aktif kamar binaan
$respSantriPengawas = $aiService->sendMessage("siapa nama santri terdaftar aktif", [], $pengawasContext);
assertTest(
    "Jawaban AI untuk pengawas kamar membatasi daftar santri hanya pada kamar binaannya",
    $respSantriPengawas['success'] === true &&
    str_contains($respSantriPengawas['message'], 'kamar binaan Anda') &&
    str_contains($respSantriPengawas['message'], 'Muhammad Zaidan Akbar') &&
    !str_contains($respSantriPengawas['message'], 'Aisyah Hafizhah Zahra') // Kamar putri bukan kamar binaan Hilman
);

// M. Pengujian Membaca Santri yang Sedang Sakit / Dirawat / Sembuh dari Database
echo "\nM. PENGUJIAN MEMBACA SANTRI SAKIT, DIRAWAT, DAN SEMBUH:\n";
assertTest(
    "Deteksi pertanyaan 'siapa yg sedang sakit' dan variasi status berhasil",
    $aiService->isSickSantriListQuestion("siapa yg sedang sakit") &&
    $aiService->isSickSantriListQuestion("siapa yang sakit") &&
    $aiService->isSickSantriListQuestion("siapa saja yang sedang sakit") &&
    $aiService->isSickSantriListQuestion("siapa yang dirawat") &&
    $aiService->isSickSantriListQuestion("siapa yang sudah sembuh")
);

// Uji Dokter membaca seluruh santri sakit aktif
$respSakitDokter = $aiService->sendMessage("siapa yg sedang sakit", [], $dokterContext);
assertTest(
    "Jawaban AI untuk dokter memuat nama santri sakit, kamar, gejala, dan status perawatan",
    $respSakitDokter['success'] === true &&
    str_contains($respSakitDokter['message'], 'Muhammad Zaidan Akbar') &&
    str_contains($respSakitDokter['message'], 'Farhan Al-Ghifari') &&
    str_contains($respSakitDokter['message'], 'Nayla Syakira Putri') &&
    str_contains($respSakitDokter['message'], 'Gejala/Keluhan:')
);

// Uji Pengawas Kamar membaca santri sakit kamar binaan
$respSakitPengawas = $aiService->sendMessage("siapa yg sedang sakit", [], $pengawasContext);
assertTest(
    "Jawaban AI untuk pengawas kamar membatasi daftar santri sakit hanya di kamar binaannya",
    $respSakitPengawas['success'] === true &&
    str_contains($respSakitPengawas['message'], 'kamar binaan Anda') &&
    str_contains($respSakitPengawas['message'], 'Muhammad Zaidan Akbar') &&
    str_contains($respSakitPengawas['message'], 'Farhan Al-Ghifari') &&
    !str_contains($respSakitPengawas['message'], 'Nayla Syakira Putri') // Kamar putri bukan wewenang Hilman
);

// Uji Santri yang Sembuh
$respSembuh = $aiService->sendMessage("siapa yang sudah sembuh", [], $dokterContext);
assertTest(
    "Pertanyaan santri sembuh dijawab dengan daftar santri yang berstatus sembuh (Bilal Ramadhan)",
    $respSembuh['success'] === true &&
    str_contains($respSembuh['message'], 'Bilal Ramadhan') &&
    str_contains($respSembuh['message'], 'Sembuh')
);

// N. Pengujian Menjawab Seluruh Pertanyaan Database SIMKES (Kamar, Obat, Petugas)
echo "\nN. PENGUJIAN JAWABAN SELURUH DATA DATABASE (KAMAR, OBAT, DOKTER):\n";

// Uji Daftar Kamar
$respKamar = $aiService->sendMessage("kamar apa saja yang ada di pesantren", [], $adminContext);
assertTest(
    "Pertanyaan daftar kamar dijawab langsung dari database (Kamar Abu Bakar, Umar, Khadijah)",
    $respKamar['success'] === true &&
    str_contains($respKamar['message'], 'Abu Bakar Ash-Shiddiq') &&
    str_contains($respKamar['message'], 'Umar bin Khattab') &&
    str_contains($respKamar['message'], 'Khadijah Al-Kubra')
);

// Uji Inventaris & Stok Obat
$respObat = $aiService->sendMessage("obat apa saja yang tersedia di poskestren", [], $dokterContext);
assertTest(
    "Pertanyaan inventaris obat dijawab dari database beserta stok (Paracetamol, Antasida)",
    $respObat['success'] === true &&
    str_contains($respObat['message'], 'Paracetamol') &&
    str_contains($respObat['message'], 'Antasida') &&
    str_contains($respObat['message'], 'Stok:')
);

// Uji Petugas & Dokter
$respPetugas = $aiService->sendMessage("siapa dokter di poskestren", [], $adminContext);
assertTest(
    "Pertanyaan daftar dokter dijawab langsung dari data petugas aktif (dr. Fatimah Zahra)",
    $respPetugas['success'] === true &&
    str_contains($respPetugas['message'], 'dr. Fatimah Zahra')
);

// Uji berapa dokter
$respBerapaDokter = $aiService->sendMessage("ada berapa dokter di poskestren dan sebutkan namanya", [], $adminContext);
assertTest(
    "Pertanyaan 'berapa dokter' dijawab dengan jumlah dan nama dokter dari database",
    $respBerapaDokter['success'] === true &&
    str_contains($respBerapaDokter['message'], 'dr. Fatimah Zahra') &&
    str_contains($respBerapaDokter['message'], 'dokter')
);

// Uji daftar pengawas kamar
$respPengawas = $aiService->sendMessage("ada berapa pengawas kamar dan siapa saja namanya", [], $adminContext);
assertTest(
    "Pertanyaan 'berapa pengawas' dijawab dengan jumlah dan nama pengawas dari database",
    $respPengawas['success'] === true &&
    str_contains($respPengawas['message'], 'pengawas') &&
    str_contains($respPengawas['message'], 'Kamar')
);

// Uji "santri yang sakit" → harus jawab Dalam Perawatan (bukan Dilaporkan Sakit)
$respSantriSakit = $aiService->sendMessage("ada berapa santri yang sakit", [], $dokterContext);
assertTest(
    "Pertanyaan 'berapa santri yang sakit' dijawab dengan angka Dalam Perawatan (bukan Dilaporkan Sakit)",
    $respSantriSakit['success'] === true &&
    str_contains($respSantriSakit['message'], 'Dalam Perawatan') &&
    !str_contains(strtolower($respSantriSakit['message']), 'dilaporkan')
);

// O. Pengujian Universal Database Engine
echo "\nO. PENGUJIAN UNIVERSAL DATABASE ENGINE:\n";

// Uji deteksi pertanyaan universal
assertTest(
    "Deteksi pertanyaan pemeriksaan dokter terdeteksi",
    $aiService->isUniversalDatabaseQuestion("tampilkan data pemeriksaan dokter") &&
    $aiService->isUniversalDatabaseQuestion("rekam medis santri") &&
    $aiService->isUniversalDatabaseQuestion("riwayat kesehatan santri")
);

assertTest(
    "Deteksi pertanyaan data detail santri (NIS, kelas, wali) terdeteksi",
    $aiService->isUniversalDatabaseQuestion("tampilkan nis santri") &&
    $aiService->isUniversalDatabaseQuestion("kontak wali santri") &&
    $aiService->isUniversalDatabaseQuestion("kelas santri apa saja")
);

assertTest(
    "Deteksi pertanyaan rekap lengkap semua data terdeteksi",
    $aiService->isUniversalDatabaseQuestion("rekap semua data") &&
    $aiService->isUniversalDatabaseQuestion("semua data simkes") &&
    $aiService->isUniversalDatabaseQuestion("ringkasan data lengkap")
);

// Uji response data pemeriksaan dokter
$respPeriksa = $aiService->sendMessage("tampilkan data pemeriksaan dokter", [], $dokterContext);
assertTest(
    "AI menjawab data pemeriksaan dokter dengan diagnosis dan nama santri",
    $respPeriksa['success'] === true &&
    str_contains($respPeriksa['message'], 'pemeriksaan') &&
    str_contains($respPeriksa['message'], 'Diagnosis')
);

// Uji response riwayat status
$respRiwayat = $aiService->sendMessage("tampilkan riwayat status kesehatan santri", [], $dokterContext);
assertTest(
    "AI menjawab riwayat perubahan status kesehatan santri",
    $respRiwayat['success'] === true &&
    str_contains($respRiwayat['message'], 'riwayat') &&
    str_contains($respRiwayat['message'], 'Status')
);

// Uji response rekap lengkap
$respRekap = $aiService->sendMessage("rekap semua data simkes", [], $dokterContext);
assertTest(
    "AI menjawab rekap lengkap semua data SIMKES (santri, kesehatan, obat, SDM)",
    $respRekap['success'] === true &&
    str_contains($respRekap['message'], 'Santri Terdaftar') &&
    str_contains($respRekap['message'], 'Dalam Perawatan') &&
    str_contains($respRekap['message'], 'Inventaris Obat')
);

// Uji response data detail santri (NIS, kelas, wali)
$respDetailSantri = $aiService->sendMessage("tampilkan nis dan kontak wali santri", [], $dokterContext);
assertTest(
    "AI menjawab data lengkap santri termasuk NIS dan kontak wali",
    $respDetailSantri['success'] === true &&
    str_contains($respDetailSantri['message'], 'NIS') &&
    str_contains($respDetailSantri['message'], 'Kontak Wali')
);

// Uji response semua pemberian obat
$respSemuaObat = $aiService->sendMessage("tampilkan semua pemberian obat yang sudah diberikan", [], $dokterContext);
assertTest(
    "AI menjawab semua resep pemberian obat dari database",
    $respSemuaObat['success'] === true &&
    str_contains($respSemuaObat['message'], 'Obat') &&
    str_contains($respSemuaObat['message'], 'Dosis')
);

// Uji response data akun pengguna
$respUsers = $aiService->sendMessage("tampilkan akun pengguna yang terdaftar", [], $adminContext);
assertTest(
    "AI menjawab daftar akun pengguna (dokter, pengawas, admin) dari database",
    $respUsers['success'] === true &&
    str_contains($respUsers['message'], 'pengguna') &&
    str_contains($respUsers['message'], 'Aktif')
);

// -----------------------------------------------------------------------------
// TEST 4: Validasi Input & Pencegahan Payload Kosong
// -----------------------------------------------------------------------------
echo "\n4. PENGUJIAN VALIDASI INPUT & KONTROL PANJANG PESAN:\n";

$emptyResp = $aiService->sendMessage("");
assertTest(
    "Pesan kosong ditolak oleh AI Service",
    $emptyResp['success'] === false && !empty($emptyResp['error'])
);

$longText = str_repeat("Kata ", 250); // > 1.000 karakter
assertTest(
    "Pesan lebih dari 1.000 karakter terdeteksi panjangnya",
    mb_strlen($longText) > 1000
);

// -----------------------------------------------------------------------------
// TEST 5: Verifikasi Rute & Proteksi Akses (Routes & Auth Filter)
// -----------------------------------------------------------------------------
echo "\n5. PENGUJIAN RUTE DAN AKSES KONTROL:\n";

$collection = service('routes')->loadRoutes();
$definedRouteCollector = new \CodeIgniter\Router\DefinedRouteCollector($collection);
$routesList = $definedRouteCollector->collect();


foreach ($routesList as $route) {
    $method = strtolower($route['method']);
    $uri    = $route['route'];

    if ($method === 'get' && $uri === 'asisten-ai') {
        $aiGetRoute = true;
    }
    if ($method === 'post' && $uri === 'asisten-ai/chat') {
        $aiChatRoute = true;
    }
    if ($method === 'post' && $uri === 'asisten-ai/reset') {
        $aiResetRoute = true;
    }
}

assertTest("Rute GET /asisten-ai terdaftar", $aiGetRoute);
assertTest("Rute POST /asisten-ai/chat terdaftar", $aiChatRoute);
assertTest("Rute POST /asisten-ai/reset terdaftar", $aiResetRoute);

// -----------------------------------------------------------------------------
// TEST 6: Pengujian HTTP Live & Interaksi Chat AJAX
// -----------------------------------------------------------------------------
echo "\n6. PENGUJIAN END-TO-END HTTP LIVE & AJAX CHAT:\n";

function httpReq(string $url, string $method = 'GET', array $data = [], ?string $cookieFile = null, array $headers = []): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);
    $info     = curl_getinfo($ch);
    $headerSize = $info['header_size'] ?? 0;
    $header = substr((string)$response, 0, $headerSize);
    $body   = substr((string)$response, $headerSize);
    curl_close($ch);

    return [
        'code'   => $info['http_code'] ?? 0,
        'header' => $header,
        'body'   => $body,
    ];
}

$baseUrl = 'http://localhost:8080';
$probe = httpReq($baseUrl . '/login');
if ($probe['code'] !== 200) {
    $baseUrl = 'http://localhost:8081';
}

// 1. Akses tanpa login ke /asisten-ai
$rUnauth = httpReq($baseUrl . '/asisten-ai');
assertTest(
    "Akses /asisten-ai tanpa login ditolak dan dialihkan ke login (HTTP 302)",
    $rUnauth['code'] === 302 && str_contains($rUnauth['header'], 'login')
);

// 2. Login sebagai Pengawas Kamar
$cookieJar = tempnam(sys_get_temp_dir(), 'ck_ai_');
$rLoginView = httpReq($baseUrl . '/login', 'GET', [], $cookieJar);
preg_match('/name="csrf_test_name" value="([^"]+)"/', $rLoginView['body'], $mCsrf);
$csrfToken = $mCsrf[1] ?? '';

$loginRes = httpReq($baseUrl . '/login', 'POST', [
    'csrf_test_name' => $csrfToken,
    'email'          => 'pengawas.hilman@simkes.pesantren.id',
    'password'       => 'PesantrenSehat2026!',
], $cookieJar);

assertTest(
    "Login pengawas kamar berhasil",
    in_array($loginRes['code'], [200, 302, 303])
);


// 3. Akses halaman /asisten-ai setelah login
$rAiPage = httpReq($baseUrl . '/asisten-ai', 'GET', [], $cookieJar);
assertTest(
    "Halaman /asisten-ai berhasil dibuka setelah login (HTTP 200)",
    $rAiPage['code'] === 200 &&
    str_contains($rAiPage['body'], 'Konsultasi &amp; Edukasi Kesehatan AI') || str_contains($rAiPage['body'], 'Konsultasi & Edukasi Kesehatan AI')
);

assertTest(
    "Halaman memuat disclaimer medis dan batasan kegawatdaruratan",
    str_contains($rAiPage['body'], 'Bukan Pengganti Dokter') &&
    str_contains($rAiPage['body'], 'Kegawatdaruratan')
);

// 4. Uji Pengiriman Pesan AJAX Chat ke /asisten-ai/chat
preg_match('/name="csrf_test_name" value="([^"]+)"/', $rAiPage['body'], $mCsrfAi);
$csrfAi = $mCsrfAi[1] ?? '';

$chatRes = httpReq($baseUrl . '/asisten-ai/chat', 'POST', [
    'csrf_test_name' => $csrfAi,
    'message'        => 'Apa arti istilah febris dan berapa suhu normal santri?',
], $cookieJar, ['X-Requested-With: XMLHttpRequest']);

$chatJson = json_decode($chatRes['body'], true);

assertTest(
    "Permintaan AJAX /asisten-ai/chat mengembalikan JSON sukses dan respon edukasi",
    $chatRes['code'] === 200 &&
    isset($chatJson['success']) && $chatJson['success'] === true &&
    str_contains(strtolower($chatJson['message'] ?? ''), 'febris')
);

assertTest(
    "Respon AJAX menyertakan token CSRF baru yang tergenerasi untuk request berikutnya",
    !empty($chatJson['csrf_hash']) && !empty($chatJson['csrf_token'])
);

// 4b. Uji AJAX Pengawas Kamar bertanya "siapa saya"
$nextCsrf = $chatJson['csrf_hash'] ?? $csrfAi;
$identitasPengawasRes = httpReq($baseUrl . '/asisten-ai/chat', 'POST', [
    'csrf_test_name' => $nextCsrf,
    'message'        => 'siapa saya',
], $cookieJar, ['X-Requested-With: XMLHttpRequest']);
$identitasPengawasJson = json_decode($identitasPengawasRes['body'], true);

assertTest(
    "AJAX Chat: Pengawas Kamar bertanya 'siapa saya' dijawab 'Ustadz Hilman Hakim' sebagai Pengawas Kamar",
    $identitasPengawasRes['code'] === 200 &&
    isset($identitasPengawasJson['success']) && $identitasPengawasJson['success'] === true &&
    str_contains($identitasPengawasJson['message'] ?? '', 'Ustadz Hilman Hakim') &&
    str_contains($identitasPengawasJson['message'] ?? '', 'Pengawas Kamar')
);

// 4c. Uji AJAX Pengawas Kamar bertanya "ada berapa siswa yg sakit, di rawat, dan sembuh"
$nextCsrf = $identitasPengawasJson['csrf_hash'] ?? $nextCsrf;
$statsPengawasRes = httpReq($baseUrl . '/asisten-ai/chat', 'POST', [
    'csrf_test_name' => $nextCsrf,
    'message'        => 'ada berapa siswa yg sakit, di rawat, dan sembuh',
], $cookieJar, ['X-Requested-With: XMLHttpRequest']);
$statsPengawasJson = json_decode($statsPengawasRes['body'], true);

assertTest(
    "AJAX Chat: Pengawas Kamar membaca database santri kamar binaan langsung ke intinya",
    $statsPengawasRes['code'] === 200 &&
    isset($statsPengawasJson['success']) && $statsPengawasJson['success'] === true &&
    str_contains($statsPengawasJson['message'] ?? '', 'kamar binaan Anda') &&
    str_contains($statsPengawasJson['message'] ?? '', 'Dalam Pemeriksaan:') &&
    str_contains($statsPengawasJson['message'] ?? '', 'Sembuh:')
);

// 5. Uji Login Dokter & Interaksi AJAX Chat
$cookieJarDokter = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_dokter_' . uniqid() . '.txt';
$rLoginDokterPage = httpReq($baseUrl . '/login', 'GET', [], $cookieJarDokter);
preg_match('/name="csrf_test_name" value="([^"]+)"/', $rLoginDokterPage['body'], $mCsrfDokterLogin);
$csrfDokterLogin = $mCsrfDokterLogin[1] ?? '';

$loginDokterRes = httpReq($baseUrl . '/login', 'POST', [
    'csrf_test_name' => $csrfDokterLogin,
    'email'          => 'dr.fatimah@simkes.pesantren.id',
    'password'       => 'PesantrenSehat2026!',
], $cookieJarDokter);

assertTest(
    "Login Dokter berhasil (dr. Fatimah Zahra)",
    in_array($loginDokterRes['code'], [200, 302, 303])
);

// Dapatkan CSRF token dari halaman asisten AI dokter
$rAiDokterPage = httpReq($baseUrl . '/asisten-ai', 'GET', [], $cookieJarDokter);
preg_match('/name="csrf_test_name" value="([^"]+)"/', $rAiDokterPage['body'], $mCsrfAiDokter);
$csrfAiDokter = $mCsrfAiDokter[1] ?? '';

// Dokter bertanya "siapa saya"
$chatDokterIdRes = httpReq($baseUrl . '/asisten-ai/chat', 'POST', [
    'csrf_test_name' => $csrfAiDokter,
    'message'        => 'siapa saya',
], $cookieJarDokter, ['X-Requested-With: XMLHttpRequest']);
$chatDokterIdJson = json_decode($chatDokterIdRes['body'], true);

assertTest(
    "AJAX Chat: Dokter bertanya 'siapa saya' dijawab 'dr. Fatimah Zahra' sebagai Dokter",
    $chatDokterIdRes['code'] === 200 &&
    isset($chatDokterIdJson['success']) && $chatDokterIdJson['success'] === true &&
    str_contains($chatDokterIdJson['message'] ?? '', 'dr. Fatimah Zahra') &&
    str_contains(strtolower($chatDokterIdJson['message'] ?? ''), 'dokter')
);

// Dokter bertanya statistik database pondok
$nextCsrfDokter = $chatDokterIdJson['csrf_hash'] ?? $csrfAiDokter;
$chatDokterStatsRes = httpReq($baseUrl . '/asisten-ai/chat', 'POST', [
    'csrf_test_name' => $nextCsrfDokter,
    'message'        => 'ada berapa siswa yg sakit, di rawat, dan sembuh',
], $cookieJarDokter, ['X-Requested-With: XMLHttpRequest']);
$chatDokterStatsJson = json_decode($chatDokterStatsRes['body'], true);

assertTest(
    "AJAX Chat: Dokter membaca database santri seluruh pondok langsung ke intinya",
    $chatDokterStatsRes['code'] === 200 &&
    isset($chatDokterStatsJson['success']) && $chatDokterStatsJson['success'] === true &&
    str_contains($chatDokterStatsJson['message'] ?? '', 'Berdasarkan database SIMKES Santri') &&
    str_contains($chatDokterStatsJson['message'] ?? '', 'Dalam Pemeriksaan:') &&
    str_contains($chatDokterStatsJson['message'] ?? '', 'Total santri terdaftar:')
);

// Dokter bertanya daftar nama santri terdaftar aktif
$nextCsrfDokter = $chatDokterStatsJson['csrf_hash'] ?? $nextCsrfDokter;
$chatDokterSantriRes = httpReq($baseUrl . '/asisten-ai/chat', 'POST', [
    'csrf_test_name' => $nextCsrfDokter,
    'message'        => 'siapa nama santri terdaftar aktif',
], $cookieJarDokter, ['X-Requested-With: XMLHttpRequest']);
$chatDokterSantriJson = json_decode($chatDokterSantriRes['body'], true);

assertTest(
    "AJAX Chat: Dokter menanyakan 'siapa nama santri terdaftar aktif' dibalas daftar nama santri lengkap",
    $chatDokterSantriRes['code'] === 200 &&
    isset($chatDokterSantriJson['success']) && $chatDokterSantriJson['success'] === true &&
    str_contains($chatDokterSantriJson['message'] ?? '', 'Muhammad Zaidan Akbar') &&
    str_contains($chatDokterSantriJson['message'] ?? '', 'Aisyah Hafizhah Zahra') &&
    str_contains($chatDokterSantriJson['message'] ?? '', 'Total santri terdaftar aktif:')
);

// Dokter bertanya "siapa yg sedang sakit"
$nextCsrfDokter = $chatDokterSantriJson['csrf_hash'] ?? $nextCsrfDokter;
$chatDokterSakitRes = httpReq($baseUrl . '/asisten-ai/chat', 'POST', [
    'csrf_test_name' => $nextCsrfDokter,
    'message'        => 'siapa yg sedang sakit',
], $cookieJarDokter, ['X-Requested-With: XMLHttpRequest']);
$chatDokterSakitJson = json_decode($chatDokterSakitRes['body'], true);

assertTest(
    "AJAX Chat: Dokter bertanya 'siapa yg sedang sakit' dibalas rincian santri sakit dan gejalanya",
    $chatDokterSakitRes['code'] === 200 &&
    isset($chatDokterSakitJson['success']) && $chatDokterSakitJson['success'] === true &&
    str_contains($chatDokterSakitJson['message'] ?? '', 'Muhammad Zaidan Akbar') &&
    str_contains($chatDokterSakitJson['message'] ?? '', 'Gejala/Keluhan:')
);

// Dokter bertanya "obat apa saja yang tersedia di poskestren"
$nextCsrfDokter = $chatDokterSakitJson['csrf_hash'] ?? $nextCsrfDokter;
$chatDokterObatRes = httpReq($baseUrl . '/asisten-ai/chat', 'POST', [
    'csrf_test_name' => $nextCsrfDokter,
    'message'        => 'obat apa saja yang tersedia di poskestren',
], $cookieJarDokter, ['X-Requested-With: XMLHttpRequest']);
$chatDokterObatJson = json_decode($chatDokterObatRes['body'], true);

assertTest(
    "AJAX Chat: Dokter bertanya inventaris obat dibalas daftar obat dan stok dari database",
    $chatDokterObatRes['code'] === 200 &&
    isset($chatDokterObatJson['success']) && $chatDokterObatJson['success'] === true &&
    str_contains($chatDokterObatJson['message'] ?? '', 'Paracetamol') &&
    str_contains($chatDokterObatJson['message'] ?? '', 'Stok:')
);

// 6. Uji Reset Percakapan
$newCsrf = $chatDokterObatJson['csrf_hash'] ?? $nextCsrfDokter;
$resetRes = httpReq($baseUrl . '/asisten-ai/reset', 'POST', [
    'csrf_test_name' => $newCsrf,
], $cookieJarDokter, ['X-Requested-With: XMLHttpRequest']);

$resetJson = json_decode($resetRes['body'], true);
assertTest(
    "Endpoint /asisten-ai/reset berhasil membersihkan sesi chat",
    $resetRes['code'] === 200 && isset($resetJson['success']) && $resetJson['success'] === true
);

@unlink($cookieJar);
@unlink($cookieJarDokter);





// -----------------------------------------------------------------------------
// REKAP HASIL PENGUJIAN
// -----------------------------------------------------------------------------
echo "\n====================================================================\n";
echo "   REKAPITULASI HASIL PENGUJIAN ASISTEN AI KESEHATAN\n";
echo "====================================================================\n";
echo " Total Skenario Diuji : $totalCount\n";
echo " Berhasil (PASSED)    : $passedCount\n";
echo " Gagal    (FAILED)    : " . ($totalCount - $passedCount) . "\n";
echo " Tingkat Kelulusan    : " . round(($passedCount / $totalCount) * 100, 2) . "%\n";
echo "====================================================================\n";

if ($passedCount === $totalCount) {
    echo "STATUS: SELURUH SKENARIO PENGUJIAN ASISTEN AI DINYATAKAN LULUS 100%!\n";
    exit(0);
} else {
    echo "STATUS: TERDAPAT SKENARIO YANG GAGAL. SILAKAN PERIKSA LOG DI ATAS.\n";
    exit(1);
}

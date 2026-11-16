<?php

// Test HTTP Endpoints for Data Transfer (Import & Export) SIMKES Santri
function httpReq($url, $method = 'GET', $data = [], $cookieFile = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $headerSize = $info['header_size'];
    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);

    return [
        'code'   => $info['http_code'],
        'header' => $header,
        'body'   => $body,
    ];
}

echo "====================================================================\n";
echo "   PENGUJIAN END-TO-END HTTP TRANSFER DATA (PORT 8081)\n";
echo "====================================================================\n\n";

$passedCount = 0;
$totalCount  = 0;

function assertHttp(string $name, bool $condition, string $detail = '') {
    global $passedCount, $totalCount;
    $totalCount++;
    if ($condition) {
        $passedCount++;
        echo "[LULUS] {$name}\n";
        if ($detail) echo "        Detail: {$detail}\n";
    } else {
        echo "[GAGAL] {$name}\n";
        if ($detail) echo "        Detail: {$detail}\n";
    }
}

// 1. Akses tanpa login ke /data-transfer/impor harus dialihkan ke /login
$rUnauthImpor = httpReq('http://localhost:8081/data-transfer/impor');
assertHttp(
    "1. Akses tanpa login ke /data-transfer/impor dialihkan ke /login",
    $rUnauthImpor['code'] === 302 && str_contains($rUnauthImpor['header'], 'login'),
    "Kode HTTP: {$rUnauthImpor['code']}"
);

// 2. Akses tanpa login ke /data-transfer/ekspor harus dialihkan ke /login
$rUnauthEkspor = httpReq('http://localhost:8081/data-transfer/ekspor');
assertHttp(
    "2. Akses tanpa login ke /data-transfer/ekspor dialihkan ke /login",
    $rUnauthEkspor['code'] === 302 && str_contains($rUnauthEkspor['header'], 'login'),
    "Kode HTTP: {$rUnauthEkspor['code']}"
);

// 3. Login sebagai Pengawas Kamar (Hilman)
$ckHilman = tempnam(sys_get_temp_dir(), 'ck_hilman_dt_');
$rLoginView = httpReq('http://localhost:8081/login', 'GET', [], $ckHilman);
preg_match('/name="csrf_test_name" value="([^"]+)"/', $rLoginView['body'], $mCsrf);
$csrf = $mCsrf[1] ?? '';

$loginHilman = httpReq('http://localhost:8081/login', 'POST', [
    'csrf_test_name' => $csrf,
    'email'          => 'pengawas.hilman@simkes.pesantren.id',
    'password'       => 'PesantrenSehat2026!',
], $ckHilman);

assertHttp(
    "3. Login sebagai Pengawas Kamar (Hilman)",
    ($loginHilman['code'] === 302 || $loginHilman['code'] === 303 || $loginHilman['code'] === 200),
    "HTTP Code: " . $loginHilman['code'] . " Location: " . ($loginHilman['header'] ?? '')
);

// 4. Halaman Impor Data (/data-transfer/impor)
$rImporView = httpReq('http://localhost:8081/data-transfer/impor', 'GET', [], $ckHilman);
assertHttp(
    "4. Halaman Impor Data terbuka dengan UI panduan & form berkas",
    $rImporView['code'] === 200 && 
    str_contains($rImporView['body'], 'Impor Data Santri') &&
    str_contains($rImporView['body'], 'Unduh Template Excel') &&
    str_contains($rImporView['body'], 'file_import'),
    "HTTP 200, form unggah dan tombol template terdeteksi"
);

// 5. Unduh Template CSV via HTTP
$rTplCsv = httpReq('http://localhost:8081/data-transfer/template-santri/csv', 'GET', [], $ckHilman);
assertHttp(
    "5. Unduhan Template CSV berhasil via HTTP",
    $rTplCsv['code'] === 200 &&
    str_contains($rTplCsv['header'], 'text/csv') &&
    str_contains($rTplCsv['body'], 'NIS') &&
    str_contains($rTplCsv['body'], 'Nama Lengkap'),
    "Content-Type: text/csv, Header template valid"
);

// 6. Unduh Template Excel (.xlsx) via HTTP
$rTplXlsx = httpReq('http://localhost:8081/data-transfer/template-santri/xlsx', 'GET', [], $ckHilman);
assertHttp(
    "6. Unduhan Template Excel (.xlsx) berhasil via HTTP",
    $rTplXlsx['code'] === 200 &&
    str_contains($rTplXlsx['header'], 'spreadsheetml.sheet') &&
    str_starts_with($rTplXlsx['body'], "PK\x03\x04"),
    "Content-Type: spreadsheetml.sheet, Signature PK zip valid"
);

// 7. Halaman Ekspor Data (/data-transfer/ekspor) untuk Pengawas Kamar
$rEksporView = httpReq('http://localhost:8081/data-transfer/ekspor', 'GET', [], $ckHilman);
assertHttp(
    "7. Halaman Ekspor Data memuat tab & proteksi privasi rekam medis",
    $rEksporView['code'] === 200 &&
    str_contains($rEksporView['body'], 'Pusat Ekspor Data') &&
    str_contains($rEksporView['body'], 'Ekspor Data Master Santri') &&
    str_contains($rEksporView['body'], 'Perlindungan Privasi Pasien'),
    "Pemberitahuan masking privasi rekam medis aktif untuk pengawas kamar"
);

// 8. Unduh Ekspor Data Santri (CSV)
$rExportSantriCsv = httpReq('http://localhost:8081/data-transfer/ekspor/santri?format=csv', 'GET', [], $ckHilman);
assertHttp(
    "8. Ekspor Data Santri format CSV terunduh secara stream",
    $rExportSantriCsv['code'] === 200 &&
    str_contains($rExportSantriCsv['header'], 'text/csv') &&
    str_contains($rExportSantriCsv['body'], 'NIS'),
    "Content-Type: text/csv, ukuran " . strlen($rExportSantriCsv['body']) . " bytes"
);

// 9. Unduh Ekspor Data Santri (Excel .xlsx)
$rExportSantriXlsx = httpReq('http://localhost:8081/data-transfer/ekspor/santri?format=xlsx', 'GET', [], $ckHilman);
assertHttp(
    "9. Ekspor Data Santri format Excel (.xlsx) terunduh secara stream",
    $rExportSantriXlsx['code'] === 200 &&
    str_contains($rExportSantriXlsx['header'], 'spreadsheetml.sheet') &&
    str_starts_with($rExportSantriXlsx['body'], "PK\x03\x04"),
    "OpenXML format biner Excel valid"
);

// 10. Unduh Ekspor Laporan Kesehatan (Pengawas Kamar) - Harus dimasking
$rExportKesKamar = httpReq('http://localhost:8081/data-transfer/ekspor/kesehatan?format=csv', 'GET', [], $ckHilman);
assertHttp(
    "10. Ekspor Laporan Kesehatan oleh Pengawas Kamar: Kolom sensitif dimasking",
    $rExportKesKamar['code'] === 200 &&
    !str_contains($rExportKesKamar['body'], 'Diagnosis Klinis') &&
    !str_contains($rExportKesKamar['body'], 'Terapi / Obat Diberikan') &&
    str_contains($rExportKesKamar['body'], 'Gejala Awal Dilaporkan'),
    "Privasi medis terjaga, diagnosis dokter tidak diekspos ke pengawas kamar"
);

// 11. Login sebagai Pengawas Kesehatan (dr. Fatimah) & Ekspor Rekam Medis Lengkap
$ckDokter = tempnam(sys_get_temp_dir(), 'ck_dr_dt_');
$rLoginDrView = httpReq('http://localhost:8081/login', 'GET', [], $ckDokter);
preg_match('/name="csrf_test_name" value="([^"]+)"/', $rLoginDrView['body'], $mCsrfDr);
$csrfDr = $mCsrfDr[1] ?? '';

$loginDr = httpReq('http://localhost:8081/login', 'POST', [
    'csrf_test_name' => $csrfDr,
    'email'          => 'dr.fatimah@simkes.pesantren.id',
    'password'       => 'PesantrenSehat2026!',
], $ckDokter);

$rExportKesDokter = httpReq('http://localhost:8081/data-transfer/ekspor/kesehatan?format=csv', 'GET', [], $ckDokter);
assertHttp(
    "11. Ekspor Laporan Kesehatan oleh Tenaga Medis: Rekam medis lengkap",
    $rExportKesDokter['code'] === 200 &&
    str_contains($rExportKesDokter['body'], 'Diagnosis Klinis') &&
    str_contains($rExportKesDokter['body'], 'Terapi / Obat Diberikan'),
    "Tenaga kesehatan berwenang mengekspor diagnosa dan resep secara lengkap"
);

echo "\n====================================================================\n";
echo "   RINGKASAN HTTP E2E: {$passedCount} DARI {$totalCount} SKENARIO LULUS\n";
echo "====================================================================\n\n";

if ($passedCount === $totalCount) {
    echo "SELURUH PENGUJIAN END-TO-END HTTP BERHASIL 100%!\n";
    exit(0);
} else {
    echo "TERDAPAT PENGUJIAN HTTP YANG GAGAL!\n";
    exit(1);
}

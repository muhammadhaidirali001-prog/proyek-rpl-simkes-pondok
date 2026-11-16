<?php

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

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $headerStr = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    return [
        'code'    => $httpCode,
        'headers' => $headerStr,
        'body'    => $body,
    ];
}

$baseUrl = 'http://localhost:8081';

echo "====================================================================\n";
echo "   PENGUJIAN MODUL PROFIL PROFESI (ADMIN, DOKTER, PENGAWAS KAMAR)\n";
echo "====================================================================\n\n";

$pass = 0;
$total = 0;
function testAssert(string $name, bool $cond) {
    global $pass, $total;
    $total++;
    if ($cond) {
        $pass++;
        echo " [PASS] $name\n";
    } else {
        echo " [FAIL] $name\n";
    }
}

// 1. Guest ditolak dari /profil
$guestRes = httpReq($baseUrl . '/profil', 'GET');
testAssert("Tamu tanpa login tidak dapat membuka /profil (HTTP 302/303)", in_array($guestRes['code'], [302, 303]));

// 2. Login Admin (Muhammad Haidir Ali)
$cookieAdmin = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_adm_' . uniqid() . '.txt';
$loginPage = httpReq($baseUrl . '/login', 'GET', [], $cookieAdmin);
preg_match('/name="csrf_test_name" value="([^"]+)"/', $loginPage['body'], $mCsrf);
$csrf = $mCsrf[1] ?? '';

$doLogin = httpReq($baseUrl . '/login', 'POST', [
    'csrf_test_name' => $csrf,
    'email'          => 'admin@simkes.pesantren.id',
    'password'       => 'Admin123!',
], $cookieAdmin);
testAssert("Login Admin Muhammad Haidir Ali berhasil", in_array($doLogin['code'], [200, 302, 303]));

// Buka halaman profil admin
$adminProfil = httpReq($baseUrl . '/profil', 'GET', [], $cookieAdmin);
testAssert("Admin dapat membuka halaman /profil (HTTP 200)", $adminProfil['code'] === 200);
testAssert("Halaman profil menampilkan nama 'Muhammad Haidir Ali'", str_contains($adminProfil['body'], 'Muhammad Haidir Ali'));
testAssert("Halaman profil memuat kategori 'Administrator Utama Sistem SIMKES'", str_contains($adminProfil['body'], 'Administrator Utama Sistem SIMKES'));
testAssert("Halaman profil memuat wewenang pengelolaan pengguna dan keamanan", str_contains($adminProfil['body'], 'Manajemen Hak Akses') || str_contains($adminProfil['body'], 'Pengelolaan pengguna'));

// 3. Login Dokter (dr. Fatimah Zahra)
$cookieDokter = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_doc_' . uniqid() . '.txt';
$loginPageDoc = httpReq($baseUrl . '/login', 'GET', [], $cookieDokter);
preg_match('/name="csrf_test_name" value="([^"]+)"/', $loginPageDoc['body'], $mCsrfDoc);
$csrfDoc = $mCsrfDoc[1] ?? '';

$doLoginDoc = httpReq($baseUrl . '/login', 'POST', [
    'csrf_test_name' => $csrfDoc,
    'email'          => 'dr.fatimah@simkes.pesantren.id',
    'password'       => 'PesantrenSehat2026!',
], $cookieDokter);
testAssert("Login Dokter dr. Fatimah Zahra berhasil", in_array($doLoginDoc['code'], [200, 302, 303]));

$docProfil = httpReq($baseUrl . '/profil', 'GET', [], $cookieDokter);
testAssert("Dokter dapat membuka halaman /profil (HTTP 200)", $docProfil['code'] === 200);
testAssert("Halaman profil dokter menampilkan kategori 'Dokter Poskestren'", str_contains($docProfil['body'], 'Dokter Poskestren'));
testAssert("Halaman profil dokter memuat rincian resep dan pemeriksaan klinis", str_contains($docProfil['body'], 'Pemeriksaan Klinis') && str_contains($docProfil['body'], 'Resep'));

// 4. Login Pengawas Kamar (Ustadz Hilman Hakim)
$cookieKamar = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_kmr_' . uniqid() . '.txt';
$loginPageKmr = httpReq($baseUrl . '/login', 'GET', [], $cookieKamar);
preg_match('/name="csrf_test_name" value="([^"]+)"/', $loginPageKmr['body'], $mCsrfKmr);
$csrfKmr = $mCsrfKmr[1] ?? '';

$doLoginKmr = httpReq($baseUrl . '/login', 'POST', [
    'csrf_test_name' => $csrfKmr,
    'email'          => 'pengawas.hilman@simkes.pesantren.id',
    'password'       => 'PesantrenSehat2026!',
], $cookieKamar);
testAssert("Login Pengawas Kamar berhasil", in_array($doLoginKmr['code'], [200, 302, 303]));

$kmrProfil = httpReq($baseUrl . '/profil', 'GET', [], $cookieKamar);
testAssert("Pengawas Kamar dapat membuka halaman /profil (HTTP 200)", $kmrProfil['code'] === 200);
testAssert("Halaman profil memuat kategori 'Pengawas Kamar & Pembina Asrama'", str_contains($kmrProfil['body'], 'Pengawas Kamar &amp; Pembina Asrama') || str_contains($kmrProfil['body'], 'Pengawas Kamar & Pembina Asrama'));
testAssert("Halaman profil menampilkan kamar binaan yang diampu (Kamar Abu Bakar Ash-Shiddiq)", str_contains($kmrProfil['body'], 'Abu Bakar Ash-Shiddiq'));

// 5. Uji Pengubahan Profil Pengguna (Update Nama)
preg_match('/name="csrf_test_name" value="([^"]+)"/', $adminProfil['body'], $mCsrfUp);
$csrfUp = $mCsrfUp[1] ?? '';
$updateRes = httpReq($baseUrl . '/profil/update', 'POST', [
    'csrf_test_name' => $csrfUp,
    'nama'           => 'Muhammad Haidir Ali',
], $cookieAdmin);
testAssert("Proses update profil via POST /profil/update berhasil (Redirect 302/303)", in_array($updateRes['code'], [200, 302, 303]));

@unlink($cookieAdmin);
@unlink($cookieDokter);
@unlink($cookieKamar);

echo "\n====================================================================\n";
echo "HASIL PENGUJIAN: $pass dari $total tes berhasil (" . round(($pass / $total) * 100, 2) . "%)\n";
echo "====================================================================\n";

if ($pass === $total) {
    echo "STATUS: SELURUH PENGUJIAN PROFIL PROFESI LULUS 100%!\n";
    exit(0);
} else {
    echo "STATUS: TERDAPAT PENGUJIAN YANG GAGAL.\n";
    exit(1);
}

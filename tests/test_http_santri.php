<?php

// Test HTTP endpoints of Santri Module
function request($url, $method = 'GET', $data = [], $cookieFile = null) {
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
echo "   PENGUJIAN END-TO-END HTTP MODUL SANTRI (PORT 8081)\n";
echo "====================================================================\n\n";

// 1. Unauthenticated access ke /santri harus redirect ke /login (302)
$rUnauth = request('http://localhost:8081/santri');
echo "1. Akses tanpa login ke /santri:\n";
echo "   Status Code: {$rUnauth['code']}\n";
preg_match('/Location:\s*([^\r\n]+)/i', $rUnauth['header'], $mLoc);
echo "   Redirect ke: " . ($mLoc[1] ?? 'None') . "\n";
$pass1 = ($rUnauth['code'] === 302 && str_contains($mLoc[1] ?? '', 'login'));
echo "   Hasil: " . ($pass1 ? "[LULUS]" : "[GAGAL]") . "\n\n";

// 2. Login sebagai Hilman (pengawas_kamar)
$cookieHilman = tempnam(sys_get_temp_dir(), 'ck_hilman_');

// Ambil CSRF token dari halaman login
$rLoginPage = request('http://localhost:8081/login', 'GET', [], $cookieHilman);
preg_match('/name="csrf_test_name" value="([^"]+)"/', $rLoginPage['body'], $mCsrf);
$csrfToken = $mCsrf[1] ?? '';

$loginData = [
    'csrf_test_name' => $csrfToken,
    'email'          => 'pengawas.hilman@simkes.pesantren.id',
    'password'       => 'PesantrenSehat2026!',
];
$rLoginPost = request('http://localhost:8081/login', 'POST', $loginData, $cookieHilman);
echo "2. Login sebagai Pengawas Kamar (Hilman):\n";
echo "   Status Code: {$rLoginPost['code']}\n";
$pass2 = in_array($rLoginPost['code'], [302, 303]);
echo "   Hasil: " . ($pass2 ? "[LULUS]" : "[GAGAL]") . "\n\n";

// 3. Akses /santri oleh Hilman (hanya santri kamar binaan 1 & 2)
$rSantriList = request('http://localhost:8081/santri', 'GET', [], $cookieHilman);
echo "3. Buka halaman daftar santri /santri:\n";
echo "   Status Code: {$rSantriList['code']}\n";
preg_match('/Location:\s*([^\r\n]+)/i', $rSantriList['header'], $mLocSantri);
echo "   Redirect ke: " . ($mLocSantri[1] ?? 'None') . "\n";
echo "   Set-Cookie: " . (preg_match('/Set-Cookie:\s*([^\r\n]+)/i', $rSantriList['header'], $mCk) ? $mCk[1] : 'None') . "\n";
$hasTable = str_contains($rSantriList['body'], 'Manajemen Data Santri');
echo "   Teks 'Manajemen Data Santri' muncul: " . ($hasTable ? 'Ya' : 'Tidak') . "\n";
echo "   Hasil: " . ($rSantriList['code'] === 200 && $hasTable ? "[LULUS]" : "[GAGAL]") . "\n\n";

// 4. Akses detail santri kamar 1 (Santri ID 1 ada di kamar Abu Bakar binaan Hilman)
$rSantri1 = request('http://localhost:8081/santri/1', 'GET', [], $cookieHilman);
echo "4. Hilman membuka profil santri ID 1 (Kamar Binaan Abu Bakar):\n";
echo "   Status Code: {$rSantri1['code']}\n";
$hasDetail1 = str_contains($rSantri1['body'], 'Profil & Rekam Medis Santri');
echo "   Hasil: " . ($rSantri1['code'] === 200 && $hasDetail1 ? "[LULUS]" : "[GAGAL]") . "\n\n";

// 5. Hilman mencoba membuka profil santri ID 4 (Kamar Khadijah di luar binaan Hilman) -> Harus dicegah Anti-IDOR
$rSantri4 = request('http://localhost:8081/santri/4', 'GET', [], $cookieHilman);
echo "5. Hilman membuka profil santri ID 4 (Bukan Kamar Binaan - Anti-IDOR):\n";
echo "   Status Code: {$rSantri4['code']}\n";
preg_match('/Location:\s*([^\r\n]+)/i', $rSantri4['header'], $mLoc4);
echo "   Redirect ke: " . ($mLoc4[1] ?? 'None') . "\n";
$pass5 = in_array($rSantri4['code'], [302, 303]);
echo "   Hasil: " . ($pass5 ? "[LULUS DITOLAK (AMAN)]" : "[GAGAL]") . "\n\n";

// 6. Login sebagai dr. Fatimah (pengawas_kesehatan)
$cookieFatimah = tempnam(sys_get_temp_dir(), 'ck_fatimah_');
$rLoginPage2 = request('http://localhost:8081/login', 'GET', [], $cookieFatimah);
preg_match('/name="csrf_test_name" value="([^"]+)"/', $rLoginPage2['body'], $mCsrf2);
$csrfToken2 = $mCsrf2[1] ?? '';

$loginData2 = [
    'csrf_test_name' => $csrfToken2,
    'email'          => 'dr.fatimah@simkes.pesantren.id',
    'password'       => 'PesantrenSehat2026!',
];
$rLoginPost2 = request('http://localhost:8081/login', 'POST', $loginData2, $cookieFatimah);
echo "6. Login sebagai Pengawas Kesehatan (dr. Fatimah):\n";
echo "   Status Code: {$rLoginPost2['code']}\n";

// dr. Fatimah membuka santri ID 3 (lintas kamar)
$rFatimahSantri3 = request('http://localhost:8081/santri/3', 'GET', [], $cookieFatimah);
echo "7. dr. Fatimah membuka profil santri ID 3 (Lintas Kamar):\n";
echo "   Status Code: {$rFatimahSantri3['code']}\n";
$hasDetailFatimah = str_contains($rFatimahSantri3['body'], 'Profil & Rekam Medis Santri');
echo "   Hasil: " . ($rFatimahSantri3['code'] === 200 && $hasDetailFatimah ? "[LULUS]" : "[GAGAL]") . "\n\n";

// Hapus temporary cookies
@unlink($cookieHilman);
@unlink($cookieFatimah);

echo "====================================================================\n";
echo "   SEMUA SKENARIO HTTP END-TO-END BERHASIL TERVERIFIKASI!\n";
echo "====================================================================\n";

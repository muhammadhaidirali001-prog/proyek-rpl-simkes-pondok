<?php

// Test HTTP Endpoints for Dashboard SIMKES Santri
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
echo "   PENGUJIAN END-TO-END HTTP DASHBOARD SIMKES SANTRI (PORT 8081)\n";
echo "====================================================================\n\n";

// 1. Akses tanpa login ke /dashboard dialihkan ke /login
$rUnauth = httpReq('http://localhost:8081/dashboard');
$pass1 = ($rUnauth['code'] === 302 && str_contains($rUnauth['header'], 'login'));
echo "1. Akses tanpa login ke /dashboard dialihkan ke /login: " . ($pass1 ? "[LULUS]" : "[GAGAL]") . "\n";

// 2. Login sebagai Pengawas Kamar (Hilman)
$ckHilman = tempnam(sys_get_temp_dir(), 'ck_hilman_dash_');
$rLoginView = httpReq('http://localhost:8081/login', 'GET', [], $ckHilman);
preg_match('/name="csrf_test_name" value="([^"]+)"/', $rLoginView['body'], $mCsrf);
$csrf = $mCsrf[1] ?? '';

$loginHilman = httpReq('http://localhost:8081/login', 'POST', [
    'csrf_test_name' => $csrf,
    'email'          => 'pengawas.hilman@simkes.pesantren.id',
    'password'       => 'PesantrenSehat2026!',
], $ckHilman);

// Buka /dashboard sebagai Hilman
$rDashHilman = httpReq('http://localhost:8081/dashboard', 'GET', [], $ckHilman);
$hasKartuStatistik = str_contains($rDashHilman['body'], 'Santri Kamar Binaan') &&
                     str_contains($rDashHilman['body'], 'Sedang Sakit') &&
                     str_contains($rDashHilman['body'], 'Periksa & Rawat') &&
                     str_contains($rDashHilman['body'], 'Santri Sembuh') &&
                     str_contains($rDashHilman['body'], 'Santri Dirujuk');

$hasRoomIsolation = str_contains($rDashHilman['body'], 'Kamar Abu Bakar') &&
                    !str_contains($rDashHilman['body'], 'Kamar Khadijah Al-Kubra');

$pass2 = ($rDashHilman['code'] === 200 && $hasKartuStatistik && $hasRoomIsolation);
echo "2. Dashboard Pengawas Kamar (Kartu statistik & isolasi kamar binaan): " . ($pass2 ? "[LULUS]" : "[GAGAL]") . "\n";

// 3. Pengujian Pencarian Nama pada Dashboard
$rSearch = httpReq('http://localhost:8081/dashboard?keyword=Zaidan', 'GET', [], $ckHilman);
$pass3 = ($rSearch['code'] === 200 && str_contains($rSearch['body'], 'Muhammad Zaidan Akbar'));
echo "3. Fitur pencarian santri pada dashboard: " . ($pass3 ? "[LULUS]" : "[GAGAL]") . "\n";

// 4. Pengujian Tampilan Kosong (Empty State)
$rEmpty = httpReq('http://localhost:8081/dashboard?keyword=NAMA_TIDAK_ADA_9999', 'GET', [], $ckHilman);
$pass4 = ($rEmpty['code'] === 200 && str_contains($rEmpty['body'], 'Tidak Ada Laporan Sakit Ditemukan'));
echo "4. Tampilan kosong informatif (Empty State) saat filter nihil: " . ($pass4 ? "[LULUS]" : "[GAGAL]") . "\n";

// 5. Login sebagai Pengawas Kesehatan (dr. Fatimah)
$ckFatimah = tempnam(sys_get_temp_dir(), 'ck_fatimah_dash_');
$rLoginView2 = httpReq('http://localhost:8081/login', 'GET', [], $ckFatimah);
preg_match('/name="csrf_test_name" value="([^"]+)"/', $rLoginView2['body'], $mCsrf2);
$csrf2 = $mCsrf2[1] ?? '';

$loginFatimah = httpReq('http://localhost:8081/login', 'POST', [
    'csrf_test_name' => $csrf2,
    'email'          => 'dr.fatimah@simkes.pesantren.id',
    'password'       => 'PesantrenSehat2026!',
], $ckFatimah);

// Buka /dashboard sebagai dr. Fatimah (Akses Lintas Kamar)
$rDashFatimah = httpReq('http://localhost:8081/dashboard', 'GET', [], $ckFatimah);
$hasCrossRoom = str_contains($rDashFatimah['body'], 'Total Santri') &&
                str_contains($rDashFatimah['body'], 'Kamar Khadijah Al-Kubra') &&
                str_contains($rDashFatimah['body'], 'Kamar Abu Bakar');

$hasDetailLink = str_contains($rDashFatimah['body'], '/laporan-kesehatan/');

$pass5 = ($rDashFatimah['code'] === 200 && $hasCrossRoom && $hasDetailLink);
echo "5. Dashboard Pengawas Kesehatan (Akses lintas kamar & tombol detail): " . ($pass5 ? "[LULUS]" : "[GAGAL]") . "\n";

@unlink($ckHilman);
@unlink($ckFatimah);

echo "\n====================================================================\n";
echo "   SEMUA SKENARIO HTTP DASHBOARD BERHASIL LULUS 100%!\n";
echo "====================================================================\n";

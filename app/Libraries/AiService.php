<?php

namespace App\Libraries;

use Config\Ai as AiConfig;

class AiService
{
    protected AiConfig $config;

    public function __construct(?AiConfig $config = null)
    {
        $this->config = $config ?? config(AiConfig::class);

        // Jika terdapat konfigurasi kustom di session pengguna aktif, prioritaskan
        if (function_exists('session') && session()->has('custom_ai_provider')) {
            $this->config->provider = session()->get('custom_ai_provider');
        }
        if (function_exists('session') && session()->has('custom_ai_model')) {
            $this->config->model = session()->get('custom_ai_model');
        }
        if (function_exists('session') && session()->has('custom_ai_api_key')) {
            $this->config->apiKey = session()->get('custom_ai_api_key');
        }
    }

    /**
     * Mendapatkan informasi status penyedia AI saat ini (tanpa mengekspos API Key)
     */
    public function getProviderInfo(): array
    {
        $hasKey = !empty(trim($this->config->apiKey)) && $this->config->apiKey !== 'mock';

        return [
            'provider'   => $this->config->provider,
            'model'      => $this->config->model,
            'is_mock'    => !$hasKey || $this->config->provider === 'mock',
            'status'     => (!$hasKey || $this->config->provider === 'mock') ? 'Mode Simulasi (Offline/Edukasi Terverifikasi)' : 'Terhubung ke API AI Server',
            'max_tokens' => $this->config->maxTokens,
            'timeout'    => $this->config->timeout,
        ];
    }

    /**
     * Sanitasi data pribadi santri (PII) sebelum dikirim ke API luar
     * Memastikan privasi santri terlindungi sesuai prinsip Privacy by Design
     */
    public function sanitizePii(string $text): string
    {
        // 1. Samarkan pola NIK kependudukan (16 digit)
        $text = preg_replace('/\b[0-9]{16}\b/', '[NIK-DISAMARKAN]', $text);

        // 2. Samarkan nomor telepon / WhatsApp Indonesia (08xx atau +628xx atau 628xx)
        $text = preg_replace('/\b(?:\+?62|0)8[0-9]{8,12}\b/', '[NOMOR-HP-DISAMARKAN]', $text);

        // 3. Samarkan pola NIS santri (pola NIS: 12345 atau No. Induk: 12345)
        $text = preg_replace('/\b(?:NIS|nis|Nis|No\.?\s*Induk)[:\s]*([0-9]{5,12})\b/i', 'NIS: [DISAMARKAN]', $text);

        // 4. Samarkan deretan angka panjang tersisa (8 hingga 12 digit)
        $text = preg_replace('/\b([0-9]{8,12})\b/', '[KODE-ANGKA-DISAMARKAN]', $text);

        // 5. Samarkan frasa nama spesifik santri jika disebutkan eksplisit
        $text = preg_replace('/(?:santri\s+bernama|nama\s+santri\s*:)\s+([A-Za-z\s\'.]{2,30})/iu', 'Santri: [Nama-Disamarkan]', $text);

        // 6. Samarkan alamat detail jika ada pola 'alamat:'
        $text = preg_replace('/(?:alamat|tinggal\s+di|domisili)\s*:?\s*([A-Za-z0-9\s,\.\/]{8,50})/i', 'Alamat: [Alamat-Disamarkan]', $text);

        return trim($text);
    }

    /**
     * System prompt ketat dengan batasan etika klinis dan nuansa pesantren
     */
    public function getSystemPrompt(): string
    {
        return <<<PROMPT
Anda adalah "Asisten Kesehatan" pada sistem SIMKES Santri di lingkungan Pondok Pesantren.

IDENTITAS RESMI:
- Jika pengguna bertanya "siapa anda", "kamu siapa", "anda siapa", "siapa kamu", atau terkait identitas asisten, JAWAB DENGAN TEGAS DAN JELAS bahwa Anda adalah "Asisten Kesehatan SIMKES Santri".
- Jika pengguna bertanya "siapa saya", "nama saya siapa", atau terkait identitas pengguna, jawab sesuai nama dan peran pengguna yang sedang aktif (misal: dokter, pengawas kamar, petugas kesehatan, admin).

KEMAMPUAN MEMBACA DATABASE SISTEM:
- Anda memiliki akses langsung ke database SIMKES Santri untuk membaca data jumlah siswa/santri yang sedang sakit, dalam perawatan/pemeriksaan, sembuh, dan dirujuk.
- Jelaskan data database langsung ke intinya saja secara ringkas, padat, dan akurat.

BATASAN KETAT MATERI (STRICT HEALTH TOPICS ONLY):
1. Anda HANYA melayani dan menjawab pertanyaan yang berkaitan langsung dengan KESEHATAN SANTRI, data kesehatan santri di SIMKES, gejala penyakit/keluhan fisik, pertolongan pertama (P3K), penjelasan istilah medis umum, sanitasi kebersihan asrama/kamar santri, dan tanda bahaya kesehatan (*red flags*).
2. JIKA PENGGUNA BERTANYA DI LUAR MATERI KESEHATAN (seperti pemrograman/koding, rumus matematika, politik, hiburan, game, gosip, otomotif, resep masakan umum, atau obrolan acak non-kesehatan), ANDA WAJIB MENOLAK SECARA LUGAS DAN TEGAS:
   "Mohon maaf, saya adalah Asisten Kesehatan SIMKES Santri. Saya hanya melayani pertanyaan seputar kesehatan santri, gejala penyakit, pertolongan pertama, istilah medis, dan sanitasi asrama pesantren. Pertanyaan di luar topik kesehatan tidak dapat saya proses."

GAYA KOMUNIKASI & BEBAS IKLAN (NO ADS / NO PROMOTION):
1. DILARANG menggunakan bahasa promosi, iklan produk, sponsor, atau kalimat pembuka/penutup yang bertele-tele.
2. Jawaban harus langsung pada pokok masalah (to the point), padat, jelas, akurat, dan edukatif.

BATASAN ETIKA MEDIS (WAJIB DIPATUHI):
1. DILARANG KERAS membuat diagnosis pasti suatu penyakit. Gunakan bahasa edukatif berhati-hati (kemungkinan mengarah ke...).
2. DILARANG KERAS meresepkan obat keras/antibiotik atau menetapkan dosis obat spesifik. Pemberian obat wajib melalui pemeriksaan langsung oleh dokter atau perawat di Poskestren.
3. DILARANG KERAS menyatakan santri sembuh tanpa pemeriksaan medis.
4. Jika mendeteksi tanda bahaya (seperti sesak napas berat, kejang, demam tinggi >39°C berhari-hari, penurunan kesadaran, muntah terus-menerus), TEGASKAN AGAR SEGERA MERUJUK SANTRI KE POSKESTREN ATAU UGD TERDEKAT!
5. Selalu cantumkan catatan disclaimer di akhir: "Catatan: Informasi ini bersifat edukatif dan bukan pengganti diagnosis medis resmi. Segera konsultasikan kondisi santri ke Poskestren Pesantren."
PROMPT;
    }

    /**
     * Memeriksa apakah pesan pengguna adalah pertanyaan mengenai identitas pengguna yang sedang login ("siapa saya")
     */
    public function isUserIdentityQuestion(string $message): bool
    {
        $clean = mb_strtolower(trim($message));

        $patterns = [
            '/\b(siapa|siapakah)\s+(saya|aku|diriku|gue|gw)\b/iu',
            '/\b(saya|aku|diriku|gue|gw)\s+(ini\s+)?(siapa|siapakah)\b/iu',
            '/\b(siapa|apakah)\s+nama\s+(saya|aku)\b/iu',
            '/\bnama\s+(saya|aku)\s+(siapa|apa)\b/iu',
            '/\b(apa|apakah)\s+(role|jabatan|posisi|peran|tugas|profesi|wewenang)\s+(saya|aku)\b/iu',
            '/\b(role|jabatan|posisi|peran|identitas|profil|profesi|wewenang|tugas)\s+(saya|aku)\b/iu',
            '/\b(profil|profesi)\s+(lengkap|resmi)?\b/iu',
            '/\b(siapa\s+yang\s+sedang\s+login|siapa\s+user\s+ini)\b/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $clean)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Respon identitas pengguna yang sedang login sesuai role, nama, dan profil profesinya
     */
    public function getUserIdentityResponse(array $userContext = []): string
    {
        $ctx = $this->getUserContext($userContext);

        $nama = trim((string) ($ctx['nama'] ?? ''));
        $role = trim((string) ($ctx['role'] ?? ($ctx['role_canonical'] ?? '')));

        if (empty($nama) && empty($role)) {
            return "Anda saat ini belum teridentifikasi masuk (login) ke sistem SIMKES Santri. Silakan masuk terlebih dahulu untuk mengakses akun Anda.";
        }

        $lowerRole = strtolower($role);
        $lowerNama = strtolower($nama);

        // 1. Kasus Dokter
        if (str_contains($lowerRole, 'dokter') || str_starts_with($lowerNama, 'dr.') || str_contains($lowerNama, 'dokter')) {
            $displayNama = !empty($nama) ? $nama : 'dr. Fatimah Zahra';
            return <<<RESP
Anda adalah **{$displayNama}**, bertugas sebagai **Dokter** di Poskestren SIMKES Santri.

**Profil Profesi & Wewenang Medis:**
- **Unit Tugas:** Pos Kesehatan Pesantren (Poskestren Terpadu).
- **Wewenang Klinis:** Pemeriksaan fisik, anamnesis keluhan santri, dan penetapan diagnosis kerja.
- **Terapi & Resep:** Menentukan instruksi obat dan dosis resmi bagi santri yang sakit.
- **Tindakan Darurat:** Menerbitkan rujukan santri ke Rumah Sakit / Puskesmas mitra jika terjadi kegawatdaruratan.
RESP;
        }

        // 2. Kasus Pengawas Kamar
        if (str_contains($lowerRole, 'kamar')) {
            $displayNama = !empty($nama) ? $nama : 'Pengawas Kamar';
            $kamarList = $ctx['kamar_names'] ?? [];
            $kamarStr = !empty($kamarList) ? implode(', ', $kamarList) : 'kamar binaan Anda';

            return <<<RESP
Anda adalah **{$displayNama}**, bertugas sebagai **Pengawas Kamar** ({$kamarStr}) di SIMKES Santri.

**Profil Profesi & Wewenang Asrama:**
- **Unit Tugas:** Asrama Santri Pondok Pesantren.
- **Kamar Binaan:** {$kamarStr}.
- **Tanggung Jawab:** Pemantauan kondisi kesehatan harian santri dan deteksi dini santri yang sakit di asrama.
- **Pelaporan:** Menginput laporan keluhan santri ke sistem Poskestren untuk pemeriksaan lebih lanjut.
- **Pendampingan:** Memastikan santri beristirahat dan meminum obat sesuai anjuran tenaga medis.
RESP;
        }

        // 3. Kasus Petugas Kesehatan / Perawat
        if (str_contains($lowerRole, 'kesehatan') || str_contains($lowerRole, 'perawat')) {
            $displayNama = !empty($nama) ? $nama : 'Petugas Kesehatan';
            return <<<RESP
Anda adalah **{$displayNama}**, bertugas sebagai **Petugas Kesehatan** di Poskestren SIMKES Santri.

**Profil Profesi & Wewenang:**
- **Unit Tugas:** Poskestren Terpadu Pesantren.
- **Tanggung Jawab:** Pelayanan pertolongan pertama, observasi tanda-tanda vital santri, dan pengelolaan inventaris obat.
- **Kolaborasi Medis:** Membantu dokter dalam pelaksanaan tindakan klinis dan distribusi obat santri.
RESP;
        }

        // 4. Kasus Administrator
        if (str_contains($lowerRole, 'admin')) {
            $displayNama = !empty($nama) ? $nama : 'Muhammad Haidir Ali';
            return <<<RESP
Anda adalah **{$displayNama}**, bertugas sebagai **Administrator Sistem** di SIMKES Santri.

**Profil Profesi & Wewenang Sistem:**
- **Unit Tugas:** Pusat Tata Kelola TI & Manajemen SIMKES Santri.
- **Tanggung Jawab:** Pengelolaan akun pengguna, pemantauan log audit, dan integritas master data santri & asrama.
- **Keamanan & Integrasi:** Pemeliharaan keamanan sistem, manajemen backup data, serta konfigurasi layanan Asisten AI Poskestren.
RESP;
        }

        // Fallback Umum
        $displayNama = !empty($nama) ? $nama : 'Pengguna';
        $displayRole = !empty($ctx['role_display']) ? $ctx['role_display'] : $role;
        return "Anda adalah **{$displayNama}**, bertugas sebagai **{$displayRole}** di SIMKES Santri.";
    }

    /**
     * Memeriksa apakah pesan pengguna adalah pertanyaan seputar jumlah/data santri di database
     */
    public function isDatabaseStatsQuestion(string $message): bool
    {
        $clean = mb_strtolower(trim($message));

        // Exclusion: "tampilkan riwayat status" dan "tampilkan data pemeriksaan"
        // → serahkan ke isUniversalDatabaseQuestion (step lebih akhir)
        if (preg_match('/\b(tampilkan|lihat|cek|ambil)\s+(data\s+)?(pemeriksaan|riwayat)\b/iu', $clean)) {
            return false;
        }
        if (preg_match('/\b(riwayat\s+(status|kesehatan)|histori\s+kesehatan)\b/iu', $clean)
            && preg_match('/\b(tampilkan|lihat|cek|ambil|semua|seluruh)\b/iu', $clean)) {
            return false;
        }

        $patterns = [
            '/\b(berapa|jumlah|total|rekap|statistik|data|kondisi|status|ada\s+berapa)\b.*\b(sakit|rawat|dirawat|periksa|diperiksa|di\s*periksa|pemeriksaan|perawatan|sembuh|rujuk)/iu',
            '/\b(ada\s+berapa)\b.*\b(santri|siswa|anak|orang|kasus)/iu',
            '/\b(santri|siswa|murid|pasien)\b.*\b(yang|yg|sedang|dalam|sudah)\b.*\b(sakit|rawat|dirawat|periksa|diperiksa|di\s*periksa|sembuh|rujuk)/iu',
            '/\b(berapa|jumlah|total)\s+(santri|siswa|murid)\b/iu',
            '/\b(santri|siswa)\s+sakit\b/iu',
            '/\b(rekap|statistik|laporan|kondisi|status)\s+(kesehatan|kesehatan\s+santri|kesehatan\s+siswa|santri|siswa)\b/iu',
            '/\b(siswa|santri)\s+(yg|yang)\s+(sakit|rawat|dirawat|sembuh|periksa|diperiksa|di\s*periksa)/iu',
            '/\b(berapa|ada)\b.*\b(yang|yg|sedang)\s+(?:di\s*)?(rawat|periksa)\b/iu',
            '/\b(santri|siswa)\s+(sedang\s+)?(?:di\s*)?(rawat|periksa)\b/iu',
            '/\b(ada\s+berapa|berapa)\s+(santri|siswa)\s+(yg|yang)\s+sakit\b/iu',
            '/\b(ada\s+berapa|berapa)\b.*\b(?:di\s*)?(?:periksa|pemeriksaan)\b/iu',
            '/\b(?:di\s*)?(?:periksa|pemeriksaan)\b.*\b(ada\s+berapa|berapa)\b/iu',
            '/\b(sedang|dalam)\s+(?:di\s*)?(?:periksa|pemeriksaan)\b/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $clean)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ambil data statistik langsung dari database SIMKES Santri secara realtime
     */
    public function getLiveDatabaseStats(?array $allowedKamarIds = null): array
    {
        try {
            $santriModel  = new \App\Models\SantriModel();
            $laporanModel = new \App\Models\LaporanKesehatanModel();

            if ($allowedKamarIds !== null) {
                $totalSantri = !empty($allowedKamarIds) 
                    ? $santriModel->whereIn('kamar_id', $allowedKamarIds)->countAllResults() 
                    : 0;
            } else {
                $totalSantri = $santriModel->countAllResults();
            }

            $stats = $laporanModel->getDashboardStats($allowedKamarIds);

            return [
                'total_santri'       => (int) $totalSantri,
                'sedang_sakit'       => (int) ($stats['sedang_sakit'] ?? 0),
                'dalam_pemeriksaan'  => (int) ($stats['dalam_pemeriksaan'] ?? 0),
                'dalam_perawatan'    => (int) ($stats['dalam_perawatan'] ?? 0),
                'rawat_periksa'      => (int) ($stats['rawat_periksa'] ?? 0),
                'sembuh'             => (int) ($stats['sembuh'] ?? 0),
                'dirujuk'            => (int) ($stats['dirujuk'] ?? 0),
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Gagal membaca statistik database AI: ' . $e->getMessage());
            return [
                'total_santri'       => 0,
                'sedang_sakit'       => 0,
                'dalam_pemeriksaan'  => 0,
                'dalam_perawatan'    => 0,
                'rawat_periksa'      => 0,
                'sembuh'             => 0,
                'dirujuk'            => 0,
            ];
        }
    }

    /**
     * Menghasilkan jawaban ringkas langsung ke intinya dari database
     */
    public function getDatabaseStatsResponse(string $userMessage, array $userContext = []): string
    {
        $ctx = $this->getUserContext($userContext);
        $isPengawasKamar = !empty($ctx['is_pengawas_kamar']);
        $allowedKamarIds = $isPengawasKamar ? ($ctx['kamar_ids'] ?? []) : null;
        $kamarNames = $ctx['kamar_names'] ?? [];
        $kamarLabel = !empty($kamarNames) ? implode(', ', $kamarNames) : 'kamar binaan Anda';

        $stats = $this->getLiveDatabaseStats($allowedKamarIds);
        $totalSantri      = $stats['total_santri'];
        $sedangSakit      = $stats['sedang_sakit'];
        $dalamPeriksa     = $stats['dalam_pemeriksaan'];
        $dalamRawat       = $stats['dalam_perawatan'];
        $rawatPeriksa     = $stats['rawat_periksa']; // gabungan
        $sembuh           = $stats['sembuh'];
        $dirujuk          = $stats['dirujuk'];

        $totalSakitAktif = $sedangSakit + $rawatPeriksa + $dirujuk;
        $santriSehat     = max(0, $totalSantri - $totalSakitAktif);

        $clean = mb_strtolower(trim($userMessage));

        // Deteksi status yang dimaksud dalam pertanyaan
        $hasKataPeriksa = (bool) preg_match('/\b(?:di\s*)?(?:periksa|pemeriksaan)\b/iu', $clean);
        $hasKataRawat   = (bool) preg_match('/\b(?:di\s*)?(?:rawat|perawatan)\b/iu', $clean);
        $hasKataSembuh  = (bool) preg_match('/\b(?:sembuh)\b/iu', $clean);
        $hasKataDirujuk = (bool) preg_match('/\b(?:rujuk|dirujuk)\b/iu', $clean);
        $hasKataSehat   = (bool) preg_match('/\b(?:sehat)\b/iu', $clean);
        $hasKataTotalSantri = (bool) preg_match('/\b(?:total\s+santri|santri\s+terdaftar|siswa\s+terdaftar|berapa\s+santri\s+terdaftar|jumlah\s+santri\s+terdaftar|total\s+siswa|santri\s+aktif|santri\s+yang\s+aktif|berapa\s+santri\s+aktif|jumlah\s+santri\s+aktif)\b/iu', $clean)
            || ((bool) preg_match('/\b(?:total|semua|terdaftar|seluruh|aktif)\b/iu', $clean) && (bool) preg_match('/\b(?:santri|siswa)\b/iu', $clean) && !$hasKataPeriksa && !$hasKataRawat && !$hasKataSembuh && !$hasKataDirujuk && !(bool) preg_match('/\bsakit\b/iu', $clean));
        $hasRekapUmum   = (bool) preg_match('/\b(?:rekap|statistik|kondisi|laporan\s+kesehatan|semua\s+data|semua\s+status)\b/iu', $clean);

        // Pertanyaan spesifik: Total Santri Terdaftar (langsung ke intinya)
        if ($hasKataTotalSantri) {
            $scope = $isPengawasKamar ? " di kamar binaan Anda ({$kamarLabel})" : ' di SIMKES Santri';
            return "Saat ini ada **{$totalSantri} santri** yang terdaftar{$scope}.";
        }

        // Pertanyaan spesifik: Santri Sehat
        if ($hasKataSehat && !$hasKataRawat && !$hasKataPeriksa && !$hasKataSembuh && !$hasKataDirujuk && !$hasRekapUmum) {
            $scope = $isPengawasKamar ? " di kamar binaan Anda ({$kamarLabel})" : ' di seluruh pondok';
            return "Saat ini ada **{$santriSehat} santri** dalam kondisi sehat{$scope}.";
        }

        // Jika hanya tanya "sakit" saja (tanpa rawat/periksa/sembuh/dirujuk/rekap) → jawab Dalam Perawatan
        $hasKataSakitSaja = (bool) preg_match('/\b(sakit)\b/iu', $clean)
            && !$hasKataRawat && !$hasKataPeriksa && !$hasKataSembuh && !$hasKataDirujuk && !$hasRekapUmum && !$hasKataTotalSantri;

        // Shortcut spesifik status (meskipun mengandung kata 'berapa' / 'jumlah' / 'total' / 'ada berapa')
        $tanyaPeriksa = $hasKataPeriksa && !$hasKataRawat && !$hasKataSembuh && !$hasKataDirujuk && !$hasRekapUmum;
        $tanyaRawat   = $hasKataRawat && !$hasKataPeriksa && !$hasKataSembuh && !$hasKataDirujuk && !$hasRekapUmum;
        $tanyaSembuh  = $hasKataSembuh && !$hasKataPeriksa && !$hasKataRawat && !$hasKataDirujuk && !$hasRekapUmum;
        $tanyaDirujuk = $hasKataDirujuk && !$hasKataPeriksa && !$hasKataRawat && !$hasKataSembuh && !$hasRekapUmum;

        // "Berapa santri yang sakit?" → Dalam Perawatan (bukan Dilaporkan Sakit)
        if ($hasKataSakitSaja) {
            $scope = $isPengawasKamar ? " di kamar binaan Anda ({$kamarLabel})" : ' di seluruh pondok';
            return "Berdasarkan database SIMKES Santri, saat ini terdapat **{$dalamRawat} santri** yang **Dalam Perawatan**{$scope} (sedang ditangani dokter/petugas Poskestren).";
        }

        if ($tanyaPeriksa) {
            $scope = $isPengawasKamar ? " di kamar binaan Anda ({$kamarLabel})" : ' di seluruh pondok';
            
            // Ambil rincian santri yang sedang dalam pemeriksaan langsung dari database
            $db = \Config\Database::connect();
            $builderPeriksa = $db->table('laporan_kesehatan')
                ->select('laporan_kesehatan.*, santri.nama as nama_santri, santri.nis, kamar.nama_kamar')
                ->join('santri', 'santri.id = laporan_kesehatan.santri_id', 'left')
                ->join('kamar', 'kamar.id = santri.kamar_id', 'left')
                ->where('laporan_kesehatan.status', \App\Models\LaporanKesehatanModel::STATUS_DALAM_PEMERIKSAAN);
            if ($isPengawasKamar && !empty($allowedKamarIds)) {
                $builderPeriksa->whereIn('santri.kamar_id', $allowedKamarIds);
            }
            $santriPeriksaList = $builderPeriksa->orderBy('laporan_kesehatan.created_at', 'DESC')->get()->getResultArray();
            $jmlPeriksa = count($santriPeriksaList);

            if ($jmlPeriksa > 0) {
                $detailSantri = "\n";
                $no = 1;
                foreach ($santriPeriksaList as $s) {
                    $namaSantri  = $s['nama_santri'] ?: 'Santri';
                    $nisSantri   = $s['nis'] ?: '-';
                    $kamarSantri = $s['nama_kamar'] ?: 'Kamar belum ditentukan';
                    $gejalaSantri = $s['gejala'] ?: '-';
                    $detailSantri .= "{$no}. **{$namaSantri}** (NIS: {$nisSantri} &bull; {$kamarSantri}) - Keluhan: {$gejalaSantri}\n";
                    $no++;
                }
                return "Saat ini ada **{$jmlPeriksa} santri** yang sedang **Dalam Pemeriksaan**{$scope}:" . $detailSantri;
            } else {
                return "Saat ini ada **0 santri** yang sedang **Dalam Pemeriksaan**{$scope}.";
            }
        }

        if ($tanyaRawat) {
            $scope = $isPengawasKamar ? " di kamar binaan Anda ({$kamarLabel})" : ' di seluruh pondok';
            return "Saat ini ada **{$dalamRawat} santri** yang sedang **Dalam Perawatan**{$scope}.";
        }

        if ($tanyaSembuh) {
            $scope = $isPengawasKamar ? " di kamar binaan Anda ({$kamarLabel})" : ' di seluruh pondok';
            return "Saat ini ada **{$sembuh} santri** yang sudah **Sembuh** (selesai rawat){$scope}.";
        }

        if ($tanyaDirujuk) {
            $scope = $isPengawasKamar ? " di kamar binaan Anda ({$kamarLabel})" : ' di seluruh pondok';
            return "Saat ini ada **{$dirujuk} santri** yang sedang **Dirujuk** ke RS/Faskes{$scope}.";
        }

        if ($isPengawasKamar) {
            if (empty($allowedKamarIds)) {
                return "Berdasarkan database SIMKES Santri, Anda belum terdaftar mengampu kamar binaan tertentu.";
            }

            return <<<RESP
Berdasarkan database SIMKES Santri di kamar binaan Anda ({$kamarLabel}):
- **Dilaporkan Sakit:** {$sedangSakit} orang
- **Dalam Pemeriksaan:** {$dalamPeriksa} orang
- **Dalam Perawatan:** {$dalamRawat} orang
- **Sembuh:** {$sembuh} orang
- **Dirujuk:** {$dirujuk} orang
- **Santri Sehat:** {$santriSehat} orang
Total santri terdaftar: {$totalSantri} orang (seluruh santri sehat & sakit di kamar binaan).
RESP;
        }

        // Dokter / Pengawas Kesehatan / Admin (Akses Lintas Kamar Pesantren)
        return <<<RESP
Berdasarkan database SIMKES Santri saat ini:
- **Dilaporkan Sakit:** {$sedangSakit} orang
- **Dalam Pemeriksaan:** {$dalamPeriksa} orang
- **Dalam Perawatan:** {$dalamRawat} orang
- **Sembuh:** {$sembuh} orang
- **Dirujuk:** {$dirujuk} orang
- **Santri Sehat:** {$santriSehat} orang
Total santri terdaftar: {$totalSantri} orang (seluruh santri sehat & sakit di pondok).
RESP;
    }

    /**
     * Memeriksa apakah pesan pengguna menanyakan daftar nama santri terdaftar aktif
     */
    public function isSantriListQuestion(string $message): bool
    {
        $clean = mb_strtolower(trim($message));

        // Jika ada indikasi pertanyaan status sakit/rawat, serahkan ke handler statistik sakit
        if (preg_match('/\b(sakit|rawat|periksa|sembuh|rujuk)\b/iu', $clean)) {
            return false;
        }

        $patterns = [
            '/\b(siapa|siapakah|sebutkan|daftar|list)\b.*\b(nama\s+)?(santri|siswa|murid)\b.*\b(terdaftar|aktif|ada)\b/iu',
            '/\b(siapa|siapakah)\s+(saja\s+)?(nama\s+)?(santri|siswa|murid)\b/iu',
            '/\b(siapa|siapakah)\s+(nama\s+)?(santri|siswa|murid)\s+(yang|yg)\s+(terdaftar|aktif)\b/iu',
            '/\b(daftar|list)\s+(nama\s+)?(santri|siswa|murid)\b/iu',
            '/\b(nama[- ]nama\s+)?(santri|siswa|murid)\s+(terdaftar|aktif)\b/iu',
            '/\b(santri|siswa)\s+terdaftar\s+aktif\b/iu',
            '/\b(santri|siswa)\s+(yang|yg)\s+(terdaftar|aktif)\b/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $clean)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Menghasilkan daftar nama santri terdaftar aktif langsung dari database SIMKES Santri
     */
    public function getSantriListResponse(string $userMessage, array $userContext = []): string
    {
        $ctx = $this->getUserContext($userContext);
        $isPengawasKamar = !empty($ctx['is_pengawas_kamar']);
        $allowedKamarIds = $isPengawasKamar ? ($ctx['kamar_ids'] ?? []) : null;
        $kamarNames = $ctx['kamar_names'] ?? [];
        $kamarLabel = !empty($kamarNames) ? implode(', ', $kamarNames) : 'kamar binaan Anda';

        try {
            $santriModel = new \App\Models\SantriModel();

            if ($isPengawasKamar) {
                if (empty($allowedKamarIds)) {
                    return "Berdasarkan database SIMKES Santri, Anda belum terdaftar mengampu kamar binaan tertentu.";
                }

                $santriList = $santriModel->select('santri.*, kamar.nama_kamar')
                    ->join('kamar', 'kamar.id = santri.kamar_id', 'left')
                    ->whereIn('santri.kamar_id', $allowedKamarIds)
                    ->orderBy('santri.nama', 'ASC')
                    ->findAll();

                if (empty($santriList)) {
                    return "Berdasarkan database SIMKES Santri, saat ini belum ada santri terdaftar di kamar binaan Anda ({$kamarLabel}).";
                }

                $output = "Daftar santri terdaftar aktif di kamar binaan Anda ({$kamarLabel}):\n";
                $no = 1;
                foreach ($santriList as $s) {
                    $namaSantri = $s['nama'];
                    $nis = $s['nis'];
                    $kamar = !empty($s['nama_kamar']) ? $s['nama_kamar'] : 'Kamar Belum Ditentukan';
                    $output .= "{$no}. **{$namaSantri}** (NIS: {$nis} &bull; {$kamar})\n";
                    $no++;
                }
                $total = count($santriList);
                $output .= "\nTotal santri aktif di kamar binaan: **{$total} orang**.";
                return $output;
            }

            // Dokter / Pengawas Kesehatan / Admin (Seluruh Santri Terdaftar)
            $santriList = $santriModel->select('santri.*, kamar.nama_kamar')
                ->join('kamar', 'kamar.id = santri.kamar_id', 'left')
                ->orderBy('santri.nama', 'ASC')
                ->findAll();

            if (empty($santriList)) {
                return "Berdasarkan database SIMKES Santri, belum ada data santri yang terdaftar di sistem.";
            }

            $output = "Daftar nama santri terdaftar aktif di SIMKES Santri:\n";
            $no = 1;
            foreach ($santriList as $s) {
                $namaSantri = $s['nama'];
                $nis = $s['nis'];
                $kamar = !empty($s['nama_kamar']) ? $s['nama_kamar'] : 'Kamar Belum Ditentukan';
                $output .= "{$no}. **{$namaSantri}** (NIS: {$nis} &bull; {$kamar})\n";
                $no++;
            }
            $total = count($santriList);
            $output .= "\nTotal santri terdaftar aktif: **{$total} orang**.";
            return $output;

        } catch (\Throwable $e) {
            log_message('error', 'Gagal membaca daftar nama santri untuk AI: ' . $e->getMessage());
            return "Mohon maaf, terjadi kendala saat membaca data santri dari database.";
        }
    }

    /**
     * Memeriksa apakah pesan pengguna menanyakan siapa saja yang sedang sakit / dirawat / sembuh
     */
    public function isSickSantriListQuestion(string $message): bool
    {
        $clean = mb_strtolower(trim($message));

        // Pertanyaan jumlah "ada berapa / berapa jumlah" dialihkan ke isDatabaseStatsQuestion
        if (preg_match('/\b(berapa|jumlah|total|ada\s+berapa)\b/iu', $clean)) {
            return false;
        }

        $isAskingWho = preg_match('/\b(siapa|siapakah|sebutkan|daftar|list|ada\s+siapa|siapa\s+saja)\b/iu', $clean);
        $isHealthStatus = preg_match('/\b(sakit|sedang\s+sakit|(?:di)?rawat|dalam\s+perawatan|(?:di)?periksa|dalam\s+pemeriksaan|sembuh|(?:di)?rujuk)\b/iu', $clean);

        if ($isAskingWho && $isHealthStatus) {
            return true;
        }

        // Variasi frasa langsung: "siapa santri yg sakit", "santri yang sakit", "siapa yang sakit", "siapa yang dirawat"
        if (preg_match('/\b(siapa|siapakah|sebutkan|daftar)\s+(nama\s+)?(santri|siswa|anak)\s+(yang\s+|yg\s+)?(sakit|sedang\s+sakit|(?:di)?rawat)\b/iu', $clean)) {
            return true;
        }

        if (preg_match('/\b(santri|siswa|anak)\b.*\b(yang|yg)\b.*\b(sakit|(?:di)?rawat|(?:di)?periksa|sembuh|(?:di)?rujuk)\b/iu', $clean)) {
            return true;
        }

        if (preg_match('/\b(siapa|siapakah)\b.*\b(yang|yg)\b.*\b(sakit|(?:di)?rawat|(?:di)?periksa|sembuh|(?:di)?rujuk)\b/iu', $clean)) {
            return true;
        }

        return false;
    }

    /**
     * Menghasilkan daftar santri yang sedang sakit / dirawat / sembuh langsung dari database
     * Aturan: Hanya menampilkan santri yang sedang sakit aktif, kecuali jika secara eksplisit ditanya yang sembuh.
     */
    public function getSickSantriListResponse(string $userMessage, array $userContext = []): string
    {
        $ctx = $this->getUserContext($userContext);
        $isPengawasKamar = !empty($ctx['is_pengawas_kamar']);
        $allowedKamarIds = $isPengawasKamar ? ($ctx['kamar_ids'] ?? []) : null;
        $kamarNames = $ctx['kamar_names'] ?? [];
        $kamarLabel = !empty($kamarNames) ? implode(', ', $kamarNames) : 'kamar binaan Anda';

        $cleanMsg = mb_strtolower($userMessage);

        // Tentukan target status berdasarkan pertanyaan pengguna
        $filterStatus = null;
        $statusLabel = 'sedang sakit / dalam perawatan';
        $isSembuhRequest = false;

        if (preg_match('/\b(sembuh)\b/iu', $cleanMsg)) {
            $filterStatus = ['Sembuh'];
            $statusLabel = 'sudah sembuh';
            $isSembuhRequest = true;
        } elseif (preg_match('/\b(rujuk|dirujuk)\b/iu', $cleanMsg)) {
            $filterStatus = ['Dirujuk'];
            $statusLabel = 'sedang dirujuk ke faskes';
        } elseif (preg_match('/\b(rawat|dalam\s+perawatan)\b/iu', $cleanMsg)) {
            $filterStatus = ['Dalam Perawatan'];
            $statusLabel = 'sedang dalam perawatan asrama';
        } elseif (preg_match('/\b(periksa|dalam\s+pemeriksaan)\b/iu', $cleanMsg)) {
            $filterStatus = ['Dalam Pemeriksaan'];
            $statusLabel = 'sedang dalam pemeriksaan di Poskestren';
        } else {
            // Default: HANYA santri yang saat ini sedang sakit aktif (belum sembuh dan bukan yang sehat)
            $filterStatus = ['Dilaporkan Sakit', 'Dalam Pemeriksaan', 'Dalam Perawatan'];
            $statusLabel = 'sedang sakit saat ini';
        }

        try {
            $db = \Config\Database::connect();
            $builder = $db->table('laporan_kesehatan')
                ->select('laporan_kesehatan.*, santri.nama as nama_santri, santri.nis, kamar.nama_kamar, kamar.id as kamar_id')
                ->join('santri', 'santri.id = laporan_kesehatan.santri_id', 'left')
                ->join('kamar', 'kamar.id = santri.kamar_id', 'left')
                ->whereIn('laporan_kesehatan.status', $filterStatus);

            if ($isPengawasKamar) {
                if (empty($allowedKamarIds)) {
                    return "Berdasarkan database SIMKES Santri, Anda belum terdaftar mengampu kamar binaan tertentu.";
                }
                $builder->whereIn('kamar.id', $allowedKamarIds);
            }

            $results = $builder->orderBy('laporan_kesehatan.tanggal_mulai_sakit', 'DESC')->get()->getResultArray();

            if (empty($results)) {
                if ($isPengawasKamar) {
                    return "Berdasarkan database SIMKES Santri di kamar binaan Anda ({$kamarLabel}), saat ini tidak ada santri yang {$statusLabel}.";
                }
                return "Berdasarkan database SIMKES Santri saat ini, Alhamdulillah tidak ada santri yang {$statusLabel}.";
            }

            $header = $isPengawasKamar
                ? "Daftar santri {$statusLabel} di kamar binaan Anda ({$kamarLabel}):\n"
                : "Daftar santri yang {$statusLabel} di SIMKES Santri:\n";

            $output = $header;
            $no = 1;
            foreach ($results as $row) {
                $nama = $row['nama_santri'] ?: 'Santri';
                $nis = $row['nis'] ?: '-';
                $kamar = $row['nama_kamar'] ?: 'Kamar belum ditentukan';
                $status = $row['status'];
                $gejala = $row['gejala'] ?: 'Tidak ada catatan gejala.';
                $tgl = date('d F Y', strtotime($row['tanggal_mulai_sakit']));

                $output .= "{$no}. **{$nama}** (NIS: {$nis} &bull; {$kamar})\n";
                $output .= "   - **Status:** {$status}\n";
                $output .= "   - **Gejala/Keluhan:** {$gejala}\n";
                $output .= "   - **Mulai Sakit:** {$tgl}\n";
                $no++;
            }

            $total = count($results);
            if ($isSembuhRequest) {
                $output .= "\nTotal santri yang sudah sembuh: **{$total} orang**.";
            } else {
                $output .= "\nTotal santri yang sedang sakit: **{$total} orang**.\n*(Catatan: Santri yang sudah sembuh atau dalam kondisi sehat tidak dimasukkan ke dalam daftar ini).*";
            }
            return $output;

        } catch (\Throwable $e) {
            log_message('error', 'Gagal membaca santri sakit untuk AI: ' . $e->getMessage());
            return "Mohon maaf, terjadi kendala saat membaca data santri sakit dari database.";
        }
    }

    /**
     * Memeriksa apakah pesan pengguna menanyakan kamar atau asrama
     */
    public function isKamarListQuestion(string $message): bool
    {
        $clean = mb_strtolower(trim($message));
        return (bool) preg_match('/\b(kamar|asrama|kobong)\b.*\b(apa\s+saja|daftar|list|ada\s+apa|ada\s+berapa|pengawas|binaan|tampilkan|lihat|semua)\b/iu', $clean)
            || (bool) preg_match('/\b(daftar|list|sebutkan|tampilkan|lihat|cek)\s+(kamar|asrama)\b/iu', $clean)
            || (bool) preg_match('/\b(kamar|asrama)\s+(santri|pondok|pesantren)\b/iu', $clean);
    }

    /**
     * Menghasilkan daftar kamar dan asrama dari database
     */
    public function getKamarListResponse(array $userContext = []): string
    {
        try {
            $db = \Config\Database::connect();
            $kamarList = $db->table('kamar')
                ->select('kamar.*, users.nama as nama_pengawas')
                ->join('users', 'users.id = kamar.pengawas_id', 'left')
                ->orderBy('kamar.id', 'ASC')
                ->get()->getResultArray();

            if (empty($kamarList)) {
                return "Berdasarkan database SIMKES Santri, belum ada data kamar/asrama yang terdaftar.";
            }

            $output = "Daftar Kamar & Asrama Santri di Pesantren:\n";
            $no = 1;
            foreach ($kamarList as $k) {
                $namaKamar = $k['nama_kamar'];
                $pengawas = !empty($k['nama_pengawas']) ? $k['nama_pengawas'] : 'Belum ditentukan';
                $output .= "{$no}. **{$namaKamar}** (Pengawas: {$pengawas})\n";
                $no++;
            }
            $total = count($kamarList);
            $output .= "\nTotal kamar terdaftar: **{$total} kamar**.";
            return $output;
        } catch (\Throwable $e) {
            return "Mohon maaf, terjadi kendala saat membaca data kamar dari database.";
        }
    }

    /**
     * Memeriksa apakah pesan pengguna menanyakan stok obat atau inventaris obat
     */
    public function isObatListQuestion(string $message): bool
    {
        $clean = mb_strtolower(trim($message));
        if (preg_match('/\b(minta\s+resep|resepkan|dosis\s+obat|berapa\s+dosis)\b/iu', $clean)) {
            return false;
        }
        return (bool) preg_match('/\b(obat|inventaris\s+obat|stok\s+obat|medikamen)\b.*\b(apa\s+saja|daftar|list|ada\s+apa|tersedia|stok|gudang|ada\s+berapa|berapa)\b/iu', $clean)
            || (bool) preg_match('/\b(daftar|list|stok|inventaris|data|jumlah|berapa)\s+obat\b/iu', $clean)
            || (bool) preg_match('/\b(obat)\b.*\b(gudang|poskestren|klinik|tersedia)\b/iu', $clean);
    }

    /**
     * Menghasilkan daftar obat dan stok dari database
     */
    public function getObatListResponse(): string
    {
        try {
            $obatModel = new \App\Models\ObatModel();
            $obatList = $obatModel->orderBy('nama_obat', 'ASC')->findAll();

            if (empty($obatList)) {
                return "Berdasarkan database SIMKES Santri, belum ada data obat yang tercatat di inventaris Poskestren.";
            }

            $output = "Daftar Inventaris Obat di Poskestren:\n";
            $no = 1;
            foreach ($obatList as $o) {
                $nama = $o['nama_obat'];
                $stok = $o['stok'];
                $satuan = $o['satuan'] ?: 'Unit';
                $info = !empty($o['informasi_penggunaan']) ? " &bull; {$o['informasi_penggunaan']}" : '';
                $output .= "{$no}. **{$nama}** - Stok: {$stok} {$satuan}{$info}\n";
                $no++;
            }
            $total = count($obatList);
            $output .= "\nTotal obat terdaftar: **{$total} jenis obat**.";
            return $output;
        } catch (\Throwable $e) {
            return "Mohon maaf, terjadi kendala saat membaca data inventaris obat dari database.";
        }
    }

    /**
     * Memeriksa apakah pesan menanyakan jumlah/daftar dokter / petugas kesehatan
     */
    public function isPetugasListQuestion(string $message): bool
    {
        $clean = mb_strtolower(trim($message));
        return (bool) preg_match('/\b(siapa|daftar|list|sebutkan|ada\s+berapa|berapa|jumlah)\b.*\b(dokter|petugas\s+kesehatan|perawat|tenaga\s+medis)\b/iu', $clean)
            || (bool) preg_match('/\b(berapa|ada)\s+(dokter|petugas\s+kesehatan|perawat)\b/iu', $clean)
            || (bool) preg_match('/\b(dokter|petugas\s+kesehatan)\s+(yang\s+)?(ada|terdaftar|bertugas)\b/iu', $clean);
    }

    /**
     * Menghasilkan daftar dokter dan tenaga medis dari database (dengan jumlah)
     */
    public function getPetugasListResponse(): string
    {
        try {
            $userModel = new \App\Models\UserModel();
            $dokterList = $userModel
                ->groupStart()
                    ->where('role', 'Dokter')
                    ->orWhere('role', 'Petugas Kesehatan')
                    ->orWhere('role', 'Pengawas Kesehatan')
                ->groupEnd()
                ->where('status', 'Aktif')
                ->orderBy('nama', 'ASC')
                ->findAll();

            if (empty($dokterList)) {
                return "Berdasarkan database SIMKES Santri, belum ada dokter / petugas medis yang terdaftar aktif.";
            }

            $total = count($dokterList);
            $output = "Berdasarkan database SIMKES Santri, terdapat **{$total} dokter/petugas medis** aktif di Poskestren:\n\n";
            $no = 1;
            foreach ($dokterList as $p) {
                $output .= "{$no}. **{$p['nama']}** ({$p['role']})\n";
                $no++;
            }
            return $output;
        } catch (\Throwable $e) {
            return "Mohon maaf, terjadi kendala saat membaca data dokter dari database.";
        }
    }

    /**
     * Memeriksa apakah pesan menanyakan jumlah/daftar pengawas kamar
     */
    public function isPengawasListQuestion(string $message): bool
    {
        $clean = mb_strtolower(trim($message));
        return (bool) preg_match('/\b(siapa|daftar|list|sebutkan|ada\s+berapa|berapa|jumlah)\b.*\b(pengawas|musyrif|wali\s+kamar|pembina)\b/iu', $clean)
            || (bool) preg_match('/\b(berapa|ada)\s+(pengawas|musyrif|wali\s+kamar)\b/iu', $clean)
            || (bool) preg_match('/\b(pengawas|musyrif)\s+(kamar|asrama)?\s*(yang\s+)?(ada|terdaftar|bertugas|aktif)\b/iu', $clean)
            || (bool) preg_match('/\bsiapa\s+(saja\s+)?(pengawas|musyrif)\b/iu', $clean);
    }

    /**
     * Menghasilkan daftar pengawas kamar dari database
     */
    public function getPengawasListResponse(): string
    {
        try {
            $db = \Config\Database::connect();

            // Ambil kamar beserta nama pengawas
            $kamarList = $db->table('kamar')
                ->select('kamar.nama_kamar, users.nama as nama_pengawas, users.email, users.role')
                ->join('users', 'users.id = kamar.pengawas_id', 'left')
                ->orderBy('kamar.nama_kamar', 'ASC')
                ->get()->getResultArray();

            // Ambil juga user dengan role Pengawas Kamar yang mungkin belum assign kamar
            $userModel = new \App\Models\UserModel();
            $pengawasList = $userModel
                ->where('role', 'Pengawas Kamar')
                ->where('status', 'Aktif')
                ->orderBy('nama', 'ASC')
                ->findAll();

            if (empty($pengawasList) && empty($kamarList)) {
                return "Berdasarkan database SIMKES Santri, belum ada pengawas kamar yang terdaftar aktif.";
            }

            $total = count($pengawasList);
            $output = "Berdasarkan database SIMKES Santri, terdapat **{$total} pengawas kamar** aktif:\n\n";
            $no = 1;
            foreach ($pengawasList as $p) {
                // Cari kamar binaan pengawas ini
                $kamarBinaan = array_filter($kamarList, fn($k) => $k['nama_pengawas'] === $p['nama']);
                $namaKamar = !empty($kamarBinaan)
                    ? implode(', ', array_column(array_values($kamarBinaan), 'nama_kamar'))
                    : 'Belum ditugaskan';
                $output .= "{$no}. **{$p['nama']}** — Kamar: {$namaKamar}\n";
                $no++;
            }
            return $output;
        } catch (\Throwable $e) {
            log_message('error', 'Gagal membaca daftar pengawas: ' . $e->getMessage());
            return "Mohon maaf, terjadi kendala saat membaca data pengawas kamar dari database.";
        }
    }

    /**
     * Memeriksa apakah pesan pengguna menanyakan obat yang diberikan dokter kepada santri tertentu
     */
    public function isSantriMedicationQuestion(string $message): bool
    {
        $clean = mb_strtolower(trim($message));

        // Harus ada indikasi obat / resep / pemeriksaan / penanganan medis / diberi apa
        $hasMedKeyword = preg_match('/\b(obat|resep|minum\s+obat|diberi\s+obat|di\s+beri\s+obat|dikasih\s+obat|di\s+kasih\s+obat|diberi\s+apa|di\s+beri\s+apa|terapi|penanganan|pemeriksaan|dosis)\b/iu', $clean);

        if (!$hasMedKeyword) {
            return false;
        }

        // Cari apakah ada nama santri dari database yang disebutkan di dalam pesan
        $matchedSantri = $this->findSantriInMessage($clean);
        return $matchedSantri !== null;
    }

    /**
     * Helper untuk mencocokkan nama santri di database dengan teks pesan
     */
    public function findSantriInMessage(string $message): ?array
    {
        $clean = mb_strtolower($message);

        try {
            $santriModel = new \App\Models\SantriModel();
            $allSantri = $santriModel->select('santri.*, kamar.nama_kamar')
                ->join('kamar', 'kamar.id = santri.kamar_id', 'left')
                ->findAll();

            // 1. Cek nama lengkap terlebih dahulu (paling spesifik)
            foreach ($allSantri as $s) {
                $namaLower = mb_strtolower($s['nama']);
                if (mb_stripos($clean, $namaLower) !== false) {
                    return $s;
                }
            }

            // 2. Cek kombinasi kata nama (misal: "zaidan akbar", "zaidan", "farhan", "nayla", "bilal")
            foreach ($allSantri as $s) {
                $parts = explode(' ', mb_strtolower($s['nama']));
                foreach ($parts as $part) {
                    if (mb_strlen($part) >= 4 && !in_array($part, ['muhammad', 'ahmad', 'putra', 'putri', 'binti'])) {
                        if (preg_match('/\b' . preg_quote($part, '/') . '\b/iu', $clean)) {
                            return $s;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gagal mencocokkan nama santri: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Menghasilkan jawaban detail obat yang diberikan oleh dokter untuk santri tertentu dari database
     */
    public function getSantriMedicationResponse(string $userMessage, array $userContext = []): string
    {
        $santri = $this->findSantriInMessage($userMessage);

        if (!$santri) {
            return "Mohon maaf, nama santri tersebut tidak ditemukan di database SIMKES Santri. Silakan pastikan nama santri sesuai data pondok.";
        }

        $ctx = $this->getUserContext($userContext);
        $isPengawasKamar = !empty($ctx['is_pengawas_kamar']);
        $allowedKamarIds = $isPengawasKamar ? ($ctx['kamar_ids'] ?? []) : null;
        $kamarNames = $ctx['kamar_names'] ?? [];
        $kamarLabel = !empty($kamarNames) ? implode(', ', $kamarNames) : 'kamar binaan Anda';

        // Pemeriksaan hak akses kamar (Anti-IDOR)
        if ($isPengawasKamar) {
            $santriKamarId = (int) ($santri['kamar_id'] ?? 0);
            if (!empty($allowedKamarIds) && !in_array($santriKamarId, $allowedKamarIds)) {
                $kamarSantri = $santri['nama_kamar'] ?: 'kamar lain';
                return "Mohon maaf, Anda hanya berwenang memantau santri di kamar binaan Anda ({$kamarLabel}). Santri **{$santri['nama']}** berada di {$kamarSantri}.";
            }
        }

        try {
            $db = \Config\Database::connect();

            // 1. Ambil laporan kesehatan santri terbaru
            $laporan = $db->table('laporan_kesehatan')
                ->where('santri_id', $santri['id'])
                ->orderBy('id', 'DESC')
                ->get()->getRowArray();

            $kamarDisplay = $santri['nama_kamar'] ?: 'Kamar belum ditentukan';

            if (!$laporan) {
                return <<<RESP
Berdasarkan database SIMKES Santri:
Santri: **{$santri['nama']}** (NIS: {$santri['nis']} &bull; {$kamarDisplay})
- **Status:** Sehat Wal Afiat
- **Keterangan Obat:** Tidak ada riwayat sakit atau catatan pemberian obat dari dokter di Poskestren.
RESP;
            }

            // 2. Ambil pemeriksaan dokter terkait laporan
            $pemeriksaan = $db->table('pemeriksaan')
                ->select('pemeriksaan.*, users.nama as nama_dokter')
                ->join('users', 'users.id = pemeriksaan.pemeriksa_id', 'left')
                ->where('laporan_id', $laporan['id'])
                ->orderBy('pemeriksaan.id', 'DESC')
                ->get()->getRowArray();

            if (!$pemeriksaan) {
                $tglSakit = date('d F Y', strtotime($laporan['tanggal_mulai_sakit']));
                $gejala = $laporan['gejala'] ?: 'Tidak ada catatan gejala.';
                $status = $laporan['status'];

                return <<<RESP
Berdasarkan database SIMKES Santri:
Santri: **{$santri['nama']}** (NIS: {$santri['nis']} &bull; {$kamarDisplay})
- **Status Saat Ini:** {$status}
- **Gejala/Keluhan:** {$gejala}
- **Tanggal Mulai Sakit:** {$tglSakit}
- **Keterangan Obat:** Saat ini **belum ada resep obat yang diberikan dokter**, karena santri baru dilaporkan sakit dan masih dalam antrean/menunggu pemeriksaan klinis tenaga medis di Poskestren.
RESP;
            }

            // 3. Ambil daftar obat yang diresepkan oleh dokter
            $resepList = $db->table('pemberian_obat')
                ->select('pemberian_obat.*, obat.nama_obat, obat.satuan, obat.informasi_penggunaan')
                ->join('obat', 'obat.id = pemberian_obat.obat_id', 'left')
                ->where('pemeriksaan_id', $pemeriksaan['id'])
                ->get()->getResultArray();

            $namaDokter  = $pemeriksaan['nama_dokter'] ?: 'Dokter Poskestren';
            $tglPeriksa  = date('d F Y', strtotime($pemeriksaan['tanggal_periksa']));
            $diagnosis   = $pemeriksaan['diagnosis_atau_dugaan'] ?: '-';
            $tindakan    = $pemeriksaan['tindakan'] ?: '-';
            $catatanMed  = $pemeriksaan['catatan'] ?: '-';

            if (empty($resepList)) {
                return <<<RESP
Berdasarkan rekam medis pemeriksaan dokter di Poskestren SIMKES Santri:
Santri: **{$santri['nama']}** (NIS: {$santri['nis']} &bull; {$kamarDisplay})
- **Dokter Pemeriksa:** {$namaDokter}
- **Tanggal Pemeriksaan:** {$tglPeriksa}
- **Diagnosis Dokter:** {$diagnosis}
- **Tindakan:** {$tindakan}
- **Keterangan Obat:** Dokter tidak meresepkan obat minum (hanya tindakan observasi/perawatan medis).
RESP;
            }

            $output = <<<RESP
Berdasarkan rekam medis pemeriksaan dokter di Poskestren SIMKES Santri:
Santri: **{$santri['nama']}** (NIS: {$santri['nis']} &bull; {$kamarDisplay})
- **Dokter Pemeriksa:** {$namaDokter}
- **Tanggal Pemeriksaan:** {$tglPeriksa}
- **Diagnosis Dokter:** {$diagnosis}

**Obat yang Diberikan oleh Dokter:**

RESP;

            $no = 1;
            foreach ($resepList as $r) {
                $namaObat   = $r['nama_obat'];
                $satuan     = $r['satuan'] ?: 'Unit';
                $dosis      = $r['dosis'];
                $aturan     = $r['aturan_pakai'];
                $durasi     = !empty($r['durasi']) ? $r['durasi'] : 'Sesuai anjuran';
                $instruksi  = !empty($r['instruksi_petugas']) ? $r['instruksi_petugas'] : 'Minum sesuai aturan pakai';

                $output .= "{$no}. **{$namaObat}** ({$satuan})\n";
                $output .= "   - **Dosis:** {$dosis}\n";
                $output .= "   - **Aturan Pakai:** {$aturan}\n";
                $output .= "   - **Durasi:** {$durasi}\n";
                $output .= "   - **Instruksi Dokter:** {$instruksi}\n";
                $no++;
            }

            $output .= "\n**Tindakan & Catatan Dokter:**\n";
            $output .= "- **Tindakan:** {$tindakan}\n";
            $output .= "- **Catatan Medis:** {$catatanMed}\n";

            return $output;

        } catch (\Throwable $e) {
            log_message('error', 'Gagal membaca medikasi santri untuk AI: ' . $e->getMessage());
            return "Mohon maaf, terjadi kendala saat membaca rekam medis obat santri dari database.";
        }
    }

    // ============================================================
    // UNIVERSAL DATABASE QUERY ENGINE
    // Membaca semua tabel SIMKES sesuai intent pertanyaan
    // ============================================================

    /**
     * Deteksi pertanyaan yang berkaitan dengan data SIMKES secara umum
     */
    public function isUniversalDatabaseQuestion(string $message): bool
    {
        $clean = mb_strtolower(trim($message));

        // ── Exclusion guard: jangan tangkap pertanyaan edukatif medis umum ──────────
        // Jika tidak mengandung kata konteks SIMKES/data/santri/pondok, lewatkan ke
        // mock/health engine agar disclaimer medis tetap tercantum.
        $hasDataContext = (bool) preg_match(
            '/\b(santri|siswa|data|database|simkes|rekam|laporan|pesantren|pondok|tampilkan|lihat\s+data|cek\s+data)\b/iu',
            $clean
        );

        // Pertanyaan murni edukatif medis (tanpa konteks data SIMKES) → skip
        $isPureHealthEducation = !$hasDataContext && (bool) preg_match(
            '/\b(apa\s+arti|artinya|istilah|definisi|pengertian|kenapa|mengapa|bagaimana|apa\s+itu|gejala|penyebab|cara\s+(mengatasi|mengobati|mencegah)|pertolongan\s+pertama|tanda\s+bahaya|red\s+flags|suhu\s+normal|suhu\s+tubuh|demam|febris)\b/iu',
            $clean
        );
        if ($isPureHealthEducation) return false;

        // Pertanyaan "diagnosis pasti X" tanpa konteks data → skip ke health engine
        $isPureDiagnosisQuestion = !$hasDataContext && (bool) preg_match(
            '/\b(apakah|apa|diagnos[ia]|gejala|penyakit|sakit)\b/iu',
            $clean
        ) && !(bool) preg_match(
            '/\b(tampilkan|lihat|cek|data|database|rekam|riwayat\s+santri|laporan)\b/iu',
            $clean
        );
        if ($isPureDiagnosisQuestion) return false;
        // ─────────────────────────────────────────────────────────────────────────────

        $patterns = [
            '/\b(tampilkan|lihat|cek|ambil|berikan)\s+(data\s+)?(pemeriksaan|rekam\s+medis|riwayat)\b/iu',
            '/\b(data|rekap)\s+pemeriksaan\b/iu',
            '/\b(rekam\s+medis|riwayat\s+medis)\b/iu',
            '/\b(riwayat\s+(status|kesehatan|sakit)|histori\s+kesehatan|log\s+status)\b/iu',
            '/\b(pemberian\s+obat|resep\s+(yang\s+)?(sudah|pernah|telah)|obat\s+yang\s+(sudah|pernah|telah)\s+diberikan)\b/iu',
            '/\b(nis|nomor\s+induk|kelas|kontak\s+wali|wali\s+santri|no\.\s*hp|nomor\s+hp)\b/iu',
            '/\b(audit|log\s+aktivitas|aktivitas\s+(sistem|pengguna)|histori\s+(login|aktivitas))\b/iu',
            '/\b(akun|pengguna|user)\s+(yang\s+)?(ada|terdaftar|aktif|nonaktif)\b/iu',
            '/\b(berapa|ada)\s+(akun|pengguna|user)\b/iu',
            '/\b(info|informasi|detail|data)\s+(santri|siswa)\b/iu',
            '/\b(rekap|ringkasan|rangkuman|summary)\s+(data|semua|seluruh|lengkap)\b/iu',
            '/\b(data\s+lengkap|semua\s+data|seluruh\s+data)\b/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $clean)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Jawaban universal dari semua tabel SIMKES sesuai intent pertanyaan
     */
    public function getUniversalDatabaseResponse(string $userMessage, array $userContext = []): string
    {
        $clean = mb_strtolower(trim($userMessage));
        $ctx   = $this->getUserContext($userContext);
        $isPengawasKamar = !empty($ctx['is_pengawas_kamar']);
        $allowedKamarIds = $isPengawasKamar ? ($ctx['kamar_ids'] ?? []) : null;

        try {
            $db = \Config\Database::connect();

            // Routing "tampilkan data pemeriksaan" / "rekap pemeriksaan dokter"
            if (preg_match('/\b(tampilkan|lihat|cek|ambil|berikan|data|rekap)\s+(data\s+)?(pemeriksaan|rekam\s+medis)\b/iu', $clean)
                || preg_match('/\bpemeriksaan\s+dokter\b/iu', $clean)) {
                return $this->_queryPemeriksaanData($db, $allowedKamarIds);
            }
            // Routing "riwayat status kesehatan" — harus ada kata "tampilkan"/"riwayat" bersama "status"
            if (preg_match('/\b(tampilkan|lihat|cek|riwayat)\s+(riwayat\s+)?(status|kesehatan)\b/iu', $clean)
                || preg_match('/\b(riwayat\s+(status|kesehatan|sakit)|histori\s+kesehatan|log\s+status)\b/iu', $clean)) {
                return $this->_queryRiwayatStatus($db, $allowedKamarIds);
            }
            if (preg_match('/\b(pemberian\s+obat|resep\s+(yang\s+)?(sudah|pernah|telah)|obat\s+yang\s+(sudah|pernah|telah)\s+diberikan)\b/iu', $clean)) {
                return $this->_querySemuaPemberianObat($db, $allowedKamarIds);
            }
            if (preg_match('/\b(nis|nomor\s+induk|kelas|kontak\s+wali|wali\s+santri|no\.\s*hp|nomor\s+hp)\b/iu', $clean)) {
                return $this->_querySantriDetail($db, $allowedKamarIds);
            }
            if (preg_match('/\b(audit|log\s+aktivitas|aktivitas\s+(sistem|pengguna)|histori\s+(login|aktivitas))\b/iu', $clean)) {
                return $this->_queryAuditLog($db);
            }
            if (preg_match('/\b(akun|pengguna|user)\b/iu', $clean)) {
                return $this->_queryUsersData($db);
            }
            return $this->_queryRekapLengkap($db, $allowedKamarIds);

        } catch (\Throwable $e) {
            log_message('error', '[AI Universal DB] ' . $e->getMessage());
            return "Mohon maaf, terjadi kendala saat membaca data dari database SIMKES.";
        }
    }

    private function _queryPemeriksaanData($db, ?array $allowedKamarIds): string
    {
        $sql = "
            SELECT p.tanggal_periksa, p.diagnosis_atau_dugaan, p.tindakan, p.catatan,
                   s.nama as nama_santri, s.nis, k.nama_kamar, u.nama as nama_dokter
            FROM pemeriksaan p
            JOIN laporan_kesehatan lk ON lk.id = p.laporan_id
            JOIN santri s ON s.id = lk.santri_id
            LEFT JOIN kamar k ON k.id = s.kamar_id
            LEFT JOIN users u ON u.id = p.pemeriksa_id
        ";
        $params = [];
        if ($allowedKamarIds !== null) {
            if (empty($allowedKamarIds)) return "Tidak ada data pemeriksaan di kamar binaan Anda.";
            $phs = implode(',', array_fill(0, count($allowedKamarIds), '?'));
            $sql .= " WHERE s.kamar_id IN ($phs)";
            $params = array_values($allowedKamarIds);
        }
        $sql .= " ORDER BY p.tanggal_periksa DESC";
        $rows = $db->query($sql, $params)->getResultArray();

        if (empty($rows)) return "Berdasarkan database SIMKES Santri, belum ada data pemeriksaan tercatat.";

        $total = count($rows);
        $out = "Berdasarkan database SIMKES Santri, tercatat **{$total} pemeriksaan dokter**:\n\n";
        foreach ($rows as $i => $r) {
            $tgl = date('d M Y', strtotime($r['tanggal_periksa']));
            $out .= ($i + 1) . ". **{$r['nama_santri']}** (NIS: {$r['nis']} • {$r['nama_kamar']})\n";
            $out .= "   - Tanggal: {$tgl} • Dokter: {$r['nama_dokter']}\n";
            $out .= "   - Diagnosis: {$r['diagnosis_atau_dugaan']}\n";
            if (!empty($r['tindakan'])) $out .= "   - Tindakan: {$r['tindakan']}\n";
            if (!empty($r['catatan']))  $out .= "   - Catatan: {$r['catatan']}\n";
        }
        return $out;
    }

    private function _queryRiwayatStatus($db, ?array $allowedKamarIds): string
    {
        $sql = "
            SELECT rs.status, rs.tanggal, rs.catatan,
                   s.nama as nama_santri, k.nama_kamar, u.nama as nama_petugas
            FROM riwayat_status rs
            JOIN laporan_kesehatan lk ON lk.id = rs.laporan_id
            JOIN santri s ON s.id = lk.santri_id
            LEFT JOIN kamar k ON k.id = s.kamar_id
            LEFT JOIN users u ON u.id = rs.pengguna_id
        ";
        $params = [];
        if ($allowedKamarIds !== null) {
            if (empty($allowedKamarIds)) return "Tidak ada riwayat status di kamar binaan Anda.";
            $phs = implode(',', array_fill(0, count($allowedKamarIds), '?'));
            $sql .= " WHERE s.kamar_id IN ($phs)";
            $params = array_values($allowedKamarIds);
        }
        $sql .= " ORDER BY rs.tanggal DESC LIMIT 20";
        $rows = $db->query($sql, $params)->getResultArray();

        if (empty($rows)) return "Berdasarkan database SIMKES Santri, belum ada riwayat perubahan status kesehatan.";

        $out = "Berdasarkan database SIMKES Santri, riwayat status kesehatan santri (20 terbaru):\n\n";
        foreach ($rows as $i => $r) {
            $tgl = date('d M Y H:i', strtotime($r['tanggal']));
            $out .= ($i + 1) . ". **{$r['nama_santri']}** ({$r['nama_kamar']}) → **Status: {$r['status']}**\n";
            $out .= "   - Waktu: {$tgl} • Petugas: {$r['nama_petugas']}\n";
            if (!empty($r['catatan'])) $out .= "   - Catatan: {$r['catatan']}\n";
        }
        return $out;
    }

    private function _querySemuaPemberianObat($db, ?array $allowedKamarIds): string
    {
        $sql = "
            SELECT po.dosis, po.aturan_pakai, po.durasi, po.instruksi_petugas,
                   o.nama_obat, o.satuan,
                   s.nama as nama_santri, s.nis, k.nama_kamar,
                   u.nama as nama_dokter, p.tanggal_periksa
            FROM pemberian_obat po
            JOIN obat o ON o.id = po.obat_id
            JOIN pemeriksaan p ON p.id = po.pemeriksaan_id
            JOIN laporan_kesehatan lk ON lk.id = p.laporan_id
            JOIN santri s ON s.id = lk.santri_id
            LEFT JOIN kamar k ON k.id = s.kamar_id
            LEFT JOIN users u ON u.id = p.pemeriksa_id
        ";
        $params = [];
        if ($allowedKamarIds !== null) {
            if (empty($allowedKamarIds)) return "Tidak ada data pemberian obat di kamar binaan Anda.";
            $phs = implode(',', array_fill(0, count($allowedKamarIds), '?'));
            $sql .= " WHERE s.kamar_id IN ($phs)";
            $params = array_values($allowedKamarIds);
        }
        $sql .= " ORDER BY p.tanggal_periksa DESC";
        $rows = $db->query($sql, $params)->getResultArray();

        if (empty($rows)) return "Berdasarkan database SIMKES Santri, belum ada catatan pemberian obat.";

        $total = count($rows);
        $out = "Berdasarkan database SIMKES Santri, tercatat **{$total} resep pemberian obat**:\n\n";
        foreach ($rows as $i => $r) {
            $tgl = date('d M Y', strtotime($r['tanggal_periksa']));
            $out .= ($i + 1) . ". **{$r['nama_santri']}** ({$r['nama_kamar']}) — {$tgl}\n";
            $out .= "   - Obat: **{$r['nama_obat']}** ({$r['satuan']}) • Dosis: {$r['dosis']}\n";
            $out .= "   - Aturan: {$r['aturan_pakai']} • Durasi: " . ($r['durasi'] ?: 'Sesuai anjuran') . "\n";
            $out .= "   - Dokter: {$r['nama_dokter']}\n";
        }
        return $out;
    }

    private function _querySantriDetail($db, ?array $allowedKamarIds): string
    {
        $builder = $db->table('santri')
            ->select('santri.nis, santri.nama, santri.jenis_kelamin, santri.kelas, santri.kontak_wali, kamar.nama_kamar')
            ->join('kamar', 'kamar.id = santri.kamar_id', 'left')
            ->orderBy('santri.nama', 'ASC');

        if ($allowedKamarIds !== null) {
            if (empty($allowedKamarIds)) return "Tidak ada data santri di kamar binaan Anda.";
            $builder->whereIn('santri.kamar_id', $allowedKamarIds);
        }
        $rows = $builder->get()->getResultArray();

        if (empty($rows)) return "Berdasarkan database SIMKES Santri, belum ada data santri terdaftar.";

        $total = count($rows);
        $out = "Berdasarkan database SIMKES Santri, data lengkap **{$total} santri**:\n\n";
        foreach ($rows as $i => $r) {
            $out .= ($i + 1) . ". **{$r['nama']}**\n";
            $out .= "   - NIS: {$r['nis']} • Kelas: " . ($r['kelas'] ?: '-') . " • {$r['jenis_kelamin']}\n";
            $out .= "   - Kamar: {$r['nama_kamar']} • Kontak Wali: " . ($r['kontak_wali'] ?: 'Belum diisi') . "\n";
        }
        return $out;
    }

    private function _queryAuditLog($db): string
    {
        $totalLog = $db->table('audit_log')->countAllResults();
        $rows = $db->table('audit_log')
            ->select('audit_log.aksi, audit_log.waktu, audit_log.detail_perubahan, users.nama as nama_pengguna')
            ->join('users', 'users.id = audit_log.pengguna_id', 'left')
            ->orderBy('audit_log.waktu', 'DESC')
            ->limit(10)
            ->get()->getResultArray();

        if (empty($rows)) return "Berdasarkan database SIMKES Santri, belum ada catatan aktivitas di audit log.";

        $out = "Berdasarkan database SIMKES Santri, total **{$totalLog} catatan aktivitas**. 10 terbaru:\n\n";
        foreach ($rows as $i => $r) {
            $tgl = date('d M Y H:i', strtotime($r['waktu']));
            $out .= ($i + 1) . ". **{$r['aksi']}** — {$r['nama_pengguna']} ({$tgl})\n";
            if (!empty($r['detail_perubahan'])) {
                $out .= "   " . mb_substr($r['detail_perubahan'], 0, 120) . "\n";
            }
        }
        return $out;
    }

    private function _queryUsersData($db): string
    {
        $rows = $db->table('users')
            ->select('nama, role, status')
            ->whereNotIn('role', ['Santri'])
            ->orderBy('role', 'ASC')
            ->orderBy('nama', 'ASC')
            ->get()->getResultArray();

        if (empty($rows)) return "Berdasarkan database SIMKES Santri, belum ada data pengguna terdaftar.";

        $total  = count($rows);
        $aktif  = count(array_filter($rows, fn($r) => $r['status'] === 'Aktif'));
        $out = "Berdasarkan database SIMKES Santri, terdapat **{$total} akun pengguna** ({$aktif} Aktif):\n\n";

        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r['role']][] = $r;
        }
        foreach ($grouped as $role => $users) {
            $out .= "**{$role}** (" . count($users) . " orang):\n";
            foreach ($users as $u) {
                $badge = $u['status'] === 'Aktif' ? '✅' : '❌';
                $statusText = $u['status'] === 'Aktif' ? 'Aktif' : 'Nonaktif';
                $out .= "  {$badge} {$u['nama']} ({$statusText})\n";
            }
        }
        return $out;
    }

    private function _queryRekapLengkap($db, ?array $allowedKamarIds): string
    {
        $totalSantri = $allowedKamarIds !== null
            ? ($allowedKamarIds ? $db->table('santri')->whereIn('kamar_id', $allowedKamarIds)->countAllResults() : 0)
            : $db->table('santri')->countAllResults();

        $totalObat    = $db->table('obat')->countAllResults();
        $totalPeriksa = $db->table('pemeriksaan')->countAllResults();
        $totalResep   = $db->table('pemberian_obat')->countAllResults();
        $totalLaporan = $db->table('laporan_kesehatan')->countAllResults();
        $totalRiwayat = $db->table('riwayat_status')->countAllResults();
        $totalKamar   = $db->table('kamar')->countAllResults();
        $totalDokter  = $db->table('users')->where('role', 'Dokter')->where('status', 'Aktif')->countAllResults();
        $totalPengawas = $db->table('users')->where('role', 'Pengawas Kamar')->where('status', 'Aktif')->countAllResults();
        $totalAudit   = $db->table('audit_log')->countAllResults();

        $laporanModel = new \App\Models\LaporanKesehatanModel();
        $stats = $laporanModel->getDashboardStats($allowedKamarIds);

        $dalamPeriksa   = $stats['dalam_pemeriksaan'] ?? 0;
        $dalamRawat     = $stats['dalam_perawatan'] ?? 0;
        $sembuh         = $stats['sembuh'] ?? 0;
        $dirujuk        = $stats['dirujuk'] ?? 0;

        $scope = $allowedKamarIds !== null ? ' (kamar binaan Anda)' : ' (seluruh pondok)';

        return <<<RESP
Berdasarkan database SIMKES Santri, berikut rekap lengkap seluruh data{$scope}:

**👥 Santri & Asrama:**
- Total Santri Terdaftar: **{$totalSantri} orang**
- Total Kamar Asrama: **{$totalKamar} kamar**

**🏥 Data Kesehatan:**
- Total Laporan Sakit Masuk: **{$totalLaporan} laporan**
- Dalam Pemeriksaan: **{$dalamPeriksa} santri**
- Dalam Perawatan: **{$dalamRawat} santri**
- Sudah Sembuh: **{$sembuh} santri**
- Dirujuk RS/Faskes: **{$dirujuk} santri**
- Total Pemeriksaan Dokter: **{$totalPeriksa} pemeriksaan**
- Total Resep Obat Diberikan: **{$totalResep} resep**
- Total Riwayat Perubahan Status: **{$totalRiwayat} catatan**

**💊 Inventaris Obat:**
- Jenis Obat Tersedia: **{$totalObat} jenis**

**👨‍⚕️ SDM Medis:**
- Dokter / Petugas Aktif: **{$totalDokter} orang**
- Pengawas Kamar Aktif: **{$totalPengawas} orang**

**🔒 Sistem:**
- Total Catatan Audit Log: **{$totalAudit} aktivitas**
RESP;
    }

    /**
     * Resolusi konteks pengguna yang aman dari argumen atau session
     */
    public function getUserContext(?array $override = null): array
    {
        $context = $override ?? [];
        if (empty($context['user_id']) && function_exists('session') && session()->get('user_id')) {
            $context['user_id']        = session()->get('user_id');
            $context['nama']           = session()->get('nama');
            $context['email']          = session()->get('email');
            $context['role']           = session()->get('role');
            $context['role_canonical'] = session()->get('role_canonical');
            $context['role_display']   = session()->get('role_display');
        }

        $userId = $context['user_id'] ?? null;
        $role   = $context['role'] ?? ($context['role_canonical'] ?? '');
        $isPengawasKamar = in_array(strtolower((string)$role), ['pengawas_kamar', 'pengawas kamar']);
        $context['is_pengawas_kamar'] = $isPengawasKamar;

        if ($userId && $isPengawasKamar) {
            try {
                $kamarModel = new \App\Models\KamarModel();
                if (empty($context['kamar_ids']) || empty($context['kamar_names'])) {
                    $kamarList = $kamarModel->where('pengawas_id', (int) $userId)->findAll();
                    if (empty($context['kamar_ids'])) {
                        $context['kamar_ids'] = array_map('intval', array_column($kamarList, 'id'));
                    }
                    if (empty($context['kamar_names'])) {
                        $context['kamar_names'] = array_column($kamarList, 'nama_kamar');
                    }
                }
            } catch (\Throwable $e) {
                if (!isset($context['kamar_ids'])) {
                    $context['kamar_ids'] = [];
                }
                if (!isset($context['kamar_names'])) {
                    $context['kamar_names'] = [];
                }
            }
        }

        return $context;
    }

    /**
     * Memeriksa apakah pesan pengguna adalah pertanyaan mengenai identitas asisten
     */
    public function isIdentityQuestion(string $message): bool
    {
        $clean = mb_strtolower(trim($message));

        $patterns = [
            '/\b(siapa|siapakah)\s+(anda|kamu|dirimu|antum|ente|asisten|bot)\b/iu',
            '/\b(anda|kamu|dirimu|asisten)\s+(ini\s+)?(siapa|siapakah)\b/iu',
            '/\b(kamu|anda)\s+ini\s+siapa\b/iu',
            '/\bkenalan\s+(dong|yuk)?\b/iu',
            '/\bapa\s+peran(mu| anda)?\b/iu',
            '/\bkamu\s+robot\s+apa\b/iu',
            '/^(siapa|siapakah)\s+(kamu|anda)\??$/iu',
            '/^(kamu|anda)\s+siapa\??$/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $clean)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Respon resmi untuk pertanyaan identitas asisten (langsung to the point, bebas iklan)
     */
    public function getIdentityResponse(): string
    {
        return <<<RESP
Saya adalah Asisten Kesehatan SIMKES Santri di Pondok Pesantren.

Tugas saya adalah memberikan edukasi dan informasi seputar kesehatan santri, membantu memahami istilah medis umum, memberikan panduan pertolongan pertama dasar, serta mengingatkan tanda-tanda bahaya (*red flags*) kesehatan santri.

*Catatan: Saya hanya melayani pertanyaan di bidang kesehatan. Informasi ini bersifat edukatif dan bukan pengganti diagnosis resmi tenaga medis Poskestren.*
RESP;
    }

    /**
     * Memeriksa apakah pesan pengguna adalah salam pembuka umum
     */
    public function isGreeting(string $message): bool
    {
        $clean = mb_strtolower(trim($message));
        return (bool) preg_match('/^(assalamu(\'|a)laikum|assalamualaykum|halo|hai|hi|selamat\s+(pagi|siang|sore|malam))\b/iu', $clean);
    }

    /**
     * Respon ramah salam pembuka yang langsung mengarahkan ke topik kesehatan
     */
    public function getGreetingResponse(): string
    {
        return <<<RESP
Wa'alaikumussalam wr. wb. / Salam sehat.

Saya Asisten Kesehatan SIMKES Santri. Silakan sampaikan keluhan kesehatan santri, pertanyaan pertolongan pertama, atau istilah medis yang ingin Anda konsultasikan.
RESP;
    }

    /**
     * Memeriksa apakah pesan pengguna berkaitan dengan kesehatan, medis, anatomi, atau sanitasi asrama
     */
    public function isHealthRelated(string $message): bool
    {
        $clean = mb_strtolower(trim($message));

        // 1. Deteksi pertanyaan yang eksplisit tentang materi luar non-kesehatan
        $explicitOffTopicPatterns = [
            '/\b(koding|coding|buatkan\s+(script|kode|fungsi|program)|python|javascript|php\s+script|source\s*code|html|css|sql\s+query|database\s+mysql)\b/iu',
            '/\b(hitunglah|berapa\s+hasil\s+dari|\d+\s*[\+\-\*\/]\s*\d+|rumus\s+matematika|kalkulus|integral|turunan)\b/iu',
            '/\b(siapa\s+presiden|pemilu\s+\d+|partai\s+politik|pilpres|pilkada|menteri\s+pertahanan)\b/iu',
            '/\b(ceritakan\s+lelucon|buatkan\s+pantun|cerita\s+lucu|dongeng|stand\s*up\s*comedy)\b/iu',
            '/\b(rekomendasi\s+game|mobile\s+legends|free\s+fire|pubg|chord\s+gitar|lirik\s+lagu)\b/iu',
            '/\b(harga\s+emas|saham|kripto|bitcoin|forex|investasi\s+bodong)\b/iu',
        ];

        foreach ($explicitOffTopicPatterns as $pattern) {
            if (preg_match($pattern, $clean)) {
                return false;
            }
        }

        // 2. Daftar kata kunci kesehatan, gejala klinis, penyakit, sanitasi, dan anatomi
        $healthKeywords = [
            // Penyakit, Gejala, & Keluhan
            'sakit', 'sehat', 'kesehatan', 'keluhan', 'gejala', 'penyakit', 'nyeri', 'demam', 'panas',
            'febris', 'dingin', 'gigil', 'pusing', 'pening', 'puyeng', 'kepala', 'cephalgia', 'sefalgi',
            'migrain', 'migren', 'batuk', 'pilek', 'flu', 'influenza', 'bersin', 'tenggorokan', 'radang',
            'faringitis', 'tonsilitis', 'amandel', 'serak', 'sesak', 'napas', 'nafas', 'asma', 'bengek',
            'ispa', 'paru', 'perut', 'lambung', 'maag', 'mag', 'mual', 'muntah', 'enek', 'dispepsia',
            'diare', 'mencret', 'murus', 'buang air', 'bab', 'tinja', 'feses', 'sembelit', 'konstipasi',
            'kolik', 'usus', 'appendisitis', 'gatal', 'kulit', 'skabies', 'scabies', 'gudik', 'gudikan',
            'kudis', 'kurap', 'kadas', 'panu', 'eksim', 'dermatitis', 'pruritus', 'ruam', 'bintik',
            'bentol', 'biduran', 'kaligata', 'alergi', 'luka', 'lecet', 'gores', 'sayat', 'darah',
            'berdarah', 'mimisan', 'borok', 'bisul', 'jerawat', 'cantengan', 'koreng', 'nanah', 'infeksi',
            'bengkak', 'lebam', 'memar', 'benjol', 'keseleo', 'terkilir', 'cedera', 'patah tulang',
            'fraktur', 'dislokasi', 'kram', 'kaku', 'pegal', 'linu', 'sendi', 'otot', 'tulang',
            'mata', 'belekan', 'konjungtivitis', 'telinga', 'congek', 'otitis', 'gigi', 'gusi',
            'sariawan', 'lemas', 'lemah', 'letih', 'lesu', 'pucat', 'pingsan', 'kejang', 'koma',
            'kesadaran', 'dehidrasi', 'haus', 'cacar', 'campak', 'gondongan', 'varicella', 'morbili',
            'tipes', 'tifoid', 'tbc', 'tuberkulosis', 'dbd', 'dengue', 'malaria', 'kolera', 'imun',
            'imunitas', 'virus', 'bakteri', 'kuman', 'jamur', 'parasit', 'cacingan',

            // Layanan Medis, Fasilitas, & Tenaga Kesehatan
            'medis', 'klinis', 'poskestren', 'dokter', 'perawat', 'bidan', 'nakes', 'tenaga kesehatan',
            'puskesmas', 'faskes', 'rumah sakit', 'rs', 'klinik', 'ugd', 'igd', 'ambulans', 'ambulan',
            'rujuk', 'rujukan', 'rawat', 'inap', 'observasi', 'isolasi', 'karantina', 'periksa',
            'pemeriksaan', 'anamnesis', 'diagnosa', 'diagnosis', 'prognosis', 'rekam medis',

            // Farmakologi & Tindakan Pertolongan Pertama
            'obat', 'resep', 'dosis', 'salep', 'sirup', 'tablet', 'kapsul', 'puyer', 'antibiotik',
            'paracetamol', 'amoxicillin', 'antiseptik', 'betadine', 'iodine', 'oralit', 'vitamin',
            'pertolongan pertama', 'p3k', 'kompres', 'balut', 'perban', 'plester', 'kassa', 'bidai',
            'rice', 'suhu', 'termometer', 'tensi', 'tekanan darah', 'vital sign', 'red flags',
            'tanda bahaya', 'darurat', 'penanganan',

            // Sanitasi Lingkungan Asrama & Gizi
            'sanitasi', 'higienis', 'kebersihan', 'phbs', 'cuci tangan', 'sabun', 'asrama', 'kamar santri',
            'kasur', 'sprei', 'kutu kasur', 'jemur', 'ventilasi', 'sirkulasi udara', 'air bersih',
            'gizi', 'nutrisi', 'diet', 'makanan sehat', 'daya tahan tubuh',
        ];

        foreach ($healthKeywords as $keyword) {
            if (mb_stripos($clean, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Respon standar penolakan pertanyaan di luar materi kesehatan (tegas, sopan, tanpa iklan)
     */
    public function getOffTopicRejectionResponse(): string
    {
        return <<<RESP
Mohon maaf, saya adalah Asisten Kesehatan SIMKES Santri.

Saya hanya melayani pertanyaan yang berkaitan dengan kesehatan, gejala sakit santri, pertolongan pertama, istilah medis, dan sanitasi lingkungan pondok pesantren.

Pertanyaan di luar topik kesehatan tidak dapat saya proses.
RESP;
    }

    /**
     * Kirim pesan ke API AI atau Engine Simulasi
     *
     * @param string $userMessage Pesan dari pengguna
     * @param array $chatHistory Riwayat chat sebelumnya [['role' => 'user'|'assistant', 'content' => '...']]
     * @return array ['success' => bool, 'message' => string, 'provider' => string, 'model' => string, 'is_mock' => bool, 'error' => ?string]
     */
    public function sendMessage(string $userMessage, array $chatHistory = [], array $userContext = []): array
    {
        // 1. Sanitasi PII dari pesan pengguna
        $sanitizedMessage = $this->sanitizePii($userMessage);

        if (empty($sanitizedMessage)) {
            return [
                'success'  => false,
                'message'  => '',
                'error'    => 'Pesan tidak boleh kosong.',
                'provider' => $this->config->provider,
                'model'    => $this->config->model,
                'is_mock'  => false,
            ];
        }

        // 2. Pertanyaan identitas PENGGUNA yang sedang login ("siapa saya")
        if ($this->isUserIdentityQuestion($sanitizedMessage)) {
            return [
                'success'  => true,
                'message'  => $this->getUserIdentityResponse($userContext),
                'provider' => 'simkes_user_identity_engine',
                'model'    => 'SIMKES-User-Auth',
                'is_mock'  => false,
                'error'    => null,
            ];
        }

        // 3. Pertanyaan identitas ASISTEN ("siapa anda")
        if ($this->isIdentityQuestion($sanitizedMessage)) {
            return [
                'success'  => true,
                'message'  => $this->getIdentityResponse(),
                'provider' => 'simkes_asisten_kesehatan',
                'model'    => 'Asisten-Kesehatan-Santri',
                'is_mock'  => false,
                'error'    => null,
            ];
        }

        // 4. Pertanyaan siapa santri yang sedang sakit / dirawat / sembuh / dirujuk
        if ($this->isSickSantriListQuestion($sanitizedMessage)) {
            return [
                'success'  => true,
                'message'  => $this->getSickSantriListResponse($sanitizedMessage, $userContext),
                'provider' => 'simkes_database_engine',
                'model'    => 'SIMKES-Santri-Sakit-Live',
                'is_mock'  => false,
                'error'    => null,
            ];
        }

        // 4.5. Pertanyaan universal database SIMKES (pemeriksaan, riwayat, rekam medis, audit, akun dll.)
        // Harus sebelum isDatabaseStatsQuestion agar "tampilkan data pemeriksaan/riwayat" tidak salah tangkap
        if ($this->isUniversalDatabaseQuestion($sanitizedMessage)) {
            return [
                'success'  => true,
                'message'  => $this->getUniversalDatabaseResponse($sanitizedMessage, $userContext),
                'provider' => 'simkes_database_engine',
                'model'    => 'SIMKES-Universal-DB',
                'is_mock'  => false,
                'error'    => null,
            ];
        }

        // 5. Pertanyaan statistik real-time database santri sakit, dirawat, sembuh
        if ($this->isDatabaseStatsQuestion($sanitizedMessage)) {
            return [
                'success'  => true,
                'message'  => $this->getDatabaseStatsResponse($sanitizedMessage, $userContext),
                'provider' => 'simkes_database_engine',
                'model'    => 'SIMKES-DB-Live',
                'is_mock'  => false,
                'error'    => null,
            ];
        }

        // 6. Pertanyaan daftar nama santri terdaftar aktif di database
        if ($this->isSantriListQuestion($sanitizedMessage)) {
            return [
                'success'  => true,
                'message'  => $this->getSantriListResponse($sanitizedMessage, $userContext),
                'provider' => 'simkes_database_engine',
                'model'    => 'SIMKES-Santri-List',
                'is_mock'  => false,
                'error'    => null,
            ];
        }

        // 7. Pertanyaan daftar kamar dan asrama
        if ($this->isKamarListQuestion($sanitizedMessage)) {
            return [
                'success'  => true,
                'message'  => $this->getKamarListResponse($userContext),
                'provider' => 'simkes_database_engine',
                'model'    => 'SIMKES-Kamar-List',
                'is_mock'  => false,
                'error'    => null,
            ];
        }

        // 8. Pertanyaan inventaris dan stok obat di Poskestren
        if ($this->isObatListQuestion($sanitizedMessage)) {
            return [
                'success'  => true,
                'message'  => $this->getObatListResponse(),
                'provider' => 'simkes_database_engine',
                'model'    => 'SIMKES-Obat-List',
                'is_mock'  => false,
                'error'    => null,
            ];
        }

        // 9. Pertanyaan daftar/jumlah dokter dan petugas kesehatan
        if ($this->isPetugasListQuestion($sanitizedMessage)) {
            return [
                'success'  => true,
                'message'  => $this->getPetugasListResponse(),
                'provider' => 'simkes_database_engine',
                'model'    => 'SIMKES-Petugas-List',
                'is_mock'  => false,
                'error'    => null,
            ];
        }

        // 10. Pertanyaan daftar/jumlah pengawas kamar
        if ($this->isPengawasListQuestion($sanitizedMessage)) {
            return [
                'success'  => true,
                'message'  => $this->getPengawasListResponse(),
                'provider' => 'simkes_database_engine',
                'model'    => 'SIMKES-Pengawas-List',
                'is_mock'  => false,
                'error'    => null,
            ];
        }

        // 11. Pertanyaan resep/obat yang diberikan dokter untuk santri tertentu dari rekam medis
        if ($this->isSantriMedicationQuestion($sanitizedMessage)) {
            return [
                'success'  => true,
                'message'  => $this->getSantriMedicationResponse($sanitizedMessage, $userContext),
                'provider' => 'simkes_database_engine',
                'model'    => 'SIMKES-Medikasi-Santri',
                'is_mock'  => false,
                'error'    => null,
            ];
        }

        // 5. Salam pembuka langsung dijawab ramah tanpa promosi/iklan
        if ($this->isGreeting($sanitizedMessage)) {
            return [
                'success'  => true,
                'message'  => $this->getGreetingResponse(),
                'provider' => 'simkes_asisten_kesehatan',
                'model'    => 'Asisten-Kesehatan-Santri',
                'is_mock'  => false,
                'error'    => null,
            ];
        }

        // 6. Verifikasi relevansi dengan topik kesehatan (Penolakan materi di luar kesehatan)
        if (!$this->isHealthRelated($sanitizedMessage)) {
            return [
                'success'  => true,
                'message'  => $this->getOffTopicRejectionResponse(),
                'provider' => 'simkes_asisten_kesehatan',
                'model'    => 'Asisten-Kesehatan-Santri',
                'is_mock'  => false,
                'error'    => null,
            ];
        }

        // 7. Evaluasi apakah menggunakan mode Mock atau API eksternal
        $apiKey = trim($this->config->apiKey);
        $useMock = empty($apiKey) || $apiKey === 'mock' || $this->config->provider === 'mock';

        if ($useMock) {
            $mockResponse = $this->generateMockResponse($sanitizedMessage);
            return [
                'success'  => true,
                'message'  => $mockResponse,
                'provider' => 'simkes_mock_knowledge_base',
                'model'    => 'Poskestren-Edu-v1',
                'is_mock'  => true,
                'error'    => null,
            ];
        }

        // 8. Panggil API AI sesuai Provider
        if ($this->config->provider === 'gemini') {
            return $this->callGeminiApi($sanitizedMessage, $chatHistory);
        } elseif ($this->config->provider === 'openai') {
            return $this->callOpenAiApi($sanitizedMessage, $chatHistory);
        }

        // Fallback default ke Gemini
        return $this->callGeminiApi($sanitizedMessage, $chatHistory);
    }

    /**
     * Panggilan ke Google Gemini API (v1beta)
     */
    protected function callGeminiApi(string $sanitizedMessage, array $chatHistory = []): array
    {
        $apiKey = trim($this->config->apiKey);
        $model  = trim($this->config->model) ?: 'gemini-1.5-flash';

        $baseUrl = $this->config->baseUrl ?: 'https://generativelanguage.googleapis.com/v1beta';
        $endpoint = rtrim($baseUrl, '/') . "/models/{$model}:generateContent?key=" . urlencode($apiKey);

        // Susun riwayat percakapan untuk konteks multi-turn
        $contents = [];

        // Tambahkan ringkasan konteks percakapan sebelumnya jika ada
        foreach ($chatHistory as $msg) {
            $role = ($msg['role'] ?? 'user') === 'assistant' ? 'model' : 'user';
            $text = $this->sanitizePii($msg['content'] ?? '');
            if (!empty($text)) {
                $contents[] = [
                    'role'  => $role,
                    'parts' => [['text' => $text]],
                ];
            }
        }

        // Tambahkan prompt pengguna saat ini
        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $sanitizedMessage]],
        ];

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $this->getSystemPrompt()]],
            ],
            'contents'         => $contents,
            'generationConfig' => [
                'temperature'     => 0.4,
                'maxOutputTokens' => $this->config->maxTokens,
            ],
        ];

        $jsonPayload = json_encode($payload);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonPayload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->config->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $responseBody = curl_exec($ch);
        $curlErrno    = curl_errno($ch);
        $curlError    = curl_error($ch);
        $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Tangani kegagalan jaringan & timeout
        if ($curlErrno !== 0) {
            if ($curlErrno === CURLE_OPERATION_TIMEDOUT) {
                return [
                    'success'  => false,
                    'message'  => '',
                    'error'    => "Batas waktu koneksi ke AI habis (Timeout {$this->config->timeout}s). Silakan periksa jaringan server dan coba lagi.",
                    'provider' => 'gemini',
                    'model'    => $model,
                    'is_mock'  => false,
                ];
            }

            return [
                'success'  => false,
                'message'  => '',
                'error'    => 'Tidak dapat terhubung ke server penyedia AI. Silakan periksa koneksi internet server.',
                'provider' => 'gemini',
                'model'    => $model,
                'is_mock'  => false,
            ];
        }

        // Tangani HTTP Error Code secara aman tanpa membocorkan API Key
        if ($httpCode !== 200) {
            $parsed = json_decode($responseBody, true);
            $apiErrorMsg = $parsed['error']['message'] ?? '';

            if ($httpCode === 400) {
                return [
                    'success'  => false,
                    'message'  => '',
                    'error'    => 'Format permintaan ke API AI tidak valid atau model tidak didukung (' . esc($model) . ').',
                    'provider' => 'gemini',
                    'model'    => $model,
                    'is_mock'  => false,
                ];
            } elseif ($httpCode === 401 || $httpCode === 403) {
                return [
                    'success'  => false,
                    'message'  => '',
                    'error'    => 'Otorisasi API Key AI tidak valid atau hak akses ditolak. Harap periksa konfigurasi AI_API_KEY di file .env server.',
                    'provider' => 'gemini',
                    'model'    => $model,
                    'is_mock'  => false,
                ];
            } elseif ($httpCode === 429) {
                return [
                    'success'  => false,
                    'message'  => '',
                    'error'    => 'Batas frekuensi permintaan (Rate Limit) penyedia AI tercapai. Mohon tunggu 1-2 menit sebelum mengirim pertanyaan lagi.',
                    'provider' => 'gemini',
                    'model'    => $model,
                    'is_mock'  => false,
                ];
            } elseif ($httpCode >= 500) {
                return [
                    'success'  => false,
                    'message'  => '',
                    'error'    => 'Server penyedia AI Google sedang mengalami gangguan sementara (HTTP ' . $httpCode . '). Coba beberapa saat lagi.',
                    'provider' => 'gemini',
                    'model'    => $model,
                    'is_mock'  => false,
                ];
            }

            return [
                'success'  => false,
                'message'  => '',
                'error'    => 'Gagal memproses jawaban dari AI (HTTP ' . $httpCode . ').',
                'provider' => 'gemini',
                'model'    => $model,
                'is_mock'  => false,
            ];
        }

        // Parse Response Sukses
        $data = json_decode($responseBody, true);
        $aiText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (empty($aiText)) {
            return [
                'success'  => false,
                'message'  => '',
                'error'    => 'Penyedia AI tidak mengembalikan teks jawaban (mungkin terhalang filter keamanan model).',
                'provider' => 'gemini',
                'model'    => $model,
                'is_mock'  => false,
            ];
        }

        return [
            'success'  => true,
            'message'  => trim($aiText),
            'provider' => 'gemini',
            'model'    => $model,
            'is_mock'  => false,
            'error'    => null,
        ];
    }

    /**
     * Panggilan ke OpenAI-compatible endpoint
     */
    protected function callOpenAiApi(string $sanitizedMessage, array $chatHistory = []): array
    {
        $apiKey  = trim($this->config->apiKey);
        $model   = trim($this->config->model) ?: 'gpt-4o-mini';
        $baseUrl = $this->config->baseUrl ?: 'https://api.openai.com/v1';
        $endpoint = rtrim($baseUrl, '/') . '/chat/completions';

        $messages = [
            ['role' => 'system', 'content' => $this->getSystemPrompt()],
        ];

        foreach ($chatHistory as $msg) {
            $role = ($msg['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user';
            $messages[] = [
                'role'    => $role,
                'content' => $this->sanitizePii($msg['content'] ?? ''),
            ];
        }

        $messages[] = [
            'role'    => 'user',
            'content' => $sanitizedMessage,
        ];

        $payload = [
            'model'       => $model,
            'messages'    => $messages,
            'max_tokens'  => $this->config->maxTokens,
            'temperature' => 0.4,
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->config->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $responseBody = curl_exec($ch);
        $curlErrno    = curl_errno($ch);
        $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlErrno !== 0) {
            return [
                'success'  => false,
                'message'  => '',
                'error'    => 'Gagal terhubung ke penyedia AI OpenAI. Silakan periksa jaringan server.',
                'provider' => 'openai',
                'model'    => $model,
                'is_mock'  => false,
            ];
        }

        if ($httpCode !== 200) {
            return [
                'success'  => false,
                'message'  => '',
                'error'    => 'Respon error dari OpenAI (HTTP ' . $httpCode . '). Harap periksa API Key dan kuota akun.',
                'provider' => 'openai',
                'model'    => $model,
                'is_mock'  => false,
            ];
        }

        $data = json_decode($responseBody, true);
        $aiText = $data['choices'][0]['message']['content'] ?? null;

        return [
            'success'  => true,
            'message'  => trim($aiText ?? 'Tidak ada jawaban dari AI.'),
            'provider' => 'openai',
            'model'    => $model,
            'is_mock'  => false,
            'error'    => null,
        ];
    }

    /**
     * Engine simulasi cerdas terverifikasi untuk mode offline atau saat API Key belum dikonfigurasi
     */
    public function generateMockResponse(string $prompt): string
    {
        $lower = strtolower($prompt);

        // Jika pertanyaan statistik / periksa / rawat dari database
        if ($this->isDatabaseStatsQuestion($prompt)) {
            return $this->getDatabaseStatsResponse($prompt);
        }

        // 1. Kasus Permintaan Resep Obat / Dosis Obat Langsung (Penolakan Medis Etis)
        if (preg_match('/(resep|minta obat|kasih obat|nama obat|dosis|antibiotik|amoxicillin|paracetamol berapa tablet|obat apa)/i', $lower)) {
            return <<<RES
Assalamu'alaikum wr. wb.

**Peringatan Keselamatan Medis Santri:**
Asisten AI **tidak memiliki kewenangan untuk meresepkan obat, menentukan nama obat tertentu, atau menetapkan dosis obat santri**.

Pemberian obat (terutama antibiotik atau obat bertanda lingkaran merah/biru) **wajib berdasarkan hasil pemeriksaan langsung oleh dokter atau tenaga kesehatan berwenang di Poskestren**. Pemberian obat tanpa pemeriksaan langsung berisiko menyebabkan reaksi alergi, resistensi antibiotik, atau komplikasi penyakit.

**Langkah yang Dianjurkan:**
1. Bawa santri ke Poskestren untuk dilakukan pemeriksaan fisik (tanda vital, suhu tubuh, tekanan darah).
2. Jika santri membutuhkan pertolongan pertama sebelum ke poskestren:
   - Berikan air putih hangat yang cukup untuk mencegah dehidrasi.
   - Anjurkan santri untuk beristirahat di kamar santri atau ruang observasi poskestren.
   - Jika demam, gunakan kompres air hangat (bukan air es) di dahi atau lipatan ketiak.

*Catatan: Informasi ini bersifat edukatif dan bukan pengganti diagnosis medis resmi. Segera konsultasikan kondisi santri ke Poskestren Pesantren.*
RES;
        }

        // 2. Permintaan Diagnosis Pasti (Penolakan Diagnosis Definitif)
        if (preg_match('/(diagnosis|sakit apa sebenarnya|apakah saya kena|pasti sakit)/i', $lower)) {
            return <<<RES
Assalamu'alaikum wr. wb.

**Batasan Diagnosis Klinis:**
Asisten AI **tidak dapat menegakkan diagnosis pasti**. Diagnosis medis hanya dapat ditetapkan oleh dokter atau tenaga kesehatan setelah melakukan anamnesis lengkap, pemeriksaan fisik langsung, dan pemeriksaan penunjang (laboratorium/tes darah jika diperlukan).

**Edukasi Gejala Umum:**
Gejala yang dirasakan santri bisa menyerupai beberapa kondisi umum di lingkungan pondok pesantren. Untuk memastikan penyebabnya secara tepat, mohon laporkan keluhan ini melalui menu **Laporan Sakit** di SIMKES Santri agar segera dijadwalkan pemeriksaan klinis oleh tim medis Poskestren.

*Catatan: Informasi ini bersifat edukatif dan bukan pengganti diagnosis medis resmi. Segera konsultasikan kondisi santri ke Poskestren Pesantren.*
RES;
        }

        // 3. Ringkasan Keluhan Santri untuk Dibawa ke Poskestren (Template SOAP Objektif)
        if (preg_match('/(ringkas|rangkum|format laporan|bantu lapor|keluhan santri)/i', $lower)) {
            return <<<RES
Assalamu'alaikum wr. wb.

Berikut adalah **Draf Ringkasan Keluhan Santri** yang terstruktur untuk diserahkan ke Tenaga Medis Poskestren:

---
**RINGKASAN KELUHAN AWAL SANTRI (POSKESTREN)**
- **Identitas Pelapor:** Pengawas Kamar
- **Waktu Lapor:** Hari ini
- **Keluhan Utama:** [Tulis keluhan terberat, misal: Demam dan pusing]
- **Mulai Sakit (Onset):** [Berapa hari/jam yang lalu keluhan mulai muncul]
- **Gejala Penyerta:** [Misal: Batuk kering, mual, nafsu makan menurun, lemas]
- **Suhu Awal (Jika Ada Termometer):** [Contoh: 38.2 °C]
- **Tindakan Mandiri yang Sudah Dilakukan:** [Contoh: Diberi minum air hangat dan istirahat]
- **Riwayat Alergi Santri (Jika Tahu):** [Contoh: Alergi dingin / Tidak ada]
---

**Saran Pengawas:**
Segera input ringkasan di atas ke form **Lapor Santri Sakit** di sistem SIMKES Santri, lalu antarkan santri ke Poskestren untuk pemeriksaan fisik oleh petugas kesehatan.

*Catatan: Informasi ini bersifat edukatif dan bukan pengganti diagnosis medis resmi. Segera konsultasikan kondisi santri ke Poskestren Pesantren.*
RES;
        }

        // 4. Istilah Medis: Febris / Demam
        if (preg_match('/(febris|demam|panas)/i', $lower)) {
            return <<<RES
Assalamu'alaikum wr. wb.

**Penjelasan Istilah Medis: Febris (Demam)**
Dalam istilah medis, **Febris** berarti kondisi peningkatan suhu tubuh di atas batas normal.

- **Suhu Tubuh Normal:** Berkisar antara **36.5°C – 37.5°C**.
- **Dinyatakan Febris:** Apabila pengukuran termometer menunjukkan suhu **≥ 37.8°C** (aksila/ketiak) atau **≥ 38.0°C** (oral/telinga).
- **Penyebab Umum di Pesantren:** Respons alami sistem imun tubuh melawan infeksi virus (seperti flu), infeksi bakteri (radang tenggorokan, tifoid), atau dehidrasi karena kurang minum di tengah padatnya aktivitas mengaji dan sekolah.

**Pertolongan Pertama Mandiri:**
1. Cukupi asupan cairan dengan minum air putih hangat minimal 2–2.5 liter per hari.
2. Kenakan pakaian yang longgar, tipis, dan menyerap keringat. Jangan gunakan selimut yang terlalu tebal.
3. Berikan kompres air hangat (suam-suam kuku) pada dahi, leher, atau lipatan ketiak. Hindari kompres es/air dingin karena dapat memicu refleks menggigil.
4. Istirahatkan santri dari kegiatan fisik berat.

🚨 **Tanda Bahaya (Red Flags) - Segera Rujuk ke Poskestren/RS:**
- Suhu tubuh mencapai **≥ 39.5°C** atau tidak turun setelah 2–3 hari.
- Disertai kejang demam atau penurunan kesadaran (mengigau, sulit dibangunkan).
- Sesak napas, bibir kebiruan, atau muntah terus-menerus sehingga tidak bisa minum sama sekali.
- Muncul bintik-bintik merah di kulit yang tidak hilang saat ditekan.

*Catatan: Informasi ini bersifat edukatif dan bukan pengganti diagnosis medis resmi. Segera konsultasikan kondisi santri ke Poskestren Pesantren.*
RES;
        }

        // 5. Istilah Medis: Cephalgia / Sakit Kepala / Pusing
        if (preg_match('/(cephalgia|sefalgi|sakit kepala|pusing)/i', $lower)) {
            return <<<RES
Assalamu'alaikum wr. wb.

**Penjelasan Istilah Medis: Cephalgia (Sakit Kepala)**
**Cephalgia** adalah istilah medis kedokteran untuk sakit kepala atau rasa nyeri/tegang di area kepala.

**Pemicu Sering pada Santri:**
- Kurang tidur atau kelelahan akibat jadwal kegiatan harian/hafalan yang padat (*Tension Headache*).
- Kurang minum air putih (dehidrasi).
- Terlambat makan (hipoglikemia ringan atau dispepsia).
- Gejala penyerta dari demam atau flu.

**Pertolongan Pertama:**
1. Istirahatkan santri di ruangan yang tenang dan redup.
2. Berikan segelas air putih hangat dan pastikan santri sudah makan.
3. Pijatan lembut di area leher dan bahu yang tegang dapat membantu relaksasi.

🚨 **Tanda Bahaya (Red Flags):**
- Sakit kepala mendadak yang sangat hebat ("sakit kepala terberat seumur hidup").
- Disertai leher kaku (kaku kuduk), muntah menyembur, pandangan kabur, atau kelemahan anggota gerak.

*Catatan: Informasi ini bersifat edukatif dan bukan pengganti diagnosis medis resmi. Segera konsultasikan kondisi santri ke Poskestren Pesantren.*
RES;
        }

        // 6. Batuk, Pilek, Radang Tenggorokan
        if (preg_match('/(batuk|pilek|flu|radang|tenggorokan|bersin)/i', $lower)) {
            return <<<RES
Assalamu'alaikum wr. wb.

**Edukasi Penanganan Batuk & Pilek di Lingkungan Asrama:**
Batuk dan pilek (Infeksi Saluran Pernapasan Akut / ISPA) sangat mudah menular antar-santri di asrama melalui droplet udara saat batuk, bersin, atau berbicara.

**Langkah Penanganan & Pencegahan Penularan di Kamar:**
1. **Etika Batuk:** Anjurkan santri memakai masker medis dan menutup mulut/hidung dengan lengan bagian dalam saat batuk.
2. **Ventilasi Kamar:** Buka jendela kamar santri di pagi hari agar sirkulasi udara lancar dan sinar matahari masuk.
3. **Hidrasi Hangat:** Berikan air minum hangat atau air jahe/madu hangat untuk melegakan tenggorokan.
4. **Pemisahan Tempat Tidur:** Jika memungkinkan, beri jarak tempat tidur santri yang bergejala dengan santri sehat lainnya.

🚨 **Tanda Bahaya (Red Flags):**
- Batuk disertai sesak napas, nafas berbunyi mengi (ngik-ngik), atau tarikan dinding dada ke dalam.
- Batuk berdahak bercampur darah.
- Batuk tidak membaik setelah lebih dari 10–14 hari.

*Catatan: Informasi ini bersifat edukatif dan bukan pengganti diagnosis medis resmi. Segera konsultasikan kondisi santri ke Poskestren Pesantren.*
RES;
        }

        // 7. Sakit Perut, Maag, Dispepsia, Diare
        if (preg_match('/(perut|maag|lambung|dispepsia|diare|mencret|mual|muntah)/i', $lower)) {
            return <<<RES
Assalamu'alaikum wr. wb.

**Edukasi Gangguan Pencernaan Santri (Dispepsia & Diare):**
Keluhan perut sering dijumpai pada santri akibat pola makan yang tidak teratur, makanan pedas/asam saat perut kosong, atau konsumsi jajanan yang kurang higienis.

**Pertolongan Pertama:**
- **Untuk Mual / Nyeri Ulu Hati (Dispepsia):** Berikan air hangat, jangan biarkan perut kosong, berikan makanan bertekstur lunak (bubur/nasi tim hangat), dan hindari makanan pedas, asam, atau berminyak.
- **Untuk Diare:** Fokus utama adalah **mencegah dehidrasi**. Berikan cairan oralit (atau larutan gula garam) setiap kali santri buang air besar cair, serta air putih yang cukup.

🚨 **Tanda Bahaya (Red Flags):**
- Diare frekuensi tinggi (>6 kali/hari) atau feses bercampur darah/lendir.
- Tanda dehidrasi sedang-berat: mata cekung, bibir sangat kering, lemas lunglai, air seni sangat pekat atau tidak buang air kecil lebih dari 6 jam.
- Nyeri perut kanan bawah yang sangat tajam (waspada radang usus buntu / appendisitis).

*Catatan: Informasi ini bersifat edukatif dan bukan pengganti diagnosis medis resmi. Segera konsultasikan kondisi santri ke Poskestren Pesantren.*
RES;
        }

        // 8. Penyakit Kulit Pesantren / Gatal / Scabies (Gudikan)
        if (preg_match('/(scabies|gudik|kudis|gatal|bintik|alergi kulit|eksim)/i', $lower)) {
            return <<<RES
Assalamu'alaikum wr. wb.

**Edukasi Kesehatan Kulit: Skabies (Gudikan) & Gatal di Pesantren:**
Skabies disebabkan oleh tungau mikroskopis *Sarcoptes scabiei* yang menular lewat kontak kulit langsung atau penggunaan pakaian, handuk, dan kasur bersama.

**Ciri Khas Gejala:**
- Rasa gatal luar biasa, terutama **semakin hebat di malam hari**.
- Terjadi berkelompok (teman sekamar atau satu asrama mengeluhkan hal serupa).
- Muncul bintil kemerahan di sela-sela jari tangan, pergelangan tangan, ketiak, sekitar pusar, atau lipatan paha.

**Langkah Penanganan Terpadu di Kamar:**
1. **Pengobatan Serentak:** Pengobatan harus dilakukan bersama-sama oleh tim Poskestren pada seluruh santri yang bergejala dalam satu kamar agar tidak saling menularkan ulang.
2. **Sanitasi Kasur & Pakaian:**
   - Rendam pakaian, sprei, dan sarung bantal dengan air panas (minimal 60°C) sebelum dicuci.
   - Jemur kasur dan bantal di bawah terik sinar matahari secara berkala.
   - Jangan saling meminjam pakaian, handuk, atau alat sholat pribadi.

*Catatan: Informasi ini bersifat edukatif dan bukan pengganti diagnosis medis resmi. Segera konsultasikan kondisi santri ke Poskestren Pesantren.*
RES;
        }

        // 9. Pertolongan Pertama Luka & Terkilir / Cedera Olahraga
        if (preg_match('/(luka|lecet|berdarah|terkilir|keseleo|cedera|jatuh)/i', $lower)) {
            return <<<RES
Assalamu'alaikum wr. wb.

**Pertolongan Pertama Luka & Terkilir Santri:**

**A. Luka Lecet / Tergores Ringan:**
1. Bersihkan luka dengan air bersih mengalir untuk membilas kotoran dan pasir.
2. Keringkan perlahan dengan kassa steril.
3. Berikan antiseptik (seperti povidone iodine).
4. Tutup dengan plester jika rawan terkena debu asrama.

**B. Terkilir / Keseleo (Metode R.I.C.E):**
1. **Rest:** Istirahatkan bagian tubuh yang cedera, jangan dipaksa berjalan atau berolahraga.
2. **Ice:** Kompres dingin dengan es yang dibungkus handuk selama 15 menit untuk mengurangi bengkak (jangan tempelkan es langsung ke kulit).
3. **Compression:** Balut dengan perban elastis secara pas (tidak terlalu kencang).
4. **Elevation:** Posisikan area yang terkilir lebih tinggi dari dada saat berbaring.
⚠️ **PENTING:** Jangan langsung memijat/mengurut area yang baru bengkak atau patah tulang, karena dapat memperparah kerusakan jaringan.

*Catatan: Informasi ini bersifat edukatif dan bukan pengganti diagnosis medis resmi. Segera konsultasikan kondisi santri ke Poskestren Pesantren.*
RES;
        }

        // 10. Respon Umum Seputar Topik Kesehatan (To The Point, Bebas Iklan)
        return <<<RES
Sebagai **Asisten Kesehatan SIMKES Santri**, saya siap membantu:
1. Menjelaskan istilah medis pada catatan pemeriksaan santri (seperti febris, cephalgia, dispepsia).
2. Panduan pertolongan pertama (P3K) santri sakit di asrama (demam, luka, batuk/flu, terkilir, gatal).
3. Mengidentifikasi tanda bahaya (*red flags*) kapan santri harus segera dirujuk ke Poskestren atau RS.
4. Menyusun draf ringkasan keluhan santri untuk diserahkan ke tim medis.

Silakan sampaikan keluhan kesehatan santri atau istilah medis yang ingin Anda konsultasikan.

*Catatan: Informasi ini bersifat edukatif dan bukan pengganti diagnosis medis resmi. Segera konsultasikan kondisi santri ke Poskestren Pesantren.*
RES;
    }
}

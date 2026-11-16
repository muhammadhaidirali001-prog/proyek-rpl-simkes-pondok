<?php

namespace App\Controllers;

use App\Libraries\AiService;
use App\Models\AuditLogModel;
use Config\Services;

class AsistenAi extends BaseController
{
    protected AiService $aiService;
    protected AuditLogModel $auditModel;

    public function __construct()
    {
        $this->aiService  = new AiService();
        $this->auditModel = new AuditLogModel();
    }

    /**
     * Tampilan Halaman Chat Asisten AI
     */
    public function index()
    {
        $providerInfo = $this->aiService->getProviderInfo();
        $chatHistory  = session()->get('ai_chat_history') ?? [];

        $data = [
            'title'        => 'Asisten AI Kesehatan Santri - SIMKES Santri',
            'providerInfo' => $providerInfo,
            'chatHistory'  => $chatHistory,
        ];

        return view('asisten_ai/chat', $data);
    }

    /**
     * Endpoint AJAX POST untuk mengirim pesan dan menerima respon AI
     */
    public function chat()
    {
        // Pastikan hanya menerima permintaan POST
        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON([
                'success'    => false,
                'error'      => 'Metode request tidak diizinkan.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        // Ambil input pesan baik dari form-encoded atau JSON body
        $rawMessage = $this->request->getPost('message');
        if ($rawMessage === null) {
            $jsonInput  = $this->request->getJSON(true);
            $rawMessage = $jsonInput['message'] ?? '';
        }

        $message = trim((string) $rawMessage);

        // 1. Validasi Keberadaan Pesan
        if ($message === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'success'    => false,
                'error'      => 'Pertanyaan tidak boleh kosong.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        // 2. Pembatasan Panjang Karakter Input (Maksimal 1.000 karakter)
        // Mencegah eksploitasi token & mengontrol biaya API
        if (mb_strlen($message) > 1000) {
            return $this->response->setStatusCode(400)->setJSON([
                'success'    => false,
                'error'      => 'Panjang pertanyaan melebihi batas maksimal (maksimal 1.000 karakter per pesan).',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        // 3. Mitigasi Biaya & Rate Limiting Per Sesi Pengguna
        // Batasi maksimal 12 pesan per menit dan jeda minimal 1.5 detik antar request
        $currentTime = time();
        $rateHistory = session()->get('ai_rate_timestamps') ?? [];

        // Hapus timestamp yang lebih lama dari 60 detik
        $rateHistory = array_filter($rateHistory, function ($ts) use ($currentTime) {
            return ($currentTime - $ts) < 60;
        });

        // Periksa frekuensi request dalam 1 menit terakhir
        if (count($rateHistory) >= 12) {
            return $this->response->setStatusCode(429)->setJSON([
                'success'    => false,
                'error'      => 'Batas laju pertanyaan tercapai (maksimal 12 pesan per menit). Mohon tunggu sejenak.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        // Simpan timestamp baru untuk rate limiting
        $rateHistory[] = $currentTime;
        session()->set('ai_rate_timestamps', $rateHistory);

        // 4. Ambil konteks percakapan sesi (maksimal 6 interaksi terakhir)
        $chatHistory = session()->get('ai_chat_history') ?? [];
        $contextMessages = array_slice($chatHistory, -6);

        // Siapkan konteks pengguna untuk personalisasi & hak akses database AI
        $userContext = [
            'user_id'        => session()->get('user_id'),
            'nama'           => session()->get('nama'),
            'email'          => session()->get('email'),
            'role'           => session()->get('role'),
            'role_canonical' => session()->get('role_canonical'),
            'role_display'   => session()->get('role_display'),
        ];

        // 5. Panggil AI Service
        $result = $this->aiService->sendMessage($message, $contextMessages, $userContext);

        if (!$result['success']) {
            return $this->response->setStatusCode(500)->setJSON([
                'success'    => false,
                'error'      => $result['error'] ?? 'Terjadi kesalahan saat memproses permintaan AI.',
                'provider'   => $result['provider'] ?? '',
                'model'      => $result['model'] ?? '',
                'is_mock'    => $result['is_mock'] ?? false,
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        // 6. Perbarui riwayat chat dalam sesi
        $chatHistory[] = [
            'role'    => 'user',
            'content' => $message,
            'time'    => date('H:i'),
        ];
        $chatHistory[] = [
            'role'    => 'assistant',
            'content' => $result['message'],
            'time'    => date('H:i'),
            'is_mock' => $result['is_mock'] ?? false,
        ];

        // Batasi penyimpanan riwayat sesi maksimal 14 percakapan terakhir
        if (count($chatHistory) > 14) {
            $chatHistory = array_slice($chatHistory, -14);
        }
        session()->set('ai_chat_history', $chatHistory);

        // 7. Catat aktivitas audit secara aman (tanpa API key atau teks sensitif lengkap)
        $userId = session()->get('user_id');
        $this->auditModel->catatAktivitas(
            $userId ? (int) $userId : null,
            'KONSULTASI_AI',
            'Konsultasi edukasi asisten AI (' . ($result['is_mock'] ? 'Mode Simulasi' : 'API Provider: ' . $result['provider']) . ')'
        );

        // 8. Kembalikan respons sukses
        return $this->response->setJSON([
            'success'    => true,
            'message'    => $result['message'],
            'provider'   => $result['provider'],
            'model'      => $result['model'],
            'is_mock'    => $result['is_mock'] ?? false,
            'time'       => date('H:i'),
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    /**
     * Endpoint AJAX POST untuk mereset riwayat percakapan sesi
     */
    public function reset()
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON([
                'success'    => false,
                'error'      => 'Metode request tidak diizinkan.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        session()->remove('ai_chat_history');
        session()->remove('ai_rate_timestamps');

        return $this->response->setJSON([
            'success'    => true,
            'message'    => 'Riwayat percakapan berhasil dibersihkan.',
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    /**
     * Endpoint AJAX GET untuk mengambil data konfigurasi AI saat ini
     */
    public function getConfig()
    {
        $aiConfig = config(\Config\Ai::class);
        $provider = $aiConfig->provider ?? 'gemini';
        $model    = $aiConfig->model ?? 'gemini-1.5-flash';
        $apiKey   = trim((string)($aiConfig->apiKey ?? ''));

        // Cek juga apakah ada di session (jika user mengubah saat runtime)
        if (session()->has('custom_ai_provider')) {
            $provider = session()->get('custom_ai_provider');
        }
        if (session()->has('custom_ai_model')) {
            $model = session()->get('custom_ai_model');
        }
        if (session()->has('custom_ai_api_key')) {
            $apiKey = session()->get('custom_ai_api_key');
        }

        $hasKey = !empty($apiKey);
        $maskedKey = '';
        if ($hasKey) {
            $len = strlen($apiKey);
            if ($len <= 8) {
                $maskedKey = str_repeat('•', $len);
            } else {
                $maskedKey = substr($apiKey, 0, 4) . str_repeat('•', max(4, $len - 8)) . substr($apiKey, -4);
            }
        }

        return $this->response->setJSON([
            'success'    => true,
            'provider'   => $provider,
            'model'      => $model,
            'has_key'    => $hasKey,
            'masked_key' => $maskedKey,
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    /**
     * Endpoint AJAX POST untuk menyimpan konfigurasi API Key & Model AI
     */
    public function saveConfig()
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON([
                'success'    => false,
                'error'      => 'Metode request tidak diizinkan.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $provider = trim((string) ($this->request->getPost('provider') ?? 'gemini'));
        $model    = trim((string) ($this->request->getPost('model') ?? 'gemini-1.5-flash'));
        $apiKey   = trim((string) ($this->request->getPost('api_key') ?? ''));

        if (!in_array($provider, ['gemini', 'openai', 'mock'])) {
            $provider = 'gemini';
        }

        if (empty($model)) {
            $model = ($provider === 'openai') ? 'gpt-4o-mini' : 'gemini-1.5-flash';
        }

        // 1. Simpan ke .env jika file .env ada dan writable
        $envPath = ROOTPATH . '.env';
        $envUpdated = false;

        if (file_exists($envPath) && is_writable($envPath)) {
            $envContent = file_get_contents($envPath);

            // Update atau tambahkan AI_PROVIDER
            if (preg_match('/^AI_PROVIDER\s*=.*/m', $envContent)) {
                $envContent = preg_replace('/^AI_PROVIDER\s*=.*/m', "AI_PROVIDER = '{$provider}'", $envContent);
            } else {
                $envContent .= "\nAI_PROVIDER = '{$provider}'";
            }

            // Update atau tambahkan AI_MODEL
            if (preg_match('/^AI_MODEL\s*=.*/m', $envContent)) {
                $envContent = preg_replace('/^AI_MODEL\s*=.*/m', "AI_MODEL = '{$model}'", $envContent);
            } else {
                $envContent .= "\nAI_MODEL = '{$model}'";
            }

            // Update AI_API_KEY hanya jika diisi (jika kosong dan bukan permintaan reset, biarkan yang lama)
            $isClearKey = ($this->request->getPost('clear_key') === '1');
            if ($isClearKey) {
                if (preg_match('/^AI_API_KEY\s*=.*/m', $envContent)) {
                    $envContent = preg_replace('/^AI_API_KEY\s*=.*/m', "AI_API_KEY = ''", $envContent);
                }
            } elseif (!empty($apiKey)) {
                // Escape petik tunggal jika ada
                $safeKey = str_replace("'", "\'", $apiKey);
                if (preg_match('/^AI_API_KEY\s*=.*/m', $envContent)) {
                    $envContent = preg_replace('/^AI_API_KEY\s*=.*/m', "AI_API_KEY = '{$safeKey}'", $envContent);
                } else {
                    $envContent .= "\nAI_API_KEY = '{$safeKey}'";
                }
            }

            if (file_put_contents($envPath, $envContent) !== false) {
                $envUpdated = true;
            }
        }

        // 2. Simpan juga ke session pengguna saat ini agar langsung aktif tanpa reload server
        session()->set('custom_ai_provider', $provider);
        session()->set('custom_ai_model', $model);
        if ($this->request->getPost('clear_key') === '1') {
            session()->remove('custom_ai_api_key');
        } elseif (!empty($apiKey)) {
            session()->set('custom_ai_api_key', $apiKey);
        }

        // Catat di audit log
        $userId = session()->get('user_id');
        $this->auditModel->catatAktivitas(
            $userId ? (int) $userId : null,
            'CONFIG_AI',
            "Memperbarui konfigurasi AI (Provider: {$provider}, Model: {$model})"
        );

        return $this->response->setJSON([
            'success'     => true,
            'message'     => 'Konfigurasi Asisten AI berhasil disimpan dan langsung aktif!',
            'env_updated' => $envUpdated,
            'provider'    => $provider,
            'model'       => $model,
            'has_key'     => !empty($apiKey) || (!empty($aiConfig->apiKey) && $this->request->getPost('clear_key') !== '1'),
            'csrf_token'  => csrf_token(),
            'csrf_hash'   => csrf_hash(),
        ]);
    }

    /**
     * Endpoint AJAX GET untuk mendapatkan notifikasi kesehatan penting
     */
    public function getNotifications()
    {
        $userId   = (int) session()->get('user_id');
        $userRole = \App\Models\UserModel::normalizeRole(session()->get('role') ?? '');
        $isPengawasKamar = ($userRole === \App\Models\UserModel::ROLE_PENGAWAS_KAMAR);

        $allowedKamarIds = null;
        if ($isPengawasKamar) {
            $kamarModel  = new \App\Models\KamarModel();
            $kamarBinaan = $kamarModel->where('pengawas_id', $userId)->findAll();
            $allowedKamarIds = array_column($kamarBinaan, 'id');
        }

        $laporanModel = new \App\Models\LaporanKesehatanModel();
        $stats = $laporanModel->getDashboardStats($allowedKamarIds);

        $dalamPeriksa = (int) ($stats['dalam_pemeriksaan'] ?? 0);
        $dalamRawat   = (int) ($stats['dalam_perawatan'] ?? 0);
        $totalKasus   = $dalamPeriksa + $dalamRawat;

        // Ambil 3 laporan terbaru yang membutuhkan perhatian
        $db = \Config\Database::connect();
        $builder = $db->table('laporan_kesehatan')
            ->select('laporan_kesehatan.*, santri.nama as nama_santri, kamar.nama_kamar')
            ->join('santri', 'santri.id = laporan_kesehatan.santri_id', 'left')
            ->join('kamar', 'kamar.id = santri.kamar_id', 'left')
            ->whereIn('laporan_kesehatan.status', [
                \App\Models\LaporanKesehatanModel::STATUS_DALAM_PEMERIKSAAN,
                \App\Models\LaporanKesehatanModel::STATUS_DALAM_PERAWATAN,
                \App\Models\LaporanKesehatanModel::STATUS_DILAPORKAN_SAKIT
            ]);

        if ($isPengawasKamar && !empty($allowedKamarIds)) {
            $builder->whereIn('santri.kamar_id', $allowedKamarIds);
        }

        $recentCases = $builder->orderBy('laporan_kesehatan.created_at', 'DESC')->limit(3)->get()->getResultArray();

        $notifications = [];
        if ($dalamPeriksa > 0) {
            $notifications[] = [
                'type'    => 'periksa',
                'title'   => 'Santri Dalam Pemeriksaan',
                'message' => "Ada {$dalamPeriksa} santri yang sedang membutuhkan/menjalani pemeriksaan klinis.",
                'badge'   => $dalamPeriksa,
            ];
        }
        if ($dalamRawat > 0) {
            $notifications[] = [
                'type'    => 'rawat',
                'title'   => 'Santri Dalam Perawatan',
                'message' => "Ada {$dalamRawat} santri yang sedang diobservasi klinis/dirawat di asrama.",
                'badge'   => $dalamRawat,
            ];
        }

        return $this->response->setJSON([
            'success'       => true,
            'unread_count'  => $totalKasus,
            'dalam_periksa' => $dalamPeriksa,
            'dalam_rawat'   => $dalamRawat,
            'cases'         => $recentCases,
            'notifications' => $notifications,
            'csrf_token'    => csrf_token(),
            'csrf_hash'     => csrf_hash(),
        ]);
    }
}

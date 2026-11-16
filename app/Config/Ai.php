<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Ai extends BaseConfig
{
    /**
     * Penyedia AI: 'gemini', 'openai', 'mock'
     */
    public string $provider = 'gemini';

    /**
     * API Key rahasia (disimpan di environment variable, TIDAK PERNAH dikirim ke klien)
     */
    public string $apiKey = '';

    /**
     * Nama Model AI (misal: 'gemini-1.5-flash', 'gemini-2.0-flash')
     */
    public string $model = 'gemini-1.5-flash';

    /**
     * Timeout request HTTP (detik)
     */
    public int $timeout = 15;

    /**
     * Batas token respon AI untuk efisiensi biaya
     */
    public int $maxTokens = 800;

    /**
     * Base URL custom (opsional, jika menggunakan proxy/gateway khusus)
     */
    public ?string $baseUrl = null;

    public function __construct()
    {
        parent::__construct();

        // Muat nilai dari environment variable dengan fallback aman
        $this->provider  = (string) (env('AI_PROVIDER') ?: $this->provider);
        $this->apiKey    = (string) (env('AI_API_KEY') ?: $this->apiKey);
        $this->model     = (string) (env('AI_MODEL') ?: $this->model);
        $this->timeout   = (int) (env('AI_TIMEOUT') ?: $this->timeout);
        $this->maxTokens = (int) (env('AI_MAX_TOKENS') ?: $this->maxTokens);
        $this->baseUrl   = env('AI_BASE_URL') ? (string) env('AI_BASE_URL') : null;
    }
}

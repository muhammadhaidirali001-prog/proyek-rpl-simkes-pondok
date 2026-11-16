<?php
  $aiHistory = session()->get('ai_chat_history') ?? [];
?>

<!-- Floating AI Assistant Launcher Button (FAB) -->
<div class="floating-ai-launcher" id="floatingAiLauncher">
  <!-- Toast Notifikasi Bubble Kecil di atas FAB -->
  <div class="ai-fab-notif-toast d-none" id="aiFabNotifToast">
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-danger rounded-pill px-1.5 py-0.5" id="aiNotifToastBadge" style="font-size: 0.65rem;">0</span>
      <span class="fw-semibold text-dark small" id="aiNotifToastText">Santri sedang sakit</span>
      <button type="button" class="btn-close ms-auto" id="btnCloseNotifToast" style="font-size: 0.55rem;"></button>
    </div>
  </div>

  <button type="button" class="btn-floating-ai" id="btnToggleFloatingAi" title="Buka Asisten Kesehatan SIMKES" aria-label="Buka Asisten Kesehatan">
    <div class="ai-fab-icon-box position-relative">
      <i class="bi bi-heart-pulse-fill ai-fab-icon-pulse"></i>
      <i class="bi bi-robot ai-fab-icon-robot"></i>
      <!-- Badge Angka Notifikasi di Tombol -->
      <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light d-none" id="aiLauncherNotifBadge" style="font-size: 0.65rem; padding: 2px 5px;">
        0
      </span>
    </div>
    <span class="ai-fab-label d-none d-md-inline-block">Asisten Kesehatan</span>
    <span class="ai-fab-badge">
      <span class="ai-pulse-dot"></span>
    </span>
  </button>
</div>

<!-- Floating AI Chat Window (Jendela Mengambang) -->
<div class="floating-ai-window" id="floatingAiWindow" role="dialog" aria-labelledby="floatingAiTitle" aria-hidden="true">
  <!-- Header Jendela -->
  <div class="floating-ai-header">
    <div class="d-flex align-items-center gap-2">
      <div class="floating-ai-avatar">
        <i class="bi bi-robot"></i>
      </div>
      <div>
        <h6 class="mb-0 fw-bold text-white" id="floatingAiTitle" style="font-size: 0.95rem;">
          Asisten Kesehatan
        </h6>
        <div class="d-flex align-items-center gap-1.5 text-white-50" style="font-size: 0.72rem;">
          <span class="ai-online-indicator"></span>
          <span>SIMKES Santri &bull; Khusus Medis</span>
        </div>
      </div>
    </div>
    <div class="d-flex align-items-center gap-1">
      <!-- Tombol Lonceng Notifikasi Kasus Sakit -->
      <button type="button" class="btn-floating-tool position-relative" id="btnFloatingAiNotif" title="Notifikasi Santri Sakit & Perawatan">
        <i class="bi bi-bell-fill"></i>
        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle d-none" id="aiHeaderNotifDot" style="margin-top: 6px; margin-left: -8px;"></span>
      </button>
      <button type="button" class="btn-floating-tool" id="btnFloatingAiSettings" title="Pengaturan Model & API Key">
        <i class="bi bi-gear-fill"></i>
      </button>
      <button type="button" class="btn-floating-tool" id="btnFloatingAiReset" title="Bersihkan Percakapan">
        <i class="bi bi-trash3"></i>
      </button>
      <button type="button" class="btn-floating-tool" id="btnFloatingAiClose" title="Tutup Jendela">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
  </div>

  <!-- Panel Dropdown Notifikasi Kasus Kesehatan (Slide down) -->
  <div class="floating-ai-notif-panel d-none" id="floatingAiNotifPanel">
    <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
      <div class="d-flex align-items-center gap-1.5 fw-bold small text-dark">
        <i class="bi bi-bell-fill text-danger"></i>
        <span>Peringatan & Kasus Aktif</span>
      </div>
      <button type="button" class="btn-close btn-close-sm" id="btnCloseAiNotif" aria-label="Tutup"></button>
    </div>
    <div id="aiNotifList" class="d-flex flex-column gap-2" style="max-height: 220px; overflow-y: auto;">
      <div class="text-center text-muted py-3 small">
        <i class="bi bi-check2-circle text-success fs-5 d-block mb-1"></i>
        Tidak ada kasus santri sakit aktif saat ini.
      </div>
    </div>
  </div>

  <!-- Panel Pengaturan Konfigurasi AI (Slide down) -->
  <div class="floating-ai-config-panel d-none" id="floatingAiConfigPanel">
    <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
      <div class="d-flex align-items-center gap-1.5 fw-bold small text-dark">
        <i class="bi bi-sliders2-vertical text-teal"></i>
        <span>Pengaturan AI & Model</span>
      </div>
      <button type="button" class="btn-close btn-close-sm" id="btnCloseAiConfig" aria-label="Tutup"></button>
    </div>

    <form id="formAiConfig" autocomplete="off">
      <div class="mb-2">
        <label for="aiProviderSelect" class="form-label mb-1 text-muted" style="font-size: 0.72rem; font-weight: 600;">
          Provider AI
        </label>
        <select class="form-select form-select-sm" id="aiProviderSelect" name="provider" style="font-size: 0.8rem;">
          <option value="gemini">Google Gemini (Disarankan)</option>
          <option value="openai">OpenAI Compatible (ChatGPT / DeepSeek / Ollama)</option>
          <option value="mock">Mode Simulasi (Offline / Tanpa Kuota)</option>
        </select>
      </div>

      <div class="mb-2">
        <label for="aiModelInput" class="form-label mb-1 text-muted" style="font-size: 0.72rem; font-weight: 600;">
          Nama Model
        </label>
        <input type="text" class="form-control form-control-sm" id="aiModelInput" name="model" placeholder="gemini-1.5-flash / gemini-2.0-flash / gpt-4o-mini" style="font-size: 0.8rem;" required>
        <div class="d-flex flex-wrap gap-1 mt-1">
          <span class="badge bg-light text-dark border model-preset-badge" data-provider="gemini" data-model="gemini-1.5-flash" style="cursor: pointer; font-size: 0.65rem;">gemini-1.5-flash</span>
          <span class="badge bg-light text-dark border model-preset-badge" data-provider="gemini" data-model="gemini-2.0-flash" style="cursor: pointer; font-size: 0.65rem;">gemini-2.0-flash</span>
          <span class="badge bg-light text-dark border model-preset-badge" data-provider="openai" data-model="gpt-4o-mini" style="cursor: pointer; font-size: 0.65rem;">gpt-4o-mini</span>
        </div>
      </div>

      <div class="mb-2.5">
        <label for="aiApiKeyInput" class="form-label mb-1 text-muted d-flex align-items-center justify-content-between" style="font-size: 0.72rem; font-weight: 600;">
          <span>API Key</span>
          <span class="text-success d-none" id="aiKeyStatusBadge" style="font-size: 0.68rem;"><i class="bi bi-check-circle-fill"></i> Tersimpan</span>
        </label>
        <div class="input-group input-group-sm">
          <input type="password" class="form-control" id="aiApiKeyInput" name="api_key" placeholder="Masukkan API Key baru..." style="font-size: 0.8rem;">
          <button class="btn btn-outline-secondary" type="button" id="btnToggleShowKey" title="Tampilkan/Sembunyikan">
            <i class="bi bi-eye"></i>
          </button>
        </div>
        <div class="form-text text-muted mt-1" style="font-size: 0.68rem;" id="aiKeyHint">
          Kosongkan jika tidak ingin mengubah kunci yang sudah tersimpan.
        </div>
      </div>

      <div class="d-flex align-items-center justify-content-between pt-1">
        <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2" id="btnResetAiKey" style="font-size: 0.72rem;">
          <i class="bi bi-x-circle me-1"></i>Hapus Key
        </button>
        <button type="submit" class="btn btn-teal btn-sm py-1 px-3 d-inline-flex align-items-center gap-1" id="btnSaveAiConfig" style="font-size: 0.75rem;">
          <i class="bi bi-check-lg" id="iconSaveConfig"></i>
          <span class="spinner-border spinner-border-sm d-none" id="spinnerSaveConfig"></span>
          <span>Simpan</span>
        </button>
      </div>
      <div class="alert alert-success py-1 px-2 small mt-2 d-none" id="alertConfigSuccess" style="font-size: 0.72rem;"></div>
      <div class="alert alert-danger py-1 px-2 small mt-2 d-none" id="alertConfigError" style="font-size: 0.72rem;"></div>
    </form>
  </div>

  <!-- Banner Disclaimer Singkat & Jelas -->
  <div class="floating-ai-disclaimer">
    <i class="bi bi-shield-exclamation text-warning me-1"></i>
    <span><strong>Bukan Pengganti Dokter.</strong> Khusus informasi kesehatan santri & pertolongan pertama. Topik non-kesehatan ditolak.</span>
  </div>

  <!-- Body Pesan Chat -->
  <div class="floating-ai-body" id="floatingAiChatBody">
    <!-- Pesan Pembuka Default -->
    <div class="ai-msg-row ai-msg-bot">
      <div class="ai-bubble-avatar">
        <i class="bi bi-robot"></i>
      </div>
      <div class="ai-msg-bubble">
        <div class="ai-bubble-title">Asisten Kesehatan SIMKES</div>
        <p class="mb-1.5">Saya adalah <strong>Asisten Kesehatan SIMKES Santri</strong>.</p>
        <p class="mb-1.5 small text-muted">Saya siap membantu Anda seputar:</p>
        <ul class="mb-2 ps-3 small text-muted">
          <li>Penjelasan istilah medis santri (febris, cephalgia, dispepsia).</li>
          <li>Pertolongan pertama demam, luka, flu, dan terkilir di asrama.</li>
          <li>Mengenali tanda bahaya (<em>red flags</em>) untuk rujukan Poskestren.</li>
          <li>Merangkum draf keluhan santri.</li>
        </ul>
        <div class="ai-quick-chips">
          <button type="button" class="quick-chip-btn" data-prompt="Siapa yang sedang sakit?">
            🤒 Siapa yang Sakit?
          </button>
          <button type="button" class="quick-chip-btn" data-prompt="Siapa nama santri terdaftar aktif?">
            👥 Nama Santri Aktif
          </button>
          <button type="button" class="quick-chip-btn" data-prompt="Ada berapa siswa yang sakit, dirawat, dan sembuh?">
            📊 Rekap Sakit, Rawat & Sembuh
          </button>
          <button type="button" class="quick-chip-btn" data-prompt="Siapa saya?">
            👤 Siapa Saya?
          </button>
          <button type="button" class="quick-chip-btn" data-prompt="Apa arti istilah febris dan berapa suhu normal santri?">
            🌡️ Febris & Suhu
          </button>
          <button type="button" class="quick-chip-btn" data-prompt="Apa pertolongan pertama untuk santri luka lecet atau terkilir?">
            🩹 Pertolongan Luka
          </button>
          <button type="button" class="quick-chip-btn" data-prompt="Bagaimana penanganan batuk pilek agar tidak menular di kamar santri?">
            😷 Batuk di Kamar
          </button>
          <button type="button" class="quick-chip-btn" data-prompt="Siapa anda?">
            🤖 Siapa Anda?
          </button>
        </div>
      </div>
    </div>

    <!-- Riwayat Chat Sesi -->
    <?php if (!empty($aiHistory)): ?>
      <?php foreach ($aiHistory as $h): ?>
        <?php if (($h['role'] ?? '') === 'user'): ?>
          <div class="ai-msg-row ai-msg-user">
            <div class="ai-msg-bubble user-bubble">
              <div class="ai-bubble-time text-end"><?= esc($h['time'] ?? date('H:i')) ?></div>
              <div class="ai-bubble-text"><?= esc($h['content'] ?? '') ?></div>
            </div>
          </div>
        <?php else: ?>
          <div class="ai-msg-row ai-msg-bot">
            <div class="ai-bubble-avatar">
              <i class="bi bi-robot"></i>
            </div>
            <div class="ai-msg-bubble">
              <div class="ai-bubble-header">
                <span class="ai-bubble-title">Asisten Kesehatan SIMKES</span>
                <span class="ai-bubble-time"><?= esc($h['time'] ?? date('H:i')) ?></span>
              </div>
              <div class="ai-bubble-text"><?= esc($h['content'] ?? '') ?></div>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>

    <!-- Indikator Mengetik -->
    <div class="ai-msg-row ai-msg-bot d-none" id="floatingAiTyping">
      <div class="ai-bubble-avatar">
        <i class="bi bi-robot"></i>
      </div>
      <div class="ai-msg-bubble py-2 px-3">
        <div class="floating-typing-dots">
          <span></span><span></span><span></span>
        </div>
      </div>
    </div>

    <!-- Alert Error Box -->
    <div class="alert alert-danger py-2 px-3 small d-none mb-2" id="floatingAiError"></div>
  </div>

  <!-- Footer Input Form -->
  <div class="floating-ai-footer">
    <form id="floatingAiForm" autocomplete="off">
      <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="floatingAiCsrf">
      <div class="floating-input-group">
        <textarea 
          class="form-control floating-input" 
          id="floatingAiMessage" 
          rows="1" 
          placeholder="Tanyakan keluhan kesehatan santri..." 
          maxlength="1000"
          required
        ></textarea>
        <button type="submit" class="btn-floating-send" id="btnFloatingAiSend" title="Kirim Pesan">
          <i class="bi bi-send-fill" id="floatingSendIcon"></i>
          <span class="spinner-border spinner-border-sm d-none" id="floatingSendSpinner" role="status"></span>
        </button>
      </div>
      <div class="d-flex align-items-center justify-content-between mt-1 px-1">
        <span class="text-muted" style="font-size: 0.68rem;">
          <i class="bi bi-shield-check text-success me-0.5"></i> Data disanitasi otomatis
        </span>
        <span class="text-muted" id="floatingAiCharCount" style="font-size: 0.68rem;">0 / 1000</span>
      </div>
    </form>
  </div>
</div>

<style>
/* ==========================================================================
   STYLE JENDELA MENGAMBANG (FLOATING AI ASSISTANT WINDOW)
   ========================================================================== */
.floating-ai-launcher {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 1050;
}

.btn-floating-ai {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 10px 18px 10px 14px;
  background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 50px;
  box-shadow: 0 8px 24px rgba(13, 148, 136, 0.35);
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  outline: none;
}

.btn-floating-ai:hover {
  transform: translateY(-3px) scale(1.02);
  box-shadow: 0 12px 28px rgba(13, 148, 136, 0.45);
  background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
}

.ai-fab-icon-box {
  width: 34px;
  height: 34px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  position: relative;
}

.ai-fab-icon-robot {
  display: none;
}

.ai-fab-label {
  font-weight: 600;
  font-size: 0.88rem;
  letter-spacing: -0.01em;
}

.ai-fab-badge {
  position: relative;
  display: flex;
  align-items: center;
}

.ai-pulse-dot {
  width: 9px;
  height: 9px;
  background-color: #34d399;
  border-radius: 50%;
  box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.8);
  animation: fabPulse 2s infinite;
}

@keyframes fabPulse {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.7); }
  70% { transform: scale(1.05); box-shadow: 0 0 0 8px rgba(52, 211, 153, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(52, 211, 153, 0); }
}

/* Floating Window Container */
.floating-ai-window {
  position: fixed;
  bottom: 85px;
  right: 24px;
  width: 390px;
  max-width: calc(100vw - 32px);
  height: 560px;
  max-height: calc(100vh - 110px);
  z-index: 1055;
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0 16px 45px rgba(15, 23, 42, 0.22), 0 0 0 1px rgba(13, 148, 136, 0.15);
  display: none;
  flex-direction: column;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  transform: translateY(20px) scale(0.96);
  opacity: 0;
}

.floating-ai-window.active {
  display: flex;
  transform: translateY(0) scale(1);
  opacity: 1;
}

/* Header */
.floating-ai-header {
  background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
  padding: 12px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.floating-ai-avatar {
  width: 36px;
  height: 36px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ffffff;
  font-size: 1.15rem;
}

.ai-online-indicator {
  display: inline-block;
  width: 6px;
  height: 6px;
  background-color: #34d399;
  border-radius: 50%;
}

.btn-floating-tool {
  background: transparent;
  border: none;
  color: rgba(255, 255, 255, 0.85);
  width: 30px;
  height: 30px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-floating-tool:hover {
  background: rgba(255, 255, 255, 0.2);
  color: #ffffff;
}

/* Disclaimer Bar */
.floating-ai-disclaimer {
  background-color: #fffbeb;
  border-bottom: 1px solid #fef3c7;
  padding: 6px 12px;
  font-size: 0.72rem;
  color: #92400e;
  line-height: 1.35;
  display: flex;
  align-items: flex-start;
}

/* Body */
.floating-ai-body {
  flex: 1;
  padding: 14px;
  overflow-y: auto;
  background-color: #f8fafc;
  display: flex;
  flex-direction: column;
  gap: 12px;
  scroll-behavior: smooth;
}

/* Message Rows */
.ai-msg-row {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  max-width: 100%;
}

.ai-msg-user {
  justify-content: flex-end;
}

.ai-bubble-avatar {
  width: 28px;
  height: 28px;
  background-color: #ccfbf1;
  color: #0f766e;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  flex-shrink: 0;
  margin-top: 2px;
}

.ai-msg-bubble {
  background-color: #ffffff;
  color: #1e293b;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  border-top-left-radius: 4px;
  padding: 10px 12px;
  font-size: 0.825rem;
  line-height: 1.45;
  max-width: 86%;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  word-wrap: break-word;
}

.ai-msg-bubble.user-bubble {
  background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
  color: #ffffff;
  border: none;
  border-radius: 14px;
  border-top-right-radius: 4px;
  box-shadow: 0 2px 6px rgba(13, 148, 136, 0.25);
}

.ai-bubble-title {
  font-weight: 700;
  font-size: 0.75rem;
  color: #0f766e;
  margin-bottom: 4px;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 3px;
}

.ai-bubble-time {
  font-size: 0.65rem;
  color: rgba(255, 255, 255, 0.7);
  margin-bottom: 2px;
}

.ai-bubble-text {
  white-space: pre-wrap;
}

/* Quick Chips */
.ai-quick-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  margin-top: 8px;
}

.quick-chip-btn {
  background-color: #f0fdfa;
  border: 1px solid #99f6e4;
  color: #0f766e;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.quick-chip-btn:hover {
  background-color: #0d9488;
  color: #ffffff;
  border-color: #0d9488;
}

/* Typing Indicator */
.floating-typing-dots {
  display: flex;
  align-items: center;
  gap: 3px;
}

.floating-typing-dots span {
  width: 6px;
  height: 6px;
  background-color: #0d9488;
  border-radius: 50%;
  animation: typingDot 1.4s infinite ease-in-out both;
}

.floating-typing-dots span:nth-child(1) { animation-delay: -0.32s; }
.floating-typing-dots span:nth-child(2) { animation-delay: -0.16s; }

@keyframes typingDot {
  0%, 80%, 100% { transform: scale(0); opacity: 0.4; }
  40% { transform: scale(1); opacity: 1; }
}

/* Floating AI Config Panel */
.floating-ai-config-panel {
  background: #ffffff;
  border-bottom: 2px solid #0d9488;
  padding: 12px 14px;
  animation: slideDownConfig 0.2s ease-out;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  max-height: 380px;
  overflow-y: auto;
  z-index: 10;
}

@keyframes slideDownConfig {
  from { opacity: 0; transform: translateY(-8px); }
  to { opacity: 1; transform: translateY(0); }
}

.model-preset-badge:hover {
  background-color: #0d9488 !important;
  color: #ffffff !important;
  border-color: #0d9488 !important;
}

/* Floating AI Toast Notifikasi di atas FAB */
.ai-fab-notif-toast {
  position: absolute;
  bottom: calc(100% + 10px);
  right: 0;
  background: #ffffff;
  border-radius: 12px;
  padding: 7px 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.14), 0 0 0 1px rgba(220, 38, 38, 0.15);
  white-space: nowrap;
  animation: bounceToast 0.4s ease-out;
  cursor: pointer;
  z-index: 1051;
}

@keyframes bounceToast {
  0% { opacity: 0; transform: translateY(10px) scale(0.9); }
  60% { opacity: 1; transform: translateY(-4px) scale(1.02); }
  100% { opacity: 1; transform: translateY(0) scale(1); }
}

/* Panel Dropdown Notifikasi */
.floating-ai-notif-panel {
  background: #ffffff;
  border-bottom: 2px solid #ef4444;
  padding: 12px 14px;
  animation: slideDownConfig 0.2s ease-out;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  z-index: 10;
}

.notif-card-item {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 8px 10px;
  transition: background 0.15s ease;
  cursor: pointer;
}

.notif-card-item:hover {
  background: #f1f5f9;
}

/* Footer Input */
.floating-ai-footer {
  padding: 10px 12px;
  background-color: #ffffff;
  border-top: 1px solid #e2e8f0;
}

.floating-input-group {
  display: flex;
  align-items: flex-end;
  gap: 6px;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 14px;
  padding: 4px 6px;
  transition: border-color 0.2s;
}

.floating-input-group:focus-within {
  border-color: #0d9488;
  background: #ffffff;
  box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
}

.floating-input {
  border: none;
  background: transparent;
  padding: 6px 8px;
  font-size: 0.825rem;
  resize: none;
  max-height: 80px;
  box-shadow: none !important;
}

.floating-input:focus {
  background: transparent;
}

.btn-floating-send {
  background: #0d9488;
  color: #ffffff;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  flex-shrink: 0;
  cursor: pointer;
  transition: background 0.15s;
}

.btn-floating-send:hover {
  background: #0f766e;
}

/* Mobile responsive adjustments */
@media (max-width: 575.98px) {
  .floating-ai-launcher {
    bottom: 16px;
    right: 16px;
  }
  .floating-ai-window {
    bottom: 74px;
    right: 12px;
    width: calc(100vw - 24px);
    height: calc(100vh - 90px);
  }
  .btn-floating-ai {
    padding: 10px;
    border-radius: 50%;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const launcher     = document.getElementById('btnToggleFloatingAi');
  const windowEl     = document.getElementById('floatingAiWindow');
  const btnClose     = document.getElementById('btnFloatingAiClose');
  const btnReset     = document.getElementById('btnFloatingAiReset');
  const chatBody     = document.getElementById('floatingAiChatBody');
  const chatForm     = document.getElementById('floatingAiForm');
  const messageInput = document.getElementById('floatingAiMessage');
  const csrfInput    = document.getElementById('floatingAiCsrf');
  const typingEl     = document.getElementById('floatingAiTyping');
  const errorEl      = document.getElementById('floatingAiError');
  const sendIcon     = document.getElementById('floatingSendIcon');
  const sendSpinner  = document.getElementById('floatingSendSpinner');
  const charCounter  = document.getElementById('floatingAiCharCount');

  if (!launcher || !windowEl) return;

  function scrollToBottom() {
    chatBody.scrollTop = chatBody.scrollHeight;
  }

  function openFloatingAi() {
    windowEl.style.display = 'flex';
    requestAnimationFrame(() => {
      windowEl.classList.add('active');
      windowEl.setAttribute('aria-hidden', 'false');
      scrollToBottom();
      setTimeout(() => messageInput.focus(), 150);
    });
  }

  function closeFloatingAi() {
    windowEl.classList.remove('active');
    windowEl.setAttribute('aria-hidden', 'true');
    setTimeout(() => {
      if (!windowEl.classList.contains('active')) {
        windowEl.style.display = 'none';
      }
    }, 250);
  }

  function toggleFloatingAi() {
    if (windowEl.classList.contains('active')) {
      closeFloatingAi();
    } else {
      openFloatingAi();
    }
  }

  // Global helper for opening floating window from anywhere
  window.openFloatingAiChat = openFloatingAi;
  window.toggleFloatingAiChat = toggleFloatingAi;

  launcher.addEventListener('click', toggleFloatingAi);
  btnClose.addEventListener('click', closeFloatingAi);

  // Close on Escape key
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && windowEl.classList.contains('active')) {
      closeFloatingAi();
    }
  });

  // Character counter & Auto-resize textarea
  messageInput.addEventListener('input', function () {
    const len = this.value.length;
    charCounter.textContent = `${len} / 1000`;
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 80) + 'px';
  });

  // Submit on Enter (without Shift)
  messageInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      chatForm.dispatchEvent(new Event('submit', { cancelable: true }));
    }
  });

  // Delegate quick chip clicks
  chatBody.addEventListener('click', function (e) {
    const chip = e.target.closest('.quick-chip-btn');
    if (chip) {
      const prompt = chip.getAttribute('data-prompt');
      if (prompt) {
        messageInput.value = prompt;
        chatForm.dispatchEvent(new Event('submit', { cancelable: true }));
      }
    }
  });

  // Append User message to floating window
  function appendUserBubble(text, timeStr) {
    const row = document.createElement('div');
    row.className = 'ai-msg-row ai-msg-user';
    row.innerHTML = `
      <div class="ai-msg-bubble user-bubble">
        <div class="ai-bubble-time text-end">${timeStr}</div>
        <div class="ai-bubble-text">${escapeHtml(text)}</div>
      </div>
    `;
    chatBody.insertBefore(row, typingEl);
    scrollToBottom();
  }

  // Append AI message to floating window
  function appendAiBubble(text, timeStr) {
    const row = document.createElement('div');
    row.className = 'ai-msg-row ai-msg-bot';
    row.innerHTML = `
      <div class="ai-bubble-avatar">
        <i class="bi bi-robot"></i>
      </div>
      <div class="ai-msg-bubble">
        <div class="ai-bubble-header">
          <span class="ai-bubble-title">Asisten Kesehatan SIMKES</span>
          <span class="ai-bubble-time">${timeStr}</span>
        </div>
        <div class="ai-bubble-text">${formatMarkdown(text)}</div>
      </div>
    `;
    chatBody.insertBefore(row, typingEl);
    scrollToBottom();
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  function formatMarkdown(text) {
    let safe = escapeHtml(text);
    safe = safe.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    safe = safe.replace(/\*(.*?)\*/g, '<em>$1</em>');
    safe = safe.replace(/\n/g, '<br>');
    return safe;
  }

  // Submit form via AJAX
  chatForm.addEventListener('submit', function (e) {
    e.preventDefault();
    const text = messageInput.value.trim();
    if (!text) return;

    if (text.length > 1000) {
      errorEl.textContent = 'Maksimal 1.000 karakter per pertanyaan.';
      errorEl.classList.remove('d-none');
      return;
    }

    errorEl.classList.add('d-none');
    messageInput.value = '';
    messageInput.style.height = 'auto';
    charCounter.textContent = '0 / 1000';

    const now = new Date();
    const timeStr = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

    appendUserBubble(text, timeStr);

    // Tampilkan loading spinner & typing indicator
    sendIcon.classList.add('d-none');
    sendSpinner.classList.remove('d-none');
    typingEl.classList.remove('d-none');
    scrollToBottom();

    const csrfTokenName  = csrfInput.getAttribute('name');
    const csrfTokenValue = csrfInput.value;

    const formData = new URLSearchParams();
    formData.append(csrfTokenName, csrfTokenValue);
    formData.append('message', text);

    fetch('<?= base_url('/asisten-ai/chat') ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
      // Perbarui CSRF token untuk request berikutnya
      if (data.csrf_token && data.csrf_hash) {
        csrfInput.setAttribute('name', data.csrf_token);
        csrfInput.value = data.csrf_hash;
      }

      if (data.success) {
        appendAiBubble(data.message, data.time || timeStr);
      } else {
        errorEl.textContent = data.error || 'Terjadi kesalahan pada layanan AI.';
        errorEl.classList.remove('d-none');
        scrollToBottom();
      }
    })
    .catch(() => {
      errorEl.textContent = 'Gagal menghubungi server. Periksa koneksi jaringan Anda.';
      errorEl.classList.remove('d-none');
      scrollToBottom();
    })
    .finally(() => {
      sendIcon.classList.remove('d-none');
      sendSpinner.classList.add('d-none');
      typingEl.classList.add('d-none');
      messageInput.focus();
    });
  });

  // Reset percakapan via AJAX
  btnReset.addEventListener('click', function () {
    if (!confirm('Bersihkan riwayat percakapan dengan Asisten Kesehatan?')) return;

    const csrfTokenName  = csrfInput.getAttribute('name');
    const csrfTokenValue = csrfInput.value;

    const formData = new URLSearchParams();
    formData.append(csrfTokenName, csrfTokenValue);

    fetch('<?= base_url('/asisten-ai/reset') ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
      if (data.csrf_token && data.csrf_hash) {
        csrfInput.setAttribute('name', data.csrf_token);
        csrfInput.value = data.csrf_hash;
      }
      // Hapus pesan dinamis selain pesan pembuka
      const bubbles = chatBody.querySelectorAll('.ai-msg-row:not(:first-child)');
      bubbles.forEach(b => {
        if (b.id !== 'floatingAiTyping') b.remove();
      });
      errorEl.classList.add('d-none');
    });
  });

  // =========================================================================
  // Panel Pengaturan Konfigurasi AI & Model
  // =========================================================================
  const btnSettings        = document.getElementById('btnFloatingAiSettings');
  const configPanel        = document.getElementById('floatingAiConfigPanel');
  const btnCloseConfig     = document.getElementById('btnCloseAiConfig');
  const formAiConfig       = document.getElementById('formAiConfig');
  const aiProviderSelect   = document.getElementById('aiProviderSelect');
  const aiModelInput       = document.getElementById('aiModelInput');
  const aiApiKeyInput      = document.getElementById('aiApiKeyInput');
  const btnToggleShowKey   = document.getElementById('btnToggleShowKey');
  const btnResetAiKey      = document.getElementById('btnResetAiKey');
  const alertConfigSuccess = document.getElementById('alertConfigSuccess');
  const alertConfigError   = document.getElementById('alertConfigError');
  const iconSaveConfig     = document.getElementById('iconSaveConfig');
  const spinnerSaveConfig  = document.getElementById('spinnerSaveConfig');
  const aiKeyStatusBadge   = document.getElementById('aiKeyStatusBadge');
  const aiKeyHint          = document.getElementById('aiKeyHint');

  // Load konfigurasi dari server
  function loadAiConfig() {
    fetch('<?= base_url('/asisten-ai/config') ?>', {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        if (aiProviderSelect) aiProviderSelect.value = data.provider || 'gemini';
        if (aiModelInput) aiModelInput.value = data.model || 'gemini-1.5-flash';
        if (data.has_key) {
          aiKeyStatusBadge.classList.remove('d-none');
          if (aiKeyHint) {
            aiKeyHint.textContent = `Tersimpan (${data.masked_key}). Kosongkan jika tidak ingin mengubah.`;
          }
        } else {
          aiKeyStatusBadge.classList.add('d-none');
          if (aiKeyHint) {
            aiKeyHint.textContent = 'Belum ada API Key (menggunakan mode simulasi offline).';
          }
        }
      }
    })
    .catch(() => {});
  }

  if (btnSettings && configPanel) {
    btnSettings.addEventListener('click', function () {
      configPanel.classList.toggle('d-none');
      if (!configPanel.classList.contains('d-none')) {
        loadAiConfig();
      }
    });
  }

  if (btnCloseConfig) {
    btnCloseConfig.addEventListener('click', function () {
      configPanel.classList.add('d-none');
    });
  }

  // Toggle tampilkan / sembunyikan API Key
  if (btnToggleShowKey && aiApiKeyInput) {
    btnToggleShowKey.addEventListener('click', function () {
      const isPassword = aiApiKeyInput.type === 'password';
      aiApiKeyInput.type = isPassword ? 'text' : 'password';
      const icon = this.querySelector('i');
      if (icon) {
        icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
      }
    });
  }

  // Preset model badge click
  document.querySelectorAll('.model-preset-badge').forEach(badge => {
    badge.addEventListener('click', function () {
      const provider = this.getAttribute('data-provider');
      const model    = this.getAttribute('data-model');
      if (aiProviderSelect && provider) aiProviderSelect.value = provider;
      if (aiModelInput && model) aiModelInput.value = model;
    });
  });

  // Ganti provider otomatis sesuaikan default model jika kosong
  if (aiProviderSelect && aiModelInput) {
    aiProviderSelect.addEventListener('change', function () {
      if (this.value === 'openai' && aiModelInput.value.includes('gemini')) {
        aiModelInput.value = 'gpt-4o-mini';
      } else if (this.value === 'gemini' && aiModelInput.value.includes('gpt')) {
        aiModelInput.value = 'gemini-1.5-flash';
      }
    });
  }

  // Simpan konfigurasi
  if (formAiConfig) {
    formAiConfig.addEventListener('submit', function (e) {
      e.preventDefault();
      alertConfigSuccess.classList.add('d-none');
      alertConfigError.classList.add('d-none');
      iconSaveConfig.classList.add('d-none');
      spinnerSaveConfig.classList.remove('d-none');

      const csrfTokenName  = csrfInput.getAttribute('name');
      const csrfTokenValue = csrfInput.value;

      const formData = new URLSearchParams();
      formData.append(csrfTokenName, csrfTokenValue);
      formData.append('provider', aiProviderSelect.value);
      formData.append('model', aiModelInput.value);
      formData.append('api_key', aiApiKeyInput.value);

      fetch('<?= base_url('/asisten-ai/config') ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData.toString()
      })
      .then(res => res.json())
      .then(data => {
        if (data.csrf_token && data.csrf_hash) {
          csrfInput.setAttribute('name', data.csrf_token);
          csrfInput.value = data.csrf_hash;
        }

        if (data.success) {
          alertConfigSuccess.textContent = data.message;
          alertConfigSuccess.classList.remove('d-none');
          aiApiKeyInput.value = '';
          loadAiConfig();
          setTimeout(() => {
            alertConfigSuccess.classList.add('d-none');
            configPanel.classList.add('d-none');
          }, 1500);
        } else {
          alertConfigError.textContent = data.error || 'Gagal menyimpan konfigurasi.';
          alertConfigError.classList.remove('d-none');
        }
      })
      .catch(err => {
        alertConfigError.textContent = 'Gagal menyimpan konfigurasi. Periksa koneksi server.';
        alertConfigError.classList.remove('d-none');
      })
      .finally(() => {
        iconSaveConfig.classList.remove('d-none');
        spinnerSaveConfig.classList.add('d-none');
      });
    });
  }

  // Tombol hapus API key
  if (btnResetAiKey) {
    btnResetAiKey.addEventListener('click', function () {
      if (!confirm('Hapus API Key yang tersimpan? Sistem akan kembali ke mode simulasi offline.')) return;

      const csrfTokenName  = csrfInput.getAttribute('name');
      const csrfTokenValue = csrfInput.value;

      const formData = new URLSearchParams();
      formData.append(csrfTokenName, csrfTokenValue);
      formData.append('provider', aiProviderSelect.value);
      formData.append('model', aiModelInput.value);
      formData.append('clear_key', '1');

      fetch('<?= base_url('/asisten-ai/config') ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData.toString()
      })
      .then(res => res.json())
      .then(data => {
        if (data.csrf_token && data.csrf_hash) {
          csrfInput.setAttribute('name', data.csrf_token);
          csrfInput.value = data.csrf_hash;
        }
        alertConfigSuccess.textContent = 'API Key berhasil dihapus. Kembali ke mode simulasi.';
        alertConfigSuccess.classList.remove('d-none');
        aiApiKeyInput.value = '';
        loadAiConfig();
        setTimeout(() => alertConfigSuccess.classList.add('d-none'), 2000);
      });
    });
  }

  // =========================================================================
  // Sistem Notifikasi Santri Sakit & Perawatan di Chatbot
  // =========================================================================
  const btnNotif          = document.getElementById('btnFloatingAiNotif');
  const notifPanel        = document.getElementById('floatingAiNotifPanel');
  const btnCloseNotif     = document.getElementById('btnCloseAiNotif');
  const notifList         = document.getElementById('aiNotifList');
  const launcherBadge     = document.getElementById('aiLauncherNotifBadge');
  const headerNotifDot    = document.getElementById('aiHeaderNotifDot');
  const fabToast          = document.getElementById('aiFabNotifToast');
  const toastBadge        = document.getElementById('aiNotifToastBadge');
  const toastText         = document.getElementById('aiNotifToastText');
  const btnCloseToast     = document.getElementById('btnCloseNotifToast');

  function checkAiNotifications() {
    fetch('<?= base_url('/asisten-ai/notifications') ?>', {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
      if (data.success && data.unread_count > 0) {
        // Tampilkan badge angka di tombol FAB
        if (launcherBadge) {
          launcherBadge.textContent = data.unread_count;
          launcherBadge.classList.remove('d-none');
        }
        // Tampilkan dot merah di lonceng header
        if (headerNotifDot) {
          headerNotifDot.classList.remove('d-none');
        }

        // Tampilkan toast notifikasi di atas FAB jika window chat sedang tertutup
        if (!windowEl.classList.contains('active') && fabToast) {
          if (toastBadge) toastBadge.textContent = data.unread_count;
          if (toastText) {
            toastText.textContent = `${data.unread_count} santri butuh perhatian kesehatan`;
          }
          fabToast.classList.remove('d-none');
        }

        // Susun daftar notifikasi di panel
        if (notifList) {
          let html = '';
          if (data.notifications && data.notifications.length > 0) {
            data.notifications.forEach(n => {
              const badgeClass = n.type === 'periksa' ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle';
              const iconClass  = n.type === 'periksa' ? 'bi-search' : 'bi-hospital-fill';
              html += `
                <div class="notif-card-item" onclick="openFloatingAi(); sendQuickPrompt('ada berapa santri yang ${n.type === 'periksa' ? 'sedang diperiksa' : 'dirawat'}');">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="badge ${badgeClass} small fw-semibold" style="font-size: 0.68rem;">
                      <i class="bi ${iconClass} me-1"></i> ${n.badge} Kasus
                    </span>
                    <span class="small text-muted" style="font-size: 0.68rem;">Klik untuk cek &rarr;</span>
                  </div>
                  <div class="fw-bold small text-dark">${n.title}</div>
                  <div class="text-muted" style="font-size: 0.72rem;">${n.message}</div>
                </div>
              `;
            });
          }

          if (data.cases && data.cases.length > 0) {
            html += `<div class="fw-bold text-muted small mt-2 mb-1" style="font-size: 0.68rem; text-transform: uppercase;">Pasien Terkini:</div>`;
            data.cases.forEach(c => {
              html += `
                <div class="p-1.5 rounded border bg-white small d-flex align-items-center justify-content-between mb-1" style="font-size: 0.72rem;">
                  <div>
                    <strong>${c.nama_santri || 'Santri'}</strong> <span class="text-muted">(${c.nama_kamar || '-'})</span>
                    <div class="text-muted" style="font-size: 0.65rem;">${c.gejala || '-'}</div>
                  </div>
                  <span class="badge bg-light text-dark border" style="font-size: 0.62rem;">${c.status}</span>
                </div>
              `;
            });
          }

          notifList.innerHTML = html;
        }
      } else {
        if (launcherBadge) launcherBadge.classList.add('d-none');
        if (headerNotifDot) headerNotifDot.classList.add('d-none');
        if (fabToast) fabToast.classList.add('d-none');
      }
    })
    .catch(() => {});
  }

  // Klik toast notifikasi membuka chat
  if (fabToast) {
    fabToast.addEventListener('click', function (e) {
      if (e.target.id === 'btnCloseNotifToast') return;
      openFloatingAi();
      fabToast.classList.add('d-none');
    });
  }

  // Tutup toast
  if (btnCloseToast) {
    btnCloseToast.addEventListener('click', function (e) {
      e.stopPropagation();
      fabToast.classList.add('d-none');
    });
  }

  // Klik lonceng notifikasi di header
  if (btnNotif && notifPanel) {
    btnNotif.addEventListener('click', function () {
      notifPanel.classList.toggle('d-none');
      if (headerNotifDot) headerNotifDot.classList.add('d-none');
    });
  }

  if (btnCloseNotif) {
    btnCloseNotif.addEventListener('click', function () {
      notifPanel.classList.add('d-none');
    });
  }

  // Helper kirim prompt cepat dari notifikasi
  window.sendQuickPrompt = function(promptText) {
    if (notifPanel) notifPanel.classList.add('d-none');
    if (messageInput) {
      messageInput.value = promptText;
      chatForm.dispatchEvent(new Event('submit'));
    }
  };

  // Cek notifikasi saat halaman dimuat dan setiap 45 detik
  setTimeout(checkAiNotifications, 1000);
  setInterval(checkAiNotifications, 45000);

  // Auto-open jika ada hash #open-ai atau query param ?open_ai=1
  if (window.location.hash === '#open-ai' || window.location.search.includes('open_ai=1')) {
    setTimeout(openFloatingAi, 400);
  }
});
</script>

<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title ?? 'Asisten AI Kesehatan Santri - SIMKES Santri') ?>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  .chat-box {
    height: 520px;
    overflow-y: auto;
    background-color: #f8fafc;
    border-radius: 1rem;
    padding: 1.25rem;
    scroll-behavior: smooth;
  }
  .chat-bubble-user {
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
    color: #ffffff;
    border-radius: 1.25rem 1.25rem 0.25rem 1.25rem;
    padding: 0.85rem 1.15rem;
    max-width: 82%;
    word-wrap: break-word;
    box-shadow: 0 2px 6px rgba(13, 148, 136, 0.2);
  }
  .chat-bubble-ai {
    background-color: #ffffff;
    color: #1e293b;
    border: 1px solid #e2e8f0;
    border-radius: 1.25rem 1.25rem 1.25rem 0.25rem;
    padding: 1rem 1.25rem;
    max-width: 88%;
    word-wrap: break-word;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
  }
  .chat-bubble-ai p:last-child {
    margin-bottom: 0;
  }
  .chat-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .typing-indicator span {
    display: inline-block;
    width: 7px;
    height: 7px;
    background-color: #0d9488;
    border-radius: 50%;
    margin: 0 2px;
    animation: typing 1.4s infinite ease-in-out both;
  }
  .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
  .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
  @keyframes typing {
    0%, 80%, 100% { transform: scale(0); opacity: 0.4; }
    40% { transform: scale(1); opacity: 1; }
  }
  .quick-chip {
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid #cbd5e1;
    background-color: #ffffff;
    color: #334155;
    font-size: 0.825rem;
    padding: 0.4rem 0.85rem;
    border-radius: 2rem;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
  }
  .quick-chip:hover {
    background-color: #f0fdfa;
    border-color: #0d9488;
    color: #0f766e;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(13, 148, 136, 0.15);
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0">

  <!-- Header Banner -->
  <div class="card border-0 rounded-4 p-4 text-white position-relative overflow-hidden shadow-sm mb-4" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 60%, #0284c7 100%);">
    <div class="row align-items-center position-relative" style="z-index: 2;">
      <div class="col-lg-8">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="badge bg-white bg-opacity-25 text-white px-3 py-1.5 rounded-pill small fw-semibold">
            <i class="bi bi-robot me-1"></i> Asisten AI Poskestren
          </span>
          <?php if (!empty($providerInfo['is_mock'])): ?>
            <span class="badge bg-warning text-dark px-2.5 py-1 rounded-pill small fw-semibold">
              <i class="bi bi-cpu-fill me-1"></i> Mode Simulasi Edukasi
            </span>
          <?php else: ?>
            <span class="badge bg-success text-white px-2.5 py-1 rounded-pill small fw-semibold">
              <i class="bi bi-broadcast me-1"></i> <?= esc(strtoupper($providerInfo['provider'])) ?>: <?= esc($providerInfo['model']) ?>
            </span>
          <?php endif; ?>
        </div>
        <h2 class="fw-bold mb-1">
          Konsultasi & Edukasi Kesehatan AI
        </h2>
        <p class="mb-0 text-white-50" style="font-size: 0.95rem;">
          Bantuan pemahaman istilah medis, tanda bahaya kesehatan (*red flags*), dan penyusunan draf keluhan santri sebelum diperiksa di Poskestren.
        </p>
      </div>
      <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
        <div class="d-inline-flex gap-2">
          <button type="button" class="btn btn-light text-teal rounded-pill px-3 py-2 btn-sm fw-semibold shadow-sm" onclick="if(window.openFloatingAiChat){window.openFloatingAiChat();document.getElementById('btnFloatingAiSettings').click();}">
            <i class="bi bi-gear-fill me-1"></i> Pengaturan API & Model
          </button>
          <button type="button" class="btn btn-outline-light rounded-pill px-3 py-2 btn-sm" id="btnResetChat" data-bs-toggle="modal" data-bs-target="#modalResetChat">
            <i class="bi bi-trash3 me-1"></i> Bersihkan
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Medical Safety & Emergency Disclaimer (Wajib & Menonjol) -->
  <div class="alert alert-warning border-0 border-start border-4 border-warning rounded-3 shadow-sm p-3 mb-4 d-flex align-items-start gap-3 bg-warning-subtle" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-4 text-warning flex-shrink-0 mt-1"></i>
    <div class="small text-dark">
      <div class="fw-bold mb-1" style="font-size: 0.95rem;">
        Pemberitahuan Etika & Batasan Medis SIMKES Santri:
      </div>
      <div>
        1. <strong>Bukan Pengganti Dokter:</strong> Jawaban AI bersifat informatif & edukatif, bukan diagnosis definitif medis atau resep obat resmi.<br>
        2. <strong>Larangan Obat Otomatis:</strong> AI tidak berhak menentukan dosis atau meresepkan antibiotik/obat keras. Pemberian obat wajib melalui pemeriksaan dokter/tenaga medis Poskestren.<br>
        3. <strong>Kegawatdaruratan:</strong> Jika santri mengalami sesak napas berat, kejang, demam tinggi >39°C berhari-hari, muntah terus-menerus, atau penurunan kesadaran, <strong>SEGERA RUJUK KE POSKESTREN / UGD TERDEKAT!</strong>
      </div>
    </div>
  </div>

  <!-- Petunjuk Konfigurasi API Server (Tampil jika Mode Simulasi) -->
  <?php if (!empty($providerInfo['is_mock'])): ?>
    <div class="card border-0 bg-light rounded-3 p-3 mb-4 shadow-sm">
      <div class="d-flex align-items-center justify-content-between cursor-pointer" data-bs-toggle="collapse" data-bs-target="#collapseApiInfo" aria-expanded="false">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-info-circle-fill text-primary"></i>
          <span class="fw-semibold text-secondary small">Petunjuk Administrator: Menghubungkan ke Google Gemini API Live</span>
        </div>
        <i class="bi bi-chevron-down text-muted small"></i>
      </div>
      <div class="collapse mt-3" id="collapseApiInfo">
        <div class="card card-body bg-white border small text-muted">
          <p class="mb-2">Saat ini sistem berjalan pada <strong>Mode Simulasi Edukasi Poskestren</strong> menggunakan pangkalan pengetahuan medis lokal terverifikasi. Untuk menghubungkan ke API langsung (Google Gemini / OpenAI compatible):</p>
          <ol class="mb-2 ps-3">
            <li>Buka berkas konfigurasi <code>.env</code> di root instalasi server.</li>
            <li>Tambahkan kunci API Anda: <code>AI_API_KEY=kunci_rahasia_anda_disini</code></li>
            <li>Simpan berkas. Kunci API Anda tersimpan aman di server dan <strong>tidak akan pernah</strong> diekspos ke browser atau JavaScript klien.</li>
          </ol>
          <div class="text-secondary fst-italic">Mode simulasi tetap aktif dan dapat digunakan dengan aman jika Anda tidak memiliki kunci API.</div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Kolom Utama: Chat Interface -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="chat-avatar bg-teal-subtle text-teal" style="background-color: #ccfbf1; color: #0f766e;">
              <i class="bi bi-heart-pulse-fill fs-5"></i>
            </div>
            <div>
              <h6 class="mb-0 fw-bold text-dark">Asisten Kesehatan</h6>
              <span class="badge bg-success-subtle text-success small" style="font-size: 0.7rem;">
                <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> Aktif Melayani
              </span>
            </div>
          </div>
          <div>
            <span class="badge bg-light text-secondary border small">
              <i class="bi bi-shield-lock-fill text-success me-1"></i> Data Terenkripsi & Disanitasi
            </span>
          </div>
        </div>

        <!-- Chat Container Body -->
        <div class="card-body p-3">
          <div class="chat-box d-flex flex-column gap-3" id="chatContainer">
            
            <!-- Pesan Pembuka Default -->
            <div class="d-flex align-items-start gap-2 mb-2">
              <div class="chat-avatar bg-teal-subtle text-teal" style="background-color: #ccfbf1; color: #0f766e;">
                <i class="bi bi-robot fs-5"></i>
              </div>
              <div class="chat-bubble-ai">
                <div class="d-flex align-items-center justify-content-between mb-1 pb-1 border-bottom">
                  <span class="fw-bold small text-teal" style="color: #0f766e;">Asisten Kesehatan SIMKES</span>
                  <span class="text-muted" style="font-size: 0.7rem;">Sistem</span>
                </div>
                <div class="small">
                  Saya adalah <strong>Asisten Kesehatan SIMKES Santri</strong> di lingkungan pesantren.
                  <ul class="mb-2 ps-3 mt-1">
                    <li>Menjelaskan istilah medis dalam rekam santri (seperti <em>febris, cephalgia, dispepsia</em>).</li>
                    <li>Panduan pertolongan pertama dasar untuk luka, terkilir, demam, dan flu.</li>
                    <li>Mengenali tanda bahaya (<em>red flags</em>) kapan santri wajib segera dibawa ke Poskestren/RS.</li>
                    <li>Menyusun draf ringkasan keluhan santri sebelum diserahkan ke tim medis.</li>
                  </ul>
                  <span class="text-muted fst-italic">Khusus melayani bidang kesehatan. Pertanyaan di luar topik kesehatan akan otomatis ditolak.</span>
                </div>
              </div>
            </div>

            <!-- Loop Riwayat Chat Sesi -->
            <?php if (!empty($chatHistory)): ?>
              <?php foreach ($chatHistory as $msg): ?>
                <?php if (($msg['role'] ?? '') === 'user'): ?>
                  <!-- Pesan Pengguna -->
                  <div class="d-flex align-items-start gap-2 justify-content-end">
                    <div class="chat-bubble-user">
                      <div class="small text-white-50 text-end mb-1" style="font-size: 0.7rem;">
                        Anda &bull; <?= esc($msg['time'] ?? date('H:i')) ?>
                      </div>
                      <div class="small" style="white-space: pre-wrap;"><?= esc($msg['content'] ?? '') ?></div>
                    </div>
                    <div class="chat-avatar bg-secondary text-white">
                      <i class="bi bi-person-fill"></i>
                    </div>
                  </div>
                <?php else: ?>
                  <!-- Balasan AI -->
                  <div class="d-flex align-items-start gap-2">
                    <div class="chat-avatar bg-teal-subtle text-teal" style="background-color: #ccfbf1; color: #0f766e;">
                      <i class="bi bi-robot fs-5"></i>
                    </div>
                    <div class="chat-bubble-ai">
                      <div class="d-flex align-items-center justify-content-between mb-1 pb-1 border-bottom">
                        <span class="fw-bold small text-teal" style="color: #0f766e;">Asisten Kesehatan SIMKES</span>
                        <div class="d-flex align-items-center gap-2">
                          <span class="text-muted" style="font-size: 0.7rem;"><?= esc($msg['time'] ?? date('H:i')) ?></span>
                          <button type="button" class="btn btn-link btn-sm p-0 text-muted btn-copy-msg" title="Salin Jawaban" data-content="<?= esc($msg['content'] ?? '', 'attr') ?>">
                            <i class="bi bi-clipboard"></i>
                          </button>
                        </div>
                      </div>
                      <div class="small ai-content" style="white-space: pre-wrap;"><?= esc($msg['content'] ?? '') ?></div>
                    </div>
                  </div>
                <?php endif; ?>
              <?php endforeach; ?>
            <?php endif; ?>

            <!-- Indikator Loading / Mengetik (Hidden by default) -->
            <div id="typingIndicator" class="d-none align-items-start gap-2">
              <div class="chat-avatar bg-teal-subtle text-teal" style="background-color: #ccfbf1; color: #0f766e;">
                <i class="bi bi-robot fs-5"></i>
              </div>
              <div class="chat-bubble-ai py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                  <div class="typing-indicator">
                    <span></span><span></span><span></span>
                  </div>
                  <span class="text-muted small fst-italic">Asisten sedang menyusun jawaban edukatif...</span>
                </div>
              </div>
            </div>

            <!-- Tempat Pesan Error Dinamis -->
            <div id="chatErrorContainer" class="d-none"></div>

          </div>
        </div>

        <!-- Chat Form Input Footer -->
        <div class="card-footer bg-white border-top p-3">
          <form id="chatForm">
            <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrfInput">
            <div class="input-group">
              <textarea 
                class="form-control border-end-0 rounded-start-3" 
                id="messageInput" 
                name="message" 
                rows="2" 
                placeholder="Ketik pertanyaan kesehatan atau ketik keluhan santri untuk dirangkum... (Tekan Enter untuk kirim, Shift+Enter untuk baris baru)" 
                maxlength="1000"
                required
              ></textarea>
              <button class="btn btn-teal px-4 rounded-end-3 d-flex align-items-center gap-2 text-white" type="submit" id="btnSubmitChat" style="background-color: #0d9488;">
                <span id="btnSendText"><i class="bi bi-send-fill"></i> Kirim</span>
                <span id="btnSendSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
              </button>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-2">
              <span class="small text-muted" style="font-size: 0.78rem;">
                <i class="bi bi-shield-check text-success me-1"></i> Data santri disanitasi otomatis sebelum diproses.
              </span>
              <span class="small text-muted" id="charCounter" style="font-size: 0.78rem;">0 / 1000 karakter</span>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Kolom Kanan: Quick Prompts & Info Poskestren -->
    <div class="col-lg-4">
      <div class="d-flex flex-column gap-3">
        
        <!-- Kartu Topik Pertanyaan Cepat -->
        <div class="card border-0 shadow-sm rounded-4 p-3">
          <div class="card-title fw-bold text-dark mb-2 d-flex align-items-center gap-2" style="font-size: 0.95rem;">
            <i class="bi bi-lightbulb-fill text-warning"></i> Saran Topik & Pertanyaan
          </div>
          <p class="text-muted small mb-3">Klik tombol pertanyaan cepat untuk langsung mengirim prompt ke asisten AI:</p>
          
          <div class="d-flex flex-column gap-2">
            <button type="button" class="btn text-start p-2.5 rounded-3 border bg-light quick-action-btn" data-prompt="Siapa yang sedang sakit?">
              <div class="fw-semibold text-dark small"><i class="bi bi-thermometer-high text-danger me-1"></i> Siapa yang Sedang Sakit?</div>
              <div class="text-muted" style="font-size: 0.75rem;">Daftar santri yang sedang sakit, kamar, gejala, dan statusnya.</div>
            </button>

            <button type="button" class="btn text-start p-2.5 rounded-3 border bg-light quick-action-btn" data-prompt="Siapa nama santri terdaftar aktif?">
              <div class="fw-semibold text-dark small"><i class="bi bi-people-fill text-teal me-1" style="color: #0d9488;"></i> Daftar Santri Terdaftar Aktif</div>
              <div class="text-muted" style="font-size: 0.75rem;">Melihat nama-nama santri aktif langsung dari database.</div>
            </button>

            <button type="button" class="btn text-start p-2.5 rounded-3 border bg-light quick-action-btn" data-prompt="Ada berapa siswa yang sakit, dirawat, dan sembuh?">
              <div class="fw-semibold text-dark small"><i class="bi bi-bar-chart-fill text-info me-1"></i> Rekap Santri Sakit, Rawat & Sembuh</div>
              <div class="text-muted" style="font-size: 0.75rem;">Data langsung dan realtime dari database SIMKES.</div>
            </button>

            <button type="button" class="btn text-start p-2.5 rounded-3 border bg-light quick-action-btn" data-prompt="Siapa saya?">
              <div class="fw-semibold text-dark small"><i class="bi bi-person-badge text-primary me-1"></i> Siapa Saya?</div>
              <div class="text-muted" style="font-size: 0.75rem;">Identitas akun, peran Anda, dan lingkup kamar.</div>
            </button>

            <button type="button" class="btn text-start p-2.5 rounded-3 border bg-light quick-action-btn" data-prompt="Apa arti istilah febris dan berapa batas suhu tubuh normal santri?">
              <div class="fw-semibold text-dark small"><i class="bi bi-thermometer-half text-danger me-1"></i> Apa itu Febris & Suhu Normal?</div>
              <div class="text-muted" style="font-size: 0.75rem;">Penjelasan demam dan batas suhu tubuh.</div>
            </button>

            <button type="button" class="btn text-start p-2.5 rounded-3 border bg-light quick-action-btn" data-prompt="Bagaimana etika dan pertolongan batuk pilek agar tidak cepat menular di kamar santri?">
              <div class="fw-semibold text-dark small"><i class="bi bi-virus text-warning me-1"></i> Batuk & Pilek di Kamar Santri</div>
              <div class="text-muted" style="font-size: 0.75rem;">Pencegahan penularan asrama & etika batuk.</div>
            </button>

            <button type="button" class="btn text-start p-2.5 rounded-3 border bg-light quick-action-btn" data-prompt="Bantu saya membuat draf ringkasan keluhan santri sakit untuk diserahkan ke Poskestren.">
              <div class="fw-semibold text-dark small"><i class="bi bi-file-earmark-medical text-primary me-1"></i> Format Ringkasan Keluhan Santri</div>
              <div class="text-muted" style="font-size: 0.75rem;">Draf laporan terstruktur untuk Poskestren.</div>
            </button>

            <button type="button" class="btn text-start p-2.5 rounded-3 border bg-light quick-action-btn" data-prompt="Apa pertolongan pertama untuk santri yang mengalami cedera terkilir saat berolahraga?">
              <div class="fw-semibold text-dark small"><i class="bi bi-bandaid text-success me-1"></i> Pertolongan Santri Terkilir / Luka</div>
              <div class="text-muted" style="font-size: 0.75rem;">Metode R.I.C.E dan perawatan luka lecet.</div>
            </button>

            <button type="button" class="btn text-start p-2.5 rounded-3 border bg-light quick-action-btn" data-prompt="Apa tanda bahaya (red flags) demam dan sakit kepala yang mewajibkan santri segera dirujuk ke Rumah Sakit?">
              <div class="fw-semibold text-dark small"><i class="bi bi-exclamation-octagon text-danger me-1"></i> Tanda Bahaya (Red Flags) Rujukan</div>
              <div class="text-muted" style="font-size: 0.75rem;">Kondisi darurat yang butuh rujukan cepat.</div>
            </button>

            <button type="button" class="btn text-start p-2.5 rounded-3 border bg-light quick-action-btn" data-prompt="Bagaimana penanganan dan sanitasi asrama untuk santri yang mengalami gatal-gatal skabies?">
              <div class="fw-semibold text-dark small"><i class="bi bi-bug text-secondary me-1"></i> Skabies (Gudikan) & Sanitasi Asrama</div>
              <div class="text-muted" style="font-size: 0.75rem;">Edukasi kutu kasur & sterilisasi pakaian.</div>
            </button>
          </div>
        </div>

        <!-- Kartu Kontak Darurat Poskestren -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
          <div class="fw-bold text-dark mb-2 d-flex align-items-center gap-2" style="font-size: 0.95rem;">
            <i class="bi bi-telephone-plus-fill text-danger"></i> Akses Darurat Poskestren
          </div>
          <div class="small text-muted mb-2">
            Jika santri memerlukan penanganan medis darurat, jangan tunda:
          </div>
          <div class="d-flex flex-column gap-2">
            <a href="<?= base_url('/laporan-kesehatan/new') ?>" class="btn btn-outline-danger btn-sm text-start py-2">
              <i class="bi bi-plus-circle-fill me-1"></i> Input Laporan Santri Sakit Sekarang
            </a>
            <a href="<?= base_url('/dashboard') ?>" class="btn btn-outline-secondary btn-sm text-start py-2">
              <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard Utama
            </a>
          </div>
        </div>

      </div>
    </div>
  </div>

</div>

<!-- Modal Konfirmasi Reset Percakapan -->
<div class="modal fade" id="modalResetChat" tabindex="-1" aria-labelledby="modalResetChatLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalResetChatLabel">Bersihkan Riwayat Obrolan?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-secondary small py-3">
        Seluruh pesan percakapan dengan Asisten AI pada sesi ini akan dibersihkan dari tampilan dan memori sesi Anda. Tindakan ini tidak dapat dibatalkan.
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-danger rounded-pill px-3" id="confirmResetBtn">Ya, Bersihkan</button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const chatForm         = document.getElementById('chatForm');
  const messageInput     = document.getElementById('messageInput');
  const chatContainer    = document.getElementById('chatContainer');
  const typingIndicator  = document.getElementById('typingIndicator');
  const btnSubmitChat    = document.getElementById('btnSubmitChat');
  const btnSendText      = document.getElementById('btnSendText');
  const btnSendSpinner   = document.getElementById('btnSendSpinner');
  const charCounter      = document.getElementById('charCounter');
  const csrfInput        = document.getElementById('csrfInput');
  const chatErrorContainer = document.getElementById('chatErrorContainer');
  const confirmResetBtn  = document.getElementById('confirmResetBtn');
  const modalResetEl     = document.getElementById('modalResetChat');
  const quickActionBtns  = document.querySelectorAll('.quick-action-btn');

  // Scroll chat box to bottom
  function scrollToBottom() {
    chatContainer.scrollTop = chatContainer.scrollHeight;
  }
  scrollToBottom();

  // Karakter Counter
  messageInput.addEventListener('input', function () {
    const len = messageInput.value.length;
    charCounter.textContent = `${len} / 1000 karakter`;
    if (len > 950) {
      charCounter.classList.add('text-danger');
      charCounter.classList.remove('text-muted');
    } else {
      charCounter.classList.remove('text-danger');
      charCounter.classList.add('text-muted');
    }
  });

  // Handle Enter to submit (Shift+Enter for newline)
  messageInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      chatForm.dispatchEvent(new Event('submit'));
    }
  });

  // Tombol aksi cepat (Quick Prompts)
  quickActionBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      const promptText = this.getAttribute('data-prompt');
      if (promptText) {
        messageInput.value = promptText;
        messageInput.dispatchEvent(new Event('input'));
        messageInput.focus();
        chatForm.dispatchEvent(new Event('submit'));
      }
    });
  });

  // Helper Sanitasi HTML & Format Teks AI
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function formatAiResponse(rawText) {
    let safe = escapeHtml(rawText);

    // Format Bold **text**
    safe = safe.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

    // Format Italic *text*
    safe = safe.replace(/\*(.*?)\*/g, '<em>$1</em>');

    // Format Alert Icons & Highlights
    safe = safe.replace(/🚨\s*(.*?)(?=\n|$)/g, '<div class="alert alert-danger py-1 px-2 my-1 small fw-semibold d-inline-block rounded">🚨 $1</div>');

    // Line breaks
    safe = safe.replace(/\n/g, '<br>');

    return safe;
  }

  // Tambahkan Pesan Pengguna ke UI
  function appendUserMessage(text, timeStr) {
    const userWrapper = document.createElement('div');
    userWrapper.className = 'd-flex align-items-start gap-2 justify-content-end';
    userWrapper.innerHTML = `
      <div class="chat-bubble-user">
        <div class="small text-white-50 text-end mb-1" style="font-size: 0.7rem;">
          Anda &bull; ${timeStr}
        </div>
        <div class="small" style="white-space: pre-wrap;">${escapeHtml(text)}</div>
      </div>
      <div class="chat-avatar bg-secondary text-white">
        <i class="bi bi-person-fill"></i>
      </div>
    `;
    // Sisipkan sebelum indikator mengetik
    chatContainer.insertBefore(userWrapper, typingIndicator);
    scrollToBottom();
  }

  // Tambahkan Pesan AI ke UI
  function appendAiMessage(text, timeStr, isMock) {
    const aiWrapper = document.createElement('div');
    aiWrapper.className = 'd-flex align-items-start gap-2';

    const formattedContent = formatAiResponse(text);

    aiWrapper.innerHTML = `
      <div class="chat-avatar bg-teal-subtle text-teal" style="background-color: #ccfbf1; color: #0f766e;">
        <i class="bi bi-robot fs-5"></i>
      </div>
      <div class="chat-bubble-ai">
        <div class="d-flex align-items-center justify-content-between mb-1 pb-1 border-bottom">
          <span class="fw-bold small text-teal" style="color: #0f766e;">Asisten Kesehatan SIMKES</span>
          <div class="d-flex align-items-center gap-2">
            <span class="text-muted" style="font-size: 0.7rem;">${timeStr}</span>
            <button type="button" class="btn btn-link btn-sm p-0 text-muted btn-copy-msg" title="Salin Jawaban">
              <i class="bi bi-clipboard"></i>
            </button>
          </div>
        </div>
        <div class="small ai-content">${formattedContent}</div>
      </div>
    `;

    // Pasang listener copy
    const copyBtn = aiWrapper.querySelector('.btn-copy-msg');
    if (copyBtn) {
      copyBtn.addEventListener('click', function () {
        navigator.clipboard.writeText(text).then(() => {
          copyBtn.innerHTML = '<i class="bi bi-check2 text-success"></i>';
          setTimeout(() => {
            copyBtn.innerHTML = '<i class="bi bi-clipboard"></i>';
          }, 2000);
        });
      });
    }

    chatContainer.insertBefore(aiWrapper, typingIndicator);
    scrollToBottom();
  }

  // Pasang copy listener untuk pesan history yang sudah dirender server
  document.querySelectorAll('.btn-copy-msg').forEach(btn => {
    btn.addEventListener('click', function () {
      const content = this.getAttribute('data-content') || this.closest('.chat-bubble-ai').querySelector('.ai-content').innerText;
      if (content) {
        navigator.clipboard.writeText(content).then(() => {
          btn.innerHTML = '<i class="bi bi-check2 text-success"></i>';
          setTimeout(() => {
            btn.innerHTML = '<i class="bi bi-clipboard"></i>';
          }, 2000);
        });
      }
    });
  });

  // Tampilkan Error UI
  function showChatError(errorText) {
    chatErrorContainer.className = 'alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 small my-2';
    chatErrorContainer.innerHTML = `
      <i class="bi bi-exclamation-circle-fill fs-5 flex-shrink-0"></i>
      <div><strong>Gagal:</strong> ${escapeHtml(errorText)}</div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    scrollToBottom();
  }

  // Kirim Form Chat via AJAX
  chatForm.addEventListener('submit', function (e) {
    e.preventDefault();

    const message = messageInput.value.trim();
    if (!message) return;

    if (message.length > 1000) {
      showChatError('Panjang pertanyaan maksimal 1.000 karakter per pesan.');
      return;
    }

    // Ambil token CSRF saat ini
    const csrfTokenName  = csrfInput.getAttribute('name');
    const csrfTokenValue = csrfInput.value;

    const now = new Date();
    const timeStr = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

    // Tampilkan pesan pengguna di UI
    appendUserMessage(message, timeStr);
    messageInput.value = '';
    charCounter.textContent = '0 / 1000 karakter';

    // Set UI State Loading
    typingIndicator.classList.remove('d-none');
    typingIndicator.classList.add('d-flex');
    btnSubmitChat.disabled = true;
    messageInput.disabled  = true;
    btnSendText.classList.add('d-none');
    btnSendSpinner.classList.remove('d-none');
    scrollToBottom();

    // Sembunyikan error sebelumnya jika ada
    chatErrorContainer.className = 'd-none';

    // Siapkan Payload Form-Data
    const formData = new FormData();
    formData.append('message', message);
    formData.append(csrfTokenName, csrfTokenValue);

    fetch('<?= base_url('/asisten-ai/chat') ?>', {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(async response => {
      const data = await response.json();

      // Perbarui token CSRF baru dari response
      if (data.csrf_token && data.csrf_hash) {
        csrfInput.setAttribute('name', data.csrf_token);
        csrfInput.value = data.csrf_hash;
      }

      if (!response.ok || !data.success) {
        throw new Error(data.error || 'Terjadi gangguan saat memproses jawaban AI.');
      }

      return data;
    })
    .then(data => {
      // Sembunyikan typing indicator
      typingIndicator.classList.add('d-none');
      typingIndicator.classList.remove('d-flex');

      // Tampilkan balasan AI
      appendAiMessage(data.message, data.time || timeStr, data.is_mock);
    })
    .catch(err => {
      typingIndicator.classList.add('d-none');
      typingIndicator.classList.remove('d-flex');
      showChatError(err.message || 'Koneksi ke asisten AI terputus. Silakan coba beberapa saat lagi.');
    })
    .finally(() => {
      btnSubmitChat.disabled = false;
      messageInput.disabled  = false;
      btnSendText.classList.remove('d-none');
      btnSendSpinner.classList.add('d-none');
      messageInput.focus();
    });
  });

  // Konfirmasi Reset Chat
  if (confirmResetBtn) {
    confirmResetBtn.addEventListener('click', function () {
      const csrfTokenName  = csrfInput.getAttribute('name');
      const csrfTokenValue = csrfInput.value;

      const formData = new FormData();
      formData.append(csrfTokenName, csrfTokenValue);

      confirmResetBtn.disabled = true;
      confirmResetBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Membersihkan...';

      fetch('<?= base_url('/asisten-ai/reset') ?>', {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(async response => {
        const data = await response.json();
        if (data.csrf_token && data.csrf_hash) {
          csrfInput.setAttribute('name', data.csrf_token);
          csrfInput.value = data.csrf_hash;
        }
        // Reload halaman untuk refresh state chat
        window.location.reload();
      })
      .catch(err => {
        alert('Gagal membersihkan percakapan: ' + err.message);
        confirmResetBtn.disabled = false;
        confirmResetBtn.textContent = 'Ya, Bersihkan';
      });
    });
  }
});
</script>
<?= $this->endSection() ?>

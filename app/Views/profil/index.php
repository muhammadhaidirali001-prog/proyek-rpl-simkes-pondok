<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Profil Profesi - <?= esc($user['nama']) ?> - SIMKES Santri
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0">

  <!-- Header Banner Profil -->
  <div class="card border-0 rounded-4 p-4 text-white position-relative overflow-hidden shadow-sm mb-4" 
       style="background: <?= ($profesi['role_canonical'] === 'admin') 
          ? 'linear-gradient(135deg, #1e293b 0%, #334155 50%, #0d9488 100%)' 
          : (($profesi['role_canonical'] === 'pengawas_kesehatan') 
              ? 'linear-gradient(135deg, #0369a1 0%, #0284c7 50%, #0d9488 100%)' 
              : 'linear-gradient(135deg, #0d9488 0%, #0f766e 50%, #15803d 100%)') ?>;">
    <div class="row align-items-center position-relative" style="z-index: 2;">
      <div class="col-lg-8">
        <div class="d-flex align-items-center gap-3 mb-2">
          <div class="rounded-circle bg-white bg-opacity-20 d-flex align-items-center justify-content-center text-white shadow-sm" style="width: 58px; height: 58px; font-size: 1.8rem;">
            <?php if ($profesi['role_canonical'] === 'admin'): ?>
              <i class="bi bi-shield-shaded"></i>
            <?php elseif ($profesi['role_canonical'] === 'pengawas_kesehatan'): ?>
              <i class="bi bi-heart-pulse-fill"></i>
            <?php else: ?>
              <i class="bi bi-door-open-fill"></i>
            <?php endif; ?>
          </div>
          <div>
            <span class="badge bg-white bg-opacity-25 text-white px-3 py-1 rounded-pill small fw-semibold">
              <i class="bi bi-patch-check-fill me-1 text-warning"></i> <?= esc($profesi['kategori']) ?>
            </span>
            <h2 class="fw-bold mb-0 mt-1"><?= esc($user['nama']) ?></h2>
          </div>
        </div>
        <p class="mb-0 text-white-50" style="font-size: 0.95rem;">
          <i class="bi bi-envelope-at me-1"></i> <?= esc($user['email']) ?> &bull; 
          <i class="bi bi-geo-alt me-1"></i> <?= esc($profesi['instansi']) ?> &bull;
          <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">
            Status: <?= esc($user['status'] ?? 'Aktif') ?>
          </span>
        </p>
      </div>
      <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
        <span class="badge bg-white text-dark shadow-sm px-3 py-2 rounded-pill small fw-semibold">
          <i class="bi bi-shield-lock-fill text-teal me-1"></i> Hak Akses: <?= esc($profesi['role_display']) ?>
        </span>
      </div>
    </div>
  </div>

  <!-- Flash Messages -->
  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center gap-2 mb-4" role="alert">
      <i class="bi bi-check-circle-fill fs-5 text-success"></i>
      <div><?= session()->getFlashdata('success') ?></div>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center gap-2 mb-4" role="alert">
      <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
      <div><?= session()->getFlashdata('error') ?></div>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
      <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-1"></i> Terdapat kendala validasi data:</div>
      <ul class="mb-0 ps-3 small">
        <?php foreach (session()->getFlashdata('errors') as $err): ?>
          <li><?= esc($err) ?></li>
        <?php endforeach; ?>
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Kolom Kiri: Rincian Profesi & Tanggung Jawab -->
    <div class="col-lg-7">
      
      <!-- Kartu Informasi Profesi Utama -->
      <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
          <div class="fw-bold text-dark d-flex align-items-center gap-2">
            <i class="bi bi-person-vcard text-teal fs-5" style="color: #0d9488;"></i>
            <span>Rincian Profil Profesi</span>
          </div>
          <span class="badge bg-light text-secondary border small">
            ID Akun: #<?= esc($user['id']) ?>
          </span>
        </div>
        <div class="card-body p-4">
          <div class="row g-3 mb-4">
            <div class="col-sm-6">
              <div class="p-3 rounded-3 bg-light border">
                <div class="text-muted small mb-1">Nama Lengkap & Gelar</div>
                <div class="fw-bold text-dark"><?= esc($user['nama']) ?></div>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="p-3 rounded-3 bg-light border">
                <div class="text-muted small mb-1">Klasifikasi Profesi</div>
                <div class="fw-bold text-dark"><?= esc($profesi['kategori']) ?></div>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="p-3 rounded-3 bg-light border">
                <div class="text-muted small mb-1">Unit Tugas / Lokasi</div>
                <div class="fw-semibold text-dark"><?= esc($profesi['instansi']) ?></div>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="p-3 rounded-3 bg-light border">
                <div class="text-muted small mb-1">Fokus Tugas Utama</div>
                <div class="fw-semibold text-dark small"><?= esc($profesi['lingkup_tugas']) ?></div>
              </div>
            </div>
          </div>

          <!-- Bagian Khusus: Berdasarkan Profesi -->
          <?php if ($profesi['role_canonical'] === 'pengawas_kamar'): ?>
            <!-- Detail Pengawas Kamar -->
            <div class="border rounded-3 p-3 bg-white mb-3">
              <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                <i class="bi bi-door-open text-success"></i> Kamar Asrama Binaan yang Diampu
              </h6>
              <?php if (!empty($profesi['kamar_binaan'])): ?>
                <div class="d-flex flex-wrap gap-2 mb-3">
                  <?php foreach ($profesi['kamar_binaan'] as $kb): ?>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-3">
                      <i class="bi bi-building me-1"></i> <?= esc($kb['nama_kamar']) ?>
                    </span>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="text-muted small mb-3 fst-italic">Belum ada kamar asrama yang ditugaskan secara spesifik.</div>
              <?php endif; ?>
              <div class="row g-2 text-center">
                <div class="col-6">
                  <div class="p-2 rounded bg-light border">
                    <div class="text-muted" style="font-size: 0.75rem;">Total Santri Binaan</div>
                    <div class="fs-5 fw-bold text-teal" style="color: #0d9488;"><?= esc($profesi['total_santri']) ?> <span class="small fs-6 text-muted fw-normal">santri</span></div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="p-2 rounded bg-light border">
                    <div class="text-muted" style="font-size: 0.75rem;">Riwayat Laporan Sakit</div>
                    <div class="fs-5 fw-bold text-warning"><?= esc($profesi['total_laporan']) ?> <span class="small fs-6 text-muted fw-normal">laporan</span></div>
                  </div>
                </div>
              </div>
            </div>

          <?php elseif ($profesi['role_canonical'] === 'pengawas_kesehatan'): ?>
            <!-- Detail Dokter / Tenaga Medis -->
            <div class="border rounded-3 p-3 bg-white mb-3">
              <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                <i class="bi bi-stethoscope text-primary"></i> Lisensi & Rekap Klinis Medis
              </h6>
              <div class="row g-3 mb-3">
                <div class="col-sm-6">
                  <div class="small text-muted">Spesialisasi Klinis:</div>
                  <div class="fw-semibold text-dark"><?= esc($profesi['spesialisasi']) ?></div>
                </div>
                <div class="col-sm-6">
                  <div class="small text-muted">No. Izin Tugas Poskestren:</div>
                  <div class="fw-semibold text-dark font-monospace"><?= esc($profesi['no_izin_tugas']) ?></div>
                </div>
              </div>
              <div class="row g-2 text-center">
                <div class="col-6">
                  <div class="p-2 rounded bg-light border">
                    <div class="text-muted" style="font-size: 0.75rem;">Total Pemeriksaan Klinis</div>
                    <div class="fs-5 fw-bold text-primary"><?= esc($profesi['total_pemeriksaan']) ?> <span class="small fs-6 text-muted fw-normal">santri</span></div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="p-2 rounded bg-light border">
                    <div class="text-muted" style="font-size: 0.75rem;">Total Resep Obat Diterbitkan</div>
                    <div class="fs-5 fw-bold text-success"><?= esc($profesi['total_resep']) ?> <span class="small fs-6 text-muted fw-normal">resep</span></div>
                  </div>
                </div>
              </div>
            </div>

          <?php else: ?>
            <!-- Detail Administrator (Muhammad Haidir Ali) -->
            <div class="border rounded-3 p-3 bg-white mb-3">
              <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                <i class="bi bi-sliders text-danger"></i> Kendali & Pengawasan Sistem Terpusat
              </h6>
              <p class="small text-muted mb-3">
                Sebagai <strong>Administrator Utama SIMKES Santri</strong>, Anda memegang tanggung jawab penuh terhadap stabilitas server, kepatuhan data santri, perlindungan privasi medis, dan pengelolaan hak akses pengguna.
              </p>
              <div class="row g-2 text-center">
                <div class="col-6">
                  <div class="p-2 rounded bg-light border">
                    <div class="text-muted" style="font-size: 0.75rem;">Total Pengguna Terdaftar</div>
                    <div class="fs-5 fw-bold text-dark"><?= esc($profesi['total_pengguna']) ?> <span class="small fs-6 text-muted fw-normal">akun</span></div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="p-2 rounded bg-light border">
                    <div class="text-muted" style="font-size: 0.75rem;">Total Log Audit Keamanan</div>
                    <div class="fs-5 fw-bold text-teal" style="color: #0d9488;"><?= esc($profesi['total_audit_log']) ?> <span class="small fs-6 text-muted fw-normal">aktivitas</span></div>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <!-- Wewenang & Tanggung Jawab -->
          <div class="mt-4">
            <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
              <i class="bi bi-check2-circle text-teal" style="color: #0d9488;"></i> Wewenang & Tanggung Jawab Profesi
            </h6>
            <div class="list-group list-group-flush rounded-3 border">
              <?php foreach ($profesi['wewenang'] as $w): ?>
                <div class="list-group-item d-flex align-items-start gap-2 py-2.5 px-3">
                  <i class="bi bi-arrow-right-short text-teal fs-5 mt-n0.5" style="color: #0d9488;"></i>
                  <span class="small text-dark"><?= esc($w) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

        </div>
      </div>

    </div>

    <!-- Kolom Kanan: Pengaturan Akun & Kata Sandi -->
    <div class="col-lg-5">
      
      <!-- Formulir Pembaruan Nama & Kata Sandi -->
      <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom py-3 px-4">
          <div class="fw-bold text-dark d-flex align-items-center gap-2">
            <i class="bi bi-gear-fill text-secondary"></i>
            <span>Pengaturan Akun & Profil</span>
          </div>
        </div>
        <div class="card-body p-4">
          <form action="<?= base_url('/profil/update') ?>" method="POST" autocomplete="off">
            <?= csrf_field() ?>

            <!-- Nama Pengguna -->
            <div class="mb-3">
              <label for="namaInput" class="form-label fw-semibold small text-dark">Nama Lengkap & Gelar</label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-person"></i></span>
                <input type="text" class="form-control border-start-0" id="namaInput" name="nama" value="<?= esc(old('nama', $user['nama'])) ?>" required minlength="3" maxlength="100">
              </div>
              <div class="form-text" style="font-size: 0.72rem;">Nama ini akan tampil pada dokumen rekam medis, laporan, dan Asisten AI.</div>
            </div>

            <!-- Email (Read-Only demi Keamanan) -->
            <div class="mb-3">
              <label class="form-label fw-semibold small text-dark">Alamat Email (Username Login)</label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control border-start-0 bg-light" value="<?= esc($user['email']) ?>" readonly disabled>
              </div>
              <div class="form-text" style="font-size: 0.72rem;">Email login dikelola secara terpusat oleh Administrator.</div>
            </div>

            <hr class="my-4">

            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-bold small text-dark"><i class="bi bi-key-fill text-warning me-1"></i> Ganti Kata Sandi</div>
              <span class="text-muted" style="font-size: 0.7rem;">Opsional (kosongkan jika tidak ingin mengubah)</span>
            </div>

            <!-- Kata Sandi Lama -->
            <div class="mb-3">
              <label for="passLama" class="form-label small text-muted">Kata Sandi Saat Ini</label>
              <input type="password" class="form-control" id="passLama" name="password_lama" placeholder="Masukkan sandi saat ini">
            </div>

            <!-- Kata Sandi Baru -->
            <div class="mb-3">
              <label for="passBaru" class="form-label small text-muted">Kata Sandi Baru</label>
              <input type="password" class="form-control" id="passBaru" name="password_baru" placeholder="Minimal 6 karakter" minlength="6">
            </div>

            <!-- Konfirmasi Kata Sandi Baru -->
            <div class="mb-4">
              <label for="passConfirm" class="form-label small text-muted">Konfirmasi Kata Sandi Baru</label>
              <input type="password" class="form-control" id="passConfirm" name="password_confirm" placeholder="Ulangi kata sandi baru">
            </div>

            <button type="submit" class="btn btn-teal w-100 py-2.5 rounded-3 text-white fw-semibold shadow-sm" style="background-color: #0d9488;">
              <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan Profil
            </button>
          </form>
        </div>
      </div>

      <!-- Kartu Bantuan AI Terintegrasi -->
      <div class="card border-0 shadow-sm rounded-4 p-3 bg-light">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle bg-teal-subtle text-teal p-3 d-flex align-items-center justify-content-center" style="background-color: #ccfbf1; color: #0f766e; width: 44px; height: 44px;">
            <i class="bi bi-robot fs-5"></i>
          </div>
          <div>
            <div class="fw-bold text-dark small">Tanyakan Profil ke Asisten AI</div>
            <div class="text-muted" style="font-size: 0.75rem;">
              Anda dapat mengklik tombol Asisten AI di sudut kanan bawah dan menanyakan <em>"Siapa saya?"</em> atau <em>"Apa profil profesi saya?"</em>.
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

</div>
<?= $this->endSection() ?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title ?? 'Pemeriksaan Klinis - SIMKES Santri') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0">

  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-2 small text-muted">
      <li class="breadcrumb-item"><a href="<?= base_url('/dashboard') ?>" class="text-decoration-none text-muted">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= base_url('/laporan-kesehatan') ?>" class="text-decoration-none text-muted">Laporan Kesehatan</a></li>
      <li class="breadcrumb-item"><a href="<?= base_url('/laporan-kesehatan/' . $laporan['id']) ?>" class="text-decoration-none text-muted">Kasus #<?= esc($laporan['id']) ?></a></li>
      <li class="breadcrumb-item active" aria-current="page">Pemeriksaan Klinis</li>
    </ol>
  </nav>

  <!-- Flash Alerts -->
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
      <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
      <div class="fw-medium"><?= esc(session()->getFlashdata('error')) ?></div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
      <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-1"></i> Terjadi kesalahan pengisian form:</div>
      <ul class="mb-0 ps-3 small">
        <?php foreach (session()->getFlashdata('errors') as $err): ?>
          <li><?= esc($err) ?></li>
        <?php endforeach; ?>
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Header -->
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
        <h3 class="fw-bold text-dark mb-0">Pemeriksaan Klinis Tenaga Medis</h3>
        <span class="badge bg-primary px-3 py-1.5 fw-semibold fs-6">
          <i class="bi bi-stethoscope me-1"></i> Poskestren Terpadu
        </span>
      </div>
      <p class="text-muted small mb-0">
        Kasus #<?= esc($laporan['id']) ?> &bull; Santri: <strong><?= esc($laporan['nama_santri']) ?></strong> (NIS: <?= esc($laporan['nis']) ?>) &bull; Kamar: <strong><?= esc($laporan['nama_kamar'] ?? '-') ?></strong>
      </p>
    </div>

    <div>
      <a href="<?= base_url('/laporan-kesehatan/' . $laporan['id']) ?>" class="btn btn-outline-secondary px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Kasus
      </a>
    </div>
  </div>

  <div class="row g-4">
    <!-- Info Ringkas Pasien -->
    <div class="col-12 col-lg-4">
      <div class="card card-dashboard border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 px-4">
          <h6 class="fw-bold text-dark mb-0"><i class="bi bi-clipboard2-pulse me-2 text-teal"></i> Keluhan Awal Santri</h6>
        </div>
        <div class="card-body p-4">
          <div class="mb-3">
            <div class="small fw-semibold text-muted text-uppercase">Waktu Mulai Sakit:</div>
            <div class="text-dark fw-bold font-monospace mt-0.5">
              <?= date('d M Y, H:i', strtotime($laporan['tanggal_mulai_sakit'])) ?> WIB
            </div>
          </div>
          <div class="mb-3">
            <div class="small fw-semibold text-muted text-uppercase">Pelapor / Kamar:</div>
            <div class="text-dark fw-medium mt-0.5">
              <?= esc($laporan['nama_pelapor'] ?? 'Pengawas') ?> &bull; <?= esc($laporan['nama_kamar'] ?? '-') ?>
            </div>
          </div>
          <div class="mb-3">
            <div class="small fw-semibold text-muted text-uppercase">Gejala yang Dilaporkan:</div>
            <div class="p-3 bg-light rounded-3 border small text-dark mt-1">
              <?= nl2br(esc($laporan['gejala'])) ?>
            </div>
          </div>
          <?php if (!empty($laporan['catatan'])): ?>
            <div>
              <div class="small fw-semibold text-muted text-uppercase">Catatan Tambahan:</div>
              <div class="p-2.5 bg-light rounded border small text-muted mt-1">
                <?= nl2br(esc($laporan['catatan'])) ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Form Pemeriksaan Medis & Terapi Obat -->
    <div class="col-12 col-lg-8">
      <div class="card card-dashboard border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 px-4">
          <h5 class="fw-bold text-dark mb-0"><i class="bi bi-person-badge-fill text-primary me-2"></i> Form Rekam Pemeriksaan Fisik & Terapi</h5>
        </div>
        <div class="card-body p-4">
          <form action="<?= base_url('/pemeriksaan/laporan/' . $laporan['id']) ?>" method="POST">
            <?= csrf_field() ?>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label for="tanggal_periksa" class="form-label fw-semibold small text-dark">Tanggal & Waktu Pemeriksaan <span class="text-danger">*</span></label>
                <input type="datetime-local" class="form-control" id="tanggal_periksa" name="tanggal_periksa"
                       value="<?= date('Y-m-d\TH:i') ?>" required>
              </div>
              <div class="col-md-6">
                <label for="status_lanjutan" class="form-label fw-semibold small text-dark">Status Kondisi Setelah Diperiksa <span class="text-danger">*</span></label>
                <select class="form-select" id="status_lanjutan" name="status_lanjutan" required>
                  <option value="Dalam Pemeriksaan" <?= ($laporan['status'] === 'Dalam Pemeriksaan') ? 'selected' : '' ?>>Dalam Pemeriksaan (Observasi Lanjutan)</option>
                  <option value="Dalam Perawatan" <?= ($laporan['status'] === 'Dalam Perawatan' || $laporan['status'] === 'Dilaporkan Sakit') ? 'selected' : '' ?>>Dalam Perawatan (Istirahat / Rawat Poskestren)</option>
                  <option value="Sembuh">Sembuh (Kondisi Membaik, Kembali ke Asrama)</option>
                  <option value="Dirujuk">Dirujuk (Rujukan ke Faskes / Rumah Sakit Luar)</option>
                </select>
              </div>
            </div>

            <div class="mb-3">
              <label for="diagnosis_atau_dugaan" class="form-label fw-semibold small text-dark">Diagnosis / Dugaan Klinis <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="diagnosis_atau_dugaan" name="diagnosis_atau_dugaan"
                     placeholder="Contoh: Faringitis Akut, Dispepsia, ISPA Ringan, Demam Typoid" required
                     value="<?= esc(old('diagnosis_atau_dugaan')) ?>">
              <div class="form-text small text-muted">Tuliskan temuan diagnosis klinis berdasarkan anamnesis dan pemeriksaan fisik.</div>
            </div>

            <div class="mb-3">
              <label for="tindakan" class="form-label fw-semibold small text-dark">Tindakan Medis & Observasi</label>
              <textarea class="form-control" id="tindakan" name="tindakan" rows="2"
                        placeholder="Contoh: Pemeriksaan TTV (TD: 110/70, N: 82, S: 38.2C). Kompres hangat, hidrasi cairan 2L/hari..."><?= esc(old('tindakan')) ?></textarea>
            </div>

            <div class="mb-4">
              <label for="catatan" class="form-label fw-semibold small text-dark">Catatan Khusus / Instruksi Perawat</label>
              <textarea class="form-control" id="catatan" name="catatan" rows="2"
                        placeholder="Instruksi pantauan suhu setiap 4 jam, batasi aktivitas fisik..."><?= esc(old('catatan')) ?></textarea>
            </div>

            <!-- Resep Obat Resmi Poskestren -->
            <div class="p-3 bg-light rounded-3 border mb-4">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-bold text-dark small"><i class="bi bi-capsule text-teal me-1"></i> Resep & Terapi Obat (Disahkan Petugas Medis)</div>
                <button type="button" class="btn btn-sm btn-outline-teal" onclick="tambahBarisObat()">
                  <i class="bi bi-plus-circle me-1"></i> Tambah Obat
                </button>
              </div>
              <p class="text-muted small mb-3">Obat yang diresepkan wajib dipilih dari inventaris resmi dengan instruksi pakai yang jelas.</p>

              <div id="wadahObat" class="d-flex flex-column gap-3">
                <!-- Baris Obat 1 -->
                <div class="row g-2 align-items-end p-2 bg-white rounded border baris-obat">
                  <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Nama Obat</label>
                    <select class="form-select form-select-sm" name="obat_id[]">
                      <option value="">-- Pilih Obat --</option>
                      <?php foreach ($daftarObat as $o): ?>
                        <option value="<?= esc($o['id']) ?>"><?= esc($o['nama_obat']) ?> (Stok: <?= esc($o['stok']) ?> <?= esc($o['satuan']) ?>)</option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Dosis</label>
                    <input type="text" class="form-control form-control-sm" name="obat_dosis[]" placeholder="500 mg">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Aturan Pakai</label>
                    <input type="text" class="form-control form-control-sm" name="obat_aturan_pakai[]" placeholder="3x1 sesudah makan">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Durasi</label>
                    <input type="text" class="form-control form-control-sm" name="obat_durasi[]" placeholder="3 hari">
                  </div>
                  <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="hapusBarisObat(this)">
                      <i class="bi bi-trash"></i> Hapus
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pernyataan Verifikasi Medis Wajib -->
            <div class="alert alert-info border-0 d-flex align-items-center gap-2 mb-4">
              <input class="form-check-input flex-shrink-0" type="checkbox" name="diverifikasi_oleh_medis" value="1" id="diverifikasi_oleh_medis" required checked>
              <label class="form-check-label small text-dark fw-semibold" for="diverifikasi_oleh_medis">
                Saya menyatakan dengan sadar bahwa diagnosis dan terapi di atas adalah hasil pemeriksaan fisik dan telaah klinis resmi tenaga medis berwenang Poskestren, bukan rekomendasi otomatis yang belum diverifikasi.
              </label>
            </div>

            <div class="d-flex justify-content-end gap-2">
              <a href="<?= base_url('/laporan-kesehatan/' . $laporan['id']) ?>" class="btn btn-light px-3">Batal</a>
              <button type="submit" class="btn btn-teal px-4 fw-semibold shadow-sm">
                <i class="bi bi-check2-circle me-1"></i> Simpan & Sahkan Pemeriksaan
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
function tambahBarisObat() {
  const container = document.getElementById('wadahObat');
  const firstRow = container.querySelector('.baris-obat');
  if (firstRow) {
    const clone = firstRow.cloneNode(true);
    // Reset nilai input pada clone
    clone.querySelectorAll('input').forEach(i => i.value = '');
    clone.querySelector('select').selectedIndex = 0;
    container.appendChild(clone);
  }
}

function hapusBarisObat(btn) {
  const container = document.getElementById('wadahObat');
  const rows = container.querySelectorAll('.baris-obat');
  if (rows.length > 1) {
    btn.closest('.baris-obat').remove();
  } else {
    // Jika hanya tersisa 1 baris, cukup kosongkan nilainya
    const row = rows[0];
    row.querySelectorAll('input').forEach(i => i.value = '');
    row.querySelector('select').selectedIndex = 0;
  }
}
</script>
<?= $this->endSection() ?>

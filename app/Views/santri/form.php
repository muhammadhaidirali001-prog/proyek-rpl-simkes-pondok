<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= esc($title ?? 'Form Data Santri - SIMKES Santri') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0">

  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-2 small text-muted">
      <li class="breadcrumb-item"><a href="<?= base_url('/dashboard') ?>" class="text-decoration-none text-muted">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= base_url('/santri') ?>" class="text-decoration-none text-muted">Data Santri</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?= $isEdit ? 'Ubah Data' : 'Tambah Baru' ?></li>
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

  <?php $validationErrors = session()->getFlashdata('errors') ?? []; ?>
  <?php if (!empty($validationErrors)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2 mb-2">
        <i class="bi bi-x-circle-fill fs-5 flex-shrink-0"></i>
        <strong class="fw-semibold">Terjadi kesalahan pada pengisian form:</strong>
      </div>
      <ul class="mb-0 small ps-4">
        <?php foreach ($validationErrors as $err): ?>
          <li><?= esc($err) ?></li>
        <?php endforeach; ?>
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Form Card -->
  <div class="row justify-content-center">
    <div class="col-12 col-lg-9 col-xl-8">
      <div class="card card-dashboard border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="stat-icon primary" style="width: 38px; height: 38px; font-size: 1.15rem;">
              <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-person-plus-fill' ?>"></i>
            </div>
            <div>
              <h5 class="fw-bold text-dark mb-0"><?= $isEdit ? 'Ubah Data Santri' : 'Tambah Data Santri Baru' ?></h5>
              <div class="small text-muted"><?= $isEdit ? 'Perbarui informasi dan kamar asrama santri' : 'Lengkapi profil santri untuk pencatatan kesehatan terpadu' ?></div>
            </div>
          </div>
          <a href="<?= $isEdit ? base_url('/santri/' . $santri['id']) : base_url('/santri') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
          </a>
        </div>

        <div class="card-body p-4">
          <form action="<?= $isEdit ? base_url('/santri/' . $santri['id']) : base_url('/santri') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="row g-3">
              <!-- NIS -->
              <div class="col-12 col-md-6">
                <label for="nis" class="form-label fw-semibold text-dark small">
                  Nomor Induk Santri (NIS) <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted"><i class="bi bi-upc-scan"></i></span>
                  <input type="text"
                         class="form-control font-monospace <?= isset($validationErrors['nis']) ? 'is-invalid' : '' ?>"
                         id="nis"
                         name="nis"
                         placeholder="Contoh: SN-2024-001"
                         value="<?= esc(old('nis', $santri['nis'] ?? '')) ?>"
                         required>
                  <?php if (isset($validationErrors['nis'])): ?>
                    <div class="invalid-feedback"><?= esc($validationErrors['nis']) ?></div>
                  <?php endif; ?>
                </div>
                <div class="form-text small text-muted">NIS unik resmi dari pesantren (maksimal 30 karakter).</div>
              </div>

              <!-- Nama Lengkap -->
              <div class="col-12 col-md-6">
                <label for="nama" class="form-label fw-semibold text-dark small">
                  Nama Lengkap Santri <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                  <input type="text"
                         class="form-control <?= isset($validationErrors['nama']) ? 'is-invalid' : '' ?>"
                         id="nama"
                         name="nama"
                         placeholder="Masukkan nama lengkap santri"
                         value="<?= esc(old('nama', $santri['nama'] ?? '')) ?>"
                         required>
                  <?php if (isset($validationErrors['nama'])): ?>
                    <div class="invalid-feedback"><?= esc($validationErrors['nama']) ?></div>
                  <?php endif; ?>
                </div>
                <div class="form-text small text-muted">Nama lengkap sesuai berkas pendaftaran pesantren.</div>
              </div>

              <!-- Jenis Kelamin -->
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold text-dark small d-block">
                  Jenis Kelamin <span class="text-danger">*</span>
                </label>
                <?php
                  $currentGender = old('jenis_kelamin', $santri['jenis_kelamin'] ?? 'Laki-laki');
                ?>
                <div class="d-flex gap-3 mt-1">
                  <div class="form-check form-check-inline p-2 border rounded-3 flex-fill text-center <?= $currentGender === 'Laki-laki' ? 'bg-primary-subtle border-primary' : 'bg-light' ?>">
                    <input class="form-check-input ms-0 me-2" type="radio" name="jenis_kelamin" id="gender_laki" value="Laki-laki"
                           <?= $currentGender === 'Laki-laki' ? 'checked' : '' ?> required>
                    <label class="form-check-label fw-medium text-dark" for="gender_laki">
                      <i class="bi bi-gender-male text-primary me-1"></i> Laki-laki
                    </label>
                  </div>
                  <div class="form-check form-check-inline p-2 border rounded-3 flex-fill text-center <?= $currentGender === 'Perempuan' ? 'bg-danger-subtle border-danger' : 'bg-light' ?>">
                    <input class="form-check-input ms-0 me-2" type="radio" name="jenis_kelamin" id="gender_perempuan" value="Perempuan"
                           <?= $currentGender === 'Perempuan' ? 'checked' : '' ?> required>
                    <label class="form-check-label fw-medium text-dark" for="gender_perempuan">
                      <i class="bi bi-gender-female text-danger me-1"></i> Perempuan
                    </label>
                  </div>
                </div>
                <?php if (isset($validationErrors['jenis_kelamin'])): ?>
                  <div class="text-danger small mt-1"><?= esc($validationErrors['jenis_kelamin']) ?></div>
                <?php endif; ?>
              </div>

              <!-- Kamar Asrama -->
              <div class="col-12 col-md-6">
                <label for="kamar_id" class="form-label fw-semibold text-dark small">
                  Kamar Asrama Santri
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted"><i class="bi bi-door-closed"></i></span>
                  <select class="form-select <?= isset($validationErrors['kamar_id']) ? 'is-invalid' : '' ?>" id="kamar_id" name="kamar_id">
                    <option value="">-- Pilih Kamar Asrama --</option>
                    <?php
                      $selectedKamar = (string) old('kamar_id', $santri['kamar_id'] ?? '');
                      foreach ($daftarKamar as $k):
                    ?>
                      <option value="<?= esc($k['id']) ?>" <?= ($selectedKamar === (string)$k['id']) ? 'selected' : '' ?>>
                        <?= esc($k['nama_kamar']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <?php if (isset($validationErrors['kamar_id'])): ?>
                    <div class="invalid-feedback"><?= esc($validationErrors['kamar_id']) ?></div>
                  <?php endif; ?>
                </div>
                <div class="form-text small text-muted">Penempatan kamar santri untuk pemantauan pengawas kamar.</div>
              </div>

              <!-- Kelas -->
              <div class="col-12 col-md-6">
                <label for="kelas" class="form-label fw-semibold text-dark small">
                  Tingkat / Kelas
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted"><i class="bi bi-mortarboard"></i></span>
                  <input type="text"
                         class="form-control <?= isset($validationErrors['kelas']) ? 'is-invalid' : '' ?>"
                         id="kelas"
                         name="kelas"
                         placeholder="Contoh: VII-A, VIII-B, X IPA 1"
                         value="<?= esc(old('kelas', $santri['kelas'] ?? '')) ?>"
                         list="kelasSuggestions">
                  <datalist id="kelasSuggestions">
                    <option value="VII-A">
                    <option value="VII-B">
                    <option value="VIII-A">
                    <option value="VIII-B">
                    <option value="IX-A">
                    <option value="IX-B">
                    <option value="X IPA 1">
                    <option value="X IPS 1">
                    <option value="XI IPA 1">
                    <option value="XII IPA 1">
                  </datalist>
                  <?php if (isset($validationErrors['kelas'])): ?>
                    <div class="invalid-feedback"><?= esc($validationErrors['kelas']) ?></div>
                  <?php endif; ?>
                </div>
                <div class="form-text small text-muted">Kelas formal/diniyyah yang sedang ditempuh santri.</div>
              </div>

              <!-- Kontak Wali -->
              <div class="col-12 col-md-6">
                <label for="kontak_wali" class="form-label fw-semibold text-dark small">
                  Kontak Wali / Orang Tua
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted"><i class="bi bi-telephone"></i></span>
                  <input type="text"
                         class="form-control <?= isset($validationErrors['kontak_wali']) ? 'is-invalid' : '' ?>"
                         id="kontak_wali"
                         name="kontak_wali"
                         placeholder="Contoh: 081234567890 (Ibu Maryam)"
                         value="<?= esc(old('kontak_wali', $santri['kontak_wali'] ?? '')) ?>">
                  <?php if (isset($validationErrors['kontak_wali'])): ?>
                    <div class="invalid-feedback"><?= esc($validationErrors['kontak_wali']) ?></div>
                  <?php endif; ?>
                </div>
                <div class="form-text small text-muted">Nomor telepon atau kontak darurat wali santri jika santri sakit.</div>
              </div>
            </div>

            <!-- Tombol Simpan -->
            <hr class="my-4 text-muted opacity-25">
            <div class="d-flex justify-content-end gap-2">
              <a href="<?= $isEdit ? base_url('/santri/' . $santri['id']) : base_url('/santri') ?>" class="btn btn-light px-4">
                Batal
              </a>
              <button type="submit" class="btn btn-teal px-4 fw-semibold shadow-sm">
                <i class="bi <?= $isEdit ? 'bi-check2-circle' : 'bi-plus-circle' ?> me-1"></i>
                <?= $isEdit ? 'Simpan Perubahan' : 'Daftarkan Santri' ?>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

</div>
<?= $this->endSection() ?>

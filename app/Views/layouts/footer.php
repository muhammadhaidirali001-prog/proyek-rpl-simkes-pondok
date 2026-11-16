<footer class="app-footer">
  <div class="container-fluid d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2">
    <div>
      <strong>SIMKES Santri</strong> &copy; <?= date('Y') ?> &mdash; Sistem Manajemen Kesehatan Santri Pondok Pesantren.
    </div>
    <div class="d-flex align-items-center gap-3">
      <span class="badge bg-light text-dark border">Lingkungan: <?= esc(ENVIRONMENT) ?></span>
      <span class="text-muted" style="font-size: 0.75rem;">
        Waktu Render: {elapsed_time} dtk | Memori: {memory_usage}
      </span>
    </div>
  </div>
</footer>

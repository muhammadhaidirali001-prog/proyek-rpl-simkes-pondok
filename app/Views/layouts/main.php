<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title><?= $this->renderSection('title') ?: 'SIMKES Santri - Sistem Manajemen Kesehatan Santri' ?></title>

  <!-- Google Fonts: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 5.3.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <!-- Bootstrap Icons 1.11.3 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- Custom SIMKES CSS -->
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">

  <?= $this->renderSection('styles') ?>
</head>
<body>
  <div class="app-wrapper">
    <!-- Sidebar Navigation -->
    <?= $this->include('layouts/sidebar') ?>

    <!-- Main Container Area -->
    <div class="app-main">
      <!-- Navbar Header -->
      <?= $this->include('layouts/navbar') ?>

      <!-- Dynamic Content Body -->
      <main class="app-content">
        <?= $this->renderSection('content') ?>
      </main>

      <!-- Footer -->
      <?= $this->include('layouts/footer') ?>
    </div>
  </div>

  <!-- Bootstrap 5.3.3 Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

  <!-- Custom App JS -->
  <script src="<?= base_url('assets/js/app.js') ?>"></script>

  <?= $this->renderSection('scripts') ?>

  <!-- Floating AI Assistant Window Component -->
  <?= $this->include('layouts/floating_ai') ?>
</body>
</html>

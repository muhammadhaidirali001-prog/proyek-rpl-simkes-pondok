<?php

use CodeIgniter\Pager\PagerRenderer;

/**
 * @var PagerRenderer $pager
 */
$pager->setSurroundCount(2);
?>
<nav aria-label="Navigasi Halaman Data">
  <ul class="pagination pagination-sm justify-content-center justify-content-md-end mb-0 gap-1">
    <?php if ($pager->hasPrevious()) : ?>
      <li class="page-item">
        <a class="page-link rounded-2" href="<?= $pager->getFirst() ?>" aria-label="Halaman Pertama">
          <i class="bi bi-chevron-double-left"></i>
        </a>
      </li>
      <li class="page-item">
        <a class="page-link rounded-2" href="<?= $pager->getPrevious() ?>" aria-label="Sebelumnya">
          <i class="bi bi-chevron-left"></i>
        </a>
      </li>
    <?php endif ?>

    <?php foreach ($pager->links() as $link) : ?>
      <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
        <a class="page-link rounded-2" href="<?= $link['uri'] ?>">
          <?= $link['title'] ?>
        </a>
      </li>
    <?php endforeach ?>

    <?php if ($pager->hasNext()) : ?>
      <li class="page-item">
        <a class="page-link rounded-2" href="<?= $pager->getNext() ?>" aria-label="Berikutnya">
          <i class="bi bi-chevron-right"></i>
        </a>
      </li>
      <li class="page-item">
        <a class="page-link rounded-2" href="<?= $pager->getLast() ?>" aria-label="Halaman Terakhir">
          <i class="bi bi-chevron-double-right"></i>
        </a>
      </li>
    <?php endif ?>
  </ul>
</nav>

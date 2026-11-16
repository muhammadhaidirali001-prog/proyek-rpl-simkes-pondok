/**
 * SIMKES Santri - JavaScript Interaktivitas UI
 */
document.addEventListener('DOMContentLoaded', function () {
  const sidebar = document.getElementById('appSidebar');
  const toggleBtn = document.getElementById('sidebarToggle');
  const backdrop = document.getElementById('sidebarBackdrop');

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('show');
      if (backdrop) {
        backdrop.classList.toggle('show');
      }
    });
  }

  if (backdrop) {
    backdrop.addEventListener('click', function () {
      sidebar.classList.remove('show');
      backdrop.classList.remove('show');
    });
  }

  // Aktifkan Bootstrap Tooltip jika ada
  if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  }
});

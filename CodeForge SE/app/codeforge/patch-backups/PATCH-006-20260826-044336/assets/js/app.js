(() => {
  const menu = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  menu?.addEventListener('click', () => sidebar?.classList.toggle('open'));
  document.addEventListener('click', e => {
    if (window.innerWidth <= 900 && sidebar?.classList.contains('open') && !sidebar.contains(e.target) && !menu?.contains(e.target)) sidebar.classList.remove('open');
  });
  document.querySelectorAll('[data-confirm]').forEach(el => el.addEventListener('click', e => { if (!confirm(el.dataset.confirm)) e.preventDefault(); }));
  setTimeout(() => document.querySelectorAll('.toast-banner').forEach(x => x.classList.add('fade')), 4200);
})();

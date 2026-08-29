(() => {
  'use strict';

  const menu = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  const searchInput = document.getElementById('globalSearchInput');

  const isMobile = () => window.innerWidth <= 900;

  const setSidebar = (open) => {
    if (!sidebar || !menu) return;
    sidebar.classList.toggle('open', open);
    menu.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.classList.toggle('nav-open', open && isMobile());
  };

  menu?.addEventListener('click', () => {
    setSidebar(!sidebar?.classList.contains('open'));
  });

  document.addEventListener('click', (event) => {
    if (!isMobile() || !sidebar?.classList.contains('open')) return;
    if (!sidebar.contains(event.target) && !menu?.contains(event.target)) setSidebar(false);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && sidebar?.classList.contains('open')) {
      setSidebar(false);
      menu?.focus();
      return;
    }

    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
      event.preventDefault();
      searchInput?.focus();
      searchInput?.select();
    }
  });

  sidebar?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
      if (isMobile()) setSidebar(false);
    });
  });

  window.addEventListener('resize', () => {
    if (!isMobile()) setSidebar(false);
  }, { passive: true });

  document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('click', (event) => {
      if (!confirm(element.dataset.confirm || 'Are you sure?')) event.preventDefault();
    });
  });

  document.querySelectorAll('[data-confirm-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!confirm(form.dataset.confirmForm || 'Are you sure?')) event.preventDefault();
    });
  });

  document.querySelectorAll('[data-tab-indent]').forEach((editor) => {
    editor.addEventListener('keydown', (event) => {
      if (event.key !== 'Tab') return;
      event.preventDefault();
      const start = editor.selectionStart;
      const end = editor.selectionEnd;
      const value = editor.value;
      editor.value = value.slice(0, start) + '    ' + value.slice(end);
      editor.selectionStart = editor.selectionEnd = start + 4;
      editor.dispatchEvent(new Event('input', { bubbles: true }));
    });
  });

  const toastTimer = window.setTimeout(() => {
    document.querySelectorAll('.toast-banner').forEach((item) => item.classList.add('fade'));
  }, 4200);

  window.addEventListener('pagehide', () => clearTimeout(toastTimer), { once: true });
})();

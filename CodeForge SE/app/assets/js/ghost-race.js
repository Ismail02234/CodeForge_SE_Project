(() => {
  'use strict';

  const root = document.querySelector('[data-race-root]');
  if (!root) return;

  const startedMs = Number(root.dataset.startedMs || Date.now());
  const speed = Math.max(1, Number(root.dataset.speed || 1));
  const ghostTime = Math.max(0, Number(root.dataset.ghostTime || 0));
  const timer = root.querySelector('[data-race-timer]');
  const ghostState = root.querySelector('[data-ghost-state]');
  const events = [...root.querySelectorAll('[data-ghost-event]')];

  const format = (seconds) => {
    const s = Math.max(0, Math.floor(seconds));
    const hours = Math.floor(s / 3600);
    const minutes = Math.floor((s % 3600) / 60);
    const secs = s % 60;
    return hours > 0
      ? `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
      : `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
  };

  let frame = 0;
  let lastPaint = 0;

  const tick = (now) => {
    if (!document.hidden && now - lastPaint >= 200) {
      lastPaint = now;
      const virtual = Math.max(0, Math.floor(((Date.now() - startedMs) / 1000) * speed));
      if (timer) timer.textContent = format(virtual);

      events.forEach((element) => {
        const at = Number(element.dataset.at || 0);
        if (virtual >= at && !element.classList.contains('is-past')) {
          element.classList.add('is-live', 'is-past');
          window.setTimeout(() => element.classList.remove('is-live'), 700);
        }
      });

      if (ghostState && ghostTime > 0 && virtual >= ghostTime) {
        ghostState.textContent = 'Ghost finished';
      }
    }
    frame = requestAnimationFrame(tick);
  };

  frame = requestAnimationFrame(tick);
  window.addEventListener('pagehide', () => cancelAnimationFrame(frame), { once: true });
})();

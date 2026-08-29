(() => {
  const root = document.querySelector('[data-race-root]');
  if (!root) return;
  const startedMs = Number(root.dataset.startedMs || Date.now());
  const speed = Number(root.dataset.speed || 1);
  const ghostTime = Number(root.dataset.ghostTime || 0);
  const timer = root.querySelector('[data-race-timer]');
  const ghostState = root.querySelector('[data-ghost-state]');
  const events = [...root.querySelectorAll('[data-ghost-event]')];
  const format = (s) => `${String(Math.floor(s/60)).padStart(2,'0')}:${String(Math.floor(s%60)).padStart(2,'0')}`;
  const update = () => {
    const virtual = Math.max(0, Math.floor(((Date.now()-startedMs)/1000)*speed));
    if (timer) timer.textContent = format(virtual);
    events.forEach((el) => {
      const at = Number(el.dataset.at || 0);
      if (virtual >= at && !el.classList.contains('is-past')) { el.classList.add('is-live','is-past'); setTimeout(()=>el.classList.remove('is-live'),700); }
    });
    if (ghostState && ghostTime > 0 && virtual >= ghostTime) ghostState.textContent = 'Ghost finished';
  };
  update(); setInterval(update, 250);
})();

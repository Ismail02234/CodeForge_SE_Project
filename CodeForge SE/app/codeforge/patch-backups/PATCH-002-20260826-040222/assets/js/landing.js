(() => {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const canvas = document.getElementById('forgeCanvas');
  const aura = document.getElementById('cursorAura');
  const core = document.getElementById('landingCore');
  const ctx = canvas?.getContext('2d');

  const pointer = { x: window.innerWidth / 2, y: window.innerHeight / 2, active: false };
  let width = window.innerWidth;
  let height = window.innerHeight;
  let dpr = Math.min(window.devicePixelRatio || 1, 2);
  let particles = [];
  let raf = 0;

  const palette = ['255,60,41', '255,112,38', '24,218,255'];

  function resize() {
    if (!canvas || !ctx) return;
    width = window.innerWidth;
    height = window.innerHeight;
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = Math.floor(width * dpr);
    canvas.height = Math.floor(height * dpr);
    canvas.style.width = `${width}px`;
    canvas.style.height = `${height}px`;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

    const count = Math.max(45, Math.min(110, Math.floor((width * height) / 18000)));
    particles = Array.from({ length: count }, (_, index) => ({
      x: Math.random() * width,
      y: Math.random() * height,
      vx: (Math.random() - 0.5) * 0.28,
      vy: (Math.random() - 0.5) * 0.28,
      r: Math.random() * 1.45 + 0.45,
      c: palette[index % palette.length],
    }));
  }

  function render() {
    if (!ctx || reducedMotion) return;
    ctx.clearRect(0, 0, width, height);

    particles.forEach((p, i) => {
      const dx = pointer.x - p.x;
      const dy = pointer.y - p.y;
      const distance = Math.hypot(dx, dy) || 1;

      if (pointer.active && distance < 210) {
        const force = (210 - distance) / 210;
        p.vx -= (dx / distance) * force * 0.018;
        p.vy -= (dy / distance) * force * 0.018;
      }

      p.vx *= 0.995;
      p.vy *= 0.995;
      p.x += p.vx;
      p.y += p.vy;

      if (p.x < -20) p.x = width + 20;
      if (p.x > width + 20) p.x = -20;
      if (p.y < -20) p.y = height + 20;
      if (p.y > height + 20) p.y = -20;

      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(${p.c}, .5)`;
      ctx.fill();

      for (let j = i + 1; j < particles.length; j += 1) {
        const q = particles[j];
        const lx = p.x - q.x;
        const ly = p.y - q.y;
        const d = Math.hypot(lx, ly);
        if (d < 108) {
          ctx.beginPath();
          ctx.moveTo(p.x, p.y);
          ctx.lineTo(q.x, q.y);
          ctx.strokeStyle = `rgba(${p.c}, ${(1 - d / 108) * 0.12})`;
          ctx.lineWidth = 0.55;
          ctx.stroke();
        }
      }
    });

    if (pointer.active) {
      const glow = ctx.createRadialGradient(pointer.x, pointer.y, 0, pointer.x, pointer.y, 145);
      glow.addColorStop(0, 'rgba(255, 64, 35, .11)');
      glow.addColorStop(1, 'rgba(255, 64, 35, 0)');
      ctx.fillStyle = glow;
      ctx.beginPath();
      ctx.arc(pointer.x, pointer.y, 145, 0, Math.PI * 2);
      ctx.fill();
    }

    raf = requestAnimationFrame(render);
  }

  function movePointer(event) {
    pointer.x = event.clientX;
    pointer.y = event.clientY;
    pointer.active = true;
    if (aura) {
      aura.style.setProperty('--mx', `${event.clientX}px`);
      aura.style.setProperty('--my', `${event.clientY}px`);
      aura.classList.add('is-active');
    }
  }

  window.addEventListener('mousemove', movePointer, { passive: true });
  window.addEventListener('mouseleave', () => {
    pointer.active = false;
    aura?.classList.remove('is-active');
  });
  window.addEventListener('resize', resize, { passive: true });

  if (core && !reducedMotion) {
    core.closest('.landing-visual')?.addEventListener('mousemove', (event) => {
      const rect = core.getBoundingClientRect();
      const x = (event.clientX - rect.left) / rect.width - 0.5;
      const y = (event.clientY - rect.top) / rect.height - 0.5;
      core.style.transform = `perspective(900px) rotateX(${(-y * 13).toFixed(2)}deg) rotateY(${(x * 17).toFixed(2)}deg) translate3d(${(x * 12).toFixed(1)}px, ${(y * 9).toFixed(1)}px, 0)`;
    });
    core.closest('.landing-visual')?.addEventListener('mouseleave', () => {
      core.style.transform = '';
    });
  }

  document.querySelectorAll('[data-tilt-card]').forEach((card) => {
    if (reducedMotion) return;
    card.addEventListener('mousemove', (event) => {
      const rect = card.getBoundingClientRect();
      const x = (event.clientX - rect.left) / rect.width - 0.5;
      const y = (event.clientY - rect.top) / rect.height - 0.5;
      card.style.transform = `perspective(850px) rotateX(${(-y * 5).toFixed(2)}deg) rotateY(${(x * 7).toFixed(2)}deg) translateY(-4px)`;
    });
    card.addEventListener('mouseleave', () => {
      card.style.transform = '';
    });
  });

  document.querySelectorAll('[data-magnetic]').forEach((element) => {
    if (reducedMotion) return;
    element.addEventListener('mousemove', (event) => {
      const rect = element.getBoundingClientRect();
      const x = event.clientX - (rect.left + rect.width / 2);
      const y = event.clientY - (rect.top + rect.height / 2);
      element.style.transform = `translate(${x * 0.08}px, ${y * 0.08}px)`;
    });
    element.addEventListener('mouseleave', () => {
      element.style.transform = '';
    });
  });

  resize();
  if (!reducedMotion) render();
  window.addEventListener('beforeunload', () => cancelAnimationFrame(raf));
})();
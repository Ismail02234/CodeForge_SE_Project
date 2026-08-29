(() => {
  'use strict';

  const canvas = document.getElementById('forgeCanvas');
  const ctx = canvas?.getContext('2d');
  const glow = document.getElementById('cursorGlow');
  const core = document.getElementById('forgeCore');
  const parallaxEls = [...document.querySelectorAll('[data-parallax]')];
  const magneticEls = [...document.querySelectorAll('.magnetic')];
  const tiltCards = [...document.querySelectorAll('.tilt-card')];
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const coarsePointer = window.matchMedia('(pointer: coarse)').matches;

  let width = window.innerWidth;
  let height = window.innerHeight;
  let dpr = Math.min(window.devicePixelRatio || 1, 1.5);
  let frame = 0;
  let particles = [];
  let pointerDirty = false;
  const pointer = {
    x: width * 0.5,
    y: height * 0.5,
    tx: width * 0.5,
    ty: height * 0.5,
    active: false,
  };

  const createParticles = () => {
    const count = Math.max(28, Math.min(72, Math.floor((width * height) / 22000)));
    particles = Array.from({ length: count }, () => ({
      x: Math.random() * width,
      y: Math.random() * height,
      vx: (Math.random() - 0.5) * 0.13,
      vy: (Math.random() - 0.5) * 0.13,
      size: Math.random() * 1.2 + 0.35,
      hot: Math.random() > 0.82,
    }));
  };

  const resize = () => {
    width = window.innerWidth;
    height = window.innerHeight;
    dpr = Math.min(window.devicePixelRatio || 1, 1.5);

    if (canvas && ctx) {
      canvas.width = Math.floor(width * dpr);
      canvas.height = Math.floor(height * dpr);
      canvas.style.width = `${width}px`;
      canvas.style.height = `${height}px`;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    createParticles();
  };

  const applyPointerEffects = () => {
    if (!pointerDirty || coarsePointer || reducedMotion) return;
    pointerDirty = false;

    const nx = (pointer.tx / Math.max(1, width) - 0.5) * 2;
    const ny = (pointer.ty / Math.max(1, height) - 0.5) * 2;

    if (glow) {
      glow.style.transform = `translate3d(${pointer.tx}px,${pointer.ty}px,0) translate(-50%,-50%)`;
    }

    parallaxEls.forEach((element) => {
      const amount = Number.parseFloat(element.dataset.parallax || '0');
      element.style.transform = `translate3d(${nx * width * amount}px,${ny * height * amount}px,0)`;
    });

    if (core) {
      core.style.transform = `rotateX(${-ny * 7}deg) rotateY(${nx * 9}deg) translate3d(${nx * 7}px,${ny * 7}px,0)`;
    }
  };

  const draw = () => {
    if (!ctx || document.hidden) {
      frame = requestAnimationFrame(draw);
      return;
    }

    ctx.clearRect(0, 0, width, height);

    pointer.x += (pointer.tx - pointer.x) * 0.08;
    pointer.y += (pointer.ty - pointer.y) * 0.08;
    applyPointerEffects();

    for (const particle of particles) {
      if (!reducedMotion) {
        particle.x += particle.vx;
        particle.y += particle.vy;
      }

      if (particle.x < -20) particle.x = width + 20;
      if (particle.x > width + 20) particle.x = -20;
      if (particle.y < -20) particle.y = height + 20;
      if (particle.y > height + 20) particle.y = -20;

      if (pointer.active && !coarsePointer && !reducedMotion) {
        const dx = pointer.x - particle.x;
        const dy = pointer.y - particle.y;
        const distanceSq = dx * dx + dy * dy;

        if (distanceSq < 175 * 175 && distanceSq > 16) {
          const distance = Math.sqrt(distanceSq);
          const pull = (1 - distance / 175) * 0.014;
          particle.x += dx * pull;
          particle.y += dy * pull;
        }
      }

      ctx.beginPath();
      ctx.fillStyle = particle.hot ? 'rgba(255,69,35,.66)' : 'rgba(255,255,255,.19)';
      ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
      ctx.fill();

      if (pointer.active && !coarsePointer && !reducedMotion) {
        const dx = pointer.x - particle.x;
        const dy = pointer.y - particle.y;
        const distance = Math.hypot(dx, dy);
        if (distance < 145) {
          ctx.beginPath();
          ctx.strokeStyle = `rgba(255,67,34,${(1 - distance / 145) * 0.18})`;
          ctx.lineWidth = 0.55;
          ctx.moveTo(particle.x, particle.y);
          ctx.lineTo(pointer.x, pointer.y);
          ctx.stroke();
        }
      }
    }

    frame = requestAnimationFrame(draw);
  };

  const onPointerMove = (event) => {
    pointer.tx = event.clientX;
    pointer.ty = event.clientY;
    pointer.active = true;
    pointerDirty = true;
  };

  window.addEventListener('pointermove', onPointerMove, { passive: true });
  window.addEventListener('pointerleave', () => { pointer.active = false; }, { passive: true });
  window.addEventListener('resize', resize, { passive: true });

  if (!coarsePointer && !reducedMotion) {
    magneticEls.forEach((element) => {
      let rect = null;
      element.addEventListener('pointerenter', () => { rect = element.getBoundingClientRect(); }, { passive: true });
      element.addEventListener('pointermove', (event) => {
        rect ||= element.getBoundingClientRect();
        const x = event.clientX - rect.left - rect.width / 2;
        const y = event.clientY - rect.top - rect.height / 2;
        element.style.transform = `translate3d(${x * 0.08}px,${y * 0.08}px,0)`;
      }, { passive: true });
      element.addEventListener('pointerleave', () => {
        rect = null;
        element.style.transform = '';
      }, { passive: true });
    });

    tiltCards.forEach((card) => {
      let rect = null;
      card.addEventListener('pointerenter', () => { rect = card.getBoundingClientRect(); }, { passive: true });
      card.addEventListener('pointermove', (event) => {
        if (window.innerWidth < 900) return;
        rect ||= card.getBoundingClientRect();
        const x = (event.clientX - rect.left) / rect.width - 0.5;
        const y = (event.clientY - rect.top) / rect.height - 0.5;
        card.style.transform = `perspective(900px) rotateX(${-y * 3.4}deg) rotateY(${x * 3.9}deg) translateY(-2px)`;
      }, { passive: true });
      card.addEventListener('pointerleave', () => {
        rect = null;
        card.style.transform = '';
      }, { passive: true });
    });
  }

  const typeTerminal = (container) => {
    const lines = [...container.querySelectorAll('[data-terminal-line]')];
    lines.forEach((line, index) => {
      const original = line.textContent || '';
      line.textContent = reducedMotion ? original : '';
      if (reducedMotion) return;

      window.setTimeout(() => {
        let i = 0;
        const write = () => {
          line.textContent = original.slice(0, i += 1);
          if (i < original.length) window.setTimeout(write, 16);
        };
        write();
      }, 260 + index * 430);
    });
  };

  const terminal = document.querySelector('.terminal-shell');
  if (terminal) {
    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries) => {
        if (!entries.some((entry) => entry.isIntersecting)) return;
        observer.disconnect();
        typeTerminal(terminal);
      }, { rootMargin: '120px' });
      observer.observe(terminal);
    } else {
      typeTerminal(terminal);
    }
  }

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden && !frame) frame = requestAnimationFrame(draw);
  });

  window.addEventListener('pagehide', () => {
    cancelAnimationFrame(frame);
    frame = 0;
  }, { once: true });

  resize();
  frame = requestAnimationFrame(draw);
})();

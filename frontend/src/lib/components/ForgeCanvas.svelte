<script lang="ts">
  import { onMount } from 'svelte';

  let canvas: HTMLCanvasElement;

  type Particle = {
    x: number;
    y: number;
    vx: number;
    vy: number;
    size: number;
    hot: boolean;
  };

  onMount(() => {
    const context = canvas.getContext('2d');

    if (!context) return;

    const ctx: CanvasRenderingContext2D = context;
    const glow = document.getElementById('cursorGlow') as HTMLElement | null;
    const core = document.getElementById('forgeCore') as HTMLElement | null;
    const parallaxEls = Array.from(document.querySelectorAll<HTMLElement>('[data-parallax]'));
    const magneticEls = Array.from(document.querySelectorAll<HTMLElement>('.magnetic'));
    const tiltCards = Array.from(document.querySelectorAll<HTMLElement>('.tilt-card'));

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const coarsePointer = window.matchMedia('(pointer: coarse)').matches;

    let width = window.innerWidth;
    let height = window.innerHeight;
    let dpr = Math.min(window.devicePixelRatio || 1, 1.5);
    let frame = 0;
    let particles: Particle[] = [];
    let pointerDirty = false;
    let visible = !document.hidden;

    const cleanups: Array<() => void> = [];

    const pointer = {
      x: width * 0.5,
      y: height * 0.5,
      tx: width * 0.5,
      ty: height * 0.5,
      active: false,
    };

    function listen<K extends keyof WindowEventMap>(
      target: Window,
      type: K,
      handler: (event: WindowEventMap[K]) => void,
      options?: AddEventListenerOptions | boolean
    ) {
      target.addEventListener(type, handler as EventListener, options);
      cleanups.push(() => target.removeEventListener(type, handler as EventListener, options));
    }

    function listenElement<K extends keyof HTMLElementEventMap>(
      target: HTMLElement,
      type: K,
      handler: (event: HTMLElementEventMap[K]) => void,
      options?: AddEventListenerOptions | boolean
    ) {
      target.addEventListener(type, handler as EventListener, options);
      cleanups.push(() => target.removeEventListener(type, handler as EventListener, options));
    }

    function createParticles() {
      const count = Math.max(28, Math.min(72, Math.floor((width * height) / 22000)));

      particles = Array.from({ length: count }, () => ({
        x: Math.random() * width,
        y: Math.random() * height,
        vx: (Math.random() - 0.5) * 0.13,
        vy: (Math.random() - 0.5) * 0.13,
        size: Math.random() * 1.2 + 0.35,
        hot: Math.random() > 0.82,
      }));
    }

    function resize() {
      width = window.innerWidth;
      height = window.innerHeight;
      dpr = Math.min(window.devicePixelRatio || 1, 1.5);

      canvas.width = Math.floor(width * dpr);
      canvas.height = Math.floor(height * dpr);
      canvas.style.width = `${width}px`;
      canvas.style.height = `${height}px`;

      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      createParticles();
    }

    function applyPointerEffects() {
      if (!pointerDirty || coarsePointer || reducedMotion) return;

      pointerDirty = false;

      const nx = (pointer.tx / Math.max(1, width) - 0.5) * 2;
      const ny = (pointer.ty / Math.max(1, height) - 0.5) * 2;

      if (glow) {
        glow.style.transform =
          `translate3d(${pointer.tx}px, ${pointer.ty}px, 0) ` + 'translate(-50%, -50%)';
      }

      for (const element of parallaxEls) {
        const amount = Number.parseFloat(element.dataset.parallax || '0');

        element.style.transform =
          `translate3d(${nx * width * amount}px, ` + `${ny * height * amount}px, 0)`;
      }

      if (core) {
        core.style.transform =
          `rotateX(${-ny * 7}deg) ` +
          `rotateY(${nx * 9}deg) ` +
          `translate3d(${nx * 7}px, ${ny * 7}px, 0)`;
      }
    }

    function draw() {
      frame = requestAnimationFrame(draw);

      if (!visible) return;

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
        ctx.fillStyle = particle.hot ? 'rgba(255, 69, 35, 0.66)' : 'rgba(255, 255, 255, 0.19)';
        ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
        ctx.fill();

        if (pointer.active && !coarsePointer && !reducedMotion) {
          const dx = pointer.x - particle.x;
          const dy = pointer.y - particle.y;
          const distance = Math.hypot(dx, dy);

          if (distance < 145) {
            ctx.beginPath();
            ctx.strokeStyle = `rgba(255, 67, 34, ${(1 - distance / 145) * 0.18})`;
            ctx.lineWidth = 0.55;
            ctx.moveTo(particle.x, particle.y);
            ctx.lineTo(pointer.x, pointer.y);
            ctx.stroke();
          }
        }
      }
    }

    function onPointerMove(event: PointerEvent) {
      pointer.tx = event.clientX;
      pointer.ty = event.clientY;
      pointer.active = true;
      pointerDirty = true;
    }

    function onPointerLeave() {
      pointer.active = false;
    }

    function setupMagneticElements() {
      if (coarsePointer || reducedMotion) return;

      for (const element of magneticEls) {
        let rect: DOMRect | null = null;

        listenElement(
          element,
          'pointerenter',
          () => {
            rect = element.getBoundingClientRect();
          },
          { passive: true }
        );

        listenElement(
          element,
          'pointermove',
          (event) => {
            rect ??= element.getBoundingClientRect();

            const x = event.clientX - rect.left - rect.width / 2;
            const y = event.clientY - rect.top - rect.height / 2;

            element.style.transform = `translate3d(${x * 0.08}px, ${y * 0.08}px, 0)`;
          },
          { passive: true }
        );

        listenElement(
          element,
          'pointerleave',
          () => {
            rect = null;
            element.style.transform = '';
          },
          { passive: true }
        );
      }
    }

    function setupTiltCards() {
      if (coarsePointer || reducedMotion) return;

      for (const card of tiltCards) {
        let rect: DOMRect | null = null;

        listenElement(
          card,
          'pointerenter',
          () => {
            rect = card.getBoundingClientRect();
          },
          { passive: true }
        );

        listenElement(
          card,
          'pointermove',
          (event) => {
            if (window.innerWidth < 900) return;

            rect ??= card.getBoundingClientRect();

            const x = (event.clientX - rect.left) / rect.width - 0.5;
            const y = (event.clientY - rect.top) / rect.height - 0.5;

            card.style.transform =
              `perspective(900px) ` +
              `rotateX(${-y * 3.4}deg) ` +
              `rotateY(${x * 3.9}deg) ` +
              'translateY(-2px)';
          },
          { passive: true }
        );

        listenElement(
          card,
          'pointerleave',
          () => {
            rect = null;
            card.style.transform = '';
          },
          { passive: true }
        );
      }
    }

    function typeTerminal(container: Element) {
      const lines = Array.from(container.querySelectorAll<HTMLElement>('[data-terminal-line]'));

      for (const [index, line] of lines.entries()) {
        const original = line.textContent || '';

        if (reducedMotion) {
          line.textContent = original;
          continue;
        }

        line.textContent = '';

        const startTimer = window.setTimeout(
          () => {
            let position = 0;

            const write = () => {
              position += 1;
              line.textContent = original.slice(0, position);

              if (position < original.length) {
                const timer = window.setTimeout(write, 16);
                cleanups.push(() => clearTimeout(timer));
              }
            };

            write();
          },
          260 + index * 430
        );

        cleanups.push(() => clearTimeout(startTimer));
      }
    }

    function setupTerminal() {
      const terminal = document.querySelector('.terminal-shell');

      if (!terminal) return;

      if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(
          (entries) => {
            if (!entries.some((entry) => entry.isIntersecting)) return;

            observer.disconnect();
            typeTerminal(terminal);
          },
          { rootMargin: '120px' }
        );

        observer.observe(terminal);
        cleanups.push(() => observer.disconnect());
      } else {
        typeTerminal(terminal);
      }
    }

    function onVisibilityChange() {
      visible = !document.hidden;
    }

    listen(window, 'pointermove', onPointerMove, { passive: true });
    listen(window, 'pointerleave', onPointerLeave, { passive: true });
    listen(window, 'resize', resize, { passive: true });

    document.addEventListener('visibilitychange', onVisibilityChange);
    cleanups.push(() => document.removeEventListener('visibilitychange', onVisibilityChange));

    setupMagneticElements();
    setupTiltCards();
    setupTerminal();

    resize();
    draw();

    return () => {
      cancelAnimationFrame(frame);

      if (glow) glow.style.transform = '';
      if (core) core.style.transform = '';

      for (const element of parallaxEls) {
        element.style.transform = '';
      }

      for (const cleanup of cleanups) {
        cleanup();
      }
    };
  });
</script>

<canvas class="forge-canvas" bind:this={canvas} aria-hidden="true"></canvas>

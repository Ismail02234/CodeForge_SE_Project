<script lang="ts">
  import { onMount } from 'svelte';
  let canvas: HTMLCanvasElement;
  onMount(() => {
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    let raf = 0,
      width = 0,
      height = 0,
      dpr = 1,
      active = true;
    const mouse = {
      x: innerWidth / 2,
      y: innerHeight / 2,
      tx: innerWidth / 2,
      ty: innerHeight / 2,
      on: false,
    };
    let particles: any[] = [];
    const resize = () => {
      width = innerWidth;
      height = innerHeight;
      dpr = Math.min(devicePixelRatio || 1, 2);
      canvas.width = Math.round(width * dpr);
      canvas.height = Math.round(height * dpr);
      canvas.style.width = `${width}px`;
      canvas.style.height = `${height}px`;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
      const count = reduced ? 20 : Math.max(34, Math.min(88, Math.floor((width * height) / 19000)));
      particles = Array.from({ length: count }, () => ({
        x: Math.random() * width,
        y: Math.random() * height,
        vx: (Math.random() - 0.5) * 0.16,
        vy: (Math.random() - 0.5) * 0.16,
        r: Math.random() * 1.2 + 0.35,
        hot: Math.random() > 0.82,
      }));
    };
    const move = (e: PointerEvent) => {
      mouse.tx = e.clientX;
      mouse.ty = e.clientY;
      mouse.on = true;
    };
    const draw = () => {
      if (!active) {
        raf = requestAnimationFrame(draw);
        return;
      }
      ctx.clearRect(0, 0, width, height);
      mouse.x += (mouse.tx - mouse.x) * 0.08;
      mouse.y += (mouse.ty - mouse.y) * 0.08;
      for (const p of particles) {
        p.x += p.vx;
        p.y += p.vy;
        if (p.x < 0) p.x = width;
        if (p.x > width) p.x = 0;
        if (p.y < 0) p.y = height;
        if (p.y > height) p.y = 0;
        if (mouse.on) {
          const dx = mouse.x - p.x,
            dy = mouse.y - p.y,
            d = Math.hypot(dx, dy);
          if (d < 180 && d > 1) {
            const pull = (1 - d / 180) * 0.018;
            p.x += dx * pull;
            p.y += dy * pull;
            if (d < 145) {
              ctx.beginPath();
              ctx.strokeStyle = `rgba(255,65,35,${(1 - d / 145) * 0.18})`;
              ctx.moveTo(p.x, p.y);
              ctx.lineTo(mouse.x, mouse.y);
              ctx.stroke();
            }
          }
        }
        ctx.beginPath();
        ctx.fillStyle = p.hot ? 'rgba(255,72,38,.7)' : 'rgba(255,255,255,.2)';
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fill();
      }
      raf = requestAnimationFrame(draw);
    };
    const visibility = () => {
      active = !document.hidden;
    };
    addEventListener('resize', resize);
    addEventListener('pointermove', move, { passive: true });
    document.addEventListener('visibilitychange', visibility);
    resize();
    draw();
    return () => {
      cancelAnimationFrame(raf);
      removeEventListener('resize', resize);
      removeEventListener('pointermove', move);
      document.removeEventListener('visibilitychange', visibility);
    };
  });
</script>

<canvas class="forge-canvas" bind:this={canvas}></canvas>

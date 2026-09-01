<script lang="ts">
  import { onMount } from 'svelte';

  export let dimensions: Record<string, number> = {};
  export let labels: Record<string, string> = {};

  let canvas: HTMLCanvasElement;
  let frame = 0;
  let lastWidth = 0;
  let lastHeight = 0;

  function scheduleDraw() {
    if (typeof window === 'undefined' || !canvas) return;

    cancelAnimationFrame(frame);
    frame = requestAnimationFrame(draw);
  }

  function draw() {
    if (!canvas) return;

    const box = canvas.getBoundingClientRect();
    const width = Math.max(1, Math.round(box.width));
    const height = Math.max(1, Math.round(box.height));
    const dpr = Math.min(window.devicePixelRatio || 1, 2);

    const pixelWidth = Math.max(1, Math.round(width * dpr));
    const pixelHeight = Math.max(1, Math.round(height * dpr));

    if (canvas.width !== pixelWidth) canvas.width = pixelWidth;
    if (canvas.height !== pixelHeight) canvas.height = pixelHeight;

    lastWidth = width;
    lastHeight = height;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.clearRect(0, 0, width, height);

    const keys = Object.keys(dimensions);
    if (keys.length < 3) return;

    const cx = width / 2;
    const cy = height / 2;
    const radius = Math.min(width, height) * 0.34;

    const point = (index: number, scale: number): [number, number] => {
      const angle = -Math.PI / 2 + (index / keys.length) * Math.PI * 2;
      return [cx + Math.cos(angle) * radius * scale, cy + Math.sin(angle) * radius * scale];
    };

    ctx.strokeStyle = 'rgba(255,255,255,.11)';
    ctx.lineWidth = 1;

    for (let ring = 1; ring <= 4; ring++) {
      ctx.beginPath();

      keys.forEach((_, index) => {
        const [x, y] = point(index, ring / 4);
        if (index === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
      });

      ctx.closePath();
      ctx.stroke();
    }

    keys.forEach((_, index) => {
      const [x, y] = point(index, 1);
      ctx.beginPath();
      ctx.moveTo(cx, cy);
      ctx.lineTo(x, y);
      ctx.stroke();
    });

    const gradient = ctx.createLinearGradient(0, 0, width, height);
    gradient.addColorStop(0, 'rgba(255,47,24,.72)');
    gradient.addColorStop(1, 'rgba(255,121,0,.35)');

    ctx.beginPath();

    keys.forEach((key, index) => {
      const value = Number.isFinite(dimensions[key]) ? dimensions[key] : 0;
      const [x, y] = point(index, Math.max(0, Math.min(100, value)) / 100);

      if (index === 0) ctx.moveTo(x, y);
      else ctx.lineTo(x, y);
    });

    ctx.closePath();
    ctx.fillStyle = gradient;
    ctx.fill();
    ctx.strokeStyle = '#ff4b2b';
    ctx.lineWidth = 2;
    ctx.stroke();

    ctx.fillStyle = '#a5a7ad';
    ctx.font = '600 10px JetBrains Mono';
    ctx.textAlign = 'center';

    keys.forEach((key, index) => {
      const [x, y] = point(index, 1.18);
      ctx.fillText((labels[key] || key).toUpperCase(), x, y);
    });
  }

  $: (dimensions, labels, scheduleDraw());

  onMount(() => {
    scheduleDraw();

    const observer = new ResizeObserver((entries) => {
      const entry = entries[0];
      if (!entry) return;

      const width = Math.max(1, Math.round(entry.contentRect.width));
      const height = Math.max(1, Math.round(entry.contentRect.height));

      if (width !== lastWidth || height !== lastHeight) {
        scheduleDraw();
      }
    });

    observer.observe(canvas);

    return () => {
      observer.disconnect();
      cancelAnimationFrame(frame);
    };
  });
</script>

<canvas class="dna-radar" bind:this={canvas} aria-label="Code DNA radar chart"></canvas>

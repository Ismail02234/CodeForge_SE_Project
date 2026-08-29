(() => {
  'use strict';

  const canvas = document.querySelector('[data-dna-radar]');
  if (!canvas) return;

  let values = [];
  let labels = [];
  try {
    values = JSON.parse(canvas.getAttribute('data-values') || '[]');
    labels = JSON.parse(canvas.getAttribute('data-labels') || '[]');
  } catch (_) {
    return;
  }

  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  let frame = 0;

  const draw = () => {
    cancelAnimationFrame(frame);
    frame = requestAnimationFrame(() => {
      const cssSize = Math.max(260, Math.min(430, canvas.parentElement?.clientWidth || 430));
      const dpr = Math.min(window.devicePixelRatio || 1, 2);
      canvas.width = Math.round(cssSize * dpr);
      canvas.height = Math.round(cssSize * dpr);
      canvas.style.width = `${cssSize}px`;
      canvas.style.height = `${cssSize}px`;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      ctx.clearRect(0, 0, cssSize, cssSize);

      const cx = cssSize / 2;
      const cy = cssSize / 2;
      const radius = cssSize * 0.31;
      const n = Math.max(3, values.length);
      const point = (i, r) => {
        const angle = -Math.PI / 2 + i * (Math.PI * 2 / n);
        return [cx + Math.cos(angle) * r, cy + Math.sin(angle) * r];
      };

      for (let ring = 1; ring <= 5; ring += 1) {
        ctx.beginPath();
        for (let i = 0; i < n; i += 1) {
          const [x, y] = point(i, radius * ring / 5);
          i ? ctx.lineTo(x, y) : ctx.moveTo(x, y);
        }
        ctx.closePath();
        ctx.strokeStyle = 'rgba(255,255,255,.11)';
        ctx.lineWidth = 1;
        ctx.stroke();
      }

      for (let i = 0; i < n; i += 1) {
        const [x, y] = point(i, radius);
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.lineTo(x, y);
        ctx.strokeStyle = 'rgba(255,255,255,.08)';
        ctx.stroke();

        const [lx, ly] = point(i, radius + 35);
        ctx.fillStyle = '#a7a9b0';
        ctx.font = '600 11px "Space Grotesk", system-ui, sans-serif';
        ctx.textAlign = lx < cx - 8 ? 'right' : lx > cx + 8 ? 'left' : 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText((labels[i] || '').replace('Problem Solving', 'Problem'), lx, ly);
      }

      ctx.beginPath();
      values.forEach((value, i) => {
        const normalized = Math.max(0, Math.min(100, Number(value) || 0));
        const [x, y] = point(i, radius * normalized / 100);
        i ? ctx.lineTo(x, y) : ctx.moveTo(x, y);
      });
      ctx.closePath();
      ctx.fillStyle = 'rgba(255,55,29,.16)';
      ctx.fill();
      ctx.strokeStyle = '#ff4228';
      ctx.lineWidth = 2;
      ctx.stroke();

      values.forEach((value, i) => {
        const normalized = Math.max(0, Math.min(100, Number(value) || 0));
        const [x, y] = point(i, radius * normalized / 100);
        ctx.beginPath();
        ctx.arc(x, y, 4, 0, Math.PI * 2);
        ctx.fillStyle = '#ff8a19';
        ctx.fill();
      });
    });
  };

  draw();

  if ('ResizeObserver' in window && canvas.parentElement) {
    const observer = new ResizeObserver(draw);
    observer.observe(canvas.parentElement);
  } else {
    window.addEventListener('resize', draw, { passive: true });
  }
})();

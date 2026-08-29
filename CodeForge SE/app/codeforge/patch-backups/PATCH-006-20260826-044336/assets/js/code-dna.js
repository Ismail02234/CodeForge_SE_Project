(() => {
  const canvas = document.querySelector('[data-dna-radar]');
  if (!canvas) return;
  const raw = canvas.getAttribute('data-values');
  const labelRaw = canvas.getAttribute('data-labels');
  let values = [], labels = [];
  try { values = JSON.parse(raw || '[]'); labels = JSON.parse(labelRaw || '[]'); } catch (_) { return; }
  const ctx = canvas.getContext('2d');
  const dpr = window.devicePixelRatio || 1;
  const cssSize = Math.min(430, canvas.parentElement?.clientWidth || 430);
  canvas.width = cssSize * dpr; canvas.height = cssSize * dpr; canvas.style.width = cssSize + 'px'; canvas.style.height = cssSize + 'px'; ctx.scale(dpr, dpr);
  const cx = cssSize / 2, cy = cssSize / 2, radius = cssSize * .31, n = Math.max(3, values.length);
  const point = (i, r) => { const a = -Math.PI/2 + i * (Math.PI*2/n); return [cx + Math.cos(a)*r, cy + Math.sin(a)*r]; };
  ctx.clearRect(0,0,cssSize,cssSize);
  for (let ring=1;ring<=5;ring++) { ctx.beginPath(); for (let i=0;i<n;i++){ const [x,y]=point(i,radius*ring/5); i?ctx.lineTo(x,y):ctx.moveTo(x,y);} ctx.closePath(); ctx.strokeStyle='rgba(143,165,194,.16)'; ctx.lineWidth=1; ctx.stroke(); }
  for(let i=0;i<n;i++){ const [x,y]=point(i,radius); ctx.beginPath();ctx.moveTo(cx,cy);ctx.lineTo(x,y);ctx.strokeStyle='rgba(143,165,194,.12)';ctx.stroke(); const [lx,ly]=point(i,radius+34); ctx.fillStyle='#9fb2c9';ctx.font='11px system-ui';ctx.textAlign=lx<cx-8?'right':lx>cx+8?'left':'center';ctx.textBaseline='middle';ctx.fillText((labels[i]||'').replace('Problem Solving','Problem'),lx,ly); }
  ctx.beginPath(); values.forEach((v,i)=>{const [x,y]=point(i,radius*Math.max(0,Math.min(100,Number(v)))/100);i?ctx.lineTo(x,y):ctx.moveTo(x,y);});ctx.closePath();ctx.fillStyle='rgba(124,92,255,.22)';ctx.fill();ctx.strokeStyle='#7c5cff';ctx.lineWidth=2;ctx.stroke();
  values.forEach((v,i)=>{const [x,y]=point(i,radius*Number(v)/100);ctx.beginPath();ctx.arc(x,y,4,0,Math.PI*2);ctx.fillStyle='#26d0ce';ctx.fill();});
})();

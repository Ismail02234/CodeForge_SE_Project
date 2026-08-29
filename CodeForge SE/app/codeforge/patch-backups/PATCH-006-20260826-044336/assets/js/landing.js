(() => {
    const canvas = document.getElementById('forgeCanvas');
    const ctx = canvas?.getContext('2d');
    const glow = document.getElementById('cursorGlow');
    const core = document.getElementById('forgeCore');
    const parallaxEls = [...document.querySelectorAll('[data-parallax]')];
    const magnetic = [...document.querySelectorAll('.magnetic')];
    const tiltCards = [...document.querySelectorAll('.tilt-card')];

    let width = innerWidth;
    let height = innerHeight;
    let dpr = Math.min(devicePixelRatio || 1, 2);
    let mouse = { x: width * 0.5, y: height * 0.5, tx: width * 0.5, ty: height * 0.5, active: false };
    let particles = [];

    function resize() {
        width = innerWidth;
        height = innerHeight;
        dpr = Math.min(devicePixelRatio || 1, 2);

        if (canvas && ctx) {
            canvas.width = Math.floor(width * dpr);
            canvas.height = Math.floor(height * dpr);
            canvas.style.width = `${width}px`;
            canvas.style.height = `${height}px`;
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        }

        const amount = Math.max(34, Math.min(92, Math.floor((width * height) / 18500)));
        particles = Array.from({ length: amount }, () => ({
            x: Math.random() * width,
            y: Math.random() * height,
            vx: (Math.random() - 0.5) * 0.16,
            vy: (Math.random() - 0.5) * 0.16,
            size: Math.random() * 1.25 + 0.35,
            hot: Math.random() > 0.82
        }));
    }

    function draw() {
        if (!ctx) return;

        ctx.clearRect(0, 0, width, height);

        mouse.x += (mouse.tx - mouse.x) * 0.09;
        mouse.y += (mouse.ty - mouse.y) * 0.09;

        for (const p of particles) {
            p.x += p.vx;
            p.y += p.vy;

            if (p.x < -20) p.x = width + 20;
            if (p.x > width + 20) p.x = -20;
            if (p.y < -20) p.y = height + 20;
            if (p.y > height + 20) p.y = -20;

            if (mouse.active) {
                const dx = mouse.x - p.x;
                const dy = mouse.y - p.y;
                const distSq = dx * dx + dy * dy;
                if (distSq < 190 * 190 && distSq > 10) {
                    const dist = Math.sqrt(distSq);
                    const pull = (1 - dist / 190) * 0.022;
                    p.x += dx * pull;
                    p.y += dy * pull;
                }
            }

            ctx.beginPath();
            ctx.fillStyle = p.hot ? 'rgba(255,69,35,.72)' : 'rgba(255,255,255,.22)';
            ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
            ctx.fill();

            if (mouse.active) {
                const dx = mouse.x - p.x;
                const dy = mouse.y - p.y;
                const dist = Math.hypot(dx, dy);

                if (dist < 155) {
                    ctx.beginPath();
                    ctx.strokeStyle = `rgba(255,67,34,${(1 - dist / 155) * 0.22})`;
                    ctx.lineWidth = 0.6;
                    ctx.moveTo(p.x, p.y);
                    ctx.lineTo(mouse.x, mouse.y);
                    ctx.stroke();
                }
            }
        }

        requestAnimationFrame(draw);
    }

    function pointerMove(e) {
        mouse.tx = e.clientX;
        mouse.ty = e.clientY;
        mouse.active = true;

        if (glow) {
            glow.style.left = `${e.clientX}px`;
            glow.style.top = `${e.clientY}px`;
        }

        const nx = (e.clientX / width - 0.5) * 2;
        const ny = (e.clientY / height - 0.5) * 2;

        for (const el of parallaxEls) {
            const amount = parseFloat(el.dataset.parallax || '0');
            el.style.transform = `translate3d(${nx * width * amount}px, ${ny * height * amount}px, 0)`;
        }

        if (core) {
            core.style.transform = `rotateX(${-ny * 7}deg) rotateY(${nx * 9}deg) translate3d(${nx * 7}px,${ny * 7}px,0)`;
        }
    }

    addEventListener('pointermove', pointerMove, { passive: true });
    addEventListener('pointerleave', () => { mouse.active = false; });
    addEventListener('resize', resize);

    magnetic.forEach(el => {
        el.addEventListener('pointermove', e => {
            const r = el.getBoundingClientRect();
            const x = e.clientX - r.left - r.width / 2;
            const y = e.clientY - r.top - r.height / 2;
            el.style.transform = `translate(${x * .09}px, ${y * .09}px)`;
        });
        el.addEventListener('pointerleave', () => {
            el.style.transform = '';
        });
    });

    tiltCards.forEach(card => {
        card.addEventListener('pointermove', e => {
            if (innerWidth < 900) return;
            const r = card.getBoundingClientRect();
            const x = (e.clientX - r.left) / r.width - .5;
            const y = (e.clientY - r.top) / r.height - .5;
            card.style.transform = `perspective(900px) rotateX(${-y * 3.7}deg) rotateY(${x * 4.2}deg) translateY(-2px)`;
        });
        card.addEventListener('pointerleave', () => {
            card.style.transform = '';
        });
    });

    const terminalLines = [...document.querySelectorAll('[data-terminal-line]')];
    terminalLines.forEach((line, index) => {
        const original = line.textContent;
        line.textContent = '';
        setTimeout(() => {
            let i = 0;
            const timer = setInterval(() => {
                line.textContent = original.slice(0, ++i);
                if (i >= original.length) clearInterval(timer);
            }, 18);
        }, 500 + index * 620);
    });

    resize();
    draw();
})();

// Public-page motion: reveal on scroll, hero tint cycle, count-up, drag carousel with a follow cursor.
// Everything is skipped when the visitor prefers reduced motion, and the page reads fine without it.
import { mesh } from './tokens.js';

function hexToRgb(hex) {
    const n = parseInt(hex.slice(1), 16);
    return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
}

// The gradient behind the page: soft moving color blobs that change palette with the section in view.
function initMesh(calm) {
    const canvas = document.querySelector('[data-mesh]');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const scale = 6;
    const resize = () => { canvas.width = Math.ceil(window.innerWidth / scale); canvas.height = Math.ceil(window.innerHeight / scale); };
    resize();
    window.addEventListener('resize', resize);
    const sections = [...document.querySelectorAll('[data-bg]')];
    let current = mesh.gray.map(hexToRgb);
    let target = current;
    const pick = () => {
        const mid = window.innerHeight * 0.5;
        let name = 'gray';
        sections.forEach((el) => { const r = el.getBoundingClientRect(); if (r.top <= mid && r.bottom > mid) name = el.dataset.bg; });
        target = (mesh[name] || mesh.gray).map(hexToRgb);
    };
    window.addEventListener('scroll', pick, { passive: true });
    pick();
    if (!calm) current = target;
    const blobs = [[0.15, 0.3, 0.55, 0.00031, 0], [0.8, 0.2, 0.5, 0.00027, 1.7], [0.5, 0.75, 0.6, 0.00023, 3.1], [0.1, 0.9, 0.45, 0.00035, 4.4], [0.9, 0.85, 0.5, 0.00029, 5.6]];
    const draw = (t) => {
        current = current.map((c, i) => c.map((v, k) => v + (target[i][k] - v) * 0.04));
        const w = canvas.width, h = canvas.height;
        ctx.fillStyle = `rgb(${current[0].map(Math.round).join(',')})`;
        ctx.fillRect(0, 0, w, h);
        blobs.forEach(([bx, by, r, sp, ph], i) => {
            const x = (bx + Math.sin(t * sp + ph) * 0.18) * w;
            const y = (by + Math.cos(t * sp * 1.3 + ph) * 0.18) * h;
            const rad = r * Math.max(w, h);
            const col = current[(i + 1) % current.length].map(Math.round).join(',');
            const g = ctx.createRadialGradient(x, y, 0, x, y, rad);
            g.addColorStop(0, `rgba(${col},0.9)`);
            g.addColorStop(1, `rgba(${col},0)`);
            ctx.fillStyle = g;
            ctx.fillRect(0, 0, w, h);
        });
    };
    if (calm) { current = target; draw(0); window.addEventListener('scroll', () => draw(0), { passive: true }); return; }
    const loop = (t) => { if (!document.hidden) draw(t); requestAnimationFrame(loop); };
    requestAnimationFrame(loop);
}

export function initPublicFx() {
    const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const root = document.documentElement;
    root.classList.add('fx');
    initMesh(calm);

    const size = document.querySelector('[data-text-size]');
    if (size) {
        const label = size.querySelector('[data-text-size-label]');
        size.addEventListener('click', () => {
            const on = root.classList.toggle('big-text');
            size.setAttribute('aria-pressed', String(on));
            if (label) label.textContent = on ? 'Normal text' : 'Bigger text';
        });
    }

    // Content shows as you scroll: everything below the first screen slides in when it arrives. Images wait until near the screen.
    const auto = '.cc-section .cc-wrap > *, .cc-pagehead .cc-wrap > *, .cc-content > *, .cc-split, .cc-calc-panel, .cc-panel, .cc-kpi, .cc-big-card, .cc-card, .cc-foot-cols > div';
    document.querySelectorAll(auto).forEach((el) => {
        if (el.closest('.cc-hero') || el.closest('.cc-nav') || el.closest('[wire\\:id]') || el.matches('script, style, template')) return;
        if (el.querySelector('[data-reveal]') || el.closest('[data-reveal]')) return;
        el.setAttribute('data-reveal', '');
    });
    document.querySelectorAll('img').forEach((img) => { if (! img.closest('.cc-hero, .cc-nav')) { img.loading = 'lazy'; img.decoding = 'async'; } });

    const reveals = document.querySelectorAll('[data-reveal]');
    if (calm || !('IntersectionObserver' in window)) {
        reveals.forEach((el) => el.classList.add('is-in'));
    } else {
        const seen = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-in');
                seen.unobserve(entry.target);
            });
        }, { threshold: 0.15 });
        reveals.forEach((el, i) => {
            el.style.transitionDelay = `${(i % 4) * 80}ms`;
            seen.observe(el);
        });
    }

    document.querySelectorAll('[data-count]').forEach((el) => {
        const end = Number(el.dataset.count);
        if (calm || !('IntersectionObserver' in window)) { el.textContent = end.toLocaleString('en-US'); return; }
        const watch = new IntersectionObserver((entries) => {
            if (!entries[0].isIntersecting) return;
            watch.disconnect();
            const start = performance.now();
            const tick = (now) => {
                const p = Math.min(1, (now - start) / 1400);
                el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3))).toLocaleString('en-US');
                if (p < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        });
        watch.observe(el);
    });

    document.querySelectorAll('[data-drag]').forEach((track) => {
        const chip = document.createElement('span');
        chip.className = 'cc-cursor';
        chip.textContent = '\u2039  Drag  \u203A';
        chip.setAttribute('aria-hidden', 'true');
        track.parentElement.appendChild(chip);
        let down = false, startX = 0, startLeft = 0;
        track.addEventListener('pointerdown', (e) => { if (e.pointerType !== 'mouse') return; down = true; startX = e.clientX; startLeft = track.scrollLeft; track.classList.add('is-drag'); });
        window.addEventListener('pointerup', () => { down = false; track.classList.remove('is-drag'); });
        track.addEventListener('pointermove', (e) => {
            if (e.pointerType !== 'mouse') return;
            const box = track.parentElement.getBoundingClientRect();
            chip.style.transform = `translate(${e.clientX - box.left}px, ${e.clientY - box.top}px)`;
            if (down) track.scrollLeft = startLeft - (e.clientX - startX);
        });
        track.addEventListener('pointerenter', (e) => { if (e.pointerType === 'mouse') chip.classList.add('is-on'); });
        track.addEventListener('pointerleave', () => chip.classList.remove('is-on'));
    });

    // Header: floats as a pill at the top, becomes a full-width bar once the page has scrolled.
    const bar = document.querySelector('.cc-nav');
    if (bar) {
        const mark = () => bar.classList.toggle('is-scrolled', window.scrollY > 10);
        window.addEventListener('scroll', mark, { passive: true });
        mark();
    }

    // Hero line three swaps between phrases. The first phrase stays in the HTML for readers and no-script visitors.
    const word = document.querySelector('[data-words]');
    if (word && !calm) {
        const words = word.dataset.words.split('|');
        let w = 0;
        setInterval(() => {
            word.classList.add('is-out');
            setTimeout(() => {
                w = (w + 1) % words.length;
                word.textContent = words[w];
                word.classList.remove('is-out');
                word.classList.add('is-pre');
                void word.offsetWidth;
                word.classList.remove('is-pre');
            }, 360);
        }, 3600);
    }
}

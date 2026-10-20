// Public-page motion: reveal on scroll, hero tint cycle, count-up, drag carousel with a follow cursor.
// Everything is skipped when the visitor prefers reduced motion, and the page reads fine without it.
export function initPublicFx() {
    const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const root = document.documentElement;
    root.classList.add('fx');

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

    const hero = document.querySelector('[data-tint-cycle]');
    if (hero && !calm) {
        const tints = ['butter', 'peach', 'mint', 'lilac'];
        let i = 0;
        setInterval(() => {
            hero.classList.remove(`cc-tint-${tints[i]}`);
            i = (i + 1) % tints.length;
            hero.classList.add(`cc-tint-${tints[i]}`);
        }, 4500);
    }

    document.querySelectorAll('[data-drag]').forEach((track) => {
        const chip = document.createElement('span');
        chip.className = 'cc-cursor';
        chip.textContent = 'Drag';
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
}

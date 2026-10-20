// Dashboard behaviour: polls the machine, redraws the gauge, ticker and numbers, and draws the payout chart.
// It reads the same JSON the server used for the first paint, so a refresh never changes a figure.
import { colors } from './tokens.js';
import { money } from './calculator.js';

const POLL_MS = 10000;

function setText(root, key, value) {
    root.querySelectorAll(`[data-bind="${key}"]`).forEach((el) => {
        if (el.textContent !== String(value)) el.textContent = String(value);
    });
}

export function render(root, s) {
    setText(root, 'stock', s.stock);
    setText(root, 'capacity', s.capacity);
    setText(root, 'sold', s.sold_today);
    setText(root, 'clock', s.clock);
    setText(root, 'date', s.date);
    setText(root, 'temp', s.temp_c.toFixed(1));
    setText(root, 'state', s.state_label);
    setText(root, 'revenue', money(s.revenue_today_cents));
    setText(root, 'accrued', money(s.accrued_today_cents));
    if (s.paid_cents !== undefined) {
        setText(root, 'paid', money(s.paid_cents));
        setText(root, 'payback', s.payback_pct + '%');
        const bar = root.querySelector('[data-payback-bar]');
        if (bar) bar.style.width = s.payback_pct + '%';
    }
    const fill = root.querySelector('[data-gauge-fill]');
    if (fill) {
        fill.style.height = s.fill_pct + '%';
        fill.dataset.state = s.state;
    }
    setText(root, 'fill', s.fill_pct + '%');

    const ticker = root.querySelector('[data-ticker]');
    if (ticker) {
        ticker.replaceChildren(...s.ticker.map((t) => {
            const li = document.createElement('li');
            li.className = 'dw-tick';
            const a = document.createElement('span');
            a.textContent = `${t.time}  unit ${t.unit}`;
            const b = document.createElement('span');
            b.textContent = money(t.cents);
            li.append(a, b);
            return li;
        }));
    }

    const logs = root.querySelector('[data-logs]');
    if (logs) {
        logs.replaceChildren(...s.logs.map((l) => {
            const li = document.createElement('li');
            li.className = 'dw-tick';
            const a = document.createElement('span');
            a.textContent = `${l.at}  ${l.event}`;
            const b = document.createElement('span');
            b.textContent = l.message;
            li.append(a, b);
            return li;
        }));
    }

    root.querySelectorAll('[data-needs-stock]').forEach((b) => { b.disabled = !s.can_burst; });
    root.querySelectorAll('[data-needs-empty]').forEach((b) => { b.disabled = !s.can_restock; });
}

export function initHub(root) {
    const url = root.dataset.src;
    let timer = null;
    const tick = async () => {
        try {
            const res = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (res.ok) render(root, await res.json());
        } catch (e) { /* keep the last numbers; try again next time */ }
    };
    const start = () => { if (!timer) timer = setInterval(tick, POLL_MS); };
    const stop = () => { clearInterval(timer); timer = null; };
    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : (tick(), start())));
    start();

    const canvas = root.querySelector('[data-payout-chart]');
    if (canvas && window.Chart) {
        const rows = JSON.parse(canvas.dataset.rows || '[]');
        const mono = { family: 'Geist Mono, ui-monospace, Menlo, Consolas, monospace', size: 11 };
        new window.Chart(canvas, {
            type: 'bar',
            data: { labels: rows.map((r) => r.date), datasets: [{ label: 'Daily payout', data: rows.map((r) => r.cents / 100), backgroundColor: colors.accent, borderWidth: 0 }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: { legend: { display: false }, tooltip: { cornerRadius: 0, backgroundColor: colors.black, titleFont: mono, bodyFont: mono } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: mono, color: colors[500], maxTicksLimit: 8 } },
                    y: { ticks: { font: mono, color: colors[500], callback: (v) => '$' + v }, grid: { color: colors[200] } },
                },
            },
        });
    }
}

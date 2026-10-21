// Yield calculator (Alpine component). All money is integer cents; the page prints it with money().
// The two tiers arrive as JSON from the server, so the figures here always match the server config.
import { colors } from './tokens.js';

export const money = (cents) =>
    (cents < 0 ? '-' : '') + '$' + (Math.abs(cents) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/** Cumulative payout for each of `days` days at `share` cents a day. */
export function cumulative(share, days) {
    return Array.from({ length: days }, (_, i) => share * (i + 1));
}

/** First day on which the running total reaches the stake, or null. */
export function paybackDay(share, stake, days = 60) {
    const day = Math.ceil(stake / share);
    return day <= days ? day : null;
}

export default function yieldCalculator(tiers) {
    return {
        tiers,
        tier: 'commercial',
        days: 30,
        chart: null,
        get t() { return this.tiers[this.tier]; },
        get total() { return this.t.share * this.days; },
        get net() { return this.total - this.t.stake; },
        get payback() { return paybackDay(this.t.share, this.t.stake); },
        get paybackExact() { return (this.t.stake / this.t.share).toFixed(1); },
        money,
        repeat: null,
        get netLabel() { return this.net >= 0 ? 'Left after deposit' : 'Still to earn back'; },
        get netShown() { return money(Math.abs(this.net)); },
        get fill() { return ((this.days - 1) / 59) * 100; },
        step(d) { this.days = Math.min(60, Math.max(1, Number(this.days) + d)); },
        // Press and hold keeps counting, so 1 to 60 days does not take 59 taps.
        hold(d) {
            this.release();
            this.step(d);
            this.repeat = setTimeout(() => { this.repeat = setInterval(() => this.step(d), 90); }, 400);
        },
        release() { clearTimeout(this.repeat); clearInterval(this.repeat); this.repeat = null; },
        init() {
            this.$watch('tier', () => this.draw());
            this.$watch('days', () => this.draw());
            this.$nextTick(() => this.draw());
        },
        draw() {
            const canvas = this.$refs.curve;
            if (!canvas || !window.Chart) return;
            const days = Math.max(30, Number(this.days));
            const totals = cumulative(this.t.share, days);
            const labels = totals.map((_, i) => String(i + 1));
            const reached = totals.map((c) => (c >= this.t.stake ? colors.accent : colors[200]));
            const data = {
                labels,
                datasets: [
                    { type: 'bar', label: 'Cumulative payout', data: totals.map((c) => c / 100), backgroundColor: reached, borderWidth: 0, order: 2 },
                    { type: 'line', label: 'Your deposit', data: totals.map(() => this.t.stake / 100), borderColor: colors.black, borderDash: [6, 4], borderWidth: 1, pointRadius: 0, order: 1 },
                ],
            };
            if (this.chart) {
                this.chart.data = data;
                this.chart.update('none');
                return;
            }
            const mono = { family: 'Geist Mono, ui-monospace, Menlo, Consolas, monospace', size: 11 };
            this.chart = new window.Chart(canvas, {
                type: 'bar',
                data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    plugins: { legend: { display: false }, tooltip: { cornerRadius: 0, backgroundColor: colors.black, titleFont: mono, bodyFont: mono } },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: mono, color: colors[500], maxTicksLimit: 10 }, title: { display: true, text: 'Day', font: mono, color: colors[500] } },
                        y: { ticks: { font: mono, color: colors[500], callback: (v) => '$' + Number(v).toLocaleString('en-US') }, grid: { color: colors[200] } },
                    },
                },
            });
        },
    };
}

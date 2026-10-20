// Responsive check (SPEC 12.6). Requests every GET route that needs no parameter at eight widths and asserts:
//  - document.documentElement.scrollWidth <= window.innerWidth
//  - no element's right edge passes the viewport unless an ancestor scrolls horizontally
//  - below lg, every visible a and button is at least 44 by 44 px (inline text links are exempt)
//  - no console errors
// Usage: npm run build && npm run check:responsive
// Env: BASE_URL (default: starts php artisan serve on port 8765), CHECK_ROUTES (comma list, overrides discovery),
//      CHECK_LOGINS (JSON array of {email, password, label}; each login also checks every route as that user).
import { spawn, spawnSync } from 'node:child_process';
import { chromium } from '@playwright/test';

const WIDTHS = [320, 360, 390, 768, 1024, 1280, 1440, 1920];
const HEIGHT = 900;
const PORT = 8765;
const BASE = process.env.BASE_URL ?? `http://127.0.0.1:${PORT}`;

function discoverRoutes() {
    if (process.env.CHECK_ROUTES) return process.env.CHECK_ROUTES.split(',').map((r) => r.trim());
    const out = spawnSync('php', ['artisan', 'route:list', '--json'], { encoding: 'utf8', shell: process.platform === 'win32' });
    if (out.status !== 0) throw new Error(`route:list failed: ${out.stderr}`);
    const skip = [/^up$/, /^storage\//, /^_/, /^api\//, /^brand\//, /^favicon\.ico$/, /^site\.webmanifest$/, /^dev\/styleguide\/frames$/];
    const routes = JSON.parse(out.stdout)
        .filter((r) => r.method.split('|').includes('GET'))
        .map((r) => `/${r.uri.replace(/^\//, '')}`.replace(/^\/\/+/, '/'))
        .filter((uri) => !uri.includes('{') && !skip.some((s) => s.test(uri.slice(1))));
    return [...new Set(routes)].sort();
}

async function waitForServer() {
    for (let i = 0; i < 60; i++) {
        try {
            await fetch(`${BASE}/up`);
            return;
        } catch {
            await new Promise((r) => setTimeout(r, 500));
        }
    }
    throw new Error(`Server did not start at ${BASE}`);
}

// Runs inside the page.
function audit(isNarrow) {
    const vw = window.innerWidth;
    const issues = [];
    const describe = (el) => {
        let s = el.tagName.toLowerCase();
        if (el.id) s += `#${el.id}`;
        if (typeof el.className === 'string' && el.className.trim()) s += `.${el.className.trim().split(/\s+/).slice(0, 3).join('.')}`;
        return s;
    };
    if (document.documentElement.scrollWidth > vw) {
        issues.push(`page scrollWidth ${document.documentElement.scrollWidth} > ${vw}`);
    }
    const scrolls = (el) => {
        for (let p = el.parentElement; p; p = p.parentElement) {
            const ox = getComputedStyle(p).overflowX;
            if (ox === 'auto' || ox === 'scroll' || ox === 'hidden' || ox === 'clip') return true;
        }
        return false;
    };
    for (const el of document.body.querySelectorAll('*')) {
        const cs = getComputedStyle(el);
        if (cs.display === 'none' || cs.visibility === 'hidden') continue;
        const r = el.getBoundingClientRect();
        if (r.width === 0 && r.height === 0) continue;
        const clipped = r.width <= 1 && r.height <= 1; // sr-only
        if (!clipped && r.right > vw + 0.5 && !scrolls(el)) {
            issues.push(`overflow right ${Math.round(r.right)} > ${vw}: ${describe(el)}`);
        }
        if (isNarrow && !clipped && (el.tagName === 'A' || el.tagName === 'BUTTON') && cs.display !== 'inline') {
            if (r.width < 44 - 0.5 || r.height < 44 - 0.5) {
                issues.push(`tap target ${Math.round(r.width)}x${Math.round(r.height)}: ${describe(el)} "${(el.textContent || '').trim().slice(0, 24)}"`);
            }
        }
    }
    return issues;
}

async function checkAs(browser, label, storageState, routes) {
    let failures = 0;
    for (const width of WIDTHS) {
        const context = await browser.newContext({ viewport: { width, height: HEIGHT }, storageState });
        const page = await context.newPage();
        const consoleErrors = [];
        page.on('console', (m) => m.type() === 'error' && consoleErrors.push(m.text()));
        page.on('pageerror', (e) => consoleErrors.push(String(e)));
        for (const route of routes) {
            consoleErrors.length = 0;
            let response;
            try {
                response = await page.goto(`${BASE}${route}`, { waitUntil: 'load', timeout: 60000 });
            } catch (err) {
                failures++;
                console.log(`FAIL [${label}] ${route} @${width}: ${err.message}`);
                continue;
            }
            const status = response?.status() ?? 0;
            // A page that belongs to another role answers 403. That is the access rule working, not a layout fault.
            if (status === 403) continue;
            if (status >= 500) {
                console.log(`FAIL [${label}] ${route} @${width}: HTTP ${status}`);
                failures++;
                continue;
            }
            // Pages whose script starts after load (maps) must have finished starting, so a script error cannot be missed.
            if (await page.$('[data-route-tool]')) {
                await page.waitForSelector('.leaflet-container', { timeout: 10000 });
                await page.waitForTimeout(500);
            }
            const issues = await page.evaluate(audit, width < 1024);
            for (const e of consoleErrors) issues.push(`console error: ${e}`);
            if (issues.length) {
                failures++;
                console.log(`FAIL [${label}] ${route} @${width}: ${issues.length} issue(s)`);
                for (const issue of issues) console.log(`    - ${issue}`);
            }
        }
        await context.close();
    }
    return failures;
}

async function login(browser, { email, password }) {
    const context = await browser.newContext();
    const page = await context.newPage();
    await page.goto(`${BASE}/login`);
    await page.fill('input[name=email]', email);
    await page.fill('input[name=password]', password);
    await Promise.all([page.waitForURL((u) => !u.pathname.endsWith('/login')), page.click('button[type=submit]')]);
    const state = await context.storageState();
    await context.close();
    return state;
}

let server;
if (!process.env.BASE_URL) {
    server = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', `--port=${PORT}`], {
        stdio: 'ignore',
        shell: process.platform === 'win32',
        env: { ...process.env, PHP_CLI_SERVER_WORKERS: '4', RATE_LIMITS_OFF: 'true' },
    });
}

let failures = 0;
try {
    await waitForServer();
    const routes = discoverRoutes();
    console.log(`Checking ${routes.length} routes at ${WIDTHS.join(', ')} px`);
    const browser = await chromium.launch();
    failures += await checkAs(browser, 'guest', undefined, routes);
    for (const account of JSON.parse(process.env.CHECK_LOGINS ?? '[]')) {
        failures += await checkAs(browser, account.label ?? account.email, await login(browser, account), routes);
    }
    await browser.close();
} finally {
    if (server) {
        if (process.platform === 'win32') spawnSync('taskkill', ['/pid', String(server.pid), '/T', '/F']);
        else server.kill();
    }
}

console.log(failures ? `check:responsive: ${failures} failure(s)` : 'check:responsive: OK');
process.exit(failures ? 1 : 0);

// Design tokens. Single place for hex values (SPEC 12.2).
// The Tailwind theme imports this file, Chart.js and Leaflet read it at runtime.

export const colors = {
    black: '#000000',
    ink: '#111111',
    700: '#3A3A3A',
    500: '#6B6B6B',
    300: '#C9C9C9',
    200: '#E2E2E2',
    100: '#F2F2F2',
    white: '#FFFFFF',
    accent: '#3E6AE1',
    'accent-ink': '#2F55BF',
    ok: '#1F7A3D',
    warn: '#9A6700',
    danger: '#B3261E',
    'ok-tint': '#EDF4F0',
    'warn-tint': '#F7F3EB',
    'danger-tint': '#F9EEED',
    'dark': '#0B0B0B',
    dim: '#B5B5B5',
};

export const fonts = {
    sans: ['Geist Sans', 'Geist', 'ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'Arial', 'sans-serif'],
    mono: ['Geist Mono', 'ui-monospace', 'SF Mono', 'Menlo', 'Consolas', 'monospace'],
};

export const screens = { sm: '640px', lg: '1024px', xl: '1280px' };

export const shadows = { popover: '0 4px 16px rgba(0,0,0,0.12)' };

export default { colors, fonts, screens, shadows };

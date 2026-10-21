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
    accent: '#F7723A',
    'accent-ink': '#2F55BF',
    ok: '#1F7A3D',
    warn: '#9A6700',
    danger: '#B3261E',
    'ok-tint': '#EDF4F0',
    'warn-tint': '#F7F3EB',
    'danger-tint': '#F9EEED',
    'dark': '#0B0B0B',
    dim: '#B5B5B5',
    cream: '#F2F2F2',
    butter: '#FCECCF',
    peach: '#FFE9E2',
    mint: '#E4F2DF',
    lilac: '#F2EAFF',
};

export const fonts = {
    sans: ['Geist Sans', 'Geist', 'ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'Arial', 'sans-serif'],
    mono: ['Geist Mono', 'ui-monospace', 'SF Mono', 'Menlo', 'Consolas', 'monospace'],
};

export const screens = { sm: '640px', lg: '1024px', xl: '1280px' };

export const shadows = { popover: '0 4px 16px rgba(0,0,0,0.12)' };

export default { colors, fonts, screens, shadows };

// Colors of the animated gradient behind the public home page, one set per section mood.
export const mesh = {
    coral: ['#F7723A', '#F8805E', '#FCB791', '#F6613B', '#F8A06E'],
    mint: ['#86D8BC', '#92D8CF', '#ACE5C6', '#9ADBD0', '#B5E8C8'],
    gray: ['#F5F5F5', '#F3F3F3', '#F7F7F7', '#F2F2F2', '#F5F5F5'],
    amber: ['#F6AF4E', '#F3BF6B', '#F1D49D', '#F0AF56', '#EFD49A'],
    cream: ['#F4EEDE', '#F2EFE7', '#F8EFDF', '#F1EDE1', '#F4F4F4'],
    white: ['#FFFFFF', '#F7F7F7', '#FFFFFF', '#F4F4F4', '#FFFFFF'],
};

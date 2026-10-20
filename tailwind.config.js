import plugin from 'tailwindcss/plugin';
import { colors, fonts, screens, shadows } from './resources/js/tokens.js';

// Spacing units 1, 2, 3, 4, 6, 8, 12, 16, 24 only (4, 8, 12, 16, 24, 32, 48, 64, 96 px).
const spacing = {
    0: '0px',
    px: '1px',
    1: '0.25rem',
    2: '0.5rem',
    3: '0.75rem',
    4: '1rem',
    6: '1.5rem',
    8: '2rem',
    12: '3rem',
    16: '4rem',
    24: '6rem',
};

// Control and shell dimensions that are not spacing: 40/44 px controls, 56 px top bar, 208 px rail.
const control = { 10: '2.5rem', 11: '2.75rem', 14: '3.5rem', 52: '13rem' };

// Size and line height come from CSS variables set by .surface-public and .surface-portal.
const type = (size, extra = {}) => [`var(--fs-${size})`, { lineHeight: `var(--lh-${size})`, ...extra }];

/** @type {import('tailwindcss').Config} */
export default {
    content: ['./resources/views/**/*.blade.php', './resources/js/**/*.js'],

    theme: {
        screens,
        colors: { transparent: 'transparent', current: 'currentColor', af: colors },
        spacing,
        fontFamily: fonts,
        fontSize: {
            display: type('display', { letterSpacing: '-0.02em', fontWeight: '600' }),
            h1: type('h1', { letterSpacing: '-0.015em', fontWeight: '600' }),
            h2: type('h2', { letterSpacing: '-0.01em', fontWeight: '600' }),
            h3: type('h3', { fontWeight: '600' }),
            body: type('body', { fontWeight: '400' }),
            label: type('label', { fontWeight: '500' }),
        },
        letterSpacing: { tight: '-0.02em', normal: '0em', label: '0.06em' },
        borderRadius: { pill: '9999px', input: '8px', panel: '4px' },
        boxShadow: { popover: shadows.popover },
        extend: {
            width: control,
            height: control,
            minWidth: { 11: control[11], 12: spacing[12] },
            minHeight: { 10: control[10], 11: control[11], 12: spacing[12], 14: control[14] },
            maxWidth: { public: '70rem', portal: '90rem', page: '48rem' },
            aspectRatio: { '4/3': '4 / 3' },
            borderColor: { DEFAULT: colors[200] },
        },
    },

    plugins: [
        plugin(({ addVariant }) => {
            // Surface variants: public:rounded-pill resolves inside .surface-public only.
            addVariant('public', '.surface-public &');
            addVariant('portal', '.surface-portal &');
        }),
    ],
};


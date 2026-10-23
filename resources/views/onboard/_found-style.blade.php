{{-- The moment a co-partner is found: the cards rise in one after another, and the detail rows fill in line by line. --}}
<style>
    @keyframes dw-rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    .dw-in { animation: dw-rise 0.6s cubic-bezier(.2, .8, .2, 1) both; animation-delay: calc(var(--d, 0) * 0.35s); }
    .dw-rows .cc-kv { animation: dw-rise 0.45s ease-out both; animation-delay: calc(var(--d, 0) * 0.35s + 0.3s); }
    .dw-rows .cc-kv:nth-child(2) { animation-delay: calc(var(--d, 0) * 0.35s + 0.42s); }
    .dw-rows .cc-kv:nth-child(3) { animation-delay: calc(var(--d, 0) * 0.35s + 0.54s); }
    .dw-rows .cc-kv:nth-child(4) { animation-delay: calc(var(--d, 0) * 0.35s + 0.66s); }
    .dw-rows .cc-kv:nth-child(5) { animation-delay: calc(var(--d, 0) * 0.35s + 0.78s); }
    .dw-rows .cc-kv:nth-child(n+6) { animation-delay: calc(var(--d, 0) * 0.35s + 0.9s); }
    /* The deposit section that follows the machine card: rises in third, its rows fill in after. */
    .dw-rows + .cc-grid { --d: 2; animation: dw-rise 0.6s cubic-bezier(.2, .8, .2, 1) both; animation-delay: 0.7s; }
    .dw-rows + .cc-grid .cc-kv { animation: dw-rise 0.45s ease-out both; animation-delay: 1s; }
    .dw-rows + .cc-grid .cc-kv:nth-child(n+3) { animation-delay: 1.15s; }
    .dw-rows + .cc-grid .cc-kv:nth-child(n+5) { animation-delay: 1.3s; }
    .dw-found-who { display: flex; align-items: center; gap: 16px; margin-bottom: 12px; }
    .dw-found-who .cc-avatar { width: 64px; height: 64px; font-size: 22px; }
    .dw-found-name { margin: 2px 0 0; font-size: clamp(22px, 2vw + 14px, 30px); font-weight: 800; line-height: 1.15; overflow-wrap: anywhere; }
    @media (prefers-reduced-motion: reduce) { .dw-in, .dw-rows .cc-kv, .dw-rows + .cc-grid, .dw-rows + .cc-grid .cc-kv { animation: none; } }
</style>

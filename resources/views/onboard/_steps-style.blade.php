{{-- The three payment screens (deposit style 2). Kept with the views, so a change needs no rebuilt stylesheet. --}}
<style>
    .dw-steps-page { display: grid; gap: 16px; width: 100%; max-width: 640px; margin: 0 auto; }
    .dw-dots { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin: 0; padding: 0; list-style: none; }
    .dw-dots li { display: grid; gap: 6px; min-width: 0; font-size: 13px; font-weight: 700; color: rgb(89, 89, 89); line-height: 1.25; }
    .dw-dots i { display: block; height: 6px; border-radius: 9999px; background: rgba(0, 0, 0, 0.14); }
    .dw-dots li.is-done i, .dw-dots li.is-now i { background: black; }
    .dw-dots li.is-now { color: black; }
    @media (max-width: 479px) { .dw-dots li:not(.is-now) span { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); } }

    .dw-card { display: grid; gap: 18px; margin: 0 !important; }
    .dw-eyebrow { margin: 0; }
    .dw-title { margin: -8px 0 0; font-size: clamp(28px, 4vw + 14px, 40px); font-weight: 800; line-height: 1.1; letter-spacing: -0.02em; }
    .dw-lead { margin: -4px 0 0; font-size: 18px; line-height: 1.5; }
    .dw-tick { display: grid; place-items: center; width: 68px; height: 68px; border-radius: 50%; background: black; color: white; animation: dw-pop 0.5s cubic-bezier(.2, 1.5, .4, 1) both; }
    @keyframes dw-pop { from { transform: scale(0.3); } to { transform: none; } }

    .dw-go { display: flex; align-items: center; justify-content: center; width: 100%; min-height: 58px; font-size: 18px; text-align: center; text-decoration: none; }
    .dw-go:disabled { background: rgb(176, 176, 176); border-color: rgb(176, 176, 176); color: white; cursor: not-allowed; transform: none; }
    .dw-back { justify-self: center; padding: 8px; font-size: 16px; font-weight: 700; color: black; text-decoration: underline; text-underline-offset: 4px; }

    .dw-partner { display: grid; gap: 4px; padding: 18px 20px 6px; border-radius: 22px; background: rgb(242, 242, 242); }
    .dw-partner-who { display: flex; align-items: center; gap: 14px; padding-bottom: 8px; }
    .dw-partner-who .cc-avatar { width: 58px; height: 58px; font-size: 20px; }
    .dw-partner-label { margin: 0; font-size: 13px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: rgb(89, 89, 89); }
    .dw-partner-name { margin: 2px 0 0; font-size: clamp(22px, 2vw + 16px, 28px); font-weight: 800; line-height: 1.15; overflow-wrap: anywhere; }
    .dw-partner .cc-kv { border-top-color: rgba(0, 0, 0, 0.1); }
    .dw-partner .cc-kv dd a { color: black; text-decoration: underline; text-underline-offset: 3px; overflow-wrap: anywhere; }
    .dw-email-row .dw-email-copy { flex-basis: 100%; text-align: left; }

    .dw-copy { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 0 18px; border: 2px solid black; border-radius: 9999px; background: white; color: black; font: inherit; font-size: 16px; font-weight: 700; cursor: pointer; white-space: nowrap; }

    .dw-instruction { padding: 20px 22px; border-radius: 22px; background: rgb(253, 241, 217); font-size: 18px; line-height: 1.6; overflow-wrap: anywhere; }
    .dw-instruction p { margin: 0 0 14px; }
    .dw-instruction ul { margin: 0 0 14px; padding-left: 26px; list-style: disc; }
    .dw-instruction li { margin: 0 0 8px; padding-left: 4px; }
    .dw-instruction li::marker { font-size: 1.1em; }
    .dw-instruction > :last-child { margin-bottom: 0; }
    .dw-read { padding: 16px 18px; border: 2px solid black; border-radius: 18px; background: white; font-size: 19px; font-weight: 800; }
    .dw-read input { width: 26px; height: 26px; }

    .dw-msg { display: grid; gap: 8px; }
    .dw-msg-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; }
    .dw-msg-head h2 { margin: 0; font-size: 18px; }
    .dw-subject, .dw-body { margin: 0; padding: 14px 16px; border-radius: 16px; background: rgb(242, 242, 242); font-size: 17px; line-height: 1.55; overflow-wrap: anywhere; }
    .dw-subject { font-weight: 700; }
    .dw-body { white-space: pre-wrap; }
    .dw-nb { white-space: nowrap; }

    @media (prefers-reduced-motion: reduce) { .dw-tick { animation: none; } }
</style>

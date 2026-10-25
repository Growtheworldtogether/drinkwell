{{-- Shown while an update installs (Admin, Settings, System). Self-contained on purpose: no layout, stylesheet or
     database, so it still shows while the site's own files are being replaced. Refreshes itself. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="20">
    <meta name="robots" content="noindex">
    <title>Back in a minute · {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px 16px; background: rgb(255, 233, 226); color: black; font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Arial, sans-serif; }
        main { width: 100%; max-width: 560px; padding: clamp(28px, 6vw, 48px); border-radius: 32px; background: white; text-align: center; }
        .brand { margin: 0 0 28px; font-size: 22px; font-weight: 800; letter-spacing: -0.02em; }
        .spin { width: 56px; height: 56px; margin: 0 auto 24px; border-radius: 50%; border: 5px solid rgb(230, 230, 230); border-top-color: black; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        h1 { margin: 0 0 12px; font-size: clamp(30px, 5vw, 40px); line-height: 1.1; letter-spacing: -0.02em; }
        p { margin: 0; font-size: 19px; line-height: 1.55; }
        @media (prefers-reduced-motion: reduce) { .spin { animation: none; } }
    </style>
</head>
<body>
    <main>
        <p class="brand">{{ config('app.name') }}</p>
        <div class="spin" aria-hidden="true"></div>
        <h1>Back in a minute</h1>
        <p>We are updating the site. Your account and money are safe. This page reloads by itself.</p>
    </main>
</body>
</html>

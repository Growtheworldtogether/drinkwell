{{-- The update's progress page: runs one short step per load, then loads itself again. Self-contained (no layout or
     stylesheet), because the site's own files are being replaced while it shows. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="1;url={{ $next }}">
    <meta name="robots" content="noindex">
    <title>Updating {{ $percent }}% · {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px 16px; background: rgb(233, 247, 241); color: black; font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Arial, sans-serif; }
        main { width: 100%; max-width: 560px; padding: clamp(28px, 6vw, 44px); border-radius: 32px; background: white; }
        h1 { margin: 0 0 8px; font-size: clamp(28px, 5vw, 36px); line-height: 1.1; }
        p { margin: 0; font-size: 18px; line-height: 1.5; }
        .bar { height: 14px; margin: 24px 0 12px; border-radius: 9999px; background: rgb(230, 230, 230); overflow: hidden; }
        .bar i { display: block; height: 100%; width: {{ $percent }}%; border-radius: inherit; background: black; transition: width 0.6s ease; }
        .num { font-size: 15px; font-weight: 700; color: rgb(89, 89, 89); }
        a { color: black; font-weight: 700; }
    </style>
</head>
<body>
    <main>
        <h1>Updating your site</h1>
        <p>{{ $label }}. Please keep this page open; it carries on by itself.</p>
        <div class="bar" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"><i></i></div>
        <p class="num">{{ $percent }}%</p>
        <noscript><p style="margin-top:16px"><a href="{{ $next }}">Continue</a></p></noscript>
    </main>
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">setTimeout(function () { window.location.replace(@json($next)); }, 400);</script>
</body>
</html>

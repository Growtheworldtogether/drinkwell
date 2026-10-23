<?php
/**
 * One-time installer for hosts with no terminal (cPanel).
 *
 * Open https://yourdomain.com/install.php after uploading the files. It checks the server, asks for the site,
 * database, email and admin details, writes .env, creates the tables, the machines and the admin, and then locks
 * itself. Updates later are done from Admin > System, not here.
 */

declare(strict_types=1);

set_time_limit(300);
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'");

// ---------------------------------------------------------------------------------------------------------------
// Find the app: this file sits in public/, or public's files were copied into public_html next to a drinkwell folder.
$base = null;
foreach ([__DIR__.'/..', __DIR__.'/../drinkwell', __DIR__] as $candidate) {
    if (is_file($candidate.'/artisan') && is_file($candidate.'/bootstrap/app.php')) {
        $base = realpath($candidate);
        break;
    }
}

$lock = $base ? $base.'/storage/app/installed.lock' : null;

function e(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function page(string $title, string $body, int $step = 0): never
{
    $steps = ['Check', 'Key', 'Site', 'Database', 'Email', 'Admin', 'Install'];
    $nav = '';
    if ($step > 0) {
        $nav = '<ol class="steps">';
        foreach ($steps as $i => $s) {
            $n = $i + 1;
            $nav .= '<li class="'.($n < $step ? 'done' : ($n === $step ? 'now' : '')).'"><span>'.$n.'</span>'.e($s).'</li>';
        }
        $nav .= '</ol>';
    }
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        .'<meta name="robots" content="noindex"><title>'.e($title).' · Install</title><style>'
        .':root{--ink:#111;--muted:#555;--line:#e3e3e3;--coral:#f7723a;--mint:#5fc29f;--bad:#b3261e;--paper:#fff;--bg:#f4efe6}'
        .'*{box-sizing:border-box}body{margin:0;min-height:100vh;font:18px/1.55 system-ui,-apple-system,"Segoe UI",Arial,sans-serif;color:var(--ink);background:linear-gradient(160deg,#fcd9c4,#f4efe6 45%,#d9f1e6)}'
        .'main{max-width:760px;margin:0 auto;padding:32px 16px 64px}h1{font-size:clamp(32px,6vw,48px);line-height:1.05;letter-spacing:-.03em;margin:0 0 8px}'
        .'h2{font-size:22px;margin:28px 0 8px}.card{background:var(--paper);border-radius:28px;padding:clamp(20px,4vw,40px);margin-top:20px;box-shadow:0 10px 40px rgba(0,0,0,.08)}'
        .'p{margin:0 0 14px}.muted{color:var(--muted);font-size:16px}label{display:block;font-weight:700;margin:16px 0 6px}'
        .'input[type=text],input[type=email],input[type=password],input[type=url],input[type=number],select{width:100%;min-height:54px;padding:12px 16px;border:2px solid var(--ink);border-radius:16px;font:inherit;background:#fff}'
        .'.two{display:grid;gap:0 16px}@media(min-width:640px){.two{grid-template-columns:1fr 1fr}}'
        .'.btn{display:inline-flex;align-items:center;justify-content:center;min-height:54px;padding:0 28px;border:0;border-radius:999px;background:var(--ink);color:#fff;font:inherit;font-weight:700;cursor:pointer;text-decoration:none;margin-top:24px}'
        .'.btn.ghost{background:transparent;color:var(--ink);border:2px solid var(--ink);margin-left:8px}.check{display:flex;gap:12px;align-items:flex-start;font-weight:600;margin:14px 0}'
        .'.check input{width:24px;height:24px;margin-top:3px;accent-color:#000}.list{list-style:none;padding:0;margin:0}.list li{display:flex;justify-content:space-between;gap:12px;padding:12px 0;border-top:1px solid var(--line)}'
        .'.ok{color:#1f7a3d;font-weight:700}.no{color:var(--bad);font-weight:700}.warn{color:#9a6700;font-weight:700}'
        .'.err{background:#f9eeed;border:2px solid var(--bad);border-radius:16px;padding:14px 18px;margin:16px 0;color:var(--bad);font-weight:600}'
        .'.note{background:#edf4f0;border-radius:16px;padding:14px 18px;margin:16px 0}code,pre{font:15px/1.5 ui-monospace,Menlo,Consolas,monospace;overflow-wrap:anywhere}'
        .'pre{white-space:pre-wrap;background:#f4f4f4;border-radius:12px;padding:12px 14px}'
        .'.steps{display:flex;flex-wrap:wrap;gap:8px;list-style:none;padding:0;margin:16px 0 0}.steps li{display:flex;align-items:center;gap:8px;padding:6px 14px 6px 6px;border-radius:999px;background:rgba(255,255,255,.6);font-size:15px;font-weight:600;color:#555}'
        .'.steps span{display:grid;place-items:center;width:28px;height:28px;border-radius:999px;background:rgba(0,0,0,.12);font-weight:700}.steps .now{background:#fff;color:#000}.steps .now span,.steps .done span{background:#000;color:#fff}'
        .'</style></head><body><main><h1>'.e($title).'</h1>'.$nav.$body.'</main></body></html>';
    exit;
}

if ($base === null) {
    http_response_code(500);
    page('Files not found', '<div class="card"><p>The installer cannot find the app next to it. Upload and extract the whole release zip, and make sure <code>artisan</code> and the <code>bootstrap</code> folder are one level above this file.</p></div>');
}

if (is_file($lock)) {
    http_response_code(404);
    page('Already installed', '<div class="card"><p>This site is already set up, so the installer is switched off.</p><p>To install an update, upload the new files, then open <strong>Admin, then System</strong> and press <strong>Install updates</strong>.</p><p class="muted">You can delete <code>install.php</code> from the public folder.</p></div>');
}

session_name('dw_install');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'cookie_secure' => (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
]);
$s = &$_SESSION;
$s['data'] ??= [];
$d = &$s['data'];
$s['csrf'] ??= bin2hex(random_bytes(16));

function back(string $to): never
{
    header('Location: install.php?step='.rawurlencode($to));
    exit;
}

function field(string $name, string $label, string $value = '', string $type = 'text', string $help = '', array $attrs = []): string
{
    $extra = '';
    foreach ($attrs as $k => $v) {
        $extra .= ' '.e($k).'="'.e((string) $v).'"';
    }

    return '<label for="f-'.e($name).'">'.e($label).'</label><input id="f-'.e($name).'" name="'.e($name).'" type="'.e($type).'" value="'.e($value).'"'.$extra.'>'
        .($help !== '' ? '<p class="muted" style="margin-top:6px">'.$help.'</p>' : '');
}

function form(string $step, string $inner, string $submit = 'Continue', ?string $backTo = null): string
{
    return '<form method="post" action="install.php?step='.e($step).'" class="card"><input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">'
        .$inner.'<div><button class="btn" type="submit">'.e($submit).'</button>'
        .($backTo ? '<a class="btn ghost" href="install.php?step='.e($backTo).'">Back</a>' : '').'</div></form>';
}

$post = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($post && ! hash_equals($s['csrf'], (string) ($_POST['csrf'] ?? ''))) {
    page('Please try again', '<div class="card"><p>The form expired. <a href="install.php">Start again</a>.</p></div>');
}
$in = fn (string $k): string => trim((string) ($_POST[$k] ?? ''));
$step = (string) ($_GET['step'] ?? 'check');
$error = '';

// ---------------------------------------------------------------------------------------------------------------
// 1. Server check
if ($step === 'check') {
    $rows = [];
    $fatal = false;
    $v = PHP_VERSION;
    $patched = ['8.2' => '8.2.35', '8.3' => '8.3.36', '8.4' => '8.4.27', '8.5' => '8.5.12'];
    $branch = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
    $phpOk = version_compare($v, '8.3.0', '>=');
    $fatal = $fatal || ! $phpOk;
    $rows[] = ['PHP version 8.3 or newer', $v, $phpOk ? 'ok' : 'no'];
    $safe = isset($patched[$branch]) ? version_compare($v, $patched[$branch], '>=') : version_compare($v, '8.6.0', '>=');
    $rows[] = ['PHP has the October 2026 security fix (PHP2Shell)', $safe ? 'Yes' : 'No, update PHP or ask your host', $safe ? 'ok' : 'warn'];
    foreach (['pdo_mysql', 'mbstring', 'openssl', 'fileinfo', 'tokenizer', 'dom', 'ctype', 'curl', 'gd', 'json'] as $ext) {
        $has = extension_loaded($ext);
        $fatal = $fatal || ! $has;
        $rows[] = ['PHP extension '.$ext, $has ? 'On' : 'Missing: tick it in Select PHP Version', $has ? 'ok' : 'no'];
    }
    $rows[] = ['PHP extension intl (optional)', extension_loaded('intl') ? 'On' : 'Off', extension_loaded('intl') ? 'ok' : 'warn'];
    $vendor = is_file($base.'/vendor/autoload.php');
    $build = is_file($base.'/public/build/manifest.json') || is_file(__DIR__.'/build/manifest.json');
    $fatal = $fatal || ! $vendor || ! $build;
    $rows[] = ['App code (vendor folder)', $vendor ? 'Found' : 'Missing: upload the release zip made by make-release', $vendor ? 'ok' : 'no'];
    $rows[] = ['Site files (public/build)', $build ? 'Found' : 'Missing: upload the release zip made by make-release', $build ? 'ok' : 'no'];
    foreach (['storage/app', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $dir) {
        @mkdir($base.'/'.$dir, 0775, true);
    }
    foreach (['' => 'App folder (to write .env)', 'storage' => 'storage folder', 'bootstrap/cache' => 'bootstrap/cache folder'] as $dir => $label) {
        $w = is_writable($base.($dir !== '' ? '/'.$dir : ''));
        $fatal = $fatal || ! $w;
        $rows[] = ['Can write to the '.$label, $w ? 'Yes' : 'No: set permissions to 755 in File Manager', $w ? 'ok' : 'no'];
    }
    $https = (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $rows[] = ['Secure connection (https)', $https ? 'Yes' : 'No: run AutoSSL in SSL/TLS Status first', $https ? 'ok' : 'warn'];

    $list = '<ul class="list">';
    foreach ($rows as [$label, $value, $tone]) {
        $list .= '<li><span>'.e($label).'</span><span class="'.$tone.'">'.e($value).'</span></li>';
    }
    $list .= '</ul>';
    $next = $fatal
        ? '<div class="err">Fix the items in red, then reload this page.</div><a class="btn" href="install.php">Check again</a>'
        : '<a class="btn" href="install.php?step=key">Continue</a>';
    page('Set up your site', '<div class="card"><p>First, a quick check that this server has everything the site needs.</p>'.$list.$next.'</div>', 1);
}

// 2. Prove it is the owner: a code written to a file only the cPanel account can open.
$keyFile = $base.'/storage/app/install-key.txt';
if (! is_file($keyFile)) {
    @mkdir(dirname($keyFile), 0775, true);
    file_put_contents($keyFile, strtoupper(bin2hex(random_bytes(4))).PHP_EOL);
}
$installKey = trim((string) file_get_contents($keyFile));

if ($step !== 'key' && empty($s['owner'])) {
    back('key');
}

if ($step === 'key') {
    if ($post) {
        if (hash_equals($installKey, strtoupper($in('key')))) {
            $s['owner'] = true;
            session_regenerate_id(true);
            back('site');
        }
        $error = 'That code does not match. Copy it again from the file.';
    }
    $rel = str_replace($base, basename($base), $keyFile);
    page('Prove it is you', ($error ? '<div class="err">'.e($error).'</div>' : '').form('key',
        '<p>So nobody else can set up your site, open <strong>cPanel, then File Manager</strong>, go to <code>'.e($rel).'</code>, open it and copy the 8-character code inside.</p>'
        .field('key', 'Code from install-key.txt', '', 'text', '', ['autocomplete' => 'off', 'maxlength' => 16, 'required' => 'required'])), 2);
}

// 3. Site
if ($step === 'site') {
    $scheme = (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http';
    $guess = $scheme.'://'.($_SERVER['HTTP_HOST'] ?? 'yourdomain.com');
    if ($post) {
        $url = rtrim($in('url'), '/');
        if ($in('name') === '' || mb_strlen($in('name')) > 40) {
            $error = 'Enter a site name of up to 40 characters.';
        } elseif (! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#', $url)) {
            $error = 'Enter the full web address, starting with https://';
        } elseif (! filter_var($in('support'), FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid support email address.';
        } else {
            $d['name'] = $in('name');
            $d['url'] = $url;
            $d['support'] = $in('support');
            $d['tz'] = in_array($in('tz'), timezone_identifiers_list(), true) ? $in('tz') : 'America/New_York';
            back('db');
        }
    }
    $tz = $d['tz'] ?? 'America/New_York';
    $zones = '';
    foreach (['America/New_York' => 'Eastern', 'America/Chicago' => 'Central', 'America/Denver' => 'Mountain', 'America/Los_Angeles' => 'Pacific', 'America/Toronto' => 'Toronto', 'America/Vancouver' => 'Vancouver'] as $z => $label) {
        $zones .= '<option value="'.e($z).'"'.($tz === $z ? ' selected' : '').'>'.e($label.' ('.$z.')').'</option>';
    }
    page('Your site', ($error ? '<div class="err">'.e($error).'</div>' : '').form('site',
        field('name', 'Site name', $post ? $in('name') : ($d['name'] ?? 'DRinkWeLL'), 'text', 'Shown everywhere on the site. You can change it later in Admin, Settings, Branding.', ['required' => 'required', 'maxlength' => 40])
        .field('url', 'Web address', $post ? $in('url') : ($d['url'] ?? $guess), 'url', 'Exactly as people type it, starting with https://', ['required' => 'required'])
        .field('support', 'Support email', $post ? $in('support') : ($d['support'] ?? ''), 'email', 'Members write to this address. Shown in the footer and on the Contact page.', ['required' => 'required'])
        .'<label for="f-tz">Time zone for the machines</label><select id="f-tz" name="tz">'.$zones.'</select>',
        'Continue', 'key'), 3);
}

// 4. Database
if ($step === 'db') {
    if ($post) {
        $db = ['host' => $in('host') ?: 'localhost', 'port' => $in('port') ?: '3306', 'name' => $in('dbname'), 'user' => $in('user'), 'pass' => (string) ($_POST['pass'] ?? '')];
        try {
            $pdo = new PDO('mysql:host='.$db['host'].';port='.$db['port'].';dbname='.$db['name'].';charset=utf8mb4', $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]);
            $version = (string) $pdo->query('select version()')->fetchColumn();
            $d['db'] = $db;
            $s['db_version'] = $version;
            back('mail');
        } catch (Throwable $ex) {
            $d['db'] = ['pass' => ''] + $db; // keep what was typed, except the password
            $msg = $ex->getMessage();
            $error = str_contains($msg, 'Access denied') ? 'The username or password is wrong, or the user is not added to this database (MySQL Databases, Add User To Database, ALL PRIVILEGES).'
                : (str_contains($msg, 'Unknown database') ? 'There is no database with that name. In cPanel the name starts with your cPanel username, like cpuser_drinkwell.'
                : 'Could not connect: '.$msg);
        }
    }
    $db = $d['db'] ?? ['host' => 'localhost', 'port' => '3306', 'name' => '', 'user' => '', 'pass' => ''];
    page('Database', ($error ? '<div class="err">'.e($error).'</div>' : '').form('db',
        '<p>In <strong>cPanel, then MySQL Databases</strong>: create a database and a user, then add the user to the database with <strong>All privileges</strong>. Enter those details here.</p>'
        .'<div class="two">'.field('dbname', 'Database name', $db['name'], 'text', 'For example cpuser_drinkwell', ['required' => 'required'])
        .field('user', 'Database user', $db['user'], 'text', 'For example cpuser_dwuser', ['required' => 'required']).'</div>'
        .field('pass', 'Database password', '', 'password', '', ['autocomplete' => 'new-password'])
        .'<div class="two">'.field('host', 'Host', $db['host'], 'text', 'Almost always localhost.').field('port', 'Port', $db['port'], 'number').'</div>',
        'Test and continue', 'site'), 4);
}

// 5. Email
if ($step === 'mail') {
    if ($post) {
        if ($in('mode') === 'later') {
            $d['mail'] = ['mode' => 'later'];
            back('admin');
        }
        $m = ['mode' => 'smtp', 'host' => $in('mhost'), 'port' => $in('mport') ?: '465', 'scheme' => $in('mport') === '587' ? 'smtp' : 'smtps', 'user' => $in('muser'), 'pass' => (string) ($_POST['mpass'] ?? ''), 'from' => $in('mfrom') ?: $in('muser')];
        if ($m['host'] === '' || $m['user'] === '') {
            $error = 'Fill in the mail server and the user name, or choose Set up email later.';
        } elseif (! filter_var($m['from'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Your user name is not an email address, so fill in "Send from" with the email address the messages come from.';
        } else {
            $errno = 0;
            $errstr = '';
            $sock = @fsockopen(($m['scheme'] === 'smtps' ? 'ssl://' : '').$m['host'], (int) $m['port'], $errno, $errstr, 8);
            if (! $sock) {
                $error = 'Could not reach the mail server at '.$m['host'].':'.$m['port'].' ('.$errstr.'). Check the server name and port.';
            } else {
                fclose($sock);
                $d['mail'] = $m;
                back('admin');
            }
        }
    }
    $m = $post ? ['mode' => $in('mode'), 'host' => $in('mhost'), 'port' => $in('mport'), 'user' => $in('muser'), 'from' => $in('mfrom')] : ($d['mail'] ?? []);
    $host = parse_url($d['url'] ?? '', PHP_URL_HOST) ?: 'yourdomain.com';
    page('Email', ($error ? '<div class="err">'.e($error).'</div>' : '').form('mail',
        '<p>The site emails sign-up codes and pairing details. In <strong>cPanel, then Email Accounts</strong>, create an address like <code>no-reply@'.e($host).'</code>, then enter it here.</p>'
        .'<label class="check"><input type="radio" name="mode" value="smtp" '.(($m['mode'] ?? 'smtp') === 'smtp' ? 'checked' : '').'><span>Send real email (recommended)</span></label>'
        .'<div class="two">'.field('mhost', 'Mail server', $m['host'] ?? 'mail.'.$host).field('mport', 'Port', $m['port'] ?? '465', 'number', '465 (most cPanel hosts) or 587.').'</div>'
        .'<div class="two">'.field('muser', 'User name', $m['user'] ?? 'no-reply@'.$host, 'text', 'On cPanel this is usually the full email address. A plain user name works too.', ['autocomplete' => 'off', 'autocapitalize' => 'off', 'spellcheck' => 'false']).field('mpass', 'Email password', '', 'password', '', ['autocomplete' => 'new-password']).'</div>'
        .field('mfrom', 'Send from (email address)', $m['from'] ?? '', 'email', 'The address members see. Leave empty if the user name above is that email address.')
        .'<label class="check"><input type="radio" name="mode" value="later" '.(($m['mode'] ?? '') === 'later' ? 'checked' : '').'><span>Set up email later. Codes are then written to the log only, so nobody can sign up until email works.</span></label>',
        'Continue', 'db'), 5);
}

// 6. Admin and options
if ($step === 'admin') {
    if ($post) {
        $pw = (string) ($_POST['apass'] ?? '');
        if ($in('aname') === '' || ! filter_var($in('aemail'), FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter your name and a valid email address.';
        } elseif (strlen($pw) < 12 || ! preg_match('/[a-z]/', $pw) || ! preg_match('/[A-Z]/', $pw) || ! preg_match('/\d/', $pw)) {
            $error = 'The admin password needs at least 12 characters, with upper and lower case letters and a number.';
        } elseif ($pw !== (string) ($_POST['apass2'] ?? '')) {
            $error = 'The two passwords do not match.';
        } else {
            $d['admin'] = ['name' => $in('aname'), 'email' => strtolower($in('aemail')), 'pass' => $pw];
            $d['demo'] = isset($_POST['demo']);
            $d['demo_pass'] = (string) ($_POST['dpass'] ?? '');
            $d['codes'] = isset($_POST['codes']);
            if ($d['demo'] && $d['demo_pass'] !== '' && strlen($d['demo_pass']) < 12) {
                $error = 'The demo member password needs at least 12 characters, or leave it empty.';
            } else {
                back('install');
            }
        }
    }
    $a = $post ? ['name' => $in('aname'), 'email' => $in('aemail')] : ($d['admin'] ?? ['name' => '', 'email' => '']);
    page('Your admin account', ($error ? '<div class="err">'.e($error).'</div>' : '').form('admin',
        '<div class="two">'.field('aname', 'Your name', $a['name']).field('aemail', 'Admin email', $a['email'], 'email').'</div>'
        .'<div class="two">'.field('apass', 'Admin password', '', 'password', 'At least 12 characters, with upper and lower case and a number.', ['autocomplete' => 'new-password'])
        .field('apass2', 'Admin password again', '', 'password', '', ['autocomplete' => 'new-password']).'</div>'
        .'<h2>Options</h2>'
        .'<label class="check"><input type="checkbox" name="demo" '.(($d['demo'] ?? true) ? 'checked' : '').'><span>Add the demo member, Arthur Pendelton, with 20 days of payouts, so the dashboard has something to show.</span></label>'
        .field('dpass', 'Password for the demo member (optional)', '', 'password', 'Sign in as arthur.pendelton@example.test with this. Leave empty to set it later.', ['autocomplete' => 'new-password'])
        .'<label class="check"><input type="checkbox" name="codes" '.(! empty($d['codes']) ? 'checked' : '').'><span>Testing only: show one-time codes on the screen. Leave this off for real members.</span></label>',
        'Continue', 'mail'), 6);
}

// 7. Install
if ($step === 'install') {
    foreach (['name', 'url', 'db', 'mail', 'admin'] as $need) {
        if (empty($d[$need])) {
            back($need === 'name' || $need === 'url' ? 'site' : ($need === 'db' ? 'db' : $need));
        }
    }

    if (! $post) {
        $mailText = ($d['mail']['mode'] ?? '') === 'later' ? 'Later (log only)' : $d['mail']['user'].' via '.$d['mail']['host'];
        $summary = '<ul class="list">'
            .'<li><span>Site name</span><strong>'.e($d['name']).'</strong></li>'
            .'<li><span>Web address</span><strong>'.e($d['url']).'</strong></li>'
            .'<li><span>Support email</span><strong>'.e($d['support']).'</strong></li>'
            .'<li><span>Database</span><strong>'.e($d['db']['name']).'</strong></li>'
            .'<li><span>Email</span><strong>'.e($mailText).'</strong></li>'
            .'<li><span>Admin</span><strong>'.e($d['admin']['email']).'</strong></li>'
            .'<li><span>Demo member</span><strong>'.($d['demo'] ? 'Yes' : 'No').'</strong></li></ul>';
        page('Ready to install', form('install', '<p>Check your answers. Installing takes up to a minute. Do not close the page.</p>'.$summary, 'Install now', 'admin'), 7);
    }

    $log = [];
    try {
        // .env from the production template
        $template = is_file($base.'/.env.production.example') ? file_get_contents($base.'/.env.production.example') : '';
        $env = (string) $template;
        $set = function (string $key, string $value) use (&$env): void {
            $line = $key.'='.(preg_match('/[\s#"\'\\\\$]/', $value) ? '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"' : $value);
            $env = preg_match('/^'.preg_quote($key, '/').'=.*$/m', $env)
                ? preg_replace('/^'.preg_quote($key, '/').'=.*$/m', str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $env)
                : rtrim($env).PHP_EOL.$line.PHP_EOL;
        };
        $set('APP_NAME', $d['name']);
        $set('APP_ENV', 'production');
        $set('APP_DEBUG', 'false');
        $set('APP_URL', $d['url']);
        $set('APP_KEY', 'base64:'.base64_encode(random_bytes(32)));
        $set('DRINKWELL_TIMEZONE', $d['tz'] ?? 'America/New_York');
        $set('DRINKWELL_SUPPORT_EMAIL', $d['support']);
        $set('DB_CONNECTION', 'mysql');
        $set('DB_HOST', $d['db']['host']);
        $set('DB_PORT', $d['db']['port']);
        $set('DB_DATABASE', $d['db']['name']);
        $set('DB_USERNAME', $d['db']['user']);
        $set('DB_PASSWORD', $d['db']['pass']);
        if (($d['mail']['mode'] ?? '') === 'smtp') {
            $set('MAIL_MAILER', 'smtp');
            $set('MAIL_HOST', $d['mail']['host']);
            $set('MAIL_PORT', $d['mail']['port']);
            $set('MAIL_SCHEME', $d['mail']['scheme']);
            $set('MAIL_USERNAME', $d['mail']['user']);
            $set('MAIL_PASSWORD', $d['mail']['pass']);
            $set('MAIL_FROM_ADDRESS', $d['mail']['from']);
        } else {
            $set('MAIL_MAILER', 'log');
            $set('MAIL_FROM_ADDRESS', $d['support']);
        }
        $set('ADMIN_EMAIL', '');
        $set('ADMIN_PASSWORD', '');
        $set('DEMO_SHOW_CODES', $d['codes'] ? 'true' : 'false');
        if (file_put_contents($base.'/.env', $env) === false) {
            throw new RuntimeException('Could not write the .env file. Check that the app folder is writable.');
        }
        @chmod($base.'/.env', 0640);
        $log[] = 'Settings file (.env) written.';

        // Boot the app. Cache and sessions stay in files until their tables exist.
        foreach (['CACHE_STORE' => 'file', 'SESSION_DRIVER' => 'file', 'DEMO_PASSWORD' => (string) ($d['demo_pass'] ?? '')] as $k => $v) {
            putenv("{$k}={$v}");
            $_ENV[$k] = $_SERVER[$k] = $v;
        }
        foreach (glob($base.'/bootstrap/cache/*.php') ?: [] as $cached) {
            if (basename($cached) !== '.gitignore') {
                @unlink($cached);
            }
        }
        require $base.'/vendor/autoload.php';
        $app = require $base.'/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        $run = function (string $command, array $args = []) use ($kernel, &$log): void {
            $code = $kernel->call($command, $args);
            if ($code !== 0) {
                throw new RuntimeException("{$command} failed: ".trim($kernel->output()));
            }
        };

        // Not installed yet (no lock), so any of our tables here are left over from a try that stopped part way.
        // Start them over, so the retry does not trip over half-made tables.
        if (Illuminate\Support\Facades\Schema::hasTable('migrations')) {
            $run('db:wipe', ['--force' => true]);
            $log[] = 'Cleared the tables left by an earlier try.';
        }
        $run('migrate', ['--force' => true]);
        $log[] = 'Tables created.';
        $run('db:seed', ['--class' => 'Database\\Seeders\\SettingsSeeder', '--force' => true]);
        $run('db:seed', ['--class' => 'Database\\Seeders\\MachineSeeder', '--force' => true]);
        $log[] = 'Machines added ('.App\Models\VendingMachine::query()->count().').';

        $admin = App\Models\User::query()->where('email', $d['admin']['email'])->first() ?? new App\Models\User(['email' => $d['admin']['email']]);
        $admin->forceFill(['name' => $d['admin']['name'], 'password' => $d['admin']['pass'], 'role' => App\Enums\Role::Admin, 'status' => App\Enums\UserStatus::Active, 'email_verified_at' => now()])->save();
        $log[] = 'Admin account created for '.$d['admin']['email'].'.';

        if ($d['demo']) {
            $run('db:seed', ['--class' => 'Database\\Seeders\\DemoSeeder', '--force' => true]);
            $log[] = 'Demo member added.';
        }

        $settings = app(App\Services\SettingsRepository::class);
        $settings->set('brand.name', $d['name'], $admin);
        $settings->set('support_email', $d['support'], $admin);
        Illuminate\Support\Facades\Cache::forget(App\Services\SettingsRepository::BRAND_CACHE_KEY);
        $log[] = 'Site name and support email saved.';

        // A first reference code, so the admin can sign someone up straight away.
        $code = App\Models\InviteCode::create(['code' => App\Models\InviteCode::generate(), 'note' => 'First code', 'max_uses' => 1, 'created_by' => $admin->id]);
        $log[] = 'First reference code made: '.$code->code.'.';

        try {
            if (! file_exists(__DIR__.'/storage')) {
                $kernel->call('storage:link');
            }
            $log[] = file_exists(__DIR__.'/storage') ? 'Storage link made.' : 'Storage link could not be made (only needed for uploaded logos).';
        } catch (Throwable) {
            $log[] = 'Storage link could not be made (only needed for uploaded logos).';
        }

        foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $c) {
            try {
                $kernel->call($c);
            } catch (Throwable) {
            }
        }

        file_put_contents($lock, 'Installed '.date('c').PHP_EOL);
        @unlink($keyFile);
        $s = [];
        session_destroy();
        $selfDeleted = @unlink(__FILE__);
    } catch (Throwable $ex) {
        page('Install did not finish', '<div class="card"><div class="err">'.e($ex->getMessage()).'</div><p>Nothing is locked yet, so you can fix it and try again.</p>'
            .'<ul>'.implode('', array_map(fn ($l) => '<li>'.e($l).'</li>', $log)).'</ul><a class="btn" href="install.php?step=install">Try again</a> <a class="btn ghost" href="install.php?step=check">Start over</a></div>', 7);
    }

    $php = '/usr/local/bin/ea-php'.PHP_MAJOR_VERSION.PHP_MINOR_VERSION;
    $cron = $php.' '.$base.'/artisan schedule:run >> /dev/null 2>&1';
    $url = rtrim($d['url'], '/');
    page('Your site is ready', '<div class="card"><ul class="list">'.implode('', array_map(fn ($l) => '<li><span>'.e($l).'</span><span class="ok">Done</span></li>', $log)).'</ul>'
        .'<h2>One last step: the clock</h2><p>The machines sell and pay out on a schedule. In <strong>cPanel, then Cron Jobs</strong>, add a job that runs <strong>every minute</strong> (* * * * *) with this command:</p>'
        .'<pre>'.e($cron).'</pre><p class="muted">If cPanel says that path does not exist, use the PHP path shown in MultiPHP Manager instead of <code>'.e($php).'</code>.</p>'
        .'<div class="note"><strong>Your first reference code:</strong> '.e($code->code).'<br>Sign-up link: <code>'.e($url).'/register?code='.e($code->code).'</code></div>'
        .($selfDeleted ? '' : '<div class="err">Delete install.php from the public folder in File Manager. It is switched off already, but it is tidier gone.</div>')
        .'<a class="btn" href="'.e($url).'/login">Sign in as admin</a> <a class="btn ghost" href="'.e($url).'">Open the site</a></div>');
}

back('check');

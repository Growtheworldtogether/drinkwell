<?php

it('shows the search page with suggestions before anything is typed', function () {
    $this->get(route('search'))->assertOk()->assertSee('People often look for')->assertSee('Start typing here');
});

it('finds a question and links straight to its answer', function () {
    machine();
    $this->get(route('search', ['q' => 'who can join']))
        ->assertOk()
        ->assertSee('Who can join?')
        ->assertSee(route('questions').'#q-who-can-join', false);
});

it('puts the withdrawal steps first when searching for withdraw', function () {
    machine();
    $html = $this->get(route('search', ['q' => 'withdraw']))->assertOk()->getContent();
    preg_match('/class="cc-result-title">([^<]+)</', $html, $m);
    expect($m[1] ?? null)->toBe('Ask');
    expect($html)->toContain(route('earnings').'#withdraw');
});

it('says so when nothing matches and offers places to start', function () {
    $this->get(route('search', ['q' => 'zzqxv']))->assertOk()->assertSee('No results for')->assertSee('Try a shorter word');
});

it('escapes what was typed', function () {
    $this->get(route('search', ['q' => '<script>alert(1)</script>']))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
});

it('sends the home page search box to the real search', function () {
    machine();
    $this->get(route('home'))->assertOk()->assertSee('action="'.route('search').'"', false);
});

it('sends the old About address to How it works, which now opens with the welcome statement', function () {
    $this->get('/about')->assertRedirect('/how-it-works')->assertStatus(301);
    $this->get(route('program'))->assertOk()->assertSee('Made for people who want a calm, steady way to earn');
});

it('shows the bigger text tip on public pages', function () {
    machine();
    $this->get(route('home'))->assertOk()->assertSee('data-size-tip', false)->assertSee('Text too small?');
});

it('shows the one-time code on the code page only when test mode is on', function () {
    $code = \App\Models\InviteCode::create(['code' => \App\Models\InviteCode::generate(), 'max_uses' => 5]);
    config(['drinkwell.show_codes' => false]);
    $this->post(route('register'), ['reference_code' => $code->code, 'name' => 'Ada Test', 'email' => 'ada@example.com', 'password' => 'Ada-Test-2026x', 'password_confirmation' => 'Ada-Test-2026x']);
    $this->get(route('verification.notice'))->assertOk()->assertDontSee('Test mode: your code is');

    auth()->logout();
    config(['drinkwell.show_codes' => true]);
    $this->post(route('register'), ['reference_code' => $code->code, 'name' => 'Bea Test', 'email' => 'bea@example.com', 'password' => 'Bea-Test-2026x', 'password_confirmation' => 'Bea-Test-2026x']);
    $this->get(route('verification.notice'))->assertOk()->assertSee('Test mode: your code is');
});

it('links styles over https behind a trusted tunnel, and ignores the forwarded scheme otherwise', function () {
    machine();
    $headers = ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'demo.trycloudflare.com'];

    config(['security.trusted_proxies' => '']);
    expect($this->get('/', $headers)->getContent())->not->toContain('https://demo.trycloudflare.com/build');

    config(['security.trusted_proxies' => '*']);
    expect($this->get('/', $headers)->getContent())->toContain('https://demo.trycloudflare.com/build');
});

it('writes https links whenever APP_URL is https, even without a trusted proxy', function () {
    machine();
    config(['app.url' => 'https://demo.trycloudflare.com', 'security.trusted_proxies' => '']);
    \Illuminate\Support\Facades\URL::forceScheme('https');
    expect($this->get('/')->getContent())->not->toContain('href="http://');
});

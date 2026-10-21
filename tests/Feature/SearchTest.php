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

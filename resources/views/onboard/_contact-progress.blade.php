@php
    // Deposit style 2 progress page. Expects: $req, $partner (name), $card (name, location, email), $initials, $machine,
    // $amount, $read (date or null), $emailed (bool), $urls (steps, message, emailed).
    $fmtDate = fn ($d) => $d?->format('M j, g:i a');
    $sent = (bool) $req->deposit_reported_at;
    $todo = [
        'read' => (bool) $read,
        'email' => $emailed || $sent,
        'send' => $sent,
        'receipt' => $sent,
    ];
    $now = collect($todo)->search(false); // the first step not done yet, or false when all are done
    $first = $card['name'];
    $title = match ($now) {
        'read' => 'Next: read the payment instruction',
        'email' => 'Next: email '.$first,
        'send', 'receipt' => 'Next: send your deposit',
        default => 'Waiting for '.$first.' to confirm',
    };
@endphp
@include('onboard._steps-style')
<style>
    .dw-todo { display: grid; gap: 10px; margin: 0; padding: 0; list-style: none; }
    .dw-todo li { display: grid; grid-template-columns: 40px minmax(0, 1fr); gap: 14px; align-items: start; padding: 16px; border: 2px solid transparent; border-radius: 20px; background: rgb(242, 242, 242); }
    .dw-todo li.is-now { border-color: black; background: white; }
    .dw-todo li.is-later { color: rgb(110, 110, 110); }
    .dw-todo .dw-mark { display: grid; place-items: center; width: 40px; height: 40px; border-radius: 50%; background: white; border: 2px solid rgb(200, 200, 200); font-weight: 800; }
    .dw-todo li.is-now .dw-mark { background: black; border-color: black; color: white; }
    .dw-todo li.is-done .dw-mark { background: rgb(31, 122, 61); border-color: rgb(31, 122, 61); color: white; }
    .dw-todo h3 { margin: 6px 0 0; font-size: 18px; line-height: 1.3; overflow-wrap: anywhere; }
    .dw-todo p { margin: 4px 0 0; font-size: 16px; line-height: 1.45; }
    .dw-todo .dw-acts { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
    .dw-todo .dw-acts .af-btn { width: auto; min-height: 50px; padding: 0 22px; font-size: 16px; }
    @media (max-width: 559px) { .dw-todo .dw-acts .af-btn { width: 100%; } .dw-todo li { grid-template-columns: 34px minmax(0, 1fr); gap: 12px; padding: 14px; } .dw-todo .dw-mark { width: 34px; height: 34px; } .dw-todo h3 { margin-top: 3px; } }
    .dw-todo .dw-link { font-size: 16px; font-weight: 700; color: black; text-decoration: underline; text-underline-offset: 3px; background: none; border: 0; padding: 0; cursor: pointer; font-family: inherit; }
    .dw-status { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 9999px; background: rgb(253, 241, 217); font-weight: 700; }
    .dw-status.is-ok { background: rgb(233, 247, 241); }
    #proof { scroll-margin-top: 92px; }
</style>

<div class="dw-steps-page" style="max-width:760px"
     x-data="{ copied: '', copy(t, key) {
         const ok = () => { this.copied = key; setTimeout(() => { if (this.copied === key) this.copied = '' }, 2200) };
         if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(t).then(ok, () => {}); return; }
         const a = document.createElement('textarea'); a.value = t; a.style.position = 'fixed'; a.style.top = '-1000px'; document.body.appendChild(a); a.select(); try { document.execCommand('copy'); ok(); } catch (e) {} a.remove();
     } }">

    <x-cc.head :title="$title" eyebrow="Your co-partner" :lead="$now === false ? 'Your receipt is in. We let you know the moment '.$first.' confirms it, and your share starts earning the next business day.' : 'You are paired with '.$first.($card['location'] ? ' in '.$card['location'] : '').'. Follow these steps to pay your share of '.$amount.'.'" />

    <div class="cc-panel dw-card">
        <h2 style="margin:0">What to do now</h2>
        <ol class="dw-todo">
            @php $state = fn ($k) => $todo[$k] ? 'is-done' : ($now === $k ? 'is-now' : 'is-later'); @endphp
            <li class="{{ $state('read') }}">
                <span class="dw-mark" aria-hidden="true">@if ($todo['read'])&#10003;@else 1 @endif</span>
                <div>
                    <h3>Read the payment instruction</h3>
                    @if ($read)
                        <p>You read it on {{ $fmtDate($read) }}. <a class="dw-link" href="{{ $urls['steps'] }}">Read it again</a></p>
                    @else
                        <p>It tells you how to pay {{ $first }} safely.</p>
                        <div class="dw-acts"><a class="af-btn af-btn-black" href="{{ $urls['steps'] }}">Read the instruction</a></div>
                    @endif
                </div>
            </li>
            <li class="{{ $state('email') }}">
                <span class="dw-mark" aria-hidden="true">@if ($todo['email'])&#10003;@else 2 @endif</span>
                <div>
                    <h3>Email {{ $first }}</h3>
                    @if ($todo['email'])
                        <p>Done{{ $req->partner_emailed_at ? ' on '.$fmtDate($req->partner_emailed_at) : '' }}. {{ $first }} will reply with how to send your deposit.</p>
                    @else
                        <p>Your message is ready to send.</p>
                        @if ($now === 'email')
                            <div class="dw-acts">
                                <a class="af-btn af-btn-black" href="{{ $urls['message'] }}">Open the message</a>
                                @if ($urls['emailed'])
                                    <form method="POST" action="{{ $urls['emailed'] }}" style="display:contents">@csrf<button type="submit" class="af-btn cc-btn-outline">I have emailed {{ $first }}</button></form>
                                @else
                                    <button type="button" class="af-btn cc-btn-outline">I have emailed {{ $first }}</button>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            </li>
            <li class="{{ $state('send') }}">
                <span class="dw-mark" aria-hidden="true">@if ($todo['send'])&#10003;@else 3 @endif</span>
                <div>
                    <h3>Send your {{ $amount }} the way {{ $first }} tells you</h3>
                    <p>{{ $todo['send'] ? 'Sent.' : 'Only use the details '.$first.' gives you by email.' }}</p>
                </div>
            </li>
            <li class="{{ $state('receipt') }}">
                <span class="dw-mark" aria-hidden="true">@if ($todo['receipt'])&#10003;@else 4 @endif</span>
                <div>
                    <h3>Upload your receipt</h3>
                    @if ($todo['receipt'])
                        <p>Thank you. {{ $first }} confirms it, then your share starts earning.</p>
                    @else
                        <p>A photo or PDF of your bank confirmation.</p>
                        @if (in_array($now, ['send', 'receipt'], true))
                            <div class="dw-acts"><a class="af-btn af-btn-black" href="#proof">Upload my receipt</a></div>
                        @endif
                    @endif
                </div>
            </li>
        </ol>
    </div>

    <div class="cc-panel dw-card">
        <h2 style="margin:0">Your co-partner</h2>
        @include('onboard._steps-partner', ['partner' => $card])
    </div>

    @include('onboard._proof')

    <div class="cc-panel">
        <h2>Your machine</h2>
        <dl class="cc-dl">
            <div class="cc-kv"><dt>Serial number</dt><dd>{{ $machine->serial_no }}</dd></div>
            <div class="cc-kv"><dt>Facility</dt><dd>{{ $machine->facility_name }}, {{ $machine->city }}</dd></div>
            <div class="cc-kv"><dt>Option</dt><dd>{{ $machine->tier->label() }}</dd></div>
            <div class="cc-kv"><dt>Your deposit</dt><dd>{{ $amount }}</dd></div>
        </dl>
    </div>
</div>

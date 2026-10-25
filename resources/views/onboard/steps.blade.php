@php
    $labels = [1 => 'Your co-partner', 2 => 'Payment instruction', 3 => 'Your message'];
    $initials = \Illuminate\Support\Str::of($partner['name'])->explode(' ')->filter()->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    $mailto = 'mailto:'.$partner['email'].'?subject='.rawurlencode($subject).'&body='.rawurlencode($body);
@endphp
<x-app-layout title="Your co-partner" bg="mint" :mode="$preview ? 'admin' : 'member'">
    @include('onboard._steps-style')

    <div class="dw-steps-page"
         x-data="{ copied: '', copy(t, key) {
             const ok = () => { this.copied = key; setTimeout(() => { if (this.copied === key) this.copied = '' }, 2200) };
             if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(t).then(ok, () => {}); return; }
             const a = document.createElement('textarea'); a.value = t; a.setAttribute('readonly', ''); a.style.position = 'fixed'; a.style.top = '-1000px';
             document.body.appendChild(a); a.select(); try { document.execCommand('copy'); ok(); } catch (e) {} a.remove();
         } }">

        @if ($preview)
            <x-cc.notice>Preview: this is what a member sees, with a sample member, Helen Brooks. Nothing here is saved or sent.</x-cc.notice>
        @endif

        <ol class="dw-dots" aria-label="Step {{ $step }} of 3">
            @foreach ($labels as $n => $label)
                <li @class(['is-done' => $n < $step, 'is-now' => $n === $step]) @if ($n === $step) aria-current="step" @endif><i></i><span>{{ $label }}</span></li>
            @endforeach
        </ol>

        @if ($step === 1)
            <div class="cc-panel dw-card">
                <div class="dw-tick" aria-hidden="true"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></div>
                <p class="cc-eyebrow dw-eyebrow">Step 1 of 3</p>
                <h1 class="dw-title"><span class="dw-nb">Co-partner</span> match confirmed</h1>
                @include('onboard._steps-partner')
                <a href="{{ ($urls['step'])(2) }}" class="af-btn af-btn-black dw-go">Continue</a>
            </div>
        @elseif ($step === 2)
            <div class="cc-panel dw-card">
                <p class="cc-eyebrow dw-eyebrow">Step 2 of 3</p>
                <h1 class="dw-title">Important payment instruction</h1>
                <div class="dw-instruction">{{ $instruction }}</div>
                <form method="POST" action="{{ $urls['read'] }}" class="cc-form" x-data="{ ok: false }">
                    @csrf
                    <label class="cc-check dw-read"><input type="checkbox" name="read" value="1" x-model="ok" required><span>I have read this</span></label>
                    @error('read')<p class="cc-error" role="alert">{{ $message }}</p>@enderror
                    <button type="submit" class="af-btn af-btn-black dw-go" x-bind:disabled="! ok">Continue</button>
                    <a href="{{ ($urls['step'])(1) }}" class="af-btn cc-btn-outline dw-go">Back</a>
                </form>
            </div>
        @else
            <div class="cc-panel dw-card">
                <p class="cc-eyebrow dw-eyebrow">Step 3 of 3</p>
                <h1 class="dw-title">Message to your <span class="dw-nb">co-partner</span></h1>
                <p class="dw-lead">Send this email to {{ $partner['name'] }}. Copy it, or open it straight in your email app.</p>
                @include('onboard._steps-partner')

                <div class="dw-msg">
                    <div class="dw-msg-head">
                        <h2>Subject</h2>
                        <button type="button" class="dw-copy" x-on:click="copy(@js($subject), 'subject')"><span x-text="copied === 'subject' ? 'Copied' : 'Copy subject'">Copy subject</span></button>
                    </div>
                    <p class="dw-subject">{{ $subject }}</p>
                </div>
                <div class="dw-msg">
                    <div class="dw-msg-head">
                        <h2>Message</h2>
                        <button type="button" class="dw-copy" x-on:click="copy(@js($body), 'body')"><span x-text="copied === 'body' ? 'Copied' : 'Copy message'">Copy message</span></button>
                    </div>
                    <div class="dw-body">{{ $body }}</div>
                </div>

                <a href="{{ $mailto }}" class="af-btn af-btn-black dw-go">Open in my email app</a>
                <a href="{{ $urls['done'] }}" class="af-btn cc-btn-outline dw-go">{{ $preview ? 'Back to settings' : 'Done' }}</a>
                <a href="{{ ($urls['step'])(2) }}" class="dw-back">Back to the payment instruction</a>
            </div>
        @endif
    </div>
</x-app-layout>

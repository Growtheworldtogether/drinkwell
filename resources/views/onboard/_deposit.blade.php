@php
    $fmt = fn (int $c) => \App\Support\Money::format($c);
    $first = $partner->name;
    $sent = $req && $req->deposit_reported_at;
@endphp
<div class="cc-grid is-split">
<div class="cc-panel" id="deposit">
    <h2>Send your deposit to {{ $first }}</h2>
    <p>You are buying your half of a machine {{ $first }} already owns, so the deposit goes straight to them and never to {{ config('app.name') }}. Your share starts the day they confirm it.</p>
    @if ($req && $req->isPaired())
        <dl class="cc-dl">
            <div class="cc-kv"><dt>Account holder</dt><dd>{{ $req->account_holder }}</dd></div>
            <div class="cc-kv"><dt>Bank</dt><dd>{{ $req->bank_name }}</dd></div>
            @if ($req->routing_number)<div class="cc-kv"><dt>Routing number</dt><dd>{{ $req->routing_number }}</dd></div>@endif
            <div class="cc-kv"><dt>Account number</dt><dd>{{ $req->account_number }}</dd></div>
            <div class="cc-kv"><dt>Amount</dt><dd>{{ $fmt($mine->capital_cents) }}</dd></div>
            <div class="cc-kv"><dt>Reference</dt><dd>{{ $reference }}</dd></div>
            @if ($req->note)<div class="cc-kv"><dt>Note</dt><dd>{{ $req->note }}</dd></div>@endif
        </dl>
    @endif
</div>

@if ($req && $req->isPaired())
    <div class="cc-panel" id="proof">
        <h2>Proof of payment</h2>
        @if ($sent)
            <x-cc.notice>You told us you sent it on {{ ($req->proof_sent_on ?? $req->deposit_reported_at)->format('M j, Y') }}. We will tell you the moment {{ $first }} confirms.</x-cc.notice>
            @if ($req->proof_path)
                <p>Your receipt: <a href="{{ route('onboard.proof') }}" target="_blank" rel="noopener" style="font-weight:700;text-decoration:underline">{{ $req->proof_name }}</a></p>
            @endif
        @else
            <p>After you send your deposit, upload a photo or PDF of your receipt. It helps {{ $first }} confirm faster.</p>
        @endif

        <form method="POST" action="{{ route('onboard.report') }}" enctype="multipart/form-data" class="cc-form" x-data="{ name: '' }">
            @csrf
            <div class="cc-field">
                <label for="proof-file">{{ $req->proof_path ? 'Replace your receipt' : 'Your receipt' }} (photo or PDF, up to 5 MB)</label>
                <label class="cc-upload" for="proof-file">
                    <b>Choose a photo or PDF</b><span>A picture of your receipt or bank confirmation</span>
                    <input id="proof-file" type="file" name="proof" accept="image/*,application/pdf" x-on:change="name = $event.target.files[0]?.name ?? ''">
                </label>
                <div class="cc-file" x-show="name" x-cloak x-text="'Attached: ' + name"></div>
                @error('proof')<p class="cc-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="cc-two">
                <x-cc.field name="sent_on" label="Date you sent it" type="date" :value="old('sent_on', ($req->proof_sent_on ?? now())->toDateString())" :max="now()->toDateString()" />
                <x-cc.field name="note" label="Note (optional)" :value="old('note', $req->proof_note)" placeholder="For example, sent by bank transfer" maxlength="255" />
            </div>
            <button type="submit" class="af-btn af-btn-black">{{ $sent ? 'Update my proof' : 'I sent my deposit' }}</button>
        </form>
    </div>
@endif
</div>

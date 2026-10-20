@php
    $bag = $errors->getBag('p'.$req->id);
    $mine = old('_pid') == $req->id;
    $v = fn (string $k, $fallback = null) => $mine ? old($k) : $fallback;
    $f = fn (string $k) => 'p'.$req->id.'-'.$k;
@endphp
<form method="POST" action="{{ $action }}" class="pf-form-grid">
    @csrf
    @if ($method !== 'POST') @method($method) @endif
    <input type="hidden" name="_pid" value="{{ $req->id }}">
    @if ($bag->has('machine'))<p class="pf-notice" role="alert">{{ $bag->first('machine') }}</p>@endif
    <x-field name="partner_name" :id="$f('partner_name')" label="Partner name shown to the member" :value="$v('partner_name', $req->partner_name)" :error="$bag->first('partner_name')" required />
    <x-field name="partner_city" :id="$f('partner_city')" label="Partner city" :value="$v('partner_city', $req->partner_city)" :error="$bag->first('partner_city')" />
    <x-field name="account_holder" :id="$f('account_holder')" label="Account holder" :value="$v('account_holder', $req->account_holder)" :error="$bag->first('account_holder')" required />
    <x-field name="bank_name" :id="$f('bank_name')" label="Bank name" :value="$v('bank_name', $req->bank_name)" :error="$bag->first('bank_name')" required />
    <x-field name="routing_number" :id="$f('routing_number')" label="Routing number (optional)" :value="$v('routing_number', $req->routing_number)" :error="$bag->first('routing_number')" />
    <x-field name="account_number" :id="$f('account_number')" label="Account number" :value="$v('account_number', $req->account_number)" :error="$bag->first('account_number')" required />
    <x-field name="reference" :id="$f('reference')" label="Reference (blank for automatic)" :value="$v('reference', $req->reference)" :error="$bag->first('reference')" />
    <x-field name="note" :id="$f('note')" label="Note to the member (optional)" :value="$v('note', $req->note)" :error="$bag->first('note')" />
    <p class="pf-text">Enter fictional or test account details only.</p>
    <div class="pf-actions"><button type="submit" class="pf-btn">{{ $submit }}</button></div>
</form>

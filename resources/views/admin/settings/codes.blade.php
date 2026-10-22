<x-cc.adm title="Settings" heading="Reference codes">
    @include('admin.settings._tabs')

    <div class="cc-panel">
        <h2>Make new codes</h2>
        <p class="cc-hint">Nobody can sign up without a reference code. Give each person a code, or one code to a group.</p>
        <form method="POST" action="{{ route('admin.settings.codes.create') }}" class="cc-form">
            @csrf
            <div class="cc-two">
                <x-cc.field name="how_many" label="How many codes" type="number" :value="old('how_many', 1)" min="1" max="20" required />
                <x-cc.field name="max_uses" label="Sign-ups per code" type="number" :value="old('max_uses', 1)" min="1" help="Leave empty for no limit." />
                <x-cc.field name="note" label="Note (optional)" :value="old('note')" placeholder="For example, church group" maxlength="120" />
                <x-cc.field name="expires_on" label="Last day it works (optional)" type="date" :value="old('expires_on')" :min="now()->toDateString()" />
            </div>
            <button type="submit" class="af-btn af-btn-black" style="width:auto">Make codes</button>
        </form>
    </div>

    <div class="cc-panel">
        <h2>Your codes</h2>
        @if ($codes->isEmpty())
            <p>No codes yet. Make one above, then give it to the person you are inviting.</p>
        @else
            <div class="cc-tablewrap">
                <table class="cc-tbl is-stack">
                    <caption class="sr-only">Reference codes</caption>
                    <thead><tr><th>Code</th><th>Used</th><th>Status</th><th>Note</th><th><span class="sr-only">Action</span></th></tr></thead>
                    <tbody>
                        @foreach ($codes as $c)
                            <tr>
                                <td data-label="Code"><strong class="cc-code">{{ $c->code }}</strong></td>
                                <td data-label="Used">{{ $c->uses }} @if ($c->max_uses) of {{ $c->max_uses }} @endif</td>
                                <td data-label="Status"><span class="cc-tone {{ $c->status() === 'Active' ? 'ok' : '' }}">{{ $c->status() }}</span>@if ($c->expires_at && $c->status() === 'Active') <small>until {{ $c->expires_at->format('M j, Y') }}</small>@endif</td>
                                <td data-label="Note">{{ $c->note ?? '' }}</td>
                                <td data-label="Action">
                                    @if ($c->status() === 'Active')
                                        <form method="POST" action="{{ route('admin.settings.codes.revoke', $c) }}">@csrf<button type="submit" class="cc-edit">Turn off</button></form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="cc-hint" style="margin-top:12px">Sign-up link with the code filled in: {{ route('register') }}?code=THE-CODE</p>
        @endif
    </div>
</x-cc.adm>

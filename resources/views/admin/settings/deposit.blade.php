<x-cc.adm title="Settings" heading="Deposit details">
    @include('admin.settings._tabs')

    <div class="cc-panel">
        <h2>Default deposit details</h2>
        <p class="cc-hint">These fill in the pairing form for every new member, so pairing is one click. You can still change them for any member before or after pairing.</p>
        <form method="POST" action="{{ route('admin.settings.deposit.save') }}" class="cc-form">
            @csrf
            @method('PUT')
            <div class="cc-two">
                <x-cc.field name="partner_name" label="Co-partner name shown to members" :value="old('partner_name', $d['partner_name'])" maxlength="120" />
                <x-cc.field name="partner_city" label="Co-partner city" :value="old('partner_city', $d['partner_city'])" maxlength="80" />
                <x-cc.field name="account_holder" label="Account holder" :value="old('account_holder', $d['account_holder'])" maxlength="120" />
                <x-cc.field name="bank_name" label="Bank name" :value="old('bank_name', $d['bank_name'])" maxlength="120" />
                <x-cc.field name="routing_number" label="Routing number, or transit and institution number" :value="old('routing_number', $d['routing_number'])" maxlength="40" help="US: 9-digit routing number. Canada: transit and institution number, for example 12345-004." />
                <x-cc.field name="account_number" label="Account number" :value="old('account_number', $d['account_number'])" maxlength="40" />
                <x-cc.field name="note" label="Note to the member" :value="old('note', $d['note'])" maxlength="255" placeholder="For example, please send within 2 days" />
            </div>
            <p class="cc-hint" style="margin:0">Enter fictional or test account details only.</p>
            <button type="submit" class="af-btn af-btn-black" style="width:auto">Save default details</button>
        </form>
    </div>
</x-cc.adm>

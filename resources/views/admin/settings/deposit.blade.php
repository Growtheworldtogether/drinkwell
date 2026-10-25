<x-cc.adm title="Settings" heading="Deposit details">
    @include('admin.settings._tabs')

    <form method="POST" action="{{ route('admin.settings.deposit.save') }}" class="cc-form" x-data="{ style: @js(old('deposit_style', $style)) }">
        @csrf
        @method('PUT')

        <div class="cc-panel">
            <h2>How members pay</h2>
            <p class="cc-hint">Choose what a member sees once they are paired. You can switch at any time; it applies to every member who has not paid yet.</p>
            <div class="cc-form" style="gap:12px">
                <label class="cc-check"><input type="radio" name="deposit_style" value="bank" x-model="style"><span><strong>Style 1: show the bank details.</strong> The member's page shows the co-partner's bank account, and the pairing email carries it too.</span></label>
                <label class="cc-check"><input type="radio" name="deposit_style" value="contact" x-model="style"><span><strong>Style 2: members contact their co-partner.</strong> No bank details anywhere and no pairing email. The member goes through three screens: their co-partner, your payment instruction, and a ready message to send the co-partner.</span></label>
            </div>
            @error('deposit_style')<p class="cc-error" role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="cc-panel">
            <h2>Co-partner</h2>
            <p class="cc-hint">Shown to every new member as their co-partner.</p>
            <div class="cc-two">
                <x-cc.field name="partner_name" label="Co-partner name" :value="old('partner_name', $d['partner_name'])" maxlength="120" />
                <x-cc.field name="partner_city" label="Location" :value="old('partner_city', $d['partner_city'])" maxlength="80" placeholder="Toronto, ON" />
                <x-cc.field name="partner_email" label="Co-partner email" type="email" :value="old('partner_email', $d['partner_email'])" maxlength="190" help="Style 2 shows this to members, and they write to it. Use a mailbox you check." />
            </div>
        </div>

        <div class="cc-panel" x-show="style === 'bank'" x-cloak>
            <h2>Bank details (style 1)</h2>
            <p class="cc-hint">Shown on the member's page and in the pairing email. You can still change them for any member on the Pairings page.</p>
            <div class="cc-two">
                <x-cc.field name="account_holder" label="Account holder" :value="old('account_holder', $d['account_holder'])" maxlength="120" />
                <x-cc.field name="bank_name" label="Bank name" :value="old('bank_name', $d['bank_name'])" maxlength="120" />
                <x-cc.field name="routing_number" label="Routing or transit number" :value="old('routing_number', $d['routing_number'])" maxlength="40" help="US: 9-digit routing number. Canada: transit and institution number, for example 12345-004." />
                <x-cc.field name="account_number" label="Account number" :value="old('account_number', $d['account_number'])" maxlength="40" />
                <x-cc.field name="note" label="Note to the member" :value="old('note', $d['note'])" maxlength="255" placeholder="Please send within 2 days" />
            </div>
        </div>

        <div class="cc-panel" x-show="style === 'contact'" x-cloak>
            <h2>Important payment instruction (style 2)</h2>
            <p class="cc-hint">Screen 2 shows only this. The member must tick "I have read this" to go on.</p>
            <x-cc.field name="payment_instruction" label="Your instruction" type="textarea" rows="10" maxlength="6000" :value="old('payment_instruction', $texts['instruction'])" help="Leave an empty line to start a new paragraph. Start a line with a dash and a space (- ) to make a bullet point." />
        </div>

        <div class="cc-panel" x-show="style === 'contact'" x-cloak>
            <h2>Message to the co-partner (style 2)</h2>
            <p class="cc-hint">Screen 3 shows this ready to copy, with a button that opens it in the member's email app.</p>
            <x-cc.field name="email_subject" label="Subject" :value="old('email_subject', $texts['subject'])" maxlength="190" />
            <x-cc.field name="email_body" label="Message" type="textarea" rows="12" maxlength="6000" :value="old('email_body', $texts['body'])" />
            <details class="cc-words">
                <summary>Fill-in words you can use</summary>
                <p class="cc-hint">Type a word in curly brackets and each member sees their own details instead. They work in the instruction too.</p>
                <dl class="cc-dl">
                    @foreach (\App\Support\DepositStyle::WORDS as $word => $meaning)
                        <div class="cc-kv"><dt><code>{{ '{'.$word.'}' }}</code></dt><dd style="font-weight:400">{{ $meaning }}</dd></div>
                    @endforeach
                </dl>
            </details>
        </div>

        <div class="cc-panel">
            <h2>Pairing</h2>
            <x-cc.check name="auto_pair" label="Pair new members automatically" :checked="$auto" />
            <p class="cc-hint" style="margin:0">When this is on, a new member sees a short search for a co-partner after the sign-up questions, then is paired with the co-partner above and a free machine for their option. Turn it off to pair each member yourself on the Pairings page.@if ($auto && $missing !== []) <strong>Waiting for: {{ implode(', ', $missing) }}.</strong>@endif</p>
            <div style="display:flex;flex-wrap:wrap;gap:12px">
                <button type="submit" class="af-btn af-btn-black" style="width:auto">Save deposit settings</button>
                <a href="{{ route('admin.settings.deposit.preview', 1) }}" target="_blank" rel="noopener" class="af-btn cc-btn-outline" style="width:auto" x-show="style === 'contact'" x-cloak>Preview as a member</a>
                <a href="{{ route('admin.settings.deposit.preview.progress') }}" target="_blank" rel="noopener" class="af-btn cc-btn-outline" style="width:auto" x-show="style === 'contact'" x-cloak>Preview the progress page</a>
            </div>
            <p class="cc-hint" style="margin:0" x-show="style === 'contact'" x-cloak>The preview shows what is saved, so save first.</p>
        </div>
    </form>
</x-cc.adm>

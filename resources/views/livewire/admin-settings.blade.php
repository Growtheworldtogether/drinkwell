<div>
    @if ($notice !== '')
        <x-cc.notice>{{ $notice }}</x-cc.notice>
    @endif

    <div class="cc-panel"><form wire:submit="saveSender" class="cc-form">
        <h2>Email sender</h2>
        <x-cc.field name="senderName" id="set-sender-name" label="Sender name" wire:model="senderName" maxlength="60" :help="'The name people see in their inbox. Leave empty to use the platform name ('.$platform_name.'). The address ('.$sender_address.') is set on the server and does not change here.'" />
        <div class="cc-step-nav"><button type="submit" class="af-btn af-btn-black">Save</button></div>
    </form></div>

    <div class="cc-panel"><form wire:submit="saveNotify" class="cc-form">
        <h2>Admin emails</h2>
        <p>Each message below can be switched off, or sent to its own address. With no address it goes to {{ $company_email ? 'the company email ('.$company_email.')' : 'every administrator account' }}.</p>
        @foreach ($topics as $key => $topic)
            <div wire:key="notify-{{ $key }}" class="cc-notify">
                <x-cc.check name="notify.{{ $key }}.on" id="set-notify-{{ $key }}" :label="$topic['label']" wire:model.live="notify.{{ $key }}.on" value="1" />
                <p class="cc-hint">{{ $topic['help'] }}</p>
                @if ($notify[$key]['on'] ?? false)
                    <x-cc.field name="notify.{{ $key }}.email" id="set-notify-{{ $key }}-email" label="Send to this email" type="email" wire:model="notify.{{ $key }}.email" help="Leave empty to use the default above." />
                @endif
            </div>
        @endforeach
        <div class="cc-step-nav"><button type="submit" class="af-btn af-btn-black">Save</button></div>
    </form></div>

    <div class="cc-panel">
        <h2>Fixed values</h2>
        <dl class="cc-dl">
            <div class="cc-kv"><dt>Code lifetime</dt><dd>{{ $otp['ttl'] }} minutes</dd></div>
            <div class="cc-kv"><dt>Wrong guesses allowed</dt><dd>{{ $otp['attempts'] }} per code</dd></div>
            <div class="cc-kv"><dt>Wait before a new code</dt><dd>{{ $otp['cooldown'] }} seconds</dd></div>
            <div class="cc-kv"><dt>Codes per hour</dt><dd>{{ $otp['hourly'] }}</dd></div>
            <div class="cc-kv"><dt>Terms version</dt><dd>{{ $terms_version }}</dd></div>
        </dl>
        <p class="cc-hint" style="margin-top:12px">The code settings are part of the application and are shown here for reference.</p>
    </div>
</div>

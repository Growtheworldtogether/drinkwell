<div class="pf-page">
    @if ($notice !== '')
        <p class="pf-notice" role="status">{{ $notice }}</p>
    @endif

    <form wire:submit="saveSender" class="pf-panel pf-form">
        <div class="pf-panel-head"><p class="pf-label">Email sender</p></div>
        <x-field name="senderName" id="set-sender-name" label="Sender name" wire:model="senderName" maxlength="60" :help="'The name people see in their inbox. Leave empty to use the platform name ('.$platform_name.'). The address ('.$sender_address.') is set on the server and does not change here.'" />
        <div class="pf-actions"><button type="submit" class="pf-btn">Save</button></div>
    </form>

    <form wire:submit="saveNotify" class="pf-panel pf-form">
        <div class="pf-panel-head"><p class="pf-label">Admin emails</p></div>
        <p class="pf-note">Each message below can be switched off, or sent to its own address. With no address it goes to {{ $company_email ? 'the company email ('.$company_email.')' : 'every administrator account' }}.</p>
        @foreach ($topics as $key => $topic)
            <div wire:key="notify-{{ $key }}">
                <x-field name="notify.{{ $key }}.on" id="set-notify-{{ $key }}" :label="$topic['label']" type="checkbox" wire:model.live="notify.{{ $key }}.on" :help="$topic['help']" />
                @if ($notify[$key]['on'] ?? false)
                    <x-field name="notify.{{ $key }}.email" id="set-notify-{{ $key }}-email" label="Send to this email" type="email" wire:model="notify.{{ $key }}.email" help="Leave empty to use the default above." />
                @endif
            </div>
        @endforeach
        <div class="pf-actions"><button type="submit" class="pf-btn">Save</button></div>
    </form>

    <section class="pf-panel" aria-labelledby="set-ro">
        <div class="pf-panel-head"><p class="pf-label" id="set-ro">Fixed values</p></div>
        <dl class="pf-rows">
            <div><dt>Code lifetime</dt><dd class="pf-mono">{{ $otp['ttl'] }} minutes</dd></div>
            <div><dt>Wrong guesses allowed</dt><dd class="pf-mono">{{ $otp['attempts'] }} per code</dd></div>
            <div><dt>Wait before a new code</dt><dd class="pf-mono">{{ $otp['cooldown'] }} seconds</dd></div>
            <div><dt>Codes per hour</dt><dd class="pf-mono">{{ $otp['hourly'] }}</dd></div>
            <div><dt>Terms version</dt><dd class="pf-mono">{{ $terms_version }}</dd></div>
        </dl>
        <p class="pf-note">The code settings are part of the application and are shown here for reference.</p>
    </section>

</div>

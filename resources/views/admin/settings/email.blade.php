<x-cc.adm title="Settings" heading="Email">
    @include('admin.settings._tabs')

    @if (session('error'))
        <x-cc.notice type="error">{{ session('error') }}</x-cc.notice>
    @endif

    <div class="cc-panel">
        <h2>Sending email</h2>
        @if ($current)
            <p>Emails are being sent through <strong>{{ $current['host'] }}</strong> from <strong>{{ $current['from'] }}</strong>.</p>
        @else
            <p><strong>Email is not connected.</strong> Sign-in codes, pairing details and withdrawal notices are only written to the log, so members cannot sign up yet.</p>
        @endif
        <p class="cc-hint">In cPanel, open Email Accounts and create an address such as no-reply@yourdomain.com. Then open Connect Devices next to it: it lists the mail server and port to enter here.</p>
    </div>

    <div class="cc-panel">
        <h2>Your mailbox (SMTP)</h2>
        <form method="POST" action="{{ route('admin.settings.email.save') }}" class="cc-form" autocomplete="off">
            @csrf
            @method('PUT')
            <x-cc.check name="enabled" label="Send email through this mailbox" :checked="old('enabled', $m['enabled'])" />
            <div class="cc-two">
                <x-cc.field name="host" label="Mail server" :value="old('host', $m['host'])" maxlength="190" placeholder="mail.yourdomain.com" />
                <x-cc.field name="port" label="Port" type="number" :value="old('port', $m['port'])" help="465 on most cPanel hosts. Use 587 if 465 does not work." />
                <x-cc.field name="username" label="Email address (user name)" :value="old('username', $m['username'])" maxlength="190" placeholder="no-reply@yourdomain.com" />
                <x-cc.field name="password" label="Email password" type="password" autocomplete="new-password" :help="$m['has_password'] ? 'Saved. Leave empty to keep it.' : 'The password of that email account.'" />
                <x-cc.field name="from_address" label="Send from" type="email" :value="old('from_address', $m['from_address'])" maxlength="190" help="Leave empty to use the same address." />
                <x-cc.field name="from_name" label="Sender name" :value="old('from_name', $m['from_name'])" maxlength="120" :placeholder="config('app.name')" />
            </div>
            <p class="cc-hint" style="margin:0">The password is stored encrypted. These details replace the MAIL_ lines in the .env file while the box above is ticked.</p>
            <button type="submit" class="af-btn af-btn-black" style="width:auto">Save email settings</button>
        </form>
    </div>

    <div class="cc-panel">
        <h2>Send a test email</h2>
        <form method="POST" action="{{ route('admin.settings.email.test') }}" class="cc-form">
            @csrf
            <x-cc.field name="to" label="Send to" type="email" :value="old('to', auth()->user()->email)" maxlength="190" />
            <button type="submit" class="af-btn af-btn-black" style="width:auto">Send test email</button>
        </form>
    </div>
</x-cc.adm>

{{-- The co-partner card on screens 1 and 3: name, location and email, with a Copy button for the email. --}}
<div class="dw-partner">
    <div class="dw-partner-who">
        <span class="cc-avatar" aria-hidden="true">{{ $initials }}</span>
        <div>
            <p class="dw-partner-label">Matched partner</p>
            <p class="dw-partner-name">{{ $partner['name'] }}</p>
        </div>
    </div>
    <dl class="cc-dl">
        @if ($partner['location'] !== '')
            <div class="cc-kv"><dt>Location</dt><dd>{{ $partner['location'] }}</dd></div>
        @endif
        @if ($partner['email'] !== '')
            <div class="cc-kv dw-email-row">
                <dt>Email</dt>
                <dd><a href="mailto:{{ $partner['email'] }}">{{ $partner['email'] }}</a></dd>
                <dd class="dw-email-copy"><button type="button" class="dw-copy" x-on:click="copy(@js($partner['email']), 'email')"><span x-text="copied === 'email' ? 'Copied' : 'Copy email'">Copy email</span></button></dd>
            </div>
        @endif
    </dl>
</div>

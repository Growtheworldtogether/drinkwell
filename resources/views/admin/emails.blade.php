<x-cc.adm title="Email log">
    @if ($mailer === 'log')
        <x-cc.notice type="error"><strong>Email:</strong> the mailer is set to "log", so these rows were not delivered to anyone.</x-cc.notice>
    @endif

    <div class="cc-panel">
        <nav class="cc-tabs" aria-label="Email status">
            @foreach (['all' => 'All', 'sent' => 'Sent', 'simulated' => 'Demo', 'failed' => 'Failed '.$failed] as $key => $label)
                <a href="{{ route('admin.emails', ['status' => $key]) }}" @if ($key === $status) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        @if ($rows->isEmpty())
            <p>No emails{{ $status === 'all' ? ' yet' : ' with this status' }}.</p>
        @else
            <div class="cc-tablewrap">
                <table class="cc-tbl is-stack">
                    <caption class="sr-only">The last 200 emails</caption>
                    <thead><tr><th>When</th><th>To</th><th>Template</th><th>Subject</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($rows as $m)
                            <tr>
                                <td data-label="When">{{ $m->created_at->format('Y-m-d H:i') }}</td>
                                <td data-label="To">{{ $m->to_email }}</td>
                                <td data-label="Template">{{ $m->template }}</td>
                                <td data-label="Subject" class="is-wrap">{{ $m->subject }}</td>
                                <td data-label="Status">
                                    <span class="cc-tone {{ in_array($m->status, ['sent', 'simulated'], true) ? 'ok' : '' }}">{{ $m->status === 'simulated' ? 'Demo' : ucfirst($m->status) }}</span>
                                    @if ($m->error)<span class="cc-hint">{{ $m->error }}</span>@endif
                                </td>
                                <td data-label="">
                                    @if ($m->status === 'failed' && $m->payload !== null)
                                        <form method="POST" action="{{ route('admin.emails.resend', $m) }}">@csrf<button type="submit" class="af-btn cc-btn-outline">Resend</button></form>
                                    @elseif ($m->status === 'failed')
                                        <span class="cc-hint">{{ str_starts_with($m->template, 'otp_') ? 'Codes are not kept' : 'Already resent' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-cc.adm>

<x-admin-layout title="Email log">
    <div class="pf-page">
        @if (session('status'))
            <p class="pf-notice" role="status">{{ session('status') }}</p>
        @endif
        @if ($mailer === 'log')
            <p class="pf-notice" role="alert"><b>Email</b> The mailer is set to log, so these rows were not delivered to anyone.</p>
        @endif

        <nav class="pf-tabs" aria-label="Email status">
            @foreach (['all' => 'All', 'sent' => 'Sent', 'simulated' => 'Simulated', 'failed' => 'Failed '.$failed] as $key => $label)
                <a href="{{ route('admin.emails', ['status' => $key]) }}" @if ($key === $status) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <section class="pf-panel" aria-label="Email log">
            @if ($rows->isEmpty())
                <p class="pf-text">No emails{{ $status === 'all' ? ' yet' : ' with this status' }}.</p>
            @else
                <div class="pf-tablewrap">
                    <table class="pf-table">
                        <caption class="sr-only">The last 200 emails</caption>
                        <thead><tr><th>When</th><th>To</th><th>Template</th><th>Subject</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($rows as $m)
                                <tr>
                                    <td data-label="When" class="pf-mono">{{ $m->created_at->format('Y-m-d H:i') }}</td>
                                    <td data-label="To">{{ $m->to_email }}</td>
                                    <td data-label="Template" class="pf-mono">{{ $m->template }}</td>
                                    <td data-label="Subject">{{ $m->subject }}</td>
                                    <td data-label="Status">
                                        <span class="pf-chip {{ in_array($m->status, ['sent', 'simulated'], true) ? 'is-ok' : 'is-off' }}">{{ ucfirst($m->status) }}</span>
                                        @if ($m->error)<span class="pf-note">{{ $m->error }}</span>@endif
                                    </td>
                                    <td data-label="">
                                        @if ($m->status === 'failed' && $m->payload !== null)
                                            <form method="POST" action="{{ route('admin.emails.resend', $m) }}">
                                                @csrf
                                                <button type="submit" class="pf-btn">Resend</button>
                                            </form>
                                        @elseif ($m->status === 'failed')
                                            <span class="pf-note">{{ str_starts_with($m->template, 'otp_') ? 'Codes are not kept' : 'Already resent' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-admin-layout>

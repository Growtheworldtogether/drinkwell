<x-admin-layout title="Pairings">
    <div class="pf-page">
        @if (session('status'))
            <p class="pf-notice" role="status">{{ session('status') }}</p>
        @endif

        <section class="pf-panel" aria-labelledby="pr-wait">
            <div class="pf-panel-head"><p class="pf-label" id="pr-wait">Finding a partner ({{ $waiting->count() }})</p></div>
            @forelse ($waiting as $req)
                <details class="pf-detail" @if (old('_pid') == $req->id) open @endif>
                    <summary><strong>{{ $req->user->name }}</strong> <span class="pf-mono">{{ $req->user->email }}</span> · {{ \App\Enums\Tier::from($req->tier)->label() }} · asked {{ $req->created_at->diffForHumans() }}</summary>
                    @include('admin._pairing-form', ['req' => $req, 'action' => route('admin.pairings.assign', $req), 'method' => 'POST', 'submit' => 'Pair and email the member'])
                </details>
            @empty
                <x-empty-state title="Nobody is waiting">New members appear here when they ask for a partner.</x-empty-state>
            @endforelse
        </section>

        <section class="pf-panel mt-4" aria-labelledby="pr-paired">
            <div class="pf-panel-head"><p class="pf-label" id="pr-paired">Paired ({{ $paired->count() }})</p></div>
            @forelse ($paired as $req)
                @php $own = $ownerships[$req->user_id] ?? null; $pending = $own && $own->settlement_status === \App\Enums\SettlementStatus::PendingSettlement; @endphp
                <details class="pf-detail" @if (old('_pid') == $req->id) open @endif>
                    <summary>
                        <strong>{{ $req->user->name }}</strong> paired with <strong>{{ $req->partner_name }}</strong> ·
                        @if (! $pending) Deposit confirmed
                        @elseif ($req->deposit_reported_at) Member says it was sent
                        @else Waiting for the deposit @endif
                    </summary>
                    @if ($pending)
                        <form method="POST" action="{{ route('admin.ownerships.confirm', $own) }}" class="pf-actions">@csrf<button type="submit" class="pf-btn">Confirm deposit</button></form>
                    @endif
                    @include('admin._pairing-form', ['req' => $req, 'action' => route('admin.pairings.update', $req), 'method' => 'PUT', 'submit' => 'Save changes and email the member'])
                </details>
            @empty
                <x-empty-state title="No pairings yet">Paired members appear here.</x-empty-state>
            @endforelse
        </section>
    </div>
</x-admin-layout>

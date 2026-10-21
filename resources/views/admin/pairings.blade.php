<x-cc.adm title="Pairings" lead="Pair each waiting member with a partner and enter the payment details they will see.">
            @php $proofs = $paired->filter(fn ($r) => $r->deposit_reported_at && ($ownerships[$r->user_id] ?? null)?->settlement_status === \App\Enums\SettlementStatus::PendingSettlement)->count(); @endphp
            <div class="cc-kpis cc-kpis-3" style="margin-bottom:16px">
                <div class="cc-kpi is-coral"><small>Finding a partner</small><b>{{ $waiting->count() }}</b><span>waiting to be paired</span></div>
                <div class="cc-kpi is-amber"><small>Deposits to confirm</small><b>{{ $proofs }}</b><span>members say they sent it</span></div>
                <div class="cc-kpi is-mint"><small>Paired</small><b>{{ $paired->count() }}</b><span>members with a partner</span></div>
            </div>
            <div class="cc-panel">
                <h2>Finding a partner ({{ $waiting->count() }})</h2>
                @forelse ($waiting as $req)
                    <details @if (old('_pid') == $req->id || $loop->first) open @endif>
                        <summary><strong>{{ $req->user->name }}</strong>&nbsp;· {{ \App\Enums\Tier::from($req->tier)->label() }} · asked {{ $req->created_at->diffForHumans() }}</summary>
                        @include('admin._pairing-form', ['req' => $req, 'action' => route('admin.pairings.assign', $req), 'method' => 'POST', 'submit' => 'Pair and email the member'])
                    </details>
                @empty
                    <p>Nobody is waiting. New members appear here when they ask for a partner.</p>
                @endforelse
            </div>

            <div class="cc-panel">
                <h2>Paired ({{ $paired->count() }})</h2>
                @forelse ($paired as $req)
                    @php $own = $ownerships[$req->user_id] ?? null; $pending = $own && $own->settlement_status === \App\Enums\SettlementStatus::PendingSettlement; @endphp
                    <details @if (old('_pid') == $req->id) open @endif>
                        <summary>
                            <strong>{{ $req->user->name }}</strong>&nbsp;paired with&nbsp;<strong>{{ $req->partner_name }}</strong>&nbsp;·
                            @if (! $pending) <span class="cc-tone ok">Deposit confirmed</span>
                            @elseif ($req->proof_path) <span class="cc-tone">Proof of payment received</span>
                            @elseif ($req->deposit_reported_at) <span class="cc-tone">Member says it was sent</span>
                            @else <span class="cc-tone">Waiting for the deposit</span> @endif
                        </summary>
                        @if ($req->deposit_reported_at)
                            <div class="cc-panel is-flat">
                                <h2 style="font-size:22px">Proof of payment</h2>
                                <dl class="cc-dl">
                                    <div class="cc-kv"><dt>Reported</dt><dd>{{ $req->deposit_reported_at->format('M j, Y g:i a') }}</dd></div>
                                    <div class="cc-kv"><dt>Date sent</dt><dd>{{ $req->proof_sent_on?->format('M j, Y') ?? 'Not given' }}</dd></div>
                                    <div class="cc-kv"><dt>Note</dt><dd>{{ $req->proof_note ?: 'None' }}</dd></div>
                                    <div class="cc-kv"><dt>File</dt><dd>{{ $req->proof_name ?: 'No file attached' }}</dd></div>
                                </dl>
                                @if ($req->proof_path)
                                    <div class="cc-step-nav cc-btns is-fit"><a class="af-btn cc-btn-outline" href="{{ route('admin.pairings.proof', $req) }}" target="_blank" rel="noopener">Open the file</a></div>
                                @endif
                            </div>
                        @endif
                        @if ($pending)
                            <div class="cc-step-nav cc-btns is-fit" style="margin-bottom:12px"><form method="POST" action="{{ route('admin.ownerships.confirm', $own) }}">@csrf<button type="submit" class="af-btn af-btn-black" style="width:auto">Confirm deposit</button></form></div>
                        @endif
                        @include('admin._pairing-form', ['req' => $req, 'action' => route('admin.pairings.update', $req), 'method' => 'PUT', 'submit' => 'Save changes and email the member'])
                    </details>
                @empty
                    <p>No pairings yet. Paired members appear here.</p>
                @endforelse
            </div>

            <div class="cc-demo">
                <p>Demo tools (admin only)</p>
                <form method="POST" action="{{ route('admin.demo') }}" class="cc-demo-form">
                    @csrf
                    <div class="cc-field">
                        <label for="demo-machine">Machine</label>
                        <select id="demo-machine" name="machine_id">
                            @foreach ($machines as $m)
                                <option value="{{ $m->id }}">{{ $m->serial_no }} {{ $m->facility_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="btns cc-btns">
                        <button type="submit" name="do" value="advance" class="af-btn af-btn-black">Fast-forward 24 hours</button>
                        <button type="submit" name="do" value="burst" class="af-btn cc-btn-outline">Sales burst</button>
                        <button type="submit" name="do" value="restock" class="af-btn cc-btn-outline">Driver restock</button>
                    </div>
                </form>
            </div>
</x-cc.adm>

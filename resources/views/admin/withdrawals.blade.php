@php $fmt = fn (int $c) => \App\Support\Money::format($c); @endphp
<x-app-layout title="Withdrawals" mode="admin">
    <section class="cc-screen cc-adm" data-bg="gray" style="padding-top:120px">
        <div class="cc-wrap">
            @if (session('status'))
                <x-cc.notice>{{ session('status') }}</x-cc.notice>
            @endif
            <h1 class="cc-title1" style="font-size:clamp(40px,6vw,88px);margin-bottom:28px">Withdrawals</h1>

            <div class="cc-tiles cc-tiles-3">
                <div class="cc-tile"><small>Waiting</small><b>{{ $counts['pending'] ?? 0 }}</b><span>{{ $fmt($pendingCents) }} in total</span></div>
                <div class="cc-tile"><small>Approved</small><b>{{ $counts['approved'] ?? 0 }}</b><span>paid out</span></div>
                <div class="cc-tile"><small>Review goal</small><b>{{ $target }} days</b><span>business days</span></div>
            </div>

            <div class="cc-panel">
                <nav class="cc-tabs" aria-label="Withdrawal status">
                    @foreach ($tabs as $key => $label)
                        <a href="{{ route('admin.withdrawals', ['tab' => $key]) }}" @if ($tab === $key) aria-current="page" @endif>{{ $label }} <span>{{ $counts[$key] ?? 0 }}</span></a>
                    @endforeach
                </nav>

                @forelse ($rows as $w)
                    @php $bag = $errors->getBag('w'.$w->id); @endphp
                    <details @if ($bag->any() || ($tab === 'pending' && $loop->first)) open @endif>
                        <summary>
                            <strong>{{ $w->user->name }}</strong>&nbsp;· {{ $fmt($w->amount_cents) }} ·
                            <span class="cc-tone {{ $w->status === \App\Enums\WithdrawalStatus::Approved ? 'ok' : '' }}">{{ $w->status->label() }}</span>
                        </summary>
                        <dl class="cc-dl">
                            <div class="cc-kv"><dt>Member</dt><dd>{{ $w->user->email }}</dd></div>
                            <div class="cc-kv"><dt>Reference</dt><dd>{{ $w->reference }}</dd></div>
                            <div class="cc-kv"><dt>Pay to</dt><dd>{{ $w->bank_name }}, ending {{ $w->account_last4 }} ({{ $w->holder_name }})</dd></div>
                            <div class="cc-kv"><dt>Asked</dt><dd>{{ ($w->confirmed_at ?? $w->created_at)->format('M j, Y g:i a') }}</dd></div>
                            <div class="cc-kv"><dt>Balance left</dt><dd>{{ $fmt($balances[$w->user_id] ?? 0) }}</dd></div>
                            @if ($w->decided_at)
                                <div class="cc-kv"><dt>Decided</dt><dd>{{ $w->decided_at->format('M j, Y g:i a') }}@if ($w->decider) by {{ $w->decider->name }}@endif</dd></div>
                            @endif
                            @if ($w->paid_reference)<div class="cc-kv"><dt>Payment reference</dt><dd>{{ $w->paid_reference }}</dd></div>@endif
                            @if ($w->admin_note)<div class="cc-kv"><dt>Reason</dt><dd>{{ $w->admin_note }}</dd></div>@endif
                        </dl>
                        @if ($w->status === \App\Enums\WithdrawalStatus::Pending)
                            <div class="cc-two" style="margin-top:16px">
                                <form method="POST" action="{{ route('admin.withdrawals.approve', $w) }}" class="cc-form">
                                    @csrf
                                    <x-cc.field name="paid_reference" :id="'ref-'.$w->id" label="Payment reference" help="Any reference for the transfer, for example an ACH number." :error="$bag->first('paid_reference')" required />
                                    <button type="submit" class="af-btn af-btn-black" style="width:auto">Approve and email the member</button>
                                </form>
                                <form method="POST" action="{{ route('admin.withdrawals.reject', $w) }}" class="cc-form">
                                    @csrf
                                    <x-cc.field name="reason" :id="'why-'.$w->id" label="Reason for declining" help="The member sees this." :error="$bag->first('reason')" required />
                                    <button type="submit" class="af-btn cc-btn-outline" style="width:auto">Decline and return the money</button>
                                </form>
                            </div>
                        @endif
                    </details>
                @empty
                    <p>Nothing here. @if ($tab === 'pending') Requests appear when a member confirms a withdrawal. @endif</p>
                @endforelse

                {{ $rows->links() }}
            </div>

            <div class="cc-demo">
                <p>Withdrawals switch</p>
                <form method="POST" action="{{ route('admin.withdrawals.toggle') }}" class="cc-demo-form">
                    @csrf
                    <input type="hidden" name="enabled" value="{{ $enabled ? 0 : 1 }}">
                    <div class="btns">
                        <button type="submit" class="af-btn {{ $enabled ? 'cc-btn-outline' : 'af-btn-black' }}">{{ $enabled ? 'Pause new withdrawals' : 'Turn withdrawals back on' }}</button>
                    </div>
                    <span class="cc-hint">Now: {{ $enabled ? 'on' : 'paused' }}. Requests already waiting are not affected.</span>
                </form>
            </div>
        </div>
    </section>
</x-app-layout>

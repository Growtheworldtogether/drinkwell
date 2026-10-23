<section class="cc-screen" data-bg="gray">
    <div class="cc-wrap" style="max-width:900px">
        <h1 class="cc-title1" style="font-size:clamp(40px,7vw,96px)">Get started</h1>
        <p class="cc-lead">Sign-up takes a minute.</p>

        <ol class="cc-pill-steps" style="margin-top:32px" aria-label="Sign-up steps">
            @foreach ($steps as $number => $label)
                <li @class(['is-done' => $number < $step, 'is-now' => $number === $step]) @if ($number === $step) aria-current="step" @endif>
                    <button type="button" wire:click="goTo({{ $number }})" @disabled($number >= $step) style="all:unset;display:flex;align-items:center;gap:10px;box-sizing:border-box;min-height:50px;padding:8px 20px 8px 8px;cursor:{{ $number < $step ? 'pointer' : 'default' }}" aria-label="Step {{ $number }}, {{ $label }}"><span>{{ $number }}</span>{{ $label }}</button>
                </li>
            @endforeach
        </ol>

        @error('form')
            <x-cc.notice type="error">{{ $message }}</x-cc.notice>
        @enderror

        <div class="cc-panel">
            <form wire:submit="{{ $step === 4 ? 'submit' : 'next' }}" novalidate class="cc-form">
                @if ($step === 4)
                    <x-form.guard model />
                @endif

                @if ($step === 1)
                    <h2>About you</h2>
                    <div class="cc-two">
                        <x-cc.field name="name" label="Full name" wire:model="name" autocomplete="name" />
                        <x-cc.field name="dob" label="Date of birth" type="date" wire:model="dob" :max="$maxBirthDate" autocomplete="bday" help="You must be {{ config('drinkwell.min_age') }} or older." />
                        <x-cc.field name="phone" label="Phone number" type="tel" wire:model="phone" autocomplete="tel" inputmode="tel" />
                        <x-cc.field name="city" label="City (optional)" wire:model="city" autocomplete="address-level2" />
                    </div>
                @elseif ($step === 2)
                    <h2>Choose your machine</h2>
                    <p>Tick the one you want. You can change it before you finish.</p>
                    <div class="cc-pick-cards">
                        @foreach ($tiers as $key => $t)
                            <label class="cc-pickcard">
                                <input type="radio" name="tier" value="{{ $key }}" wire:model="tier">
                                <span class="tick" aria-hidden="true"></span>
                                <h3>{{ $t['label'] }}</h3>
                                <p class="big">{{ \App\Support\Money::format($t['share']) }}</p>
                                <p>your share a day</p>
                                <p>Your deposit {{ \App\Support\Money::format($t['stake']) }}. Holds {{ $t['capacity'] }} drinks.</p>
                            </label>
                        @endforeach
                    </div>
                    @error('tier')<p class="cc-err" role="alert">{{ $message }}</p>@enderror
                @elseif ($step === 3)
                    <h2>Where should payouts go?</h2>
                    <p>Payouts go to this bank account in the United States or Canada. Your bank numbers are stored encrypted, and only the last four digits are shown back to you.</p>
                    <fieldset class="cc-country" role="radiogroup">
                        <legend>Where is your bank?</legend>
                        <label class="cc-check"><input type="radio" name="bankCountry" value="US" wire:model.live="bankCountry"><span>United States</span></label>
                        <label class="cc-check"><input type="radio" name="bankCountry" value="CA" wire:model.live="bankCountry"><span>Canada</span></label>
                    </fieldset>
                    <div class="cc-two">
                        <x-cc.field name="holderName" label="Account holder" wire:model="holderName" autocomplete="off" />
                        <x-cc.field name="bankName" label="Bank name" wire:model="bankName" autocomplete="off" />
                        @if ($bankCountry === 'CA')
                            <x-cc.field name="transitNumber" label="Transit number" wire:model="transitNumber" autocomplete="off" inputmode="numeric" maxlength="5" help="5 digits, on your cheque or in your banking app." />
                            <x-cc.field name="institutionNumber" label="Institution number" wire:model="institutionNumber" autocomplete="off" inputmode="numeric" maxlength="3" help="3 digits, for example 004." />
                        @else
                            <x-cc.field name="routingNumber" label="Routing number" wire:model="routingNumber" autocomplete="off" inputmode="numeric" maxlength="9" help="Nine digits, as printed on a check." />
                        @endif
                        <x-cc.field name="accountNumber" label="Account number" wire:model="accountNumber" autocomplete="off" inputmode="numeric" maxlength="17" />
                        <x-cc.field name="accountNumberConfirmation" label="Confirm account number" wire:model="accountNumberConfirmation" autocomplete="off" inputmode="numeric" maxlength="17" />
                    </div>
                @else
                    @php $t = $tiers[$tier] ?? null; @endphp
                    <h2>Check your answers</h2>
                    <p>Please read this once, then press the black button. Nothing is sent until you do.</p>
                    <div class="cc-panel is-flat">
                        <h2 class="cc-edit-head" style="font-size:22px"><span>About you</span> <button type="button" class="cc-edit" wire:click="goTo(1)">Change</button></h2>
                        <dl class="cc-dl">
                            <div class="cc-kv"><dt>Name</dt><dd>{{ $name }}</dd></div>
                            <div class="cc-kv"><dt>Date of birth</dt><dd>{{ $dob ? \Illuminate\Support\Carbon::parse($dob)->format('F j, Y') : '' }}</dd></div>
                            <div class="cc-kv"><dt>Phone</dt><dd>{{ $phone }}</dd></div>
                            @if ($city !== '')<div class="cc-kv"><dt>City</dt><dd>{{ $city }}</dd></div>@endif
                        </dl>
                    </div>
                    <div class="cc-panel is-flat">
                        <h2 class="cc-edit-head" style="font-size:22px"><span>Your machine</span> <button type="button" class="cc-edit" wire:click="goTo(2)">Change</button></h2>
                        <dl class="cc-dl">
                            <div class="cc-kv"><dt>Option</dt><dd>{{ $t['label'] ?? '' }}</dd></div>
                            <div class="cc-kv"><dt>Your deposit</dt><dd>{{ $t ? \App\Support\Money::format($t['stake']) : '' }}</dd></div>
                        </dl>
                    </div>
                    <div class="cc-panel is-flat">
                        <h2 class="cc-edit-head" style="font-size:22px"><span>Where payouts go</span> <button type="button" class="cc-edit" wire:click="goTo(3)">Change</button></h2>
                        <dl class="cc-dl">
                            <div class="cc-kv"><dt>Bank</dt><dd>{{ $bankName }} ({{ $bankCountry === 'CA' ? 'Canada' : 'United States' }})</dd></div>
                            <div class="cc-kv"><dt>Account holder</dt><dd>{{ $holderName }}</dd></div>
                        </dl>
                    </div>
                    <h2 style="margin-top:28px;font-size:24px">What happens next</h2>
                    <ol class="cc-next">
                        <li>We find a co-partner for your machine. This usually takes 1 business day.</li>
                        <li>We email you your partner and their payment details.</li>
                        <li>You send your deposit to your partner, then upload your proof of payment.</li>
                    </ol>
                    <x-cc.check name="agreeTerms" label="I accept the terms of use and the privacy notice." wire:model="agreeTerms" />
                    <p class="cc-hint" style="margin:0"><a href="{{ route('terms') }}" target="_blank" rel="noopener" style="font-weight:700;text-decoration:underline">Terms</a> and <a href="{{ route('privacy') }}" target="_blank" rel="noopener" style="font-weight:700;text-decoration:underline">privacy</a> open in a new tab.</p>
                @endif

                <div class="cc-step-nav cc-btns is-fit">
                    @if ($step > 1)
                        <button type="button" class="af-btn cc-btn-outline" wire:click="back">Back</button>
                    @endif
                    <button type="submit" class="af-btn af-btn-black" wire:loading.attr="disabled">{{ $step === 4 ? 'Find me a partner' : 'Continue' }}</button>
                </div>
            </form>
        </div>
    </div>
</section>

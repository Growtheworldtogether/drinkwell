<section class="af-band">
    <div class="af-wrap af-apply">
        <p class="af-apply-meta"><span class="tabular-nums">Step {{ $step }} of 4</span>: {{ $steps[$step] }}</p>
        <h1 class="af-h1">{{ $steps[$step] }}</h1>
        <p class="af-lead">Pairing takes a minute. This is a classroom simulation: no money moves.</p>

        <ol class="af-apply-steps mt-6" aria-label="Onboarding steps">
            @foreach ($steps as $number => $label)
                <li>
                    <button type="button" wire:click="goTo({{ $number }})" @disabled($number > $step) @if ($number === $step) aria-current="step" @endif aria-label="Step {{ $number }}, {{ $label }}">{{ $number }}</button>
                </li>
            @endforeach
        </ol>

        @error('form')
            <x-banner type="error" class="mb-4">{{ $message }}</x-banner>
        @enderror

        <form wire:submit="{{ $step === 4 ? 'submit' : 'next' }}" novalidate>
            @if ($step === 4)
                <x-form.guard model />
            @endif
            <div class="af-apply-form">
                @if ($step === 1)
                    <x-field name="name" label="Full name" wire:model="name" autocomplete="name" />
                    <x-field name="dob" label="Date of birth" type="date" wire:model="dob" :max="$maxBirthDate" autocomplete="bday" class="font-mono" help="Co-partners must be 51 or older." />
                    <x-field name="phone" label="Phone number" type="tel" wire:model="phone" autocomplete="tel" inputmode="tel" />
                    <x-field name="city" label="City (optional)" wire:model="city" autocomplete="address-level2" />
                @elseif ($step === 2)
                    <fieldset class="min-w-0">
                        <legend class="mb-3 text-label uppercase tracking-label text-af-500">Option</legend>
                        <div class="flex flex-col gap-3">
                            @foreach ($tiers as $key => $t)
                                <label class="af-choice">
                                    <input type="radio" name="tier" value="{{ $key }}" wire:model="tier">
                                    <span class="min-w-0">
                                        <span class="block font-medium">{{ $t['label'] }}</span>
                                        <span class="block text-af-700">
                                            Your 50% deposit <span class="font-mono tabular-nums">{{ \App\Support\Money::format($t['stake']) }}</span>.
                                            <span class="font-mono tabular-nums">{{ $t['capacity'] }}</span> drinks a day, about <span class="font-mono tabular-nums">{{ \App\Support\Money::format($t['share']) }}</span> a day to you.
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    @error('tier')<p class="text-af-danger">{{ $message }}</p>@enderror
                @elseif ($step === 3)
                    <p>Payouts go to this US bank account. Your routing and account numbers are stored encrypted, and only the last four digits are shown back to you.</p>
                    <x-field name="holderName" label="Account holder name" wire:model="holderName" autocomplete="off" />
                    <x-field name="bankName" label="Bank name" wire:model="bankName" autocomplete="off" />
                    <x-field name="routingNumber" label="Routing number" wire:model="routingNumber" autocomplete="off" inputmode="numeric" maxlength="9" class="font-mono" help="Nine digits, as printed on a check." />
                    <x-field name="accountNumber" label="Account number" wire:model="accountNumber" autocomplete="off" inputmode="numeric" maxlength="17" class="font-mono" />
                    <x-field name="accountNumberConfirmation" label="Confirm account number" wire:model="accountNumberConfirmation" autocomplete="off" inputmode="numeric" maxlength="17" class="font-mono" />
                    <x-field name="agreeSim" label="I understand this is a classroom simulation and no money is moved from this account." type="checkbox" wire:model="agreeSim" />
                @else
                    <dl class="pf-rows">
                        <div><dt>Name</dt><dd>{{ $name }}</dd></div>
                        <div><dt>Date of birth</dt><dd class="font-mono">{{ $dob }}</dd></div>
                        <div><dt>Option</dt><dd>{{ $tiers[$tier]['label'] }}, deposit <span class="font-mono">{{ \App\Support\Money::format($tiers[$tier]['stake']) }}</span></dd></div>
                    </dl>
                    <p>Pairing assigns you a machine and a co-partner right away. The co-partner is simulated. Your ownership then waits in "pending settlement" until the off-platform deposit is confirmed.</p>
                    <x-field name="agreeTerms" label="I accept the terms and privacy notice." type="checkbox" wire:model="agreeTerms" />
                    <p><a class="underline" href="{{ route('terms') }}" target="_blank" rel="noopener">Terms</a> and <a class="underline" href="{{ route('privacy') }}" target="_blank" rel="noopener">privacy</a> open in a new tab.</p>
                @endif
            </div>
            <div class="af-apply-actions mt-6">
                @if ($step > 1)
                    <button type="button" class="af-btn af-btn-soft" wire:click="back">Back</button>
                @endif
                <button type="submit" class="af-btn af-btn-black" wire:loading.attr="disabled">{{ $step === 4 ? 'Pair me now' : 'Continue' }}</button>
            </div>
        </form>
    </div>
</section>

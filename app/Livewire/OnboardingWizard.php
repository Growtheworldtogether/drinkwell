<?php

namespace App\Livewire;

use App\Domain\Prng;
use App\Enums\Tier;
use App\Exceptions\NoMachineAvailable;
use App\Models\PartnerProfile;
use App\Models\PayoutAccount;
use App\Services\PairingService;
use App\Support\FormGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Four steps: details and age (51 or older), the machine option, a simulated identity and bank check, then pairing.
 * Every step is validated on the server. No real bank details are asked for: the account is generated and fictional.
 */
#[Layout('layouts::marketing')]
#[Title('Get started')]
class OnboardingWizard extends Component
{
    public int $step = 1;

    public string $name = '';

    public string $dob = '';

    public string $phone = '';

    public string $city = '';

    public string $tier = 'commercial';

    public bool $agreeSim = false;

    public bool $agreeTerms = false;

    public string $guardWebsite = '';

    public string $guardToken = '';

    public function mount(): void
    {
        $user = auth()->user();

        if ($user->ownerships()->exists()) {
            $this->redirectRoute('onboard.status', navigate: false);

            return;
        }

        $this->guardToken = FormGuard::token();
        $this->name = (string) $user->name;
        $profile = $user->profile;

        if ($profile) {
            $this->dob = $profile->date_of_birth->toDateString();
            $this->phone = (string) $profile->phone;
            $this->city = (string) $profile->city;
        }
    }

    public function goTo(int $step): void
    {
        $this->step = max(1, min($this->step, $step));
    }

    public function next(): void
    {
        match ($this->step) {
            1 => $this->validateDetails(),
            2 => $this->validate(['tier' => ['required', 'in:commercial,enterprise']]),
            3 => $this->validate(['agreeSim' => ['accepted']], ['agreeSim.accepted' => 'Confirm that you understand this is a simulation.']),
            default => null,
        };

        $this->step = min(4, $this->step + 1);
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function submit(PairingService $pairing): void
    {
        if (! FormGuard::passes($this->guardWebsite, $this->guardToken, 'onboarding-submit', request()->ip())) {
            $this->addError('form', 'Something went wrong. Please try again.');

            return;
        }

        $this->validateDetails();
        $this->validate(['tier' => ['required', 'in:commercial,enterprise']]);
        $this->validate(['agreeSim' => ['accepted'], 'agreeTerms' => ['accepted']], [
            'agreeSim.accepted' => 'Confirm that you understand this is a simulation.',
            'agreeTerms.accepted' => 'Accept the terms to continue.',
        ]);

        $user = auth()->user();
        $dob = Carbon::parse($this->dob);

        PartnerProfile::updateOrCreate(['user_id' => $user->id], [
            'date_of_birth' => $dob->toDateString(),
            'age_at_signup' => (int) $dob->diffInYears(now()),
            'phone' => $this->phone,
            'city' => $this->city !== '' ? $this->city : null,
            'kyc_status' => 'simulated_verified',
            'kyc_verified_at' => now(),
            'is_simulated' => false,
        ]);

        $user->forceFill(['name' => trim($this->name), 'phone' => $this->phone])->save();
        $this->fictionalBank($user->id);

        try {
            $pairing->pair($user->fresh(), Tier::from($this->tier));
        } catch (NoMachineAvailable $e) {
            $this->addError('form', $e->getMessage().' Try the other option or come back later.');

            return;
        } catch (\DomainException) {
            // Already paired in another tab.
        }

        $this->redirectRoute('onboard.status');
    }

    private function validateDetails(): void
    {
        $oldest = now()->subYears((int) config('drinkwell.min_age'))->toDateString();

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'dob' => ['required', 'date', 'after:1900-01-01', 'before_or_equal:'.$oldest],
            'phone' => ['required', 'regex:/^[0-9 ()+\-.]{7,20}$/'],
            'city' => ['nullable', 'string', 'max:80'],
        ], [
            'dob.before_or_equal' => 'DRinkWeLL co-partners must be '.config('drinkwell.min_age').' or older.',
            'phone.regex' => 'Enter a phone number with digits, spaces, dashes or brackets.',
        ], ['dob' => 'date of birth']);
    }

    /** A made-up account so the payout screens have something to show. Nothing the person typed is stored. */
    private function fictionalBank(int $userId): void
    {
        if (PayoutAccount::query()->where('user_id', $userId)->exists()) {
            return;
        }

        $r = new Prng("bank:{$userId}");
        $account = sprintf('%010d', $r->below(1_000_000_000));

        PayoutAccount::create([
            'user_id' => $userId,
            'holder_name' => auth()->user()->name,
            'bank_name' => 'Demo Bank (fictional)',
            'routing_number' => sprintf('%09d', 100_000_000 + $r->below(899_999_999)),
            'account_number' => $account,
            'last4' => substr($account, -4),
            'is_default' => true,
        ]);
    }

    public function render(): View
    {
        return view('livewire.onboarding-wizard', [
            'steps' => [1 => 'About you', 2 => 'Your machine', 3 => 'Identity and bank (simulated)', 4 => 'Review and pair'],
            'maxBirthDate' => now()->subYears((int) config('drinkwell.min_age'))->toDateString(),
            'tiers' => collect(Tier::cases())->mapWithKeys(fn (Tier $t) => [$t->value => [
                'label' => $t->label(), 'stake' => $t->stakeCents(), 'capacity' => $t->capacity(),
                'share' => intdiv($t->grossCents() - $t->cfg()['fee_center_cents'], 2),
            ]])->all(),
        ]);
    }
}

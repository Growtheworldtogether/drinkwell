<?php

namespace App\Livewire;

use App\Enums\Tier;
use App\Models\PartnerProfile;
use App\Models\PayoutAccount;
use App\Rules\AbaRoutingNumber;
use App\Services\PairingService;
use App\Support\FormGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Four steps: details and age (51 or older), the machine option, a bank check, then a request for a partner.
 * Every step is validated on the server. No real bank details are asked for: the account is generated and fictional.
 */
#[Layout('layouts::app', ['mode' => 'focus'])]
#[Title('Get started')]
class OnboardingWizard extends Component
{
    public int $step = 1;

    public string $name = '';

    public string $dob = '';

    public string $phone = '';

    public string $city = '';

    public string $tier = '';

    public string $holderName = '';

    public string $bankName = '';

    public string $routingNumber = '';

    /** US or CA. Canadian banks use a transit number and an institution number instead of a routing number. */
    public string $bankCountry = 'US';

    public string $transitNumber = '';

    public string $institutionNumber = '';

    public string $accountNumber = '';

    public string $accountNumberConfirmation = '';

    public bool $agreeTerms = false;

    public string $guardWebsite = '';

    public string $guardToken = '';

    public function mount(): void
    {
        $user = auth()->user();

        if ($user->ownerships()->exists() || $user->pairingRequest()->exists()) {
            $this->redirectRoute('onboard.status', navigate: false);

            return;
        }

        $this->guardToken = FormGuard::token();
        $this->name = (string) $user->name;
        $this->holderName = (string) $user->name;
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
        $this->resetValidation();
    }

    public function next(): void
    {
        match ($this->step) {
            1 => $this->validateDetails(),
            2 => $this->validate(['tier' => ['required', 'in:commercial,enterprise']], ['tier.required' => 'Please tick a machine to continue.', 'tier.in' => 'Please tick a machine to continue.']),
            3 => $this->validateBank(),
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
        $this->validate(['tier' => ['required', 'in:commercial,enterprise']], ['tier.required' => 'Please tick a machine to continue.']);
        $this->validateBank();
        $this->validate(['agreeTerms' => ['accepted']], ['agreeTerms.accepted' => 'Accept the terms to continue.']);

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
        $this->saveBank($user->id);

        $pairing->request($user->fresh(), Tier::from($this->tier));

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
            'dob.before_or_equal' => app(\App\Services\BrandService::class)->name().' co-partners must be '.config('drinkwell.min_age').' or older.',
            'phone.regex' => 'Enter a phone number with digits, spaces, dashes or brackets.',
        ], ['dob' => 'date of birth']);
    }

    private function validateBank(): void
    {
        $canada = $this->bankCountry === 'CA';
        $this->validate([
            'bankCountry' => ['required', 'in:US,CA'],
            'holderName' => ['required', 'string', 'max:120'],
            'bankName' => ['required', 'string', 'max:120'],
            'routingNumber' => $canada ? ['nullable'] : ['required', 'string', new AbaRoutingNumber],
            'transitNumber' => $canada ? ['required', 'regex:/^\d{5}$/'] : ['nullable'],
            'institutionNumber' => $canada ? ['required', 'regex:/^\d{3}$/'] : ['nullable'],
            'accountNumber' => ['required', 'string', $canada ? 'regex:/^\d{7,12}$/' : 'regex:/^\d{4,17}$/'],
            'accountNumberConfirmation' => ['required', 'same:accountNumber'],
        ], [
            'transitNumber.regex' => 'The transit number is 5 digits.',
            'institutionNumber.regex' => 'The institution number is 3 digits, for example 004.',
            'accountNumber.regex' => $canada ? 'A Canadian account number is 7 to 12 digits.' : 'The account number is 4 to 17 digits.',
            'accountNumberConfirmation.same' => 'The two account numbers do not match.',
        ], ['holderName' => 'account holder name', 'bankName' => 'bank name', 'routingNumber' => 'routing number', 'transitNumber' => 'transit number', 'institutionNumber' => 'institution number', 'accountNumber' => 'account number', 'accountNumberConfirmation' => 'account number confirmation']);
    }

    /** Stored the same way as the earlier platform: routing and account number encrypted, only the last four readable. */
    private function saveBank(int $userId): void
    {
        $number = trim($this->accountNumber);

        PayoutAccount::query()->where('user_id', $userId)->delete();
        PayoutAccount::create([
            'user_id' => $userId,
            'country' => $this->bankCountry === 'CA' ? 'CA' : 'US',
            'holder_name' => trim($this->holderName),
            'bank_name' => trim($this->bankName),
            // Canadian accounts keep transit and institution together, as printed on a cheque: 12345-004.
            'routing_number' => $this->bankCountry === 'CA' ? trim($this->transitNumber).'-'.trim($this->institutionNumber) : trim($this->routingNumber),
            'account_number' => $number,
            'last4' => substr($number, -4),
            'is_default' => true,
        ]);
        $this->accountNumber = $this->accountNumberConfirmation = $this->routingNumber = $this->transitNumber = $this->institutionNumber = '';
    }

    public function render(): View
    {
        return view('livewire.onboarding-wizard', [
            'steps' => [1 => 'About you', 2 => 'Your machine', 3 => 'Bank account', 4 => 'Review and send'],
            'maxBirthDate' => now()->subYears((int) config('drinkwell.min_age'))->toDateString(),
            'tiers' => collect(Tier::cases())->mapWithKeys(fn (Tier $t) => [$t->value => [
                'label' => $t->label(), 'stake' => $t->stakeCents(), 'capacity' => $t->capacity(),
                'share' => intdiv($t->grossCents() - $t->cfg()['fee_center_cents'], 2),
            ]])->all(),
        ]);
    }
}

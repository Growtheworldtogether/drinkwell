<?php

namespace App\Services;

use App\Domain\Prng;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\PartnerProfile;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Makes the fictional co-partners that pairing hands out. Nobody waits for a real second person: a profile is made on
 * the spot from the seeded generator. Names, phone numbers and bank names are invented and the mailbox is on the
 * reserved example.test domain, so nothing here belongs to a real person.
 */
class SimulatedPartners
{
    private const FIRST = ['Margaret', 'Harold', 'Dorothy', 'Walter', 'Eleanor', 'Raymond', 'Beverly', 'Lawrence', 'Gloria', 'Franklin', 'Loretta', 'Chester', 'Marjorie', 'Leonard', 'Phyllis', 'Gerald'];

    private const LAST = ['Whitfield', 'Castellano', 'Okafor', 'Lindqvist', 'Marlowe', 'Brennan', 'Tanaka', 'Delacroix', 'Hargrove', 'Petrosyan', 'Abernathy', 'Kowalski', 'Montgomery', 'Rinaldi', 'Sutherland', 'Valdez'];

    private const BANKS = ['Harbor Federal Credit Union', 'Summit Trust Bank', 'Lakeshore Savings', 'Keystone National', 'Meridian Community Bank'];

    /** The nth simulated partner. The same n always gives the same person; the email is unique per n. */
    public function make(int $n, ?string $city = null): User
    {
        $r = new Prng("partner:{$n}");
        $first = self::FIRST[$r->below(count(self::FIRST))];
        $last = self::LAST[$r->below(count(self::LAST))];
        $dob = now()->subYears(52 + $r->below(24))->subDays($r->below(300))->startOfDay();

        $user = User::create([
            'name' => "{$first} {$last}",
            'email' => Str::lower("{$first}.{$last}.{$n}@example.test"),
            'password' => null,
            'role' => Role::Operator,
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
            'phone' => sprintf('(555) 01%02d-%04d', $r->below(100), 1000 + $r->below(9000)),
        ]);

        PartnerProfile::create([
            'user_id' => $user->id,
            'date_of_birth' => $dob,
            'age_at_signup' => (int) $dob->diffInYears(now()),
            'phone' => $user->phone,
            'city' => $city,
            'kyc_status' => 'simulated_verified',
            'kyc_verified_at' => now(),
            'is_simulated' => true,
        ]);

        return $user;
    }

    public function bank(User $partner): string
    {
        return self::BANKS[crc32($partner->email) % count(self::BANKS)];
    }

    /** The next unused number, so pairing never repeats a person. */
    public function nextNumber(): int
    {
        return (int) User::query()->where('role', Role::Operator)->count() + 1;
    }
}

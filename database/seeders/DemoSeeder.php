<?php

namespace Database\Seeders;

use App\Enums\AssignmentStatus;
use App\Enums\Role;
use App\Enums\SettlementStatus;
use App\Enums\UserStatus;
use App\Models\MachineOwnership;
use App\Models\PartnerProfile;
use App\Models\PayoutAccount;
use App\Models\User;
use App\Models\VendingMachine;
use App\Services\MachineEngine;
use App\Services\SimulatedPartners;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * A lived-in demo: six machines are already paired and have {history_days} days of settled payouts. The first
 * of them belongs to Arthur Pendelton, a verified senior aged 64. Every other owner is a simulated co-partner.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (MachineOwnership::query()->exists()) {
            // Already seeded: running it again only (re)sets Arthur's password from DEMO_PASSWORD, so the demo
            // member can always be signed in to without wiping anything.
            if ($password = env('DEMO_PASSWORD')) {
                User::where('email', 'arthur.pendelton@example.test')->first()?->forceFill(['password' => $password])->save();
                $this->command?->info('Demo member password set from DEMO_PASSWORD.');
            } else {
                $this->command?->warn('DEMO_PASSWORD is empty, so the demo member password was not changed. Or run: php artisan drinkwell:set-password arthur.pendelton@example.test "your-password"');
            }

            return;
        }

        $engine = app(MachineEngine::class);
        $partners = app(SimulatedPartners::class);
        $days = (int) config('drinkwell.history_days');
        $tz = (string) config('drinkwell.timezone');
        $today = now($tz)->startOfDay();
        $password = env('DEMO_PASSWORD');

        $arthur = User::create([
            'name' => 'Arthur Pendelton',
            'email' => 'arthur.pendelton@example.test',
            'password' => $password ?: null,
            'role' => Role::Partner,
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
            'phone' => '(555) 014-2200',
        ]);
        $dob = $today->copy()->subYears(64)->subDays(120);
        PartnerProfile::create([
            'user_id' => $arthur->id, 'date_of_birth' => $dob, 'age_at_signup' => 64, 'phone' => '(555) 014-2200',
            'city' => 'Boston, MA', 'kyc_status' => 'simulated_verified', 'kyc_verified_at' => now(), 'is_simulated' => false,
        ]);
        PayoutAccount::create([
            'user_id' => $arthur->id, 'holder_name' => 'Arthur Pendelton', 'bank_name' => 'Demo Bank (fictional)',
            'routing_number' => '100000001', 'account_number' => '0000004821', 'last4' => '4821', 'is_default' => true,
        ]);

        $machines = VendingMachine::query()->whereIn('serial_no', ['DW-C-0001', 'DW-C-0002', 'DW-C-0003', 'DW-C-0004', 'DW-E-0001', 'DW-E-0002'])->orderBy('id')->get();
        $n = 1;

        foreach ($machines as $i => $machine) {
            DB::transaction(function () use ($machine, $i, $arthur, $partners, $engine, $days, $today, &$n) {
                $start = $today->copy()->subDays($days);
                $first = $i === 0 ? $arthur : $partners->make($n++, $machine->city);
                $second = $partners->make($n++, $machine->city);

                foreach ([[$first, 1], [$second, 2]] as [$user, $slot]) {
                    MachineOwnership::create([
                        'machine_id' => $machine->id, 'user_id' => $user->id, 'slot_no' => $slot,
                        'capital_cents' => $machine->tier->stakeCents(),
                        'settlement_status' => SettlementStatus::Settled,
                        'earning_from' => $start->toDateString(), 'settled_at' => $start,
                    ]);
                }

                $keep = $machine->operating_date;
                $minute = $machine->day_minute;
                for ($d = 0; $d < $days; $d++) {
                    $machine->operating_date = $start->copy()->addDays($d);
                    $engine->settleDay($machine);
                }
                $machine->operating_date = $keep;
                $machine->day_minute = $minute;
                $machine->assignment_status = AssignmentStatus::Active;
                $machine->save();
            });
        }
    }
}

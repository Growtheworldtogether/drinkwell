<?php

use App\Enums\Role;
use App\Enums\SettlementStatus;
use App\Models\MachineOwnership;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

/**
 * Members now get the dashboard only once their deposit is confirmed. Anyone who was paired but has not had the
 * deposit confirmed yet goes back to the progress page until it is.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pending = MachineOwnership::query()->where('settlement_status', SettlementStatus::PendingSettlement)->pluck('user_id');
        User::query()->whereIn('id', $pending)->where('role', Role::Partner)->update(['role' => Role::Applicant]);
    }

    public function down(): void
    {
        $pending = MachineOwnership::query()->where('settlement_status', SettlementStatus::PendingSettlement)->pluck('user_id');
        User::query()->whereIn('id', $pending)->where('role', Role::Applicant)->update(['role' => Role::Partner]);
    }
};

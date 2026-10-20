<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyDistribution extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'machine_day_id', 'machine_id', 'user_id', 'ownership_id', 'distribution_date', 'payout_cents', 'ach_ref',
    ];

    protected function casts(): array
    {
        return ['distribution_date' => 'date', 'payout_cents' => 'integer'];
    }

    /** @return BelongsTo<VendingMachine, $this> */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(VendingMachine::class, 'machine_id');
    }

    /** @return BelongsTo<MachineDay, $this> */
    public function day(): BelongsTo
    {
        return $this->belongsTo(MachineDay::class, 'machine_day_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

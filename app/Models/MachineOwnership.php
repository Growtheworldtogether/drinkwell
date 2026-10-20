<?php

namespace App\Models;

use App\Enums\SettlementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineOwnership extends Model
{
    protected $fillable = [
        'machine_id', 'user_id', 'slot_no', 'capital_cents', 'settlement_status', 'earning_from', 'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'settlement_status' => SettlementStatus::class,
            'slot_no' => 'integer',
            'capital_cents' => 'integer',
            'earning_from' => 'date',
            'settled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<VendingMachine, $this> */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(VendingMachine::class, 'machine_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    protected $fillable = [
        'user_id', 'amount_cents', 'bank_name', 'holder_name', 'account_last4', 'status', 'reference',
        'confirmed_at', 'decided_by', 'decided_at', 'admin_note', 'paid_reference',
    ];

    protected function casts(): array
    {
        return [
            'status' => WithdrawalStatus::class,
            'amount_cents' => 'integer',
            'confirmed_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}

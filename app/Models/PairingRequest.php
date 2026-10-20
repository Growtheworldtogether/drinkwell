<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Hidden(['routing_number', 'account_number'])]
class PairingRequest extends Model
{
    public const FINDING = 'finding';

    public const PAIRED = 'paired';

    protected $fillable = [
        'user_id', 'tier', 'status', 'partner_name', 'partner_city', 'account_holder', 'bank_name', 'routing_number',
        'account_number', 'account_last4', 'reference', 'note', 'paired_at', 'deposit_reported_at',
    ];

    protected function casts(): array
    {
        return [
            'routing_number' => 'encrypted',
            'account_number' => 'encrypted',
            'paired_at' => 'datetime',
            'deposit_reported_at' => 'datetime',
        ];
    }

    public function isPaired(): bool
    {
        return $this->status === self::PAIRED;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

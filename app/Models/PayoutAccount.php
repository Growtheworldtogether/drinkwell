<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Hidden(['routing_number', 'account_number'])]
class PayoutAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'country', 'holder_name', 'bank_name', 'routing_number', 'account_number', 'last4', 'is_default',
    ];

    protected function casts(): array
    {
        return [
            'routing_number' => 'encrypted',
            'account_number' => 'encrypted',
            'is_default' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

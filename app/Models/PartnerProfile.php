<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerProfile extends Model
{
    protected $fillable = [
        'user_id', 'date_of_birth', 'age_at_signup', 'phone', 'city', 'kyc_status', 'kyc_verified_at', 'is_simulated',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'age_at_signup' => 'integer',
            'kyc_verified_at' => 'datetime',
            'is_simulated' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use App\Enums\OtpPurpose;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Hidden(['code_hash'])]
class OtpCode extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'email', 'purpose', 'subject_id', 'code_hash', 'attempts', 'expires_at', 'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}

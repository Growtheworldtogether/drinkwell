<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use HasFactory;

    public const CREATED_AT = null;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'key', 'value', 'updated_by',
    ];

    /** Always stored as JSON text, so null is the JSON literal `null` and not SQL NULL. */
    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn (?string $raw) => $raw === null ? null : json_decode($raw, true),
            set: fn (mixed $value) => json_encode($value),
        );
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

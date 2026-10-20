<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MachineDay extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['machine_id', 'operating_date', 'units_sold', 'gross_cents', 'fee_cents', 'pool_cents'];

    protected function casts(): array
    {
        return [
            'operating_date' => 'date',
            'units_sold' => 'integer',
            'gross_cents' => 'integer',
            'fee_cents' => 'integer',
            'pool_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<VendingMachine, $this> */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(VendingMachine::class, 'machine_id');
    }

    /** @return HasMany<DailyDistribution, $this> */
    public function distributions(): HasMany
    {
        return $this->hasMany(DailyDistribution::class);
    }
}

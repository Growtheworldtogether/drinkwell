<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelemetryLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['machine_id', 'event', 'message', 'context', 'logged_at'];

    protected function casts(): array
    {
        return ['context' => 'array', 'logged_at' => 'datetime'];
    }

    /** @return BelongsTo<VendingMachine, $this> */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(VendingMachine::class, 'machine_id');
    }
}

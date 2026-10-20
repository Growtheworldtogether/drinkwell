<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use App\Enums\HardwareState;
use App\Enums\Tier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendingMachine extends Model
{
    protected $fillable = [
        'serial_no', 'tier', 'capacity', 'facility_name', 'facility_type', 'city', 'latitude', 'longitude',
        'stock', 'temp_c', 'hardware_state', 'assignment_status', 'operating_date', 'day_minute', 'clock_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'tier' => Tier::class,
            'hardware_state' => HardwareState::class,
            'assignment_status' => AssignmentStatus::class,
            'capacity' => 'integer',
            'stock' => 'integer',
            'day_minute' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'temp_c' => 'float',
            'operating_date' => 'date',
            'clock_synced_at' => 'datetime',
        ];
    }

    /** @return HasMany<MachineOwnership, $this> */
    public function ownerships(): HasMany
    {
        return $this->hasMany(MachineOwnership::class, 'machine_id');
    }

    /** @return HasMany<MachineDay, $this> */
    public function days(): HasMany
    {
        return $this->hasMany(MachineDay::class, 'machine_id');
    }

    /** @return HasMany<DailyDistribution, $this> */
    public function distributions(): HasMany
    {
        return $this->hasMany(DailyDistribution::class, 'machine_id');
    }

    /** @return HasMany<TelemetryLog, $this> */
    public function logs(): HasMany
    {
        return $this->hasMany(TelemetryLog::class, 'machine_id');
    }
}

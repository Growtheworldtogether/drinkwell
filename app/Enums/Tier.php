<?php

namespace App\Enums;

enum Tier: string
{
    case Commercial = 'commercial';
    case Enterprise = 'enterprise';

    public function cfg(): array
    {
        return config('drinkwell.tiers.'.$this->value);
    }

    public function label(): string
    {
        return $this->cfg()['label'];
    }

    public function capacity(): int
    {
        return $this->cfg()['capacity'];
    }

    public function stakeCents(): int
    {
        return $this->cfg()['stake_cents'];
    }

    public function machineCostCents(): int
    {
        return $this->cfg()['machine_cost_cents'];
    }

    public function grossCents(): int
    {
        return $this->capacity() * (int) config('drinkwell.unit_price_cents');
    }
}

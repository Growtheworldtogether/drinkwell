<?php

namespace App\Enums;

enum SettlementStatus: string
{
    case PendingSettlement = 'pending_settlement';
    case Settled = 'settled';

    public function label(): string
    {
        return match ($this) {
            self::PendingSettlement => 'Pending settlement',
            self::Settled => 'Settled',
        };
    }
}

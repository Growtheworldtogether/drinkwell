<?php

namespace App\Enums;

enum HardwareState: string
{
    case Online = 'online';
    case LowStock = 'low_stock';
    case SoldOut = 'sold_out';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::LowStock => 'Low stock',
            self::SoldOut => 'Sold out',
        };
    }
}

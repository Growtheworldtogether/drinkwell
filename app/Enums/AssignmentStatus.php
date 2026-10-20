<?php

namespace App\Enums;

enum AssignmentStatus: string
{
    case Open = 'open';
    case Paired = 'paired';
    case Active = 'active';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}

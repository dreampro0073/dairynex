<?php

namespace App\Enums;

enum DeliveryStatus: int
{
    case Pending = 1;
    case PinVerified = 2;
    case Delivered = 3;
    case Skipped = 4;
    case Cancelled = 5;
    case Failed = 6;
    case CustomerUnavailable = 7;
    case Partial = 8;

    public function label(): string
    {
        return match ($this) {
            self::PinVerified => 'PIN verified', self::CustomerUnavailable => 'Customer unavailable',
            default => ucfirst($this->name),
        };
    }
}

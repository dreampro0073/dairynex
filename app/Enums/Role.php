<?php

namespace App\Enums;

// Preserve reference IDs from dairynex.sql. Legacy IDs 3/4 confer no access.
enum Role: int
{
    case SuperAdmin = 1;
    case ClientAdmin = 2;
    case DeliveryBoy = 5;
    case Customer = 6;

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'super_admin',
            self::ClientAdmin => 'client_admin',
            self::DeliveryBoy => 'delivery_boy',
            self::Customer => 'customer',
        };
    }

    public function home(): string
    {
        return match ($this) {
            self::SuperAdmin => '/platform',
            self::ClientAdmin => '/dashboard',
            self::DeliveryBoy => '/delivery',
            self::Customer => '/customer',
        };
    }
}

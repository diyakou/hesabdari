<?php

namespace App\Enums;

enum Ownership: string
{
    case Shop = 'shop';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Shop => 'متعلق به فروشگاه',
            self::Customer => 'متعلق به مشتری (خدماتی)',
        };
    }
}

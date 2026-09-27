<?php

namespace App\Enums;

enum PartyType: string
{
    case Individual = 'individual';
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'شخص حقیقی',
            self::Company => 'شخص حقوقی',
        };
    }
}

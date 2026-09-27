<?php

namespace App\Enums;

enum RegistryStatus: string
{
    case Unknown = 'unknown';
    case Registered = 'registered';
    case Unregistered = 'unregistered';

    public function label(): string
    {
        return match ($this) {
            self::Unknown => 'نامشخص',
            self::Registered => 'رجیستر شده',
            self::Unregistered => 'رجیستر نشده / مسافری',
        };
    }
}

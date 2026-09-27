<?php

namespace App\Enums;

enum DeviceCondition: string
{
    case New = 'new';
    case Used = 'used';

    public function label(): string
    {
        return match ($this) {
            self::New => 'نو (آکبند)',
            self::Used => 'کارکرده (دست دوم)',
        };
    }
}

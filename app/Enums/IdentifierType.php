<?php

namespace App\Enums;

enum IdentifierType: string
{
    case Imei = 'imei';
    case Serial = 'serial';

    public function label(): string
    {
        return match ($this) {
            self::Imei => 'شناسه IMEI',
            self::Serial => 'شماره سریال',
        };
    }
}

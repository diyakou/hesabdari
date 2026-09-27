<?php

namespace App\Enums;

enum ProductType: string
{
    case Stock = 'stock';
    case Serialized = 'serialized';
    case Service = 'service';

    public function label(): string
    {
        return match ($this) {
            self::Stock => 'کالای تعدادی',
            self::Serialized => 'دستگاه سریالی / گوشی',
            self::Service => 'خدمات',
        };
    }
}

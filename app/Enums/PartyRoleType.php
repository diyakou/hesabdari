<?php

namespace App\Enums;

enum PartyRoleType: string
{
    case Customer = 'customer';
    case Supplier = 'supplier';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'مشتری',
            self::Supplier => 'تأمین‌کننده',
        };
    }
}

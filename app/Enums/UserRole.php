<?php

namespace App\Enums;

enum UserRole: string
{
    case Manager = 'manager';
    case Accountant = 'accountant';
    case Salesperson = 'salesperson';
    case WarehouseKeeper = 'warehouse_keeper';

    public function label(): string
    {
        return match ($this) {
            self::Manager => 'مدیر',
            self::Accountant => 'حسابدار',
            self::Salesperson => 'فروشنده',
            self::WarehouseKeeper => 'انباردار',
        };
    }

    public function persianLabel(): string
    {
        return $this->label();
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role): array => [$role->value => $role->label()])
            ->all();
    }
}

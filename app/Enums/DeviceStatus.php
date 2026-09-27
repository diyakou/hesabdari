<?php

namespace App\Enums;

enum DeviceStatus: string
{
    case PendingReceipt = 'pending_receipt';
    case Available = 'available';
    case Sold = 'sold';
    case Quarantine = 'quarantine';
    case ReturnedToSupplier = 'returned_to_supplier';

    public function label(): string
    {
        return match ($this) {
            self::PendingReceipt => 'در انتظار ورود',
            self::Available => 'موجود در انبار',
            self::Sold => 'فروخته‌شده',
            self::Quarantine => 'قرنطینه / بررسی',
            self::ReturnedToSupplier => 'مرجوع به تأمین‌کننده',
        };
    }
}

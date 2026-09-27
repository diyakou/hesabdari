<?php

namespace App\Support\Localization;

final class LocalizedDigits
{
    private const LOCALIZED_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    private const ASCII_DIGITS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    public static function toAscii(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return str_replace(self::LOCALIZED_DIGITS, self::ASCII_DIGITS, $value);
    }
}

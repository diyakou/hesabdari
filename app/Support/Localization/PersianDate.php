<?php

namespace App\Support\Localization;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use IntlDateFormatter;

final class PersianDate
{
    /** @var array<string, IntlDateFormatter> */
    private static array $formatters = [];

    public static function format(DateTimeInterface|string|null $date, bool $withTime = false): string
    {
        if ($date === null || $date === '') {
            return '—';
        }

        $value = $date instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($date)
            : new DateTimeImmutable($date, new DateTimeZone(config('app.timezone', 'Asia/Tehran')));

        $value = $value->setTimezone(new DateTimeZone('Asia/Tehran'));
        $pattern = $withTime ? 'yyyy/MM/dd HH:mm' : 'yyyy/MM/dd';

        self::$formatters[$pattern] ??= new IntlDateFormatter(
            'fa_IR@calendar=persian',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            'Asia/Tehran',
            IntlDateFormatter::TRADITIONAL,
            $pattern,
        );

        return self::$formatters[$pattern]->format($value) ?: '—';
    }
}

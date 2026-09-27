<?php

namespace App\Support\Localization;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use IntlDateFormatter;
use InvalidArgumentException;

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

    public static function toGregorian(string $date): string
    {
        $date = trim((string) LocalizedDigits::toAscii($date));
        if (! preg_match('/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})$/', $date, $parts)) {
            throw new InvalidArgumentException('فرمت تاریخ باید به‌شکل ۱۴۰۵/۰۷/۰۵ باشد.');
        }

        if ((int) $parts[1] >= 1700) {
            return sprintf('%04d-%02d-%02d', $parts[1], $parts[2], $parts[3]);
        }

        $formatter = new IntlDateFormatter(
            'fa_IR@calendar=persian',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            'Asia/Tehran',
            IntlDateFormatter::TRADITIONAL,
            'yyyy/MM/dd',
        );
        $formatter->setLenient(false);
        $position = 0;
        $timestamp = $formatter->parse(sprintf('%04d/%02d/%02d', $parts[1], $parts[2], $parts[3]), $position);

        if ($timestamp === false) {
            throw new InvalidArgumentException('تاریخ شمسی واردشده معتبر نیست.');
        }

        return (new DateTimeImmutable('@'.$timestamp))
            ->setTimezone(new DateTimeZone('Asia/Tehran'))
            ->format('Y-m-d');
    }
}

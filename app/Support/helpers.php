<?php

use App\Support\Localization\PersianDate;

if (! function_exists('persian_date')) {
    function persian_date(\DateTimeInterface|string|null $date, bool $withTime = false): string
    {
        return PersianDate::format($date, $withTime);
    }
}

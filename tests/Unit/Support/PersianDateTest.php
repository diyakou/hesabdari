<?php

namespace Tests\Unit\Support;

use App\Support\Localization\LocalizedDigits;
use App\Support\Localization\PersianDate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PersianDateTest extends TestCase
{
    #[Test]
    public function it_formats_a_gregorian_date_as_a_persian_date(): void
    {
        $formatted = PersianDate::format('2026-03-21');

        $this->assertSame('1405/01/01', LocalizedDigits::toAscii($formatted));
    }
}

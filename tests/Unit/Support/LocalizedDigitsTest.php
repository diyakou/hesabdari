<?php

namespace Tests\Unit\Support;

use App\Support\Localization\LocalizedDigits;
use PHPUnit\Framework\TestCase;

class LocalizedDigitsTest extends TestCase
{
    public function test_it_converts_persian_and_arabic_digits_without_removing_leading_zeros(): void
    {
        $this->assertSame('09123456789', LocalizedDigits::toAscii('۰۹۱۲۳۴۵۶۷۸۹'));
        $this->assertSame('0123456789', LocalizedDigits::toAscii('٠١٢٣٤٥٦٧٨٩'));
        $this->assertSame('+989121234567', LocalizedDigits::toAscii('+۹۸۹۱۲۱۲۳۴۵۶۷'));
        $this->assertNull(LocalizedDigits::toAscii(null));
    }
}

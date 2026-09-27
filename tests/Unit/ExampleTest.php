<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_all_supported_roles_have_persian_labels(): void
    {
        $this->assertSame([
            'manager' => 'مدیر',
            'accountant' => 'حسابدار',
            'salesperson' => 'فروشنده',
            'warehouse_keeper' => 'انباردار',
        ], UserRole::options());
    }
}

<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\StoreSetting;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreStructureSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            StoreSetting::query()->updateOrCreate(
                ['key' => 'primary'],
                [
                    'name' => 'فروشگاه موبایل',
                    'timezone' => 'Asia/Tehran',
                    'storage_currency' => 'IRR',
                    'display_currency' => 'IRT',
                ],
            );

            $branch = Branch::query()->updateOrCreate(
                ['code' => 'MAIN'],
                [
                    'name' => 'شعبه اصلی',
                    'is_active' => true,
                    'is_default' => true,
                ],
            );

            Warehouse::query()->updateOrCreate(
                ['code' => 'MAIN-WH'],
                [
                    'branch_id' => $branch->getKey(),
                    'name' => 'انبار اصلی',
                    'is_active' => true,
                    'is_default' => true,
                ],
            );
        });
    }
}

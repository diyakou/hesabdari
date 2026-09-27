<?php

namespace Tests\Feature\Settings;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\StoreSetting;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_primary_store_branch_and_warehouse_atomically(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->put(route('settings.update'), $this->validSettings([
            'store_phone' => '۰۲۱۱۲۳۴۵۶۷۸',
            'branch_phone' => '٠٩١٢٣٤٥٦٧٨٩',
        ]))->assertRedirect(route('settings.edit'))
            ->assertSessionHasNoErrors();

        $store = StoreSetting::query()->sole();
        $branch = Branch::query()->sole();
        $warehouse = Warehouse::query()->sole();

        $this->assertSame('primary', $store->key);
        $this->assertSame('02112345678', $store->phone);
        $this->assertSame('Asia/Tehran', $store->timezone);
        $this->assertSame('IRR', $store->storage_currency);
        $this->assertSame('IRT', $store->display_currency);
        $this->assertSame('MAIN', $branch->code);
        $this->assertSame('09123456789', $branch->phone);
        $this->assertTrue($branch->is_active);
        $this->assertTrue($branch->is_default);
        $this->assertSame('MAIN-WH', $warehouse->code);
        $this->assertTrue($warehouse->branch->is($branch));
        $this->assertTrue($warehouse->is_active);
        $this->assertTrue($warehouse->is_default);

        $this->assertDatabaseCount('audit_logs', 3);
        $this->assertSame(
            ['branch.updated', 'store_settings.updated', 'warehouse.updated'],
            AuditLog::query()->orderBy('action')->pluck('action')->all(),
        );
        $this->assertTrue(AuditLog::query()->get()->every(
            fn (AuditLog $log): bool => $log->actor_user_id === $manager->getKey(),
        ));
    }

    public function test_repeated_settings_updates_do_not_create_duplicate_primary_records(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->put(route('settings.update'), $this->validSettings());
        $this->actingAs($manager)->put(route('settings.update'), $this->validSettings([
            'store_name' => 'فروشگاه به‌روز',
            'branch_name' => 'شعبه مرکزی',
            'warehouse_name' => 'انبار مرکزی',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('store_settings', 1);
        $this->assertDatabaseCount('branches', 1);
        $this->assertDatabaseCount('warehouses', 1);
        $this->assertDatabaseHas('store_settings', ['key' => 'primary', 'name' => 'فروشگاه به‌روز']);
        $this->assertDatabaseHas('branches', ['code' => 'MAIN', 'name' => 'شعبه مرکزی']);
        $this->assertDatabaseHas('warehouses', ['code' => 'MAIN-WH', 'name' => 'انبار مرکزی']);
        $this->assertDatabaseCount('audit_logs', 6);
    }

    public function test_invalid_settings_are_rejected_without_partial_writes(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->put(route('settings.update'), $this->validSettings([
            'store_name' => '',
            'store_phone' => '12-invalid',
        ]))->assertSessionHasErrors(['store_name', 'store_phone']);

        $this->assertDatabaseCount('store_settings', 0);
        $this->assertDatabaseCount('branches', 0);
        $this->assertDatabaseCount('warehouses', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    /** @param array<string, string|null> $overrides */
    private function validSettings(array $overrides = []): array
    {
        return array_merge([
            'store_name' => 'فروشگاه همراه',
            'legal_name' => null,
            'store_phone' => '02112345678',
            'store_address' => 'تهران',
            'branch_name' => 'شعبه اصلی',
            'branch_phone' => '09123456789',
            'branch_address' => 'تهران',
            'warehouse_name' => 'انبار اصلی',
        ], $overrides);
    }
}

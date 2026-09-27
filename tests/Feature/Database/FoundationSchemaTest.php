<?php

namespace Tests\Feature\Database;

use App\Models\Branch;
use App\Models\StoreSetting;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FoundationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_a_schema_contains_required_tables_and_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('users', ['role', 'is_active']));
        $this->assertTrue(Schema::hasColumns('store_settings', [
            'key', 'name', 'timezone', 'storage_currency', 'display_currency',
        ]));
        $this->assertTrue(Schema::hasColumns('branches', ['code', 'name', 'is_active', 'is_default']));
        $this->assertTrue(Schema::hasColumns('warehouses', ['branch_id', 'code', 'name', 'is_active', 'is_default']));
        $this->assertTrue(Schema::hasColumns('audit_logs', [
            'actor_user_id', 'action', 'auditable_type', 'auditable_id', 'changes', 'correlation_id',
        ]));
    }

    public function test_database_seeder_creates_only_idempotent_store_structure_and_no_user(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('store_settings', 1);
        $this->assertDatabaseCount('branches', 1);
        $this->assertDatabaseCount('warehouses', 1);

        $store = StoreSetting::query()->sole();
        $branch = Branch::query()->sole();
        $warehouse = Warehouse::query()->sole();

        $this->assertSame('primary', $store->key);
        $this->assertSame('MAIN', $branch->code);
        $this->assertSame('MAIN-WH', $warehouse->code);
        $this->assertTrue($warehouse->branch->is($branch));
    }
}

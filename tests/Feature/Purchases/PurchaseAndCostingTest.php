<?php

namespace Tests\Feature\Purchases;

use App\Actions\Accounting\RecordJournalEntryAction;
use App\Actions\Inventory\UpdateStockAction;
use App\Actions\Purchases\FinalizePurchaseAction;
use App\Enums\DeviceCondition;
use App\Enums\DeviceStatus;
use App\Enums\PartyRoleType;
use App\Enums\ProductType;
use App\Enums\UserRole;
use App\Models\Device;
use App\Models\DeviceIdentifier;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\JournalEntry;
use App\Models\Party;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\StoreStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PurchaseAndCostingTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private Party $supplier;
    private Warehouse $warehouse;
    private ProductVariant $phoneVariant;
    private ProductVariant $caseVariant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(StoreStructureSeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);

        $this->manager = User::factory()->create([
            'role' => UserRole::Manager,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::first();

        $this->supplier = Party::create([
            'name' => 'تأمین‌کننده اصلی',
            'type' => 'company',
            'is_active' => true,
        ]);
        $this->supplier->roles()->create(['role' => PartyRoleType::Supplier]);

        // Serialized phone
        $phone = Product::create([
            'name' => 'iPhone 13',
            'type' => ProductType::Serialized,
            'is_active' => true,
        ]);
        $this->phoneVariant = ProductVariant::create([
            'product_id' => $phone->id,
            'sku' => 'IP13-128',
            'selling_price_rials' => 450000000,
        ]);

        // Stock item (accessory)
        $case = Product::create([
            'name' => 'قاب محافظ سیلیکونی',
            'type' => ProductType::Stock,
            'is_active' => true,
        ]);
        $this->caseVariant = ProductVariant::create([
            'product_id' => $case->id,
            'sku' => 'CASE-SILICONE',
            'selling_price_rials' => 2000000,
        ]);
    }

    /**
     * Requirement 6:
     * پیش‌نویس خرید موجودی ایجاد نکند؛ نهایی‌سازی دقیقاً یک‌بار موجودی ایجاد کند.
     */
    public function test_purchase_draft_creates_no_stock_and_finalization_creates_stock_once(): void
    {
        $device = Device::create([
            'product_variant_id' => $this->phoneVariant->id,
            'physical_condition' => DeviceCondition::New,
            'operational_status' => DeviceStatus::PendingReceipt,
        ]);
        DeviceIdentifier::create([
            'device_id' => $device->id,
            'type' => 'imei',
            'position' => 'primary',
            'value' => '352094101111111',
        ]);

        $invoice = Invoice::create([
            'type' => 'purchase',
            'invoice_number' => 'PUR-TEST-001',
            'party_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal_rials' => 400000000,
            'total_amount_rials' => 400000000,
            'created_by' => $this->manager->id,
        ]);

        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->phoneVariant->product_id,
            'product_variant_id' => $this->phoneVariant->id,
            'device_id' => $device->id,
            'quantity' => 1,
            'unit_price_rials' => 400000000,
        ]);

        // Verify draft creates NO stock or available device
        $device->refresh();
        $this->assertEquals(DeviceStatus::PendingReceipt, $device->operational_status);
        $this->assertDatabaseMissing('journal_entries', ['source_id' => $invoice->id]);

        // Finalize purchase
        $action = app(FinalizePurchaseAction::class);
        $action->execute($invoice, $this->manager);

        // Verify device is available and journal entry created
        $device->refresh();
        $this->assertEquals(DeviceStatus::Available, $device->operational_status);
        $this->assertEquals($this->warehouse->id, $device->warehouse_id);

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'finalized']);
        $this->assertDatabaseHas('journal_entries', ['source_id' => $invoice->id]);

        // Repeating finalization should not duplicate
        $action->execute($invoice, $this->manager);
        $this->assertEquals(1, JournalEntry::where('source_id', $invoice->id)->count());
    }

    /**
     * Requirement 11:
     * خرید ۲ واحد با قیمت ۱۰۰ و ۲ واحد با قیمت ۲۰۰ ریال، موجودی ۴ و ارزش ۶۰۰ بسازد؛ فروش ۱ واحد، بهای خروج ۱۵۰ ثبت کند.
     */
    public function test_moving_weighted_average_cost_calculation(): void
    {
        $updateStock = app(UpdateStockAction::class);

        // 1. Purchase 2 units at 100 rials
        $updateStock->increase(
            $this->caseVariant->id,
            $this->warehouse->id,
            2,
            200, // total cost = 2 * 100
            'test',
            1,
            'خرید اول'
        );

        $balance = StockBalance::where('product_variant_id', $this->caseVariant->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertEquals(2, $balance->quantity);
        $this->assertEquals(200, $balance->total_cost_rials);

        // 2. Purchase 2 units at 200 rials (total 400 rials)
        $updateStock->increase(
            $this->caseVariant->id,
            $this->warehouse->id,
            2,
            400,
            'test',
            2,
            'خرید دوم'
        );

        $balance->refresh();
        $this->assertEquals(4, $balance->quantity);
        $this->assertEquals(600, $balance->total_cost_rials);

        // 3. Exit 1 unit: unit cost must be 600 / 4 = 150 rials
        $exitResult = $updateStock->decrease(
            $this->caseVariant->id,
            $this->warehouse->id,
            1,
            'test',
            3,
            'فروش یک واحد'
        );

        $this->assertEquals(150, $exitResult['unit_cost_rials']);
        $this->assertEquals(150, $exitResult['total_cost_rials']);

        $balance->refresh();
        $this->assertEquals(3, $balance->quantity);
        $this->assertEquals(450, $balance->total_cost_rials);
    }

    /**
     * Requirement 12:
     * در مثال ارزش موجودی تقسیم‌ناپذیر، خروج آخرین واحد هیچ ارزش باقیمانده‌ای نگذارد.
     */
    public function test_indivisible_inventory_cost_clears_to_zero_on_last_unit_exit(): void
    {
        $updateStock = app(UpdateStockAction::class);

        // Purchase 3 units for 100 rials (100 / 3 = 33.33... not evenly divisible)
        $updateStock->increase(
            $this->caseVariant->id,
            $this->warehouse->id,
            3,
            100,
            'test',
            10,
            'خرید با بهای تقسیم‌ناپذیر'
        );

        // Exit 2 units: floor(100 / 3) = 33 each -> 66 total
        $updateStock->decrease(
            $this->caseVariant->id,
            $this->warehouse->id,
            2,
            'test',
            11,
            'خروج دو واحد'
        );

        $balance = StockBalance::where('product_variant_id', $this->caseVariant->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertEquals(1, $balance->quantity);
        $this->assertEquals(34, $balance->total_cost_rials); // 100 - 66 = 34

        // Exit the LAST unit: must clear ALL remaining 34 rials to 0
        $lastExit = $updateStock->decrease(
            $this->caseVariant->id,
            $this->warehouse->id,
            1,
            'test',
            12,
            'خروج آخرین واحد'
        );

        $this->assertEquals(34, $lastExit['total_cost_rials']);

        $balance->refresh();
        $this->assertEquals(0, $balance->quantity);
        $this->assertEquals(0, $balance->total_cost_rials);
    }

    /**
     * Requirement 14 (partial for purchase):
     * سرشکن کردن دقیق هزینه جانبی خرید بین ردیف‌ها
     */
    public function test_landed_costs_distributed_proportionally_with_exact_remainder_handling(): void
    {
        $invoice = Invoice::create([
            'type' => 'purchase',
            'invoice_number' => 'PUR-LANDED-001',
            'party_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal_rials' => 300,
            'discount_rials' => 0,
            'additional_cost_rials' => 100, // 100 rials landed cost across 3 items
            'total_amount_rials' => 400,
            'created_by' => $this->manager->id,
        ]);

        $line1 = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->caseVariant->product_id,
            'product_variant_id' => $this->caseVariant->id,
            'quantity' => 1,
            'unit_price_rials' => 100,
        ]);

        $line2 = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->caseVariant->product_id,
            'product_variant_id' => $this->caseVariant->id,
            'quantity' => 1,
            'unit_price_rials' => 100,
        ]);

        $line3 = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->caseVariant->product_id,
            'product_variant_id' => $this->caseVariant->id,
            'quantity' => 1,
            'unit_price_rials' => 100,
        ]);

        $action = app(FinalizePurchaseAction::class);
        $action->execute($invoice, $this->manager);

        $line1->refresh();
        $line2->refresh();
        $line3->refresh();

        // 100 distributed over 3: 33, 33, and last line takes remainder 34!
        $this->assertEquals(33, $line1->allocated_cost_rials);
        $this->assertEquals(33, $line2->allocated_cost_rials);
        $this->assertEquals(34, $line3->allocated_cost_rials);
        $this->assertEquals(100, $line1->allocated_cost_rials + $line2->allocated_cost_rials + $line3->allocated_cost_rials);
    }

    /**
     * Requirement 19 & 20:
     * تمام سندهای مالی متوازن و خطای مصنوعی کل عملیات را rollback کند.
     */
    public function test_journal_entry_must_be_balanced(): void
    {
        $recordJournal = app(RecordJournalEntryAction::class);

        $this->expectException(ValidationException::class);
        $recordJournal->execute([
            'date' => now()->toDateString(),
            'description' => 'سند نامتوازن تستی',
            'lines' => [
                ['account_code' => '101', 'debit_rials' => 1000, 'credit_rials' => 0],
                ['account_code' => '102', 'debit_rials' => 0, 'credit_rials' => 800], // Imbalanced by 200
            ],
        ]);
    }
}

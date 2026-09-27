<?php

namespace Tests\Feature\Sales;

use App\Actions\Accounting\RecordJournalEntryAction;
use App\Actions\Inventory\UpdateStockAction;
use App\Actions\Invoicing\FinalizeSaleAction;
use App\Enums\DeviceCondition;
use App\Enums\DeviceStatus;
use App\Enums\PartyRoleType;
use App\Enums\ProductType;
use App\Enums\UserRole;
use App\Models\Device;
use App\Models\DeviceIdentifier;
use App\Models\FinancialAccount;
use App\Models\Invoice;
use App\Models\InvoiceLine;
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

class SaleAndCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $salesperson;
    private Party $customer;
    private Warehouse $warehouse;
    private ProductVariant $phoneVariant;
    private ProductVariant $caseVariant;
    private ProductVariant $serviceVariant;
    private FinancialAccount $cashBox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(StoreStructureSeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);

        $this->manager = User::factory()->create([
            'role' => UserRole::Manager,
            'is_active' => true,
        ]);

        $this->salesperson = User::factory()->create([
            'role' => UserRole::Salesperson,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::first();
        $this->cashBox = FinancialAccount::where('type', 'cash')->first();

        $this->customer = Party::create([
            'name' => 'رضا مرادی',
            'type' => 'individual',
            'mobile' => '09129998877',
            'is_active' => true,
        ]);
        $this->customer->roles()->create(['role' => PartyRoleType::Customer]);

        // Phone
        $phone = Product::create([
            'name' => 'iPhone 13 128GB',
            'type' => ProductType::Serialized,
            'is_active' => true,
        ]);
        $this->phoneVariant = ProductVariant::create([
            'product_id' => $phone->id,
            'sku' => 'IP13-BLK',
            'selling_price_rials' => 450000000,
            'min_selling_price_rials' => 400000000,
        ]);

        // Case (stock)
        $case = Product::create([
            'name' => 'قاب محافظ',
            'type' => ProductType::Stock,
            'is_active' => true,
        ]);
        $this->caseVariant = ProductVariant::create([
            'product_id' => $case->id,
            'sku' => 'CASE-1',
            'selling_price_rials' => 3000000,
            'min_selling_price_rials' => 2500000,
        ]);

        // Service
        $service = Product::create([
            'name' => 'راه‌اندازی اولیه و ساخت اپل آیدی',
            'type' => ProductType::Service,
            'is_active' => true,
        ]);
        $this->serviceVariant = ProductVariant::create([
            'product_id' => $service->id,
            'sku' => 'SRV-SETUP',
            'selling_price_rials' => 5000000,
            'min_selling_price_rials' => 4000000,
        ]);
    }

    /**
     * Requirement 7:
     * فروش گوشی، دستگاه را sold کند و تلاش بعدی برای همان دستگاه رد شود.
     */
    public function test_selling_phone_marks_device_sold_and_subsequent_sale_is_rejected(): void
    {
        $device = Device::create([
            'product_variant_id' => $this->phoneVariant->id,
            'warehouse_id' => $this->warehouse->id,
            'physical_condition' => DeviceCondition::New,
            'operational_status' => DeviceStatus::Available,
        ]);
        DeviceIdentifier::create([
            'device_id' => $device->id,
            'type' => 'imei',
            'position' => 'primary',
            'value' => '352094109999999',
        ]);

        // First sale
        $invoice1 = Invoice::create([
            'type' => 'sale',
            'invoice_number' => 'SAL-001',
            'party_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal_rials' => 450000000,
            'total_amount_rials' => 450000000,
            'created_by' => $this->salesperson->id,
        ]);
        InvoiceLine::create([
            'invoice_id' => $invoice1->id,
            'product_id' => $this->phoneVariant->product_id,
            'product_variant_id' => $this->phoneVariant->id,
            'device_id' => $device->id,
            'quantity' => 1,
            'unit_price_rials' => 450000000,
        ]);

        $action = app(FinalizeSaleAction::class);
        $action->execute($invoice1, [], $this->salesperson);

        $device->refresh();
        $this->assertEquals(DeviceStatus::Sold, $device->operational_status);

        // Subsequent attempt to sell the exact same device must be rejected
        $invoice2 = Invoice::create([
            'type' => 'sale',
            'invoice_number' => 'SAL-002',
            'party_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal_rials' => 450000000,
            'total_amount_rials' => 450000000,
            'created_by' => $this->salesperson->id,
        ]);
        InvoiceLine::create([
            'invoice_id' => $invoice2->id,
            'product_id' => $this->phoneVariant->product_id,
            'product_variant_id' => $this->phoneVariant->id,
            'device_id' => $device->id,
            'quantity' => 1,
            'unit_price_rials' => 450000000,
        ]);

        $this->expectException(ValidationException::class);
        $action->execute($invoice2, [], $this->salesperson);
    }

    /**
     * Requirement 8:
     * فروش بیش از موجودی تعدادی رد شود؛ مانده و دفتر کل بدون تغییر بمانند.
     */
    public function test_sale_exceeding_stock_is_rejected(): void
    {
        // Initial stock: 2 cases
        $updateStock = app(UpdateStockAction::class);
        $updateStock->increase(
            $this->caseVariant->id,
            $this->warehouse->id,
            2,
            4000000,
            'test',
            1,
            'ورود اولیه'
        );

        $invoice = Invoice::create([
            'type' => 'sale',
            'invoice_number' => 'SAL-OVER-001',
            'party_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal_rials' => 9000000,
            'total_amount_rials' => 9000000,
            'created_by' => $this->salesperson->id,
        ]);
        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->caseVariant->product_id,
            'product_variant_id' => $this->caseVariant->id,
            'quantity' => 5, // Exceeds available 2
            'unit_price_rials' => 3000000,
        ]);

        $action = app(FinalizeSaleAction::class);

        $this->expectException(ValidationException::class);
        $action->execute($invoice, [], $this->salesperson);
    }

    /**
     * Requirement 13:
     * خرید بعدی، سود snapshot فاکتور فروش قبلی را تغییر ندهد.
     */
    public function test_future_purchase_does_not_change_past_sale_snapshot_cost(): void
    {
        $updateStock = app(UpdateStockAction::class);
        $finalizeSale = app(FinalizeSaleAction::class);

        // 1. Initial purchase: 1 unit at 1000 rials
        $updateStock->increase(
            $this->caseVariant->id,
            $this->warehouse->id,
            1,
            1000,
            'test',
            1,
            'خرید اول'
        );

        // 2. Sell this 1 unit at 2,800,000 rials (min price is 2,500,000)
        $invoice = Invoice::create([
            'type' => 'sale',
            'invoice_number' => 'SAL-SNAP-001',
            'party_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal_rials' => 2800000,
            'total_amount_rials' => 2800000,
            'created_by' => $this->salesperson->id,
        ]);
        $line = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->caseVariant->product_id,
            'product_variant_id' => $this->caseVariant->id,
            'quantity' => 1,
            'unit_price_rials' => 2800000,
        ]);

        $finalizeSale->execute($invoice, [], $this->salesperson);

        $line->refresh();
        $this->assertEquals(1000, $line->snapshot_cost_rials);

        // 3. Subsequent purchase at a much higher price (e.g. 5000 rials)
        $updateStock->increase(
            $this->caseVariant->id,
            $this->warehouse->id,
            2,
            10000,
            'test',
            2,
            'خرید بعدی با قیمت بالاتر'
        );

        // Verify past sale snapshot cost is unchanged
        $line->refresh();
        $this->assertEquals(1000, $line->snapshot_cost_rials);
    }

    /**
     * Requirement 14:
     * تخفیف کل به‌طور دقیق توزیع شود؛ جمع مبلغ ردیف‌ها با کل سند برابر باشد.
     */
    public function test_proportional_discount_distribution(): void
    {
        $invoice = Invoice::create([
            'type' => 'sale',
            'invoice_number' => 'SAL-DISC-001',
            'party_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'discount_rials' => 100, // Total discount = 100 rials
            'created_by' => $this->salesperson->id,
        ]);

        // Line 1: service 200 rials
        $line1 = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->serviceVariant->product_id,
            'product_variant_id' => $this->serviceVariant->id,
            'quantity' => 1,
            'unit_price_rials' => 200,
        ]);

        // Line 2: service 100 rials
        $line2 = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->serviceVariant->product_id,
            'product_variant_id' => $this->serviceVariant->id,
            'quantity' => 1,
            'unit_price_rials' => 100,
        ]);

        $action = app(FinalizeSaleAction::class);
        $action->execute($invoice, [], $this->manager);

        $line1->refresh();
        $line2->refresh();

        // 200/300 of 100 = 66, and last line takes remainder 34 -> total discount = 100
        $this->assertEquals(66, $line1->discount_rials);
        $this->assertEquals(34, $line2->discount_rials);
        $this->assertEquals(100, $line1->discount_rials + $line2->discount_rials);

        $netTotal = ($line1->quantity * $line1->unit_price_rials - $line1->discount_rials)
            + ($line2->quantity * $line2->unit_price_rials - $line2->discount_rials);

        $invoice->refresh();
        $this->assertEquals($invoice->total_amount_rials, $netTotal);
    }

    /**
     * تخفیف مشخص‌شده برای هر ردیف حفظ می‌شود و تخفیف کل فقط روی مبلغ باقی‌مانده پخش می‌شود.
     */
    public function test_invoice_discount_is_distributed_after_explicit_line_discounts(): void
    {
        $invoice = Invoice::create([
            'type' => 'sale',
            'invoice_number' => 'SAL-LINE-DISC-001',
            'party_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'discount_rials' => 60,
            'created_by' => $this->manager->id,
        ]);

        $line1 = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->serviceVariant->product_id,
            'product_variant_id' => $this->serviceVariant->id,
            'quantity' => 1,
            'unit_price_rials' => 200,
            'discount_rials' => 20,
        ]);
        $line2 = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->serviceVariant->product_id,
            'product_variant_id' => $this->serviceVariant->id,
            'quantity' => 1,
            'unit_price_rials' => 100,
            'discount_rials' => 10,
        ]);

        app(FinalizeSaleAction::class)->execute($invoice, [], $this->manager);

        $line1->refresh();
        $line2->refresh();

        // Remaining amounts are 180 and 90; 60 rials is allocated as 40 and 20.
        $this->assertEquals(60, $line1->discount_rials);
        $this->assertEquals(30, $line2->discount_rials);

        $invoice->refresh();
        $this->assertEquals(210, $invoice->total_amount_rials);
    }

    /**
     * Requirement 26:
     * فاکتور ترکیبی کالا و خدمت، فقط موجودی کالای واقعی را تغییر دهد.
     */
    public function test_mixed_invoice_only_decreases_stock_for_goods(): void
    {
        $updateStock = app(UpdateStockAction::class);
        $updateStock->increase(
            $this->caseVariant->id,
            $this->warehouse->id,
            5,
            5000000,
            'test',
            1,
            'موجودی کالا'
        );

        $invoice = Invoice::create([
            'type' => 'sale',
            'invoice_number' => 'SAL-MIXED-001',
            'party_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'created_by' => $this->salesperson->id,
        ]);

        // Good
        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->caseVariant->product_id,
            'product_variant_id' => $this->caseVariant->id,
            'quantity' => 2,
            'unit_price_rials' => 3000000,
        ]);

        // Service
        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->serviceVariant->product_id,
            'product_variant_id' => $this->serviceVariant->id,
            'quantity' => 1,
            'unit_price_rials' => 5000000,
        ]);

        $action = app(FinalizeSaleAction::class);
        $action->execute($invoice, [], $this->salesperson);

        $balance = StockBalance::where('product_variant_id', $this->caseVariant->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        // 5 - 2 = 3
        $this->assertEquals(3, $balance->quantity);

        // Service has no stock record
        $serviceBalance = StockBalance::where('product_variant_id', $this->serviceVariant->id)->first();
        $this->assertNull($serviceBalance);
    }

    /**
     * Requirement 2 & 33:
     * چاپ مشتری فاقد قیمت خرید و سود باشد و فروشنده دسترسی به قیمت خرید ندارد.
     */
    public function test_printed_invoice_does_not_contain_purchase_costs_or_profits(): void
    {
        $invoice = Invoice::create([
            'type' => 'sale',
            'invoice_number' => 'SAL-PRINT-001',
            'party_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'issue_date' => now()->toDateString(),
            'status' => 'finalized',
            'subtotal_rials' => 3000000,
            'total_amount_rials' => 3000000,
            'created_by' => $this->salesperson->id,
        ]);

        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->caseVariant->product_id,
            'product_variant_id' => $this->caseVariant->id,
            'quantity' => 1,
            'unit_price_rials' => 3000000,
            'snapshot_cost_rials' => 1800000, // Cost is 1,800,000 rials
        ]);

        $response = $this->actingAs($this->salesperson)->get(route('sales.print', $invoice));

        $response->assertOk();
        // The purchase cost (180,000 toman) and profit (120,000 toman) must NOT appear anywhere in the view
        $response->assertDontSee('180000');
        $response->assertDontSee('قیمت خرید');
        $response->assertDontSee('سود');
    }

    public function test_customer_can_be_created_without_leaving_sales_form(): void
    {
        $response = $this->actingAs($this->salesperson)->postJson(route('sales.quick-party'), [
            'name' => 'مشتری فوری',
            'type' => 'individual',
            'mobile' => '۰۹۱۲۳۴۵۶۷۸۹',
        ]);

        $response->assertCreated()->assertJsonStructure(['id', 'label']);
        $this->assertDatabaseHas('parties', ['name' => 'مشتری فوری', 'mobile' => '09123456789']);
        $this->assertDatabaseHas('party_roles', ['party_id' => $response->json('id'), 'role' => 'customer']);
    }

    public function test_manager_can_create_a_stock_product_without_leaving_sales_form(): void
    {
        $response = $this->actingAs($this->manager)->postJson(route('sales.quick-product'), [
            'name' => 'کابل شارژ فوری',
            'type' => 'stock',
            'barcode' => '۶۲۶۱۲۳۴۵۶',
            'selling_price_toman' => '۲۵۰٬۰۰۰',
        ]);

        $response->assertCreated()->assertJsonStructure(['id', 'label', 'selling_price_toman']);
        $this->assertDatabaseHas('products', ['name' => 'کابل شارژ فوری', 'type' => 'stock']);
        $this->assertDatabaseHas('product_variants', [
            'id' => $response->json('id'),
            'barcode' => '626123456',
            'selling_price_rials' => 2500000,
        ]);
    }

    public function test_salesperson_cannot_quick_create_products(): void
    {
        $this->actingAs($this->salesperson)->postJson(route('sales.quick-product'), [
            'name' => 'کالای غیرمجاز',
            'type' => 'stock',
            'selling_price_toman' => 1000,
        ])->assertForbidden();
    }

    public function test_sales_form_supports_percentage_invoice_discount(): void
    {
        $response = $this->actingAs($this->salesperson)->post(route('sales.store'), [
            'party_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'issue_date' => '۱۴۰۵/۰۱/۰۱',
            'discount_type' => 'percentage',
            'discount_value' => '۱۰',
            'finalize_now' => 0,
            'lines' => [[
                'product_variant_id' => $this->serviceVariant->id,
                'quantity' => 2,
                'unit_price_toman' => '۱٬۰۰۰٬۰۰۰',
                'discount_type' => 'percentage',
                'discount_value' => '۱۰',
            ]],
        ]);

        $response->assertRedirect();
        $invoice = Invoice::where('type', 'sale')->latest('id')->firstOrFail();
        $this->assertSame(20_000_000, $invoice->subtotal_rials);
        $this->assertSame(1_800_000, $invoice->discount_rials);
        $this->assertSame(16_200_000, $invoice->total_amount_rials);
        $this->assertSame(2_000_000, $invoice->lines()->firstOrFail()->discount_rials);
    }
}

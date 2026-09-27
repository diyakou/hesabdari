<?php

namespace Tests\Feature\Services;

use App\Enums\DeviceStatus;
use App\Enums\UserRole;
use App\Models\Device;
use App\Models\DeviceIdentifier;
use App\Models\FinancialAccount;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\JournalEntry;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ServiceDefinition;
use App\Models\ServiceOrder;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\ServiceDefinitionSeeder;
use Database\Seeders\StoreStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceOrderAndReturnsTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $accountant;
    private User $salesperson;
    private User $warehouseKeeper;
    private Warehouse $warehouse;
    private Party $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            StoreStructureSeeder::class,
            ChartOfAccountsSeeder::class,
            ServiceDefinitionSeeder::class,
        ]);

        $this->warehouse = Warehouse::firstOrFail();

        $this->manager = User::factory()->create([
            'role' => UserRole::Manager,
            'is_active' => true,
        ]);

        $this->accountant = User::factory()->create([
            'role' => UserRole::Accountant,
            'is_active' => true,
        ]);

        $this->salesperson = User::factory()->create([
            'role' => UserRole::Salesperson,
            'is_active' => true,
        ]);

        $this->warehouseKeeper = User::factory()->create([
            'role' => UserRole::WarehouseKeeper,
            'is_active' => true,
        ]);

        $this->customer = Party::create([
            'name' => 'رضا حسینی',
            'phone' => '09123456789',
            'is_active' => true,
        ]);
        PartyRole::create(['party_id' => $this->customer->id, 'role' => 'customer']);
    }

    public function test_service_order_intake_and_lifecycle_transitions(): void
    {
        $service = ServiceDefinition::where('code', 'data_transfer')->firstOrFail();

        // 1. Create service order
        $response = $this->actingAs($this->salesperson)->post(route('services.store'), [
            'party_id' => $this->customer->id,
            'service_definition_id' => $service->id,
            'form_data' => [
                'source_device' => 'iPhone 11',
                'target_device' => 'iPhone 15 Pro',
                'data_types' => 'کامل (تمام اطلاعات)',
                'estimated_gb' => 64,
            ],
            'promised_date' => now()->addDays(2)->toDateString(),
            'notes' => 'لطفاً واتساپ حتماً منتقل شود',
        ]);

        $response->assertRedirect();
        $order = ServiceOrder::latest()->firstOrFail();
        $this->assertEquals('queued', $order->status);
        $this->assertEquals($this->customer->id, $order->party_id);
        $this->assertEquals('iPhone 11', $order->form_data['source_device']);

        // 2. Transition: queued -> in_progress
        $response = $this->actingAs($this->salesperson)->patch(route('services.status', $order), [
            'status' => 'in_progress',
        ]);
        $response->assertRedirect();
        $this->assertEquals('in_progress', $order->fresh()->status);

        // 3. Transition: in_progress -> ready
        $response = $this->actingAs($this->salesperson)->patch(route('services.status', $order), [
            'status' => 'ready',
        ]);
        $response->assertRedirect();
        $this->assertEquals('ready', $order->fresh()->status);

        // 4. Transition: ready -> delivered
        $response = $this->actingAs($this->salesperson)->patch(route('services.status', $order), [
            'status' => 'delivered',
        ]);
        $response->assertRedirect();
        $order = $order->fresh();
        $this->assertEquals('delivered', $order->status);
        $this->assertNotNull($order->delivered_date);

        // 5. Terminal state: delivered cannot transition to cancelled
        $this->actingAs($this->salesperson)->patch(route('services.status', $order), [
            'status' => 'cancelled',
            'cancellation_reason' => 'علت تست',
        ])->assertSessionHasErrors(['status']);
    }

    public function test_service_cancellation_requires_reason(): void
    {
        $service = ServiceDefinition::where('code', 'data_transfer')->firstOrFail();

        $order = ServiceOrder::create([
            'order_number' => 'SRV-TEST-01',
            'party_id' => $this->customer->id,
            'service_definition_id' => $service->id,
            'service_form_version_id' => $service->latestFormVersion->id,
            'form_data' => ['source_device' => 'A54', 'target_device' => 'S24'],
            'status' => 'queued',
            'direct_cost_rials' => 0,
            'created_by' => $this->salesperson->id,
        ]);

        $this->actingAs($this->salesperson)->patch(route('services.status', $order), [
            'status' => 'cancelled',
            'cancellation_reason' => '',
        ])->assertSessionHasErrors(['cancellation_reason']);

        $this->actingAs($this->salesperson)->patch(route('services.status', $order), [
            'status' => 'cancelled',
            'cancellation_reason' => 'انصراف مشتری از انجام کار',
        ])->assertRedirect();

        $this->assertEquals('cancelled', $order->fresh()->status);
        $this->assertEquals('انصراف مشتری از انجام کار', $order->fresh()->cancellation_reason);
    }

    public function test_sale_return_quarantines_phone_and_reverses_original_snapshot_cogs(): void
    {
        // Setup phone product and device
        $product = Product::create(['name' => 'iPhone 13', 'type' => 'serialized', 'is_active' => true]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'IPH13-128-BLU',
            'variant_name' => '128GB Blue',
            'selling_price_rials' => 450000000,
            'min_selling_price_rials' => 400000000,
            'is_active' => true,
        ]);

        $device = Device::create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'warehouse_id' => $this->warehouse->id,
            'operational_status' => DeviceStatus::Sold,
            'created_by' => $this->manager->id,
        ]);

        DeviceIdentifier::create([
            'device_id' => $device->id,
            'type' => \App\Enums\IdentifierType::Imei,
            'value' => '359876543210987',
            'normalized_value' => '359876543210987',
            'is_primary' => true,
        ]);

        // Sale Invoice with snapshot cost 380,000,000 Rials
        $saleInvoice = Invoice::create([
            'type' => 'sale',
            'invoice_number' => 'INV-SALE-RETURN-TEST',
            'party_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'issue_date' => now()->toDateString(),
            'status' => 'finalized',
            'subtotal_rials' => 450000000,
            'discount_rials' => 0,
            'total_amount_rials' => 450000000,
            'created_by' => $this->manager->id,
        ]);

        $saleLine = InvoiceLine::create([
            'invoice_id' => $saleInvoice->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'device_id' => $device->id,
            'quantity' => 1,
            'unit_price_rials' => 450000000,
            'discount_rials' => 0,
            'snapshot_cost_rials' => 380000000,
        ]);

        // Process Return via ReturnController
        $response = $this->actingAs($this->salesperson)->post(route('returns.store'), [
            'reference_invoice_id' => $saleInvoice->id,
            'reference_line_id' => $saleLine->id,
            'quantity' => 1,
            'notes' => 'مشکل در ال سی دی، ارجاع به قرنطینه فنی',
        ]);

        $response->assertRedirect();

        // 1. Device status MUST be Quarantine, NOT Available!
        $this->assertEquals(DeviceStatus::Quarantine, $device->fresh()->operational_status);

        // 2. Return invoice created with finalized status
        $returnInvoice = Invoice::where('type', 'sale_return')->latest()->firstOrFail();
        $this->assertEquals('finalized', $returnInvoice->status);
        $this->assertEquals(450000000, $returnInvoice->total_amount_rials);

        // 3. GL Entry verifies reverse COGS of exactly 380,000,000 Rials (snapshot cost)
        $cogsJournal = JournalEntry::where('source_type', Invoice::class . ':return_cogs')
            ->where('source_id', $returnInvoice->id)
            ->firstOrFail();

        $this->assertTrue($cogsJournal->isBalanced());
        $merchandiseDebit = $cogsJournal->lines()->whereHas('ledgerAccount', fn ($q) => $q->where('code', '103'))->firstOrFail();
        $cogsCredit = $cogsJournal->lines()->whereHas('ledgerAccount', fn ($q) => $q->where('code', '501'))->firstOrFail();

        $this->assertEquals(380000000, $merchandiseDebit->debit_rials);
        $this->assertEquals(380000000, $cogsCredit->credit_rials);
    }

    public function test_operating_expense_records_balanced_gl_entry(): void
    {
        $financialAccount = FinancialAccount::firstOrFail(); // e.g. Cash drawer

        $response = $this->actingAs($this->accountant)->post(route('expenses.store'), [
            'category' => 'قبوض آب، برق، گاز و تلفن',
            'financial_account_id' => $financialAccount->id,
            'amount_toman' => 450000, // 4,500,000 Rials
            'date' => now()->toDateString(),
            'description' => 'پرداخت قبض برق فروشگاه',
        ]);

        $response->assertRedirect(route('expenses.index'));

        $journal = JournalEntry::where('description', 'like', '%قبض برق%')->firstOrFail();
        $this->assertTrue($journal->isBalanced());

        // Debit 601 (Operating Expenses) 4,500,000
        $expenseLine = $journal->lines()->whereHas('ledgerAccount', fn ($q) => $q->where('code', '601'))->firstOrFail();
        $this->assertEquals(4500000, $expenseLine->debit_rials);

        // Credit 101 (Cash/Bank) 4,500,000
        $cashLine = $journal->lines()->where('ledger_account_id', $financialAccount->ledger_account_id)->firstOrFail();
        $this->assertEquals(4500000, $cashLine->credit_rials);

        // Salesperson is forbidden from viewing or recording expenses
        $this->actingAs($this->salesperson)->get(route('expenses.index'))->assertStatus(403);
        $this->actingAs($this->salesperson)->post(route('expenses.store'), [
            'category' => 'سایر هزینه‌های عمومی',
            'financial_account_id' => $financialAccount->id,
            'amount_toman' => 10000,
            'date' => now()->toDateString(),
        ])->assertStatus(403);
    }

    public function test_fund_transfer_between_accounts_keeps_net_assets_balanced(): void
    {
        $accounts = FinancialAccount::take(2)->get();
        $this->assertCount(2, $accounts);
        $source = $accounts[0];
        $dest = $accounts[1];

        $response = $this->actingAs($this->accountant)->post(route('transfers.store'), [
            'source_account_id' => $source->id,
            'destination_account_id' => $dest->id,
            'amount_toman' => 2000000, // 20,000,000 Rials
            'date' => now()->toDateString(),
            'tracking_number' => 'TRF-123456',
            'notes' => 'انتقال از صندوق به حساب بانک',
        ]);

        $response->assertRedirect(route('transfers.index'));

        $journal = JournalEntry::where('description', 'like', '%انتقال داخلی وجه%')->firstOrFail();
        $this->assertTrue($journal->isBalanced());

        $destDebit = $journal->lines()->where('debit_rials', '>', 0)->firstOrFail();
        $sourceCredit = $journal->lines()->where('credit_rials', '>', 0)->firstOrFail();

        $this->assertEquals(20000000, $destDebit->debit_rials);
        $this->assertEquals(20000000, $sourceCredit->credit_rials);
    }

    public function test_inventory_adjustment_shortage_and_surplus_by_manager(): void
    {
        $product = Product::create(['name' => 'قاب سیلیکونی', 'type' => 'stock', 'is_active' => true]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'CASE-SILICONE-BLK',
            'variant_name' => 'مشکی',
            'selling_price_rials' => 2000000,
            'min_selling_price_rials' => 1500000,
            'is_active' => true,
        ]);

        // 1. Initial surplus of 10 units via adjustment
        $response = $this->actingAs($this->manager)->post(route('inventory.adjust.store'), [
            'product_variant_id' => $variant->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity_change' => 10,
            'unit_cost_toman' => 80000, // 800,000 Rials each -> 8,000,000 total
            'reason' => 'کشف مغایرت مثبت در انبارگردانی',
        ]);

        $response->assertRedirect(route('inventory.index'));

        $stock = StockBalance::where('product_variant_id', $variant->id)->firstOrFail();
        $this->assertEquals(10, $stock->quantity);
        $this->assertEquals(8000000, $stock->total_cost_rials);

        // Check GL: Debit 103 (Inventory), Credit 602 (Adjustment Gain)
        $gainJournal = JournalEntry::where('source_type', StockBalance::class . ':surplus')->firstOrFail();
        $this->assertTrue($gainJournal->isBalanced());
        $this->assertEquals(8000000, $gainJournal->lines()->whereHas('ledgerAccount', fn ($q) => $q->where('code', '103'))->first()->debit_rials);
        $this->assertEquals(8000000, $gainJournal->lines()->whereHas('ledgerAccount', fn ($q) => $q->where('code', '602'))->first()->credit_rials);

        // 2. Shortage adjustment of -2 units
        $response = $this->actingAs($this->manager)->post(route('inventory.adjust.store'), [
            'product_variant_id' => $variant->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity_change' => -2,
            'reason' => 'شکستگی و تلفات فیزیکی در قفسه',
        ]);

        $response->assertRedirect(route('inventory.index'));

        $stock = $stock->fresh();
        $this->assertEquals(8, $stock->quantity);
        $this->assertEquals(6400000, $stock->total_cost_rials);

        // Check GL: Debit 602 (Adjustment Loss), Credit 103 (Inventory)
        $lossJournal = JournalEntry::where('source_type', StockBalance::class . ':shortage')->firstOrFail();
        $this->assertTrue($lossJournal->isBalanced());
        $this->assertEquals(1600000, $lossJournal->lines()->whereHas('ledgerAccount', fn ($q) => $q->where('code', '602'))->first()->debit_rials);
        $this->assertEquals(1600000, $lossJournal->lines()->whereHas('ledgerAccount', fn ($q) => $q->where('code', '103'))->first()->credit_rials);

        // 3. Non-manager (accountant/salesperson) is blocked from adjusting inventory
        $this->actingAs($this->accountant)->post(route('inventory.adjust.store'), [
            'product_variant_id' => $variant->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity_change' => 1,
            'reason' => 'تست غیرمجاز',
        ])->assertStatus(403);
    }
}

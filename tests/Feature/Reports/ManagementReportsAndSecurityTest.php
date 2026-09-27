<?php

namespace Tests\Feature\Reports;

use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\Device;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Reports\ManagementReportService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\StoreStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementReportsAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $accountant;
    private User $salesperson;
    private Warehouse $warehouse;
    private Brand $appleBrand;
    private Brand $samsungBrand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            StoreStructureSeeder::class,
            ChartOfAccountsSeeder::class,
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

        $this->appleBrand = Brand::create(['name' => 'Apple', 'slug' => 'apple', 'is_active' => true]);
        $this->samsungBrand = Brand::create(['name' => 'Samsung', 'slug' => 'samsung', 'is_active' => true]);
    }

    public function test_salesperson_is_strictly_forbidden_from_viewing_or_exporting_reports(): void
    {
        // 1. Salesperson cannot open reports index
        $this->actingAs($this->salesperson)->get(route('reports.index'))->assertStatus(403);

        // 2. Salesperson cannot open brand report
        $this->actingAs($this->salesperson)->get(route('reports.brands'))->assertStatus(403);

        // 3. Salesperson cannot open customers report
        $this->actingAs($this->salesperson)->get(route('reports.customers'))->assertStatus(403);

        // 4. Salesperson cannot open inventory valuation
        $this->actingAs($this->salesperson)->get(route('reports.inventory'))->assertStatus(403);

        // 5. Salesperson cannot open trial balance
        $this->actingAs($this->salesperson)->get(route('reports.trial_balance'))->assertStatus(403);

        // 6. Salesperson cannot export CSV
        $this->actingAs($this->salesperson)->get(route('reports.export'))->assertStatus(403);
    }

    public function test_draft_invoices_are_excluded_from_sales_and_profit_summary(): void
    {
        $customer = Party::create(['name' => 'مشتری آزمایشی', 'is_active' => true]);
        PartyRole::create(['party_id' => $customer->id, 'role' => 'customer']);

        $product = Product::create(['name' => 'گوشی A54', 'type' => 'stock', 'is_active' => true]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'A54-128',
            'variant_name' => '128GB',
            'selling_price_rials' => 150000000,
            'min_selling_price_rials' => 120000000,
            'is_active' => true,
        ]);

        // 1. Draft Sale: 150,000,000 Rials (Must NOT be counted in reports)
        $draftInvoice = Invoice::create([
            'type' => 'sale',
            'invoice_number' => 'INV-DRAFT-01',
            'party_id' => $customer->id,
            'warehouse_id' => $this->warehouse->id,
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal_rials' => 150000000,
            'discount_rials' => 0,
            'total_amount_rials' => 150000000,
            'created_by' => $this->manager->id,
        ]);

        InvoiceLine::create([
            'invoice_id' => $draftInvoice->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price_rials' => 150000000,
            'discount_rials' => 0,
            'snapshot_cost_rials' => 110000000,
        ]);

        $reportService = app(ManagementReportService::class);
        $summaryBefore = $reportService->getSalesAndProfitSummary();
        $this->assertEquals(0, $summaryBefore['total_net_sales_rials']);
        $this->assertEquals(0, $summaryBefore['goods_gross_profit_rials']);

        // 2. Finalized Sale: 200,000,000 Rials, COGS: 140,000,000 Rials
        $finalizedInvoice = Invoice::create([
            'type' => 'sale',
            'invoice_number' => 'INV-FINAL-01',
            'party_id' => $customer->id,
            'warehouse_id' => $this->warehouse->id,
            'issue_date' => now()->toDateString(),
            'status' => 'finalized',
            'subtotal_rials' => 200000000,
            'discount_rials' => 0,
            'total_amount_rials' => 200000000,
            'created_by' => $this->manager->id,
        ]);

        InvoiceLine::create([
            'invoice_id' => $finalizedInvoice->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price_rials' => 200000000,
            'discount_rials' => 0,
            'snapshot_cost_rials' => 140000000,
        ]);

        $summaryAfter = $reportService->getSalesAndProfitSummary();
        $this->assertEquals(200000000, $summaryAfter['total_net_sales_rials']);
        $this->assertEquals(60000000, $summaryAfter['goods_gross_profit_rials']); // 200M - 140M = 60M
        $this->assertEquals(30.0, $summaryAfter['goods_margin_percent']); // 60M / 200M = 30%
    }

    public function test_customer_classification_distinguishes_dormant_from_non_purchasing(): void
    {
        // Customer 1: Active (purchased yesterday)
        $activeCustomer = Party::create(['name' => 'مشتری فعال', 'is_active' => true]);
        PartyRole::create(['party_id' => $activeCustomer->id, 'role' => 'customer']);

        Invoice::create([
            'type' => 'sale',
            'invoice_number' => 'INV-ACTIVE',
            'party_id' => $activeCustomer->id,
            'warehouse_id' => $this->warehouse->id,
            'issue_date' => now()->subDay()->toDateString(),
            'status' => 'finalized',
            'subtotal_rials' => 50000000,
            'discount_rials' => 0,
            'total_amount_rials' => 50000000,
            'created_by' => $this->manager->id,
        ]);

        // Customer 2: Dormant (purchased 120 days ago)
        $dormantCustomer = Party::create(['name' => 'مشتری غیرفعال', 'is_active' => true]);
        PartyRole::create(['party_id' => $dormantCustomer->id, 'role' => 'customer']);

        Invoice::create([
            'type' => 'sale',
            'invoice_number' => 'INV-DORMANT',
            'party_id' => $dormantCustomer->id,
            'warehouse_id' => $this->warehouse->id,
            'issue_date' => now()->subDays(120)->toDateString(),
            'status' => 'finalized',
            'subtotal_rials' => 30000000,
            'discount_rials' => 0,
            'total_amount_rials' => 30000000,
            'created_by' => $this->manager->id,
        ]);

        // Customer 3: Non-purchasing (registered but 0 purchases)
        $nonPurchasingCustomer = Party::create(['name' => 'مشتری ثبت‌شده بدون خرید', 'is_active' => true]);
        PartyRole::create(['party_id' => $nonPurchasingCustomer->id, 'role' => 'customer']);

        $reportService = app(ManagementReportService::class);
        $classification = $reportService->getCustomerClassification(90);

        $this->assertTrue($classification['active_customers']->contains('id', $activeCustomer->id));
        $this->assertFalse($classification['active_customers']->contains('id', $dormantCustomer->id));
        $this->assertFalse($classification['active_customers']->contains('id', $nonPurchasingCustomer->id));

        $this->assertTrue($classification['dormant_customers']->contains('id', $dormantCustomer->id));
        $this->assertFalse($classification['dormant_customers']->contains('id', $activeCustomer->id));
        $this->assertFalse($classification['dormant_customers']->contains('id', $nonPurchasingCustomer->id));

        $this->assertTrue($classification['non_purchasing_customers']->contains('id', $nonPurchasingCustomer->id));
        $this->assertFalse($classification['non_purchasing_customers']->contains('id', $dormantCustomer->id));
    }

    public function test_safe_csv_export_neutralizes_formula_injection_attempts(): void
    {
        $reportService = app(ManagementReportService::class);

        // Attempt formula injection characters (=, +, -, @)
        $headers = ['نام کالا', 'قیمت'];
        $dangerousRows = [
            ['=cmd|"/C calc"!A0', '1000'],
            ['+SUM(A1:A10)', '2000'],
            ['-HYPERLINK("evil.com")', '3000'],
            ['@IMPORTXML("http://evil.com")', '4000'],
            ['گوشی آیفون ۱۳ معمولی', '5000'],
        ];

        $csv = $reportService->generateSafeCsv($headers, $dangerousRows);

        // Verify CSV starts with UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);

        // Verify dangerous prefixes are neutralized by prepending a single quote
        $this->assertStringContainsString("'=cmd", $csv);
        $this->assertStringContainsString("'+SUM", $csv);
        $this->assertStringContainsString("'-HYPERLINK", $csv);
        $this->assertStringContainsString("'@IMPORTXML", $csv);

        // Safe cell without dangerous prefix is not quoted
        $this->assertStringContainsString('گوشی آیفون ۱۳ معمولی', $csv);
    }

    public function test_trial_balance_is_balanced(): void
    {
        $reportService = app(ManagementReportService::class);
        $trialBalance = $reportService->getTrialBalance();

        // In a fresh seeded database, debits equal credits
        $this->assertTrue($trialBalance['is_balanced']);
        $this->assertEquals($trialBalance['total_debits_rials'], $trialBalance['total_credits_rials']);
    }
}

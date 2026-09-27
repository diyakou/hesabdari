<?php

namespace App\Services\Reports;

use App\Enums\DeviceStatus;
use App\Models\Device;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use App\Models\Party;
use App\Models\StockBalance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ManagementReportService
{
    /**
     * Sales and profit summary (separating goods gross profit and estimated service margin).
     * Only finalized sales and returns within date range.
     *
     * @return array{
     *     period_label: string,
     *     total_net_sales_rials: int,
     *     goods_sales_rials: int,
     *     goods_cogs_rials: int,
     *     goods_gross_profit_rials: int,
     *     goods_margin_percent: float|null,
     *     services_sales_rials: int,
     *     services_direct_cost_rials: int,
     *     services_margin_rials: int,
     *     services_margin_percent: float|null,
     *     total_invoices_count: int,
     *     total_returns_count: int
     * }
     */
    public function getSalesAndProfitSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $salesQuery = InvoiceLine::with(['invoice', 'product'])
            ->whereHas('invoice', function ($q) use ($startDate, $endDate) {
                $q->where('type', 'sale')->where('status', 'finalized');
                if ($startDate) {
                    $q->where('issue_date', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('issue_date', '<=', $endDate);
                }
            });

        $returnsQuery = InvoiceLine::with(['invoice', 'product'])
            ->whereHas('invoice', function ($q) use ($startDate, $endDate) {
                $q->where('type', 'sale_return')->where('status', 'finalized');
                if ($startDate) {
                    $q->where('issue_date', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('issue_date', '<=', $endDate);
                }
            });

        $saleLines = $salesQuery->get();
        $returnLines = $returnsQuery->get();

        $goodsSalesRials = 0;
        $goodsCogsRials = 0;
        $servicesSalesRials = 0;
        $servicesDirectCostRials = 0;

        foreach ($saleLines as $line) {
            $netLineAmount = ($line->quantity * $line->unit_price_rials) - $line->discount_rials;
            $lineCogs = $line->snapshot_cost_rials * $line->quantity;

            if ($line->product && $line->product->isService()) {
                $servicesSalesRials += $netLineAmount;
                // Direct cost of service line (snapshot or recorded)
                $servicesDirectCostRials += $lineCogs;
            } else {
                $goodsSalesRials += $netLineAmount;
                $goodsCogsRials += $lineCogs;
            }
        }

        foreach ($returnLines as $line) {
            $netLineAmount = ($line->quantity * $line->unit_price_rials) - $line->discount_rials;
            $lineCogs = $line->snapshot_cost_rials * $line->quantity;

            if ($line->product && $line->product->isService()) {
                $servicesSalesRials -= $netLineAmount;
                $servicesDirectCostRials -= $lineCogs;
            } else {
                $goodsSalesRials -= $netLineAmount;
                $goodsCogsRials -= $lineCogs;
            }
        }

        $goodsGrossProfitRials = $goodsSalesRials - $goodsCogsRials;
        $goodsMarginPercent = $goodsSalesRials > 0
            ? round(($goodsGrossProfitRials / $goodsSalesRials) * 100, 2)
            : null;

        $servicesMarginRials = $servicesSalesRials - $servicesDirectCostRials;
        $servicesMarginPercent = $servicesSalesRials > 0
            ? round(($servicesMarginRials / $servicesSalesRials) * 100, 2)
            : null;

        $totalNetSalesRials = $goodsSalesRials + $servicesSalesRials;

        $periodLabel = 'کل دوره';
        if ($startDate && $endDate) {
            $periodLabel = "از {$startDate} تا {$endDate}";
        } elseif ($startDate) {
            $periodLabel = "از تاریخ {$startDate}";
        }

        $salesInvoiceCount = Invoice::where('type', 'sale')->where('status', 'finalized')
            ->when($startDate, fn ($q) => $q->where('issue_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('issue_date', '<=', $endDate))
            ->count();

        $returnInvoiceCount = Invoice::where('type', 'sale_return')->where('status', 'finalized')
            ->when($startDate, fn ($q) => $q->where('issue_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('issue_date', '<=', $endDate))
            ->count();

        return [
            'period_label' => $periodLabel,
            'total_net_sales_rials' => $totalNetSalesRials,
            'goods_sales_rials' => $goodsSalesRials,
            'goods_cogs_rials' => $goodsCogsRials,
            'goods_gross_profit_rials' => $goodsGrossProfitRials,
            'goods_margin_percent' => $goodsMarginPercent,
            'services_sales_rials' => $servicesSalesRials,
            'services_direct_cost_rials' => $servicesDirectCostRials,
            'services_margin_rials' => $servicesMarginRials,
            'services_margin_percent' => $servicesMarginPercent,
            'total_invoices_count' => $salesInvoiceCount,
            'total_returns_count' => $returnInvoiceCount,
        ];
    }

    /**
     * Brand performance report:
     * - Best-selling by quantity and by net sales volume.
     * - Most profitable by total gross profit in Rials.
     * Only goods with a brand (services excluded).
     *
     * @return Collection<int, array{
     *     brand_id: int,
     *     brand_name: string,
     *     net_quantity: int,
     *     net_sales_rials: int,
     *     goods_cogs_rials: int,
     *     gross_profit_rials: int,
     *     margin_percent: float|null
     * }>
     */
    public function getBrandPerformance(?string $startDate = null, ?string $endDate = null): Collection
    {
        $saleLines = InvoiceLine::with(['invoice', 'product.brand'])
            ->whereHas('invoice', function ($q) use ($startDate, $endDate) {
                $q->whereIn('type', ['sale', 'sale_return'])->where('status', 'finalized');
                if ($startDate) {
                    $q->where('issue_date', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('issue_date', '<=', $endDate);
                }
            })
            ->whereHas('product', fn ($q) => $q->whereNotNull('brand_id'))
            ->get();

        $brands = [];

        foreach ($saleLines as $line) {
            $brand = $line->product?->brand;
            if (! $brand) {
                continue;
            }

            $brandId = $brand->id;
            if (! isset($brands[$brandId])) {
                $brands[$brandId] = [
                    'brand_id' => $brandId,
                    'brand_name' => $brand->name,
                    'net_quantity' => 0,
                    'net_sales_rials' => 0,
                    'goods_cogs_rials' => 0,
                    'gross_profit_rials' => 0,
                    'margin_percent' => null,
                ];
            }

            $isReturn = $line->invoice->type === 'sale_return';
            $direction = $isReturn ? -1 : 1;

            $netAmount = (($line->quantity * $line->unit_price_rials) - $line->discount_rials) * $direction;
            $cogs = ($line->snapshot_cost_rials * $line->quantity) * $direction;
            $qty = $line->quantity * $direction;

            $brands[$brandId]['net_quantity'] += $qty;
            $brands[$brandId]['net_sales_rials'] += $netAmount;
            $brands[$brandId]['goods_cogs_rials'] += $cogs;
        }

        foreach ($brands as &$b) {
            $b['gross_profit_rials'] = $b['net_sales_rials'] - $b['goods_cogs_rials'];
            $b['margin_percent'] = $b['net_sales_rials'] > 0
                ? round(($b['gross_profit_rials'] / $b['net_sales_rials']) * 100, 2)
                : null;
        }

        return collect($brands)->sortByDesc('gross_profit_rials')->values();
    }

    /**
     * Customer classification:
     * - Active: last purchase within $thresholdDays (default 90)
     * - Dormant (مشتری غیرفعال): has purchases, but last purchase > $thresholdDays ago
     * - Non-purchasing (مشتری بدون خرید): registered customer with 0 purchases
     *
     * @return array{
     *     active_customers: Collection,
     *     dormant_customers: Collection,
     *     non_purchasing_customers: Collection,
     *     top_customers: Collection
     * }
     */
    public function getCustomerClassification(int $thresholdDays = 90): array
    {
        $cutoffDate = now()->subDays($thresholdDays)->toDateString();

        $customers = Party::whereHas('roles', fn ($q) => $q->where('role', 'customer'))
            ->with(['invoices' => fn ($q) => $q->where('type', 'sale')->where('status', 'finalized')])
            ->get();

        $active = collect();
        $dormant = collect();
        $nonPurchasing = collect();
        $profitability = [];

        foreach ($customers as $customer) {
            $finalizedSales = $customer->invoices;

            if ($finalizedSales->isEmpty()) {
                $nonPurchasing->push($customer);
                continue;
            }

            $lastSaleDate = $finalizedSales->max('issue_date');

            if ($lastSaleDate >= $cutoffDate) {
                $active->push($customer);
            } else {
                $dormant->push($customer);
            }

            // Calculate customer lifetime gross profit
            $totalSales = $finalizedSales->sum('total_amount_rials');
            $cogsTotal = InvoiceLine::whereIn('invoice_id', $finalizedSales->pluck('id'))
                ->sum(DB::raw('snapshot_cost_rials * quantity'));

            $profitability[] = [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'phone' => $customer->phone,
                'last_purchase_date' => $lastSaleDate,
                'invoices_count' => $finalizedSales->count(),
                'total_sales_rials' => $totalSales,
                'gross_profit_rials' => $totalSales - $cogsTotal,
            ];
        }

        $topCustomers = collect($profitability)->sortByDesc('gross_profit_rials')->take(10)->values();

        return [
            'active_customers' => $active,
            'dormant_customers' => $dormant,
            'non_purchasing_customers' => $nonPurchasing,
            'top_customers' => $topCustomers,
        ];
    }

    /**
     * Inventory valuation and idle stock:
     * - Book value of stock items (stock_balances)
     * - Value of devices (including quarantine, excluding customer repair phones)
     * - Reconciliation with GL Account 103 (Merchandise Inventory)
     * - Idle stock
     *
     * @return array{
     *     stock_items_value_rials: int,
     *     devices_count: int,
     *     quarantine_devices_count: int,
     *     total_inventory_value_rials: int,
     *     gl_account_103_balance_rials: int,
     *     is_reconciled: bool,
     *     idle_stocks: Collection
     * }
     */
    public function getInventoryValuation(): array
    {
        $stockValue = (int) StockBalance::where('quantity', '>', 0)->sum('total_cost_rials');

        $availableDevicesCount = Device::where('operational_status', DeviceStatus::Available)->count();
        $quarantineDevicesCount = Device::where('operational_status', DeviceStatus::Quarantine)->count();

        // For serialized devices, each has its snapshot cost or variant price
        // In our model, devices are serialized products; stock_balance or GL holds the value
        $glAccount = LedgerAccount::where('code', '103')->first();
        $glDebit = $glAccount ? (int) JournalLine::where('ledger_account_id', $glAccount->id)->sum('debit_rials') : 0;
        $glCredit = $glAccount ? (int) JournalLine::where('ledger_account_id', $glAccount->id)->sum('credit_rials') : 0;
        $glBalance = $glDebit - $glCredit;

        // Idle stock: items with positive balance where last movement was > 45 days ago
        $idleStocks = StockBalance::with(['productVariant.product', 'warehouse'])
            ->where('quantity', '>', 0)
            ->where('updated_at', '<', now()->subDays(45))
            ->get();

        return [
            'stock_items_value_rials' => $stockValue,
            'devices_count' => $availableDevicesCount + $quarantineDevicesCount,
            'quarantine_devices_count' => $quarantineDevicesCount,
            'total_inventory_value_rials' => $stockValue,
            'gl_account_103_balance_rials' => $glBalance,
            'is_reconciled' => $stockValue === $glBalance,
            'idle_stocks' => $idleStocks,
        ];
    }

    /**
     * Trial Balance & GL Balance audit:
     * - Lists all accounts with sum of debits and sum of credits.
     * - Checks whether sum(all debits) === sum(all credits).
     *
     * @return array{
     *     is_balanced: bool,
     *     total_debits_rials: int,
     *     total_credits_rials: int,
     *     accounts: Collection<int, array{
     *         code: string,
     *         name: string,
     *         type: string,
     *         debit_rials: int,
     *         credit_rials: int,
     *         balance_rials: int
     *     }>
     * }
     */
    public function getTrialBalance(): array
    {
        $accounts = LedgerAccount::orderBy('code')->get();
        $accountRows = [];
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($accounts as $account) {
            $debits = (int) JournalLine::where('ledger_account_id', $account->id)->sum('debit_rials');
            $credits = (int) JournalLine::where('ledger_account_id', $account->id)->sum('credit_rials');

            $balance = in_array($account->type, ['asset', 'expense'])
                ? $debits - $credits
                : $credits - $debits;

            $totalDebits += $debits;
            $totalCredits += $credits;

            $accountRows[] = [
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'debit_rials' => $debits,
                'credit_rials' => $credits,
                'balance_rials' => $balance,
            ];
        }

        return [
            'is_balanced' => $totalDebits === $totalCredits,
            'total_debits_rials' => $totalDebits,
            'total_credits_rials' => $totalCredits,
            'accounts' => collect($accountRows),
        ];
    }

    /**
     * Generate Safe CSV with formula injection protection (Req 34).
     * Sanitizes `=, +, -, @` by prepending `'` (apostrophe quote).
     * Includes UTF-8 BOM (\xEF\xBB\xBF) for correct Persian characters in Excel.
     *
     * @param list<string> $headers
     * @param list<list<mixed>> $rows
     */
    public function generateSafeCsv(array $headers, array $rows): string
    {
        $output = fopen('php://temp', 'r+');
        if (! $output) {
            throw new \RuntimeException('Cannot open temp stream for CSV generation.');
        }

        // UTF-8 BOM
        fwrite($output, "\xEF\xBB\xBF");

        // Write sanitized headers
        $sanitizedHeaders = array_map([$this, 'sanitizeCell'], $headers);
        fputcsv($output, $sanitizedHeaders);

        // Write sanitized rows
        foreach ($rows as $row) {
            $sanitizedRow = array_map([$this, 'sanitizeCell'], $row);
            fputcsv($output, $sanitizedRow);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv ?: '';
    }

    /**
     * Prevent CSV Formula Injection by prefixing unsafe leading characters with single quote.
     */
    public function sanitizeCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $str = (string) $value;

        // If cell begins with =, +, -, @, or tab/CR, prepend apostrophe to neutralize spreadsheet formula execution
        if ($str !== '' && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $str;
        }

        return $str;
    }
}

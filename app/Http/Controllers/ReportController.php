<?php

namespace App\Http\Controllers;

use App\Services\Reports\ManagementReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ManagementReportService $reportService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeAccess($request);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $summary = $this->reportService->getSalesAndProfitSummary($startDate, $endDate);

        return view('reports.index', compact('summary', 'startDate', 'endDate'));
    }

    public function brands(Request $request): View
    {
        $this->authorizeAccess($request);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $brands = $this->reportService->getBrandPerformance($startDate, $endDate);

        return view('reports.brands', compact('brands', 'startDate', 'endDate'));
    }

    public function customers(Request $request): View
    {
        $this->authorizeAccess($request);

        $threshold = (int) $request->input('threshold', 90);
        $classification = $this->reportService->getCustomerClassification($threshold);

        return view('reports.customers', compact('classification', 'threshold'));
    }

    public function inventory(Request $request): View
    {
        $this->authorizeAccess($request);

        $valuation = $this->reportService->getInventoryValuation();

        return view('reports.inventory', compact('valuation'));
    }

    public function trialBalance(Request $request): View
    {
        $this->authorizeAccess($request);

        $trialBalance = $this->reportService->getTrialBalance();

        return view('reports.trial_balance', compact('trialBalance'));
    }

    public function export(Request $request): Response
    {
        $this->authorizeAccess($request);

        $type = $request->input('type', 'sales');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($type === 'brands') {
            $brands = $this->reportService->getBrandPerformance($startDate, $endDate);
            $headers = ['کد برند', 'نام برند', 'تعداد خالص', 'فروش خالص (تومان)', 'بهای تمام‌شده (تومان)', 'سود ناخالص (تومان)', 'حاشیه سود (%)'];
            $rows = $brands->map(fn ($b) => [
                $b['brand_id'],
                $b['brand_name'],
                $b['net_quantity'],
                (int) ($b['net_sales_rials'] / 10),
                (int) ($b['goods_cogs_rials'] / 10),
                (int) ($b['gross_profit_rials'] / 10),
                $b['margin_percent'] ?? 'نامعین',
            ])->all();
            $filename = 'brands-report-' . now()->format('Ymd') . '.csv';
        } elseif ($type === 'trial_balance') {
            $tb = $this->reportService->getTrialBalance();
            $headers = ['کد حساب', 'نام حساب', 'ماهیت حساب', 'جمع گردش بدهکار (تومان)', 'جمع گردش بستانکار (تومان)', 'مانده حساب (تومان)'];
            $rows = $tb['accounts']->map(fn ($a) => [
                $a['code'],
                $a['name'],
                $a['type'],
                (int) ($a['debit_rials'] / 10),
                (int) ($a['credit_rials'] / 10),
                (int) ($a['balance_rials'] / 10),
            ])->all();
            $filename = 'trial-balance-' . now()->format('Ymd') . '.csv';
        } else {
            // Default: Sales summary
            $summary = $this->reportService->getSalesAndProfitSummary($startDate, $endDate);
            $headers = ['شاخص مالی', 'مقدار (تومان / تعداد)', 'توضیحات'];
            $rows = [
                ['فروش خالص کل', (int) ($summary['total_net_sales_rials'] / 10), 'مجموع فروش پس از کسر تخفیف و مرجوعی'],
                ['فروش خالص کالا', (int) ($summary['goods_sales_rials'] / 10), 'گوشی و لوازم جانبی'],
                ['بهای تمام‌شده کالای فروش‌رفته (COGS)', (int) ($summary['goods_cogs_rials'] / 10), 'بر مبنای بهای اسنپ‌شات'],
                ['سود ناخالص کالا', (int) ($summary['goods_gross_profit_rials'] / 10), 'فروش کالا منهای بهای تمام‌شده'],
                ['حاشیه سود ناخالص کالایی', ($summary['goods_margin_percent'] ?? 'نامعین') . '%', 'درصد سود به فروش خالص کالا'],
                ['فروش و درآمد خدمات', (int) ($summary['services_sales_rials'] / 10), 'خدمات نرم‌افزاری و راه‌اندازی'],
                ['هزینه مستقیم برآوردی خدمات', (int) ($summary['services_direct_cost_rials'] / 10), 'خرید اکانت، قطعه مصرفی و...'],
                ['حاشیه برآوردی خدمات', (int) ($summary['services_margin_rials'] / 10), 'درآمد خدمات منهای هزینه مستقیم'],
                ['تعداد فاکتورهای فروش', $summary['total_invoices_count'], 'فاکتورهای نهایی'],
                ['تعداد فاکتورهای مرجوعی', $summary['total_returns_count'], 'مرجوعی‌های نهایی'],
            ];
            $filename = 'sales-summary-' . now()->format('Ymd') . '.csv';
        }

        $csv = $this->reportService->generateSafeCsv($headers, $rows);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function authorizeAccess(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->is_active || ! $user->canSeeFinancials()) {
            abort(403, 'مشاهده گزارش‌های مالی و مدیریتی فقط برای مدیر و حسابدار مجاز است.');
        }
    }
}

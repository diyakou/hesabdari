<?php

namespace App\Http\Controllers;

use App\Enums\DeviceStatus;
use App\Models\Branch;
use App\Models\Device;
use App\Models\Invoice;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use App\Models\ServiceOrder;
use App\Models\StoreSetting;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $today = now()->toDateString();
        $user = $request->user();

        $todaySalesCount = Invoice::where('type', 'sale')
            ->where('status', 'finalized')
            ->whereDate('issue_date', $today)
            ->count();

        $todaySalesTotalRials = 0;
        $cashAndBankBalanceRials = 0;

        if ($user && $user->canSeeFinancials()) {
            $todaySalesTotalRials = (int) Invoice::where('type', 'sale')
                ->where('status', 'finalized')
                ->whereDate('issue_date', $today)
                ->sum('total_amount_rials');

            $cashLedger = LedgerAccount::where('code', '101')->first();
            if ($cashLedger) {
                $debits = (int) JournalLine::where('ledger_account_id', $cashLedger->id)->sum('debit_rials');
                $credits = (int) JournalLine::where('ledger_account_id', $cashLedger->id)->sum('credit_rials');
                $cashAndBankBalanceRials = $debits - $credits;
            }
        }

        $activeServicesCount = ServiceOrder::whereIn('status', ['queued', 'in_progress'])->count();
        $availableDevicesCount = Device::where('operational_status', DeviceStatus::Available)->count();
        $quarantineDevicesCount = Device::where('operational_status', DeviceStatus::Quarantine)->count();

        return view('dashboard.index', [
            'store' => StoreSetting::query()->where('key', 'primary')->first(),
            'activeUsersCount' => User::query()->where('is_active', true)->count(),
            'activeBranchesCount' => Branch::query()->where('is_active', true)->count(),
            'activeWarehousesCount' => Warehouse::query()->where('is_active', true)->count(),
            'todaySalesCount' => $todaySalesCount,
            'todaySalesTotalRials' => $todaySalesTotalRials,
            'cashAndBankBalanceRials' => $cashAndBankBalanceRials,
            'activeServicesCount' => $activeServicesCount,
            'availableDevicesCount' => $availableDevicesCount,
            'quarantineDevicesCount' => $quarantineDevicesCount,
        ]);
    }
}

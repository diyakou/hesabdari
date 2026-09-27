<?php

namespace App\Http\Controllers;

use App\Actions\Operations\RecordFundTransferAction;
use App\Models\FinancialAccount;
use App\Models\FundTransfer;
use App\Services\Auditing\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FundTransferController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly RecordFundTransferAction $recordFundTransferAction,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        if (! $user->canSeeFinancials()) {
            abort(403, 'دسترسی به بخش انتقال وجوه فقط برای مدیر و حسابدار مجاز است.');
        }

        $transfers = FundTransfer::with(['sourceAccount', 'destinationAccount', 'creator'])
            ->latest('date')
            ->paginate(15);

        return view('transfers.index', compact('transfers'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        if (! $user->canSeeFinancials()) {
            abort(403, 'دسترسی به ثبت انتقال وجه فقط برای مدیر و حسابدار مجاز است.');
        }

        $accounts = FinancialAccount::where('is_active', true)->get();

        return view('transfers.create', compact('accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->canSeeFinancials()) {
            abort(403, 'دسترسی به ثبت انتقال وجه فقط برای مدیر و حسابدار مجاز است.');
        }

        $validated = $request->validate([
            'source_account_id' => ['required', 'exists:financial_accounts,id'],
            'destination_account_id' => ['required', 'exists:financial_accounts,id', 'different:source_account_id'],
            'amount_toman' => ['required', 'numeric', 'min:1'],
            'date' => ['required', 'date'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $amountRials = (int) ($validated['amount_toman'] * 10);

        $transfer = $this->recordFundTransferAction->execute([
            'source_account_id' => (int) $validated['source_account_id'],
            'destination_account_id' => (int) $validated['destination_account_id'],
            'amount_rials' => $amountRials,
            'date' => $validated['date'],
            'tracking_number' => $validated['tracking_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ], $user);

        $this->auditLogger->record(
            'fund_transfer.created',
            $transfer,
            [],
            [
                'transfer_number' => $transfer->transfer_number,
                'source_account_id' => $transfer->source_account_id,
                'destination_account_id' => $transfer->destination_account_id,
                'amount_rials' => $transfer->amount_rials,
            ],
            $user
        );

        return redirect()->route('transfers.index')
            ->with('status', "انتقال وجه با شماره سند {$transfer->transfer_number} با موفقیت ثبت گردید.");
    }
}

<?php

namespace App\Http\Controllers;

use App\Actions\Payments\RecordPaymentAction;
use App\Models\FinancialAccount;
use App\Models\Party;
use App\Models\Payment;
use App\Services\Auditing\AuditLogger;
use App\Support\Localization\LocalizedDigits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly RecordPaymentAction $recordPaymentAction,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Payment::class);

        $query = Payment::with(['party', 'financialAccount', 'creator'])->latest();

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($search = $request->input('search')) {
            $asciiSearch = LocalizedDigits::toAscii($search);
            $query->where(function ($q) use ($search, $asciiSearch) {
                $q->where('payment_number', 'like', "%{$asciiSearch}%")
                    ->orWhereHas('party', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        $payments = $query->paginate(15)->withQueryString();

        return view('payments.index', compact('payments'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Payment::class);

        $type = $request->input('type', 'payment'); // payment or receipt
        $parties = Party::where('is_active', true)->orderBy('name')->get();
        $financialAccounts = FinancialAccount::where('is_active', true)->get();

        return view('payments.create', compact('type', 'parties', 'financialAccounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Payment::class);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:payment,receipt'],
            'party_id' => ['required', 'exists:parties,id'],
            'financial_account_id' => ['required', 'exists:financial_accounts,id'],
            'amount_toman' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string', 'in:cash,bank_transfer,pos'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $amountRials = (int) ($validated['amount_toman'] * 10);

        $payment = $this->recordPaymentAction->execute([
            'type' => $validated['type'],
            'party_id' => (int) $validated['party_id'],
            'financial_account_id' => (int) $validated['financial_account_id'],
            'amount_rials' => $amountRials,
            'payment_method' => $validated['payment_method'],
            'reference_number' => $validated['reference_number'] ?? null,
            'date' => $validated['date'],
            'notes' => $validated['notes'] ?? null,
        ], $request->user());

        $this->auditLogger->record(
            'payment.created',
            $payment,
            [],
            ['payment_number' => $payment->payment_number, 'amount_rials' => $amountRials],
            $request->user(),
        );

        return redirect()->route('payments.show', $payment)
            ->with('status', 'تراکنش مالی با موفقیت ثبت شد و سند حسابداری صادر گردید.');
    }

    public function show(Payment $payment): View
    {
        Gate::authorize('view', $payment);

        $payment->load(['party', 'financialAccount', 'allocations.invoice', 'creator']);

        return view('payments.show', compact('payment'));
    }
}

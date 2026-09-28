<?php

namespace App\Http\Controllers;

use App\Actions\Payments\RecordPaymentAction;
use App\Models\FinancialAccount;
use App\Models\Cheque;
use App\Models\Party;
use App\Models\Payment;
use App\Services\Auditing\AuditLogger;
use App\Support\Localization\LocalizedDigits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        $availableCheques = Cheque::with('party')->where('direction', 'received')->where('status', 'on_hand')->orderBy('due_date')->get();

        return view('payments.create', compact('type', 'parties', 'financialAccounts', 'availableCheques'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Payment::class);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:payment,receipt'],
            'party_id' => ['required', 'exists:parties,id'],
            'financial_account_id' => ['required', 'exists:financial_accounts,id'],
            'amount_toman' => ['nullable', 'required_unless:payment_method,endorsed_cheque', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string', 'in:cash,bank_transfer,pos,cheque,endorsed_cheque'],
            'cheque_id' => ['nullable', 'required_if:payment_method,endorsed_cheque', 'exists:cheques,id'],
            'check_number' => ['nullable', 'required_if:payment_method,cheque', 'string', 'max:100'],
            'sayad_id' => ['nullable', 'digits:16'],
            'bank_name' => ['nullable', 'required_if:payment_method,cheque', 'string', 'max:100'],
            'account_owner' => ['nullable', 'string', 'max:150'],
            'due_date' => ['nullable', 'required_if:payment_method,cheque', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = DB::transaction(function () use ($validated, $request) {
            $cheque = null;
            if ($validated['payment_method'] === 'endorsed_cheque') {
                if ($validated['type'] !== 'payment') {
                    throw ValidationException::withMessages(['payment_method' => 'خرج‌کردن چک فقط در سند پرداخت امکان‌پذیر است.']);
                }
                $cheque = Cheque::lockForUpdate()->findOrFail($validated['cheque_id']);
                if ($cheque->direction !== 'received' || $cheque->status !== 'on_hand') {
                    throw ValidationException::withMessages(['cheque_id' => 'این چک قبلاً خرج شده یا در دسترس نیست.']);
                }
            }

            $amountRials = $cheque?->amount_rials ?? (int) ($validated['amount_toman'] * 10);
            $payload = [
            'type' => $validated['type'],
            'party_id' => (int) $validated['party_id'],
            'financial_account_id' => (int) $validated['financial_account_id'],
            'amount_rials' => $amountRials,
            'payment_method' => $validated['payment_method'],
            'reference_number' => $validated['reference_number'] ?? null,
            'date' => $validated['date'],
            'notes' => $validated['notes'] ?? null,
            ];
            if ($validated['payment_method'] === 'cheque') {
                $payload['cheque'] = [
                    'check_number' => $validated['check_number'], 'sayad_id' => $validated['sayad_id'] ?? null,
                    'bank_name' => $validated['bank_name'], 'account_owner' => $validated['account_owner'] ?? null,
                    'due_date' => $validated['due_date'],
                ];
            }
            $payment = $this->recordPaymentAction->execute($payload, $request->user());
            if ($cheque) {
                $cheque->update(['status' => 'endorsed', 'endorsed_to_party_id' => $validated['party_id'], 'endorsed_payment_id' => $payment->id]);
            }
            return $payment;
        });

        $this->auditLogger->record(
            'payment.created',
            $payment,
            [],
            ['payment_number' => $payment->payment_number, 'amount_rials' => $payment->amount_rials],
            $request->user(),
        );

        return redirect()->route('payments.show', $payment)
            ->with('status', 'تراکنش مالی با موفقیت ثبت شد و سند حسابداری صادر گردید.');
    }

    public function show(Payment $payment): View
    {
        Gate::authorize('view', $payment);

        $payment->load(['party', 'financialAccount', 'allocations.invoice', 'creator', 'cheques']);

        return view('payments.show', compact('payment'));
    }
}

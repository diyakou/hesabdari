<?php

namespace App\Actions\Payments;

use App\Actions\Accounting\RecordJournalEntryAction;
use App\Models\FinancialAccount;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordPaymentAction
{
    public function __construct(
        private readonly RecordJournalEntryAction $recordJournalEntryAction,
    ) {}

    /**
     * @param array{
     *     type: string, // receipt, payment
     *     party_id: int,
     *     financial_account_id: int,
     *     amount_rials: int,
     *     payment_method: string,
     *     reference_number?: string|null,
     *     date: string|\DateTimeInterface,
     *     notes?: string|null,
     *     idempotency_key?: string|null,
     *     allocations?: array<int, array{invoice_id: int, amount_rials: int}>
     * } $data
     */
    public function execute(array $data, ?User $actor = null): Payment
    {
        $idempotencyKey = $data['idempotency_key'] ?? null;
        if ($idempotencyKey) {
            $existing = Payment::where('idempotency_key', $idempotencyKey)
                ->with(['party', 'financialAccount', 'allocations'])
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $amount = (int) ($data['amount_rials'] ?? 0);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount_rials' => 'مبلغ پرداخت/دریافت باید بیشتر از صفر باشد.',
            ]);
        }

        return DB::transaction(function () use ($data, $amount, $idempotencyKey, $actor) {
            $party = Party::findOrFail($data['party_id']);
            $financialAccount = FinancialAccount::with('ledgerAccount')->findOrFail($data['financial_account_id']);

            $date = $data['date'] instanceof \DateTimeInterface
                ? $data['date']->format('Y-m-d')
                : $data['date'];

            $prefix = $data['type'] === 'receipt' ? 'REC' : 'PAY';
            $countToday = Payment::whereDate('created_at', now()->toDateString())->count();
            $paymentNumber = $prefix . '-' . now()->format('Ymd') . '-' . str_pad((string) ($countToday + 1), 4, '0', STR_PAD_LEFT);

            $payment = Payment::create([
                'type' => $data['type'],
                'payment_number' => $paymentNumber,
                'party_id' => $party->id,
                'financial_account_id' => $financialAccount->id,
                'amount_rials' => $amount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference_number' => $data['reference_number'] ?? null,
                'date' => $date,
                'idempotency_key' => $idempotencyKey,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor?->id ?? 1,
            ]);

            // Process allocations if provided
            $allocations = $data['allocations'] ?? [];
            $totalAllocated = 0;
            foreach ($allocations as $allocation) {
                $allocAmount = (int) $allocation['amount_rials'];
                if ($allocAmount <= 0) {
                    continue;
                }

                $invoice = Invoice::where('id', $allocation['invoice_id'])->lockForUpdate()->firstOrFail();
                if ($invoice->party_id !== $party->id) {
                    throw ValidationException::withMessages([
                        'allocations' => 'فاکتور انتخاب شده متعلق به این طرف‌حساب نیست.',
                    ]);
                }

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'amount_rials' => $allocAmount,
                ]);

                $totalAllocated += $allocAmount;
            }

            if ($totalAllocated > $amount) {
                throw ValidationException::withMessages([
                    'allocations' => 'مجموع مبالغ تخصیص‌یافته به فاکتورها نمی‌تواند بیشتر از مبلغ کل پرداخت باشد.',
                ]);
            }

            // Balanced GL Entry
            if ($data['type'] === 'payment') {
                // Payment to supplier:
                // Debit: 201 (Accounts Payable) with party_id
                // Credit: 101 (Cash & Bank) via financial_account's ledger_account
                $this->recordJournalEntryAction->execute([
                    'date' => $date,
                    'description' => "پرداخت وجه به {$party->name} سند {$paymentNumber}",
                    'source_type' => Payment::class,
                    'source_id' => $payment->id,
                    'lines' => [
                        [
                            'account_code' => '201',
                            'party_id' => $party->id,
                            'debit_rials' => $amount,
                            'credit_rials' => 0,
                            'notes' => "کاهش بدهی به تأمین‌کننده {$party->name}",
                        ],
                        [
                            'ledger_account_id' => $financialAccount->ledger_account_id,
                            'debit_rials' => 0,
                            'credit_rials' => $amount,
                            'notes' => "خروج وجه از {$financialAccount->name}",
                        ],
                    ],
                ]);
            } else {
                // Receipt from customer:
                // Debit: 101 (Cash & Bank) via financial_account's ledger_account
                // Credit: 102 (Accounts Receivable) with party_id
                $this->recordJournalEntryAction->execute([
                    'date' => $date,
                    'description' => "دریافت وجه از {$party->name} سند {$paymentNumber}",
                    'source_type' => Payment::class,
                    'source_id' => $payment->id,
                    'lines' => [
                        [
                            'ledger_account_id' => $financialAccount->ledger_account_id,
                            'debit_rials' => $amount,
                            'credit_rials' => 0,
                            'notes' => "ورود وجه به {$financialAccount->name}",
                        ],
                        [
                            'account_code' => '102',
                            'party_id' => $party->id,
                            'debit_rials' => 0,
                            'credit_rials' => $amount,
                            'notes' => "کاهش طلب از مشتری {$party->name}",
                        ],
                    ],
                ]);
            }

            return $payment->load(['party', 'financialAccount', 'allocations.invoice']);
        });
    }
}

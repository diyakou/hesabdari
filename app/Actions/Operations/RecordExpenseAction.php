<?php

namespace App\Actions\Operations;

use App\Actions\Accounting\RecordJournalEntryAction;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\Party;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordExpenseAction
{
    public function __construct(
        private readonly RecordJournalEntryAction $recordJournalEntryAction,
    ) {}

    /**
     * @param array{
     *     category: string,
     *     financial_account_id: int,
     *     amount_rials: int,
     *     date: string|\DateTimeInterface,
     *     party_id?: int|null,
     *     description?: string|null
     * } $data
     */
    public function execute(array $data, ?User $actor = null): Expense
    {
        $amount = (int) ($data['amount_rials'] ?? 0);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount_rials' => 'مبلغ هزینه باید بیشتر از صفر باشد.',
            ]);
        }

        return DB::transaction(function () use ($data, $amount, $actor) {
            $financialAccount = FinancialAccount::findOrFail($data['financial_account_id']);

            $date = $data['date'] instanceof \DateTimeInterface
                ? $data['date']->format('Y-m-d')
                : $data['date'];

            $countToday = Expense::whereDate('created_at', now()->toDateString())->count();
            $expenseNumber = 'EXP-' . now()->format('Ymd') . '-' . str_pad((string) ($countToday + 1), 4, '0', STR_PAD_LEFT);

            $expense = Expense::create([
                'expense_number' => $expenseNumber,
                'category' => $data['category'],
                'financial_account_id' => $financialAccount->id,
                'party_id' => $data['party_id'] ?? null,
                'amount_rials' => $amount,
                'date' => $date,
                'description' => $data['description'] ?? null,
                'created_by' => $actor?->id ?? 1,
            ]);

            // GL Entry:
            // Debit: 601 (Operating Expenses)
            // Credit: Financial Account's ledger account (101 Cash & Bank)
            $journalDesc = "ثبت هزینه {$data['category']} سند {$expenseNumber}";
            if (! empty($data['description'])) {
                $journalDesc .= " ({$data['description']})";
            }

            $this->recordJournalEntryAction->execute([
                'date' => $date,
                'description' => $journalDesc,
                'source_type' => Expense::class,
                'source_id' => $expense->id,
                'lines' => [
                    [
                        'account_code' => '601', // Operating Expenses
                        'party_id' => $data['party_id'] ?? null,
                        'debit_rials' => $amount,
                        'credit_rials' => 0,
                        'notes' => "هزینه {$data['category']}",
                    ],
                    [
                        'ledger_account_id' => $financialAccount->ledger_account_id,
                        'debit_rials' => 0,
                        'credit_rials' => $amount,
                        'notes' => "پرداخت از حساب {$financialAccount->name}",
                    ],
                ],
            ]);

            return $expense->load(['financialAccount', 'party']);
        });
    }
}

<?php

namespace App\Actions\Operations;

use App\Actions\Accounting\RecordJournalEntryAction;
use App\Models\FinancialAccount;
use App\Models\FundTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordFundTransferAction
{
    public function __construct(
        private readonly RecordJournalEntryAction $recordJournalEntryAction,
    ) {}

    /**
     * @param array{
     *     source_account_id: int,
     *     destination_account_id: int,
     *     amount_rials: int,
     *     date: string|\DateTimeInterface,
     *     tracking_number?: string|null,
     *     notes?: string|null
     * } $data
     */
    public function execute(array $data, ?User $actor = null): FundTransfer
    {
        $amount = (int) ($data['amount_rials'] ?? 0);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount_rials' => 'مبلغ انتقال وجه باید بیشتر از صفر باشد.',
            ]);
        }

        if ((int) $data['source_account_id'] === (int) $data['destination_account_id']) {
            throw ValidationException::withMessages([
                'destination_account_id' => 'حساب مبدأ و مقصد انتقال نمی‌توانند یکسان باشند.',
            ]);
        }

        return DB::transaction(function () use ($data, $amount, $actor) {
            $sourceAccount = FinancialAccount::findOrFail($data['source_account_id']);
            $destAccount = FinancialAccount::findOrFail($data['destination_account_id']);

            $date = $data['date'] instanceof \DateTimeInterface
                ? $data['date']->format('Y-m-d')
                : $data['date'];

            $countToday = FundTransfer::whereDate('created_at', now()->toDateString())->count();
            $transferNumber = 'TRF-' . now()->format('Ymd') . '-' . str_pad((string) ($countToday + 1), 4, '0', STR_PAD_LEFT);

            $transfer = FundTransfer::create([
                'transfer_number' => $transferNumber,
                'source_account_id' => $sourceAccount->id,
                'destination_account_id' => $destAccount->id,
                'amount_rials' => $amount,
                'date' => $date,
                'tracking_number' => $data['tracking_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor?->id ?? 1,
            ]);

            // GL Entry:
            // Debit: Destination Account (Cash & Bank)
            // Credit: Source Account (Cash & Bank)
            // Total net liquid assets unchanged, no revenue/expense created!
            $this->recordJournalEntryAction->execute([
                'date' => $date,
                'description' => "انتقال داخلی وجه از {$sourceAccount->name} به {$destAccount->name}",
                'source_type' => FundTransfer::class,
                'source_id' => $transfer->id,
                'lines' => [
                    [
                        'ledger_account_id' => $destAccount->ledger_account_id,
                        'debit_rials' => $amount,
                        'credit_rials' => 0,
                        'notes' => "ورود وجه به {$destAccount->name}",
                    ],
                    [
                        'ledger_account_id' => $sourceAccount->ledger_account_id,
                        'debit_rials' => 0,
                        'credit_rials' => $amount,
                        'notes' => "خروج وجه از {$sourceAccount->name}",
                    ],
                ],
            ]);

            return $transfer->load(['sourceAccount', 'destinationAccount']);
        });
    }
}

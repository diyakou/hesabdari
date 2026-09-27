<?php

namespace App\Actions\Accounting;

use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordJournalEntryAction
{
    /**
     * @param array{
     *     date: string|\DateTimeInterface,
     *     description: string,
     *     source_type?: string|null,
     *     source_id?: int|null,
     *     lines: array<int, array{
     *         ledger_account_id?: int,
     *         account_code?: string,
     *         party_id?: int|null,
     *         debit_rials?: int,
     *         credit_rials?: int,
     *         notes?: string|null
     *     }>
     * } $data
     */
    public function execute(array $data): JournalEntry
    {
        $sourceType = $data['source_type'] ?? null;
        $sourceId = $data['source_id'] ?? null;

        // Idempotency: if source already has a journal entry, return it
        if ($sourceType && $sourceId) {
            $existing = JournalEntry::where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->with('lines.ledgerAccount')
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $lines = $data['lines'] ?? [];
        if (empty($lines)) {
            throw ValidationException::withMessages([
                'lines' => 'سند حسابداری باید حداقل شامل دو ردیف باشد.',
            ]);
        }

        $totalDebit = 0;
        $totalCredit = 0;
        $preparedLines = [];

        foreach ($lines as $index => $line) {
            $debit = (int) ($line['debit_rials'] ?? 0);
            $credit = (int) ($line['credit_rials'] ?? 0);

            if ($debit < 0 || $credit < 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => 'مبلغ بدهکار یا بستانکار نمی‌تواند منفی باشد.',
                ]);
            }

            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => 'یک ردیف سند نمی‌تواند هم‌زمان دارای بدهکار و بستانکار باشد.',
                ]);
            }

            if ($debit === 0 && $credit === 0) {
                continue; // ignore zero lines
            }

            $accountId = $line['ledger_account_id'] ?? null;
            if (! $accountId && ! empty($line['account_code'])) {
                $account = LedgerAccount::where('code', $line['account_code'])->first();
                if (! $account) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.account_code" => "کد حساب {$line['account_code']} یافت نشد.",
                    ]);
                }
                $accountId = $account->id;
            }

            if (! $accountId) {
                throw ValidationException::withMessages([
                    "lines.{$index}.ledger_account_id" => 'حساب دفتر کل نامشخص است.',
                ]);
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $preparedLines[] = [
                'ledger_account_id' => $accountId,
                'party_id' => $line['party_id'] ?? null,
                'debit_rials' => $debit,
                'credit_rials' => $credit,
                'notes' => $line['notes'] ?? null,
            ];
        }

        if ($totalDebit === 0 && $totalCredit === 0) {
            throw ValidationException::withMessages([
                'lines' => 'مبلغ کل سند نمی‌تواند صفر باشد.',
            ]);
        }

        if ($totalDebit !== $totalCredit) {
            throw ValidationException::withMessages([
                'lines' => "سند حسابداری تراز نیست. جمع بدهکار ({$totalDebit}) با جمع بستانکار ({$totalCredit}) برابر نیست.",
            ]);
        }

        return DB::transaction(function () use ($data, $sourceType, $sourceId, $preparedLines) {
            $date = $data['date'] instanceof \DateTimeInterface
                ? $data['date']->format('Y-m-d')
                : $data['date'];

            // Generate human-readable entry number
            $countToday = JournalEntry::whereDate('created_at', now()->toDateString())->count();
            $entryNumber = 'SANAD-' . now()->format('Ymd') . '-' . str_pad((string) ($countToday + 1), 4, '0', STR_PAD_LEFT);

            $entry = JournalEntry::create([
                'entry_number' => $entryNumber,
                'date' => $date,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $data['description'],
            ]);

            foreach ($preparedLines as $lineData) {
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'ledger_account_id' => $lineData['ledger_account_id'],
                    'party_id' => $lineData['party_id'],
                    'debit_rials' => $lineData['debit_rials'],
                    'credit_rials' => $lineData['credit_rials'],
                    'notes' => $lineData['notes'],
                ]);
            }

            return $entry->load('lines.ledgerAccount');
        });
    }
}

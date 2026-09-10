<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\NumberSequence;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class AccountingPostingService
{
    /** @param list<array{account_code: string, debit?: numeric-string|int|float, credit?: numeric-string|int|float, description?: string|null}> $lines */
    public function post(User $user, string $description, string $sourceType, int $sourceId, array $lines, ?int $branchId = null): JournalEntry
    {
        return DB::transaction(function () use ($user, $description, $sourceType, $sourceId, $lines, $branchId): JournalEntry {
            $postingDate = today();

            if (! AccountingPeriod::query()->where('status', 'Open')->whereDate('starts_on', '<=', $postingDate)->whereDate('ends_on', '>=', $postingDate)->exists()) {
                throw new DomainException('No open accounting period exists for the posting date.');
            }

            $debits = collect($lines)->sum(fn (array $line): int => $this->toMinorUnits($line['debit'] ?? 0));
            $credits = collect($lines)->sum(fn (array $line): int => $this->toMinorUnits($line['credit'] ?? 0));

            if ($debits <= 0 || $debits !== $credits) {
                throw new DomainException('Journal entry debits and credits must be equal and greater than zero.');
            }

            $entry = JournalEntry::query()->create([
                'branch_id' => $branchId,
                'journal_number' => NumberSequence::issue('journal'),
                'posting_date' => $postingDate,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
                'status' => 'Posted',
                'created_by' => $user->id,
                'posted_by' => $user->id,
                'posted_at' => now(),
            ]);

            foreach ($lines as $line) {
                $account = ChartOfAccount::query()->where('code', $line['account_code'])->where('is_active', true)->firstOrFail();
                $entry->lines()->create(['chart_of_account_id' => $account->id, 'branch_id' => $branchId, 'description' => $line['description'] ?? $description, 'debit' => $this->fromMinorUnits($this->toMinorUnits($line['debit'] ?? 0)), 'credit' => $this->fromMinorUnits($this->toMinorUnits($line['credit'] ?? 0))]);
            }

            return $entry->load('lines.account');
        });
    }

    private function toMinorUnits(string|int|float $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function fromMinorUnits(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}

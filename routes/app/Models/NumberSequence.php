<?php

namespace App\Models;

use Database\Factories\NumberSequenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class NumberSequence extends Model
{
    protected $fillable = [
        'branch_id',
        'key',
        'prefix',
        'next_number',
        'padding',
        'reset_annually',
        'current_year',
    ];

    /** @use HasFactory<NumberSequenceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['reset_annually' => 'boolean'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public static function issue(string $key, ?int $branchId = null): string
    {
        return DB::transaction(function () use ($key, $branchId): string {
            $sequence = self::query()
                ->where('key', $key)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->firstOrFail();

            $year = (int) now()->year;

            if ($sequence->reset_annually && $sequence->current_year !== $year) {
                $sequence->next_number = 1;
                $sequence->current_year = $year;
            }

            do {
                $number = str_pad((string) $sequence->next_number, $sequence->padding, '0', STR_PAD_LEFT);
                $reference = str_replace('{YEAR}', (string) $year, $sequence->prefix).$number;
                $sequence->next_number++;
            } while (self::referenceAlreadyExists($key, $reference));

            $sequence->save();

            return $reference;
        });
    }

    private static function referenceAlreadyExists(string $key, string $reference): bool
    {
        $referenceLocations = match ($key) {
            'application' => [['loan_applications', 'application_number']],
            'customer' => [['customers', 'customer_number']],
            'loan' => [['loans', 'loan_number']],
            'disbursement' => [['loan_disbursements', 'disbursement_number']],
            'repayment' => [['repayments', 'receipt_number']],
            'transaction' => [['loan_transactions', 'transaction_number'], ['cash_transactions', 'transaction_number']],
            'journal' => [['journal_entries', 'journal_number']],
            'collection_case' => [['collection_cases', 'case_number']],
            'collateral' => [['collaterals', 'collateral_number']],
            default => [],
        };

        if ($referenceLocations === []) {
            return false;
        }

        foreach ($referenceLocations as [$table, $column]) {
            if (DB::table($table)->where($column, $reference)->exists()) {
                return true;
            }
        }

        return false;
    }
}

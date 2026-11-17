<?php

namespace App\Services;

use App\Models\Loan;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

class LoanScheduleService
{
    public function generate(Loan $loan, CarbonImmutable $firstDueDate): void
    {
        DB::transaction(function () use ($loan, $firstDueDate): void {
            $loan = Loan::query()->with('application.productVersion.fees')->lockForUpdate()->findOrFail($loan->id);

            if ($loan->installments()->exists()) {
                throw new DomainException('A repayment schedule already exists for this loan.');
            }

            $version = $loan->application->productVersion;
            $periods = $this->numberOfPeriods($version->repayment_frequency, $loan->application->requested_term, $version->term_unit);
            $principalMinor = $this->toMinorUnits($loan->principal_amount);
            $annualRate = (float) $version->interest_rate;
            $periodRate = match ($version->rate_basis) {
                'Monthly' => $annualRate / ($version->repayment_frequency === 'Monthly' ? 1 : $this->periodsPerYear($version->repayment_frequency) / 12),
                'Whole Term' => $annualRate / $periods,
                default => $annualRate / $this->periodsPerYear($version->repayment_frequency),
            };
            $principalBase = intdiv($principalMinor, $periods);
            $principalRemainder = $principalMinor - ($principalBase * $periods);
            $outstanding = $principalMinor;
            $scheduledFeeMinor = $version->fees
                ->filter(fn ($fee): bool => $fee->collection_point === 'Installment' || $fee->is_financed)
                ->reject(fn ($fee): bool => strcasecmp($fee->name, 'Assurance') === 0)
                ->sum(fn ($fee): int => $this->toMinorUnits($fee->calculation_method === 'Percentage' ? (float) $loan->principal_amount * (float) $fee->value : $fee->value));
            $assurancePaid = (float) $loan->application->paymentSubmissions()->where('purpose', 'Assurance')->where('status', 'Approved')->sum('amount');
            $assuranceMinor = $this->toMinorUnits(max(0, $loan->application->assuranceAmount() - $assurancePaid));
            $feeBase = intdiv($scheduledFeeMinor, $periods);
            $feeRemainder = $scheduledFeeMinor - ($feeBase * $periods);

            for ($number = 1; $number <= $periods; $number++) {
                $principal = $principalBase + ($number === $periods ? $principalRemainder : 0);
                $interestBase = $version->interest_method === 'Flat' ? $principalMinor : $outstanding;
                $interest = (int) round($interestBase * $periodRate);

                $loan->installments()->create([
                    'installment_number' => $number,
                    'due_date' => $this->dueDate($firstDueDate, $version->repayment_frequency, $number - 1),
                    'principal_due' => $this->fromMinorUnits($principal),
                    'interest_due' => $this->fromMinorUnits($interest),
                    'fees_due' => $this->fromMinorUnits($feeBase + ($number === $periods ? $feeRemainder : 0) + ($number === 1 ? $assuranceMinor : 0)),
                    'penalties_due' => 0,
                    'status' => 'Pending',
                ]);
                $outstanding -= $principal;
            }
        });
    }

    private function numberOfPeriods(string $frequency, int $term, string $termUnit): int
    {
        $months = match ($termUnit) {
            'Days' => $term / 30,
            'Weeks' => $term / 4.345,
            default => $term,
        };

        return max(1, (int) round($months * match ($frequency) {
            'Daily' => 30,
            'Weekly' => 4.345,
            'Biweekly' => 2.1725,
            default => 1,
        }));
    }

    private function periodsPerYear(string $frequency): int
    {
        return match ($frequency) {
            'Daily' => 365,
            'Weekly' => 52,
            'Biweekly' => 26,
            default => 12,
        };
    }

    private function dueDate(CarbonImmutable $firstDueDate, string $frequency, int $offset): CarbonImmutable
    {
        return match ($frequency) {
            'Daily' => $firstDueDate->addDays($offset),
            'Weekly' => $firstDueDate->addWeeks($offset),
            'Biweekly' => $firstDueDate->addWeeks($offset * 2),
            default => $firstDueDate->addMonthsNoOverflow($offset),
        };
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

<?php

namespace App\Services;

use App\Models\LoanProductVersion;
use InvalidArgumentException;

class LoanQuotationService
{
    /** @return array<string, mixed> */
    public function quote(LoanProductVersion $version, float $amount, int $term, float $monthlyIncome, float $monthlyExpenses, float $existingDebtPayment): array
    {
        if ($amount <= 0 || $term <= 0) {
            throw new InvalidArgumentException('Amount and term must be greater than zero.');
        }

        $periods = $this->periods($version, $term);
        $periodicRate = match ($version->rate_basis) {
            'Monthly' => (float) $version->interest_rate / ($version->repayment_frequency === 'Monthly' ? 1 : $this->periodsPerYear($version->repayment_frequency) / 12),
            'Whole Term' => (float) $version->interest_rate / $periods,
            default => (float) $version->interest_rate / $this->periodsPerYear($version->repayment_frequency),
        };
        $schedule = $this->schedule($version->interest_method, $amount, $periods, $periodicRate);
        $installment = collect($schedule)->max('installment');
        $totalInterest = collect($schedule)->sum('interest');
        $totalRepaymentBeforeFees = $amount + $totalInterest;
        $fees = $version->fees->map(function ($fee) use ($amount): array {
            $feeAmount = $fee->calculation_method === 'Percentage'
                ? $amount * (float) $fee->value
                : (float) $fee->value;

            return ['name' => $fee->name, 'amount' => round($feeAmount, 2), 'collection_point' => $fee->collection_point, 'is_financed' => $fee->is_financed];
        })->values();
        $totalFees = $fees->sum('amount');
        $assurance = round($amount * (float) $version->assurance_percent / 100, 2);
        if ($assurance > 0) {
            $fees->push(['name' => 'Assurance', 'amount' => $assurance, 'collection_point' => 'Installment', 'is_financed' => true]);
        }
        $totalFees += $assurance;
        $scheduledFees = $fees->reject(fn (array $fee): bool => $fee['name'] === 'Assurance')
            ->filter(fn (array $fee): bool => $fee['collection_point'] === 'Installment' || $fee['is_financed'])->sum('amount');
        $schedule = $this->applyScheduledFees($schedule, $scheduledFees, $assurance);
        $installment = collect($schedule)->max('installment');
        $deductedFees = $fees->where('collection_point', 'Disbursement')->where('is_financed', false)->sum('amount');
        $disposableIncome = max(0, $monthlyIncome - $monthlyExpenses - $existingDebtPayment);
        $maximumInstallment = $disposableIncome * (float) $version->maximum_installment_ratio;
        $affordabilityFactor = $installment > 0 ? min(1, $maximumInstallment / $installment) : 0;
        $recommendedAmount = min($amount, $amount * $affordabilityFactor, (float) $version->maximum_amount);
        $policyFailures = [];

        if ($term < $version->minimum_term || $term > $version->maximum_term) {
            $policyFailures[] = "Requested term {$term} {$version->term_unit} is outside the product range of {$version->minimum_term} to {$version->maximum_term} {$version->term_unit}.";
        }

        if ($amount < (float) $version->minimum_amount || $amount > (float) $version->maximum_amount) {
            $policyFailures[] = 'Requested principal '.number_format($amount, 2).' is outside the product range of '.number_format((float) $version->minimum_amount, 2).' to '.number_format((float) $version->maximum_amount, 2).'.';
        }

        if ($installment > $maximumInstallment) {
            $policyFailures[] = 'Projected installment '.number_format($installment, 2).' exceeds the affordability limit of '.number_format($maximumInstallment, 2).'.';
        }

        if ($deductedFees >= $amount) {
            $policyFailures[] = 'Fees deducted at disbursement must be lower than the requested principal.';
        }

        return [
            'periods' => $periods,
            'periodic_rate' => round($periodicRate, 8),
            'installment' => round($installment, 2),
            'principal' => round($amount, 2),
            'annual_interest_rate' => round((float) $version->interest_rate, 6),
            'interest_method' => $version->interest_method,
            'rate_basis' => $version->rate_basis,
            'repayment_frequency' => $version->repayment_frequency,
            'term' => $term,
            'term_unit' => $version->term_unit,
            'total_interest' => round($totalInterest, 2),
            'fees' => $fees->all(),
            'total_fees' => round($totalFees, 2),
            'assurance_percent' => (float) $version->assurance_percent,
            'assurance_amount' => $assurance,
            'total_repayment' => round($totalRepaymentBeforeFees + $scheduledFees + $assurance, 2),
            'total_customer_cost' => round($totalRepaymentBeforeFees + $totalFees, 2),
            'estimated_net_disbursement' => round(max(0, $amount - $deductedFees), 2),
            'projected_schedule' => $schedule,
            'disposable_income' => round($disposableIncome, 2),
            'maximum_installment' => round($maximumInstallment, 2),
            'recommended_amount' => round(max(0, $recommendedAmount), 2),
            'installment_ratio' => $disposableIncome > 0 ? round($installment / $disposableIncome, 4) : 999,
            'affordable' => $policyFailures === [],
            'policy_failures' => $policyFailures,
            'formula' => $version->interest_method === 'Flat'
                ? 'Installment = (Principal + Principal x periodic rate x periods) / periods. Rate basis: '.$version->rate_basis.'.'
                : 'Principal per period = Principal / periods; Interest = opening principal x periodic rate; Installment = principal per period + interest',
            'affordability_formula' => 'Maximum installment = (monthly income - monthly expenses - existing debt) x maximum installment ratio',
            'policy' => [
                'minimum_amount' => (float) $version->minimum_amount,
                'maximum_amount' => (float) $version->maximum_amount,
                'minimum_term' => $version->minimum_term,
                'maximum_term' => $version->maximum_term,
                'maximum_installment_ratio' => (float) $version->maximum_installment_ratio,
                'required_guarantors' => $version->required_guarantors,
                'requires_collateral' => $version->requires_collateral,
                'principal_grace_period' => $version->principal_grace_period,
                'interest_grace_period' => $version->interest_grace_period,
            ],
        ];
    }

    /** @param array<int, array<string, int|float>> $schedule @return array<int, array<string, int|float>> */
    private function applyScheduledFees(array $schedule, float $totalFees, float $firstInstallmentFee = 0): array
    {
        $feeMinor = (int) round($totalFees * 100);
        $periods = count($schedule);
        $base = intdiv($feeMinor, $periods);
        $remainder = $feeMinor - ($base * $periods);

        return collect($schedule)->map(function (array $row, int $index) use ($base, $remainder, $periods, $firstInstallmentFee): array {
            $fee = ($base + ($index === $periods - 1 ? $remainder : 0)) / 100 + ($index === 0 ? $firstInstallmentFee : 0);
            $row['fees'] = round($fee, 2);
            $row['installment'] = round((float) $row['installment'] + $fee, 2);

            return $row;
        })->all();
    }

    /** @return array<int, array{number: int, opening_principal: float, principal: float, interest: float, fees: float, installment: float, closing_principal: float}> */
    private function schedule(string $interestMethod, float $amount, int $periods, float $periodicRate): array
    {
        $principalMinor = (int) round($amount * 100);
        $principalBase = intdiv($principalMinor, $periods);
        $principalRemainder = $principalMinor - ($principalBase * $periods);
        $outstandingMinor = $principalMinor;
        $schedule = [];

        for ($number = 1; $number <= $periods; $number++) {
            $principalMinorForPeriod = $principalBase + ($number === $periods ? $principalRemainder : 0);
            $interestBaseMinor = $interestMethod === 'Flat' ? $principalMinor : $outstandingMinor;
            $interestMinor = (int) round($interestBaseMinor * $periodicRate);
            $closingMinor = max(0, $outstandingMinor - $principalMinorForPeriod);
            $schedule[] = [
                'number' => $number,
                'opening_principal' => round($outstandingMinor / 100, 2),
                'principal' => round($principalMinorForPeriod / 100, 2),
                'interest' => round($interestMinor / 100, 2),
                'fees' => 0.0,
                'installment' => round(($principalMinorForPeriod + $interestMinor) / 100, 2),
                'closing_principal' => round($closingMinor / 100, 2),
                'interest_base' => round($interestBaseMinor / 100, 2),
                'interest_calculation' => number_format($interestBaseMinor / 100, 2, '.', ',').' × '.number_format($periodicRate * 100, 6).'% = '.number_format($interestMinor / 100, 2, '.', ','),
            ];
            $outstandingMinor = $closingMinor;
        }

        return $schedule;
    }

    private function periods(LoanProductVersion $version, int $term): int
    {
        $months = match ($version->term_unit) {
            'Days' => $term / 30,
            'Weeks' => $term / 4.345,
            default => $term,
        };

        return max(1, (int) round($months * match ($version->repayment_frequency) {
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
}

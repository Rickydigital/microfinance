<?php

namespace Database\Seeders;

use App\CustomerStatus;
use App\LoanApplicationStatus;
use App\LoanStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanDisbursement;
use App\Models\LoanInstallment;
use App\Models\LoanProduct;
use App\Models\LoanProductVersion;
use App\Models\ProductFee;
use App\Models\Repayment;
use App\Models\RepaymentAllocation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MyCashHistoricalLoansSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $branch = Branch::query()->where('code', config('microfinance.defaults.branch.code'))->firstOrFail();
            $importer = $this->importer($branch);
            $products = $this->products($importer);
            $customers = [];

            foreach ($this->loans() as $index => $loan) {
                $loan += $this->loanLedgerData()[$index + 1];
                $customerKey = $this->customerKey($loan['name']);
                $customers[$customerKey] ??= $this->customer($branch, $importer, $customerKey, $loan, count($customers) + 1);
                $loanDate = CarbonImmutable::createFromFormat('d/m/Y', $loan['date'])->startOfDay();
                $isCompleted = $loan['condition'] === 'COMPLETED';

                $application = LoanApplication::query()->updateOrCreate(
                    ['application_number' => sprintf('MYC-2026-%04d', $index + 1)],
                    [
                        'branch_id' => $branch->id,
                        'customer_id' => $customers[$customerKey]->id,
                        'loan_product_version_id' => $products[(string) $loan['rate']]->id,
                        'created_by' => $importer->id,
                        'source' => 'historical-import',
                        'requested_amount' => $loan['amount'],
                        'requested_term' => $loan['term'],
                        'repayment_frequency' => 'Monthly',
                        'purpose' => 'Historical MyCash loan migrated from the legacy workbook.',
                        'declared_monthly_income' => 0,
                        'declared_monthly_expenses' => 0,
                        'existing_debt_payment' => 0,
                        'approved_amount' => $loan['amount'],
                        'status' => LoanApplicationStatus::ConvertedToLoan,
                        'submitted_at' => $loanDate,
                        'decided_at' => $loanDate,
                        'created_at' => $loanDate,
                        'updated_at' => $loanDate,
                    ],
                );

                $this->loan($application, $importer, $loan, $loanDate, $index + 1);
            }
        });
    }

    /** @param array{name: string, phone: ?string, amount: int, date: string, rate: float, installment: int, term: int, paid_summary: ?int, condition: string} $source */
    private function loan(LoanApplication $application, User $importer, array $source, CarbonImmutable $loanDate, int $sequence): void
    {
        $isCompleted = $source['condition'] === 'COMPLETED';
        $maturityDate = $loanDate->addMonths($source['term']);
        $daysPastDue = $isCompleted || $maturityDate->isFuture() ? 0 : $maturityDate->diffInDays(today());
        $loan = Loan::query()->updateOrCreate(
            ['loan_application_id' => $application->id],
            [
                'branch_id' => $application->branch_id,
                'customer_id' => $application->customer_id,
                'loan_number' => sprintf('MYC-LN-2026-%04d', $sequence),
                'principal_amount' => $source['amount'],
                'outstanding_principal' => $isCompleted ? 0 : $source['amount'],
                'arrears_amount' => 0,
                'days_past_due' => $daysPastDue,
                'status' => $isCompleted ? LoanStatus::Settled : ($daysPastDue > 0 ? LoanStatus::InArrears : LoanStatus::Active),
                'disbursed_at' => $loanDate,
                'maturity_date' => $maturityDate,
                'imported_at' => now(),
                'imported_by' => $importer->id,
                'historical_net_disbursement' => $source['amount'],
                'historical_upfront_income' => 0,
                'created_at' => $loanDate,
                'updated_at' => $loanDate,
            ],
        );
        LoanDisbursement::query()->updateOrCreate(
            ['loan_application_id' => $application->id],
            [
                'loan_id' => $loan->id,
                'branch_id' => $application->branch_id,
                'disbursement_number' => sprintf('MYC-DSB-2026-%04d', $sequence),
                'gross_amount' => $source['amount'],
                'deducted_fees' => 0,
                'net_amount' => $source['amount'],
                'method' => 'Historical import',
                'destination_reference' => sprintf('MYCASH-WORKBOOK-%04d', $sequence),
                'provider_reference' => sprintf('LEGACY-%04d', $sequence),
                'status' => 'Disbursed',
                'created_by' => $importer->id,
                'authorized_by' => $importer->id,
                'authorized_at' => $loanDate,
                'disbursed_at' => $loanDate,
                'created_at' => $loanDate,
                'updated_at' => $loanDate,
            ],
        );

        $repaymentRows = $this->repayments()[$sequence] ?? [];
        $recordedRepayments = array_sum(array_column($repaymentRows, 'amount'));
        $contractTotal = $isCompleted && $recordedRepayments > 0
            ? $recordedRepayments
            : max($source['amount'], $source['installment'] * $source['term']);
        $totalInterest = max(0, $contractTotal - $source['amount']);
        $principalPerInstallment = round($source['amount'] / $source['term'], 2);
        $reducingWeightTotal = ($source['term'] * ($source['term'] + 1)) / 2;
        $allocatedInterest = 0.0;

        for ($number = 1; $number <= $source['term']; $number++) {
            $principal = $number === $source['term']
                ? $source['amount'] - ($principalPerInstallment * ($source['term'] - 1))
                : $principalPerInstallment;
            $interest = $number === $source['term']
                ? $totalInterest - $allocatedInterest
                : round($totalInterest * (($source['term'] - $number + 1) / $reducingWeightTotal), 2);
            $allocatedInterest += $interest;
            LoanInstallment::query()->updateOrCreate(
                ['loan_id' => $loan->id, 'installment_number' => $number],
                [
                    'due_date' => $loanDate->addMonths($number),
                    'principal_due' => $principal,
                    'interest_due' => $interest,
                    'fees_due' => 0,
                    'penalties_due' => 0,
                    'principal_paid' => 0,
                    'interest_paid' => 0,
                    'fees_paid' => 0,
                    'penalties_paid' => 0,
                    'status' => 'Pending',
                ],
            );
        }

        $this->importRepayments($loan, $importer, $repaymentRows, $sequence, $isCompleted);
    }

    private function importer(Branch $branch): User
    {
        return User::query()->updateOrCreate(
            ['email' => 'historical.import@mycash.local'],
            [
                'branch_id' => $branch->id,
                'staff_number' => 'MYC-HIST-IMPORT',
                'first_name' => 'Historical',
                'last_name' => 'Import',
                'password' => Hash::make(Str::random(64)),
                'is_active' => false,
            ],
        );
    }

    /**
     * @param  list<array{date: string, amount: int}>  $rows
     */
    private function importRepayments(Loan $loan, User $importer, array $rows, int $sequence, bool $isCompleted): void
    {
        $existingIds = Repayment::query()->where('loan_id', $loan->id)
            ->where(function ($query): void {
                $query->where('idempotency_key', 'like', 'mycash-sheet2-%')
                    ->orWhere('idempotency_key', 'like', 'mycash-historical-%');
            })->pluck('id');
        RepaymentAllocation::query()->whereIn('repayment_id', $existingIds)->delete();
        Repayment::query()->whereIn('id', $existingIds)->delete();

        $installments = $loan->installments()->orderBy('due_date')->orderBy('installment_number')->get();
        $loan->installments()->update([
            'principal_paid' => 0,
            'interest_paid' => 0,
            'fees_paid' => 0,
            'penalties_paid' => 0,
            'status' => 'Pending',
        ]);
        $principalPaid = 0.0;

        foreach ($rows as $index => $row) {
            $paidAt = CarbonImmutable::createFromFormat('Y-m-d', $row['date'])->startOfDay();
            $repayment = Repayment::query()->create([
                'loan_id' => $loan->id,
                'receipt_number' => sprintf('MYC-RCP-%04d-%03d', $sequence, $index + 1),
                'amount' => $row['amount'],
                'status' => 'Posted',
                'payment_method' => 'Historical import',
                'external_reference' => sprintf('MYCASH-SHEET2-%04d-%03d', $sequence, $index + 1),
                'paid_at' => $paidAt,
                'received_by' => $importer->id,
                'idempotency_key' => sprintf('mycash-sheet2-%04d-%03d', $sequence, $index + 1),
                'created_at' => $paidAt,
                'updated_at' => $paidAt,
            ]);
            $available = (float) $row['amount'];

            foreach ($installments as $installment) {
                foreach (['interest', 'principal'] as $component) {
                    $outstanding = max(0, (float) $installment->getAttribute("{$component}_due") - (float) $installment->getAttribute("{$component}_paid"));
                    $allocated = min($available, $outstanding);

                    if ($allocated <= 0) {
                        continue;
                    }

                    $paidColumn = "{$component}_paid";
                    $installment->setAttribute($paidColumn, (float) $installment->getAttribute($paidColumn) + $allocated);
                    $installment->status = $installment->isFullyPaid() ? 'Paid' : 'Partially Paid';
                    $installment->save();
                    RepaymentAllocation::query()->create([
                        'repayment_id' => $repayment->id,
                        'loan_installment_id' => $installment->id,
                        'component' => $component,
                        'amount' => $allocated,
                    ]);
                    $available -= $allocated;

                    if ($component === 'principal') {
                        $principalPaid += $allocated;
                    }

                    if ($available <= 0) {
                        break 2;
                    }
                }
            }
        }

        if ($isCompleted) {
            $loan->update(['outstanding_principal' => 0, 'arrears_amount' => 0, 'days_past_due' => 0, 'status' => LoanStatus::Settled]);

            return;
        }

        $outstandingPrincipal = max(0, $loan->principal_amount - $principalPaid);
        $overdue = $installments->filter(fn (LoanInstallment $installment): bool => $installment->due_date->isPast())
            ->sum(fn (LoanInstallment $installment): float => max(0, (float) $installment->principal_due + (float) $installment->interest_due - (float) $installment->principal_paid - (float) $installment->interest_paid));
        $daysPastDue = $overdue > 0
            ? $installments->filter(fn (LoanInstallment $installment): bool => $installment->due_date->isPast() && ! $installment->isFullyPaid())->max(fn (LoanInstallment $installment): int => $installment->due_date->diffInDays(today()))
            : 0;
        $loan->update([
            'outstanding_principal' => $outstandingPrincipal,
            'arrears_amount' => $overdue,
            'days_past_due' => $daysPastDue,
            'status' => $overdue > 0 ? LoanStatus::InArrears : LoanStatus::Active,
        ]);
    }

    /** @return array<int, list<array{date: string, amount: int}>> */
    private function repayments(): array
    {
        $rows = static fn (array $payments): array => array_map(
            static fn (array $payment): array => ['date' => $payment[0], 'amount' => $payment[1]],
            $payments,
        );

        return [
            1 => $rows([['2026-05-08', 296000], ['2026-06-08', 1483000]]),
            2 => $rows([['2026-04-29', 395000], ['2026-05-31', 395000], ['2026-07-04', 395000], ['2026-07-22', 690000], ['2026-07-27', 490000]]),
            3 => $rows([['2026-05-14', 1100000], ['2026-06-15', 1050000]]),
            4 => $rows([['2026-03-28', 105000], ['2026-04-28', 105000], ['2026-05-28', 105000]]),
            5 => $rows([['2026-03-14', 663000], ['2026-04-14', 663000], ['2026-05-14', 663000]]),
            6 => $rows([['2026-05-28', 7245000]]),
            7 => $rows([['2026-04-25', 62500], ['2026-05-02', 125000], ['2026-05-16', 62500], ['2026-05-23', 62500], ['2026-05-31', 62500], ['2026-06-06', 62500], ['2026-06-13', 62500], ['2026-06-21', 62500], ['2026-06-28', 125000], ['2026-07-12', 62500], ['2026-07-19', 62500], ['2026-07-26', 62500], ['2026-08-02', 125000], ['2026-08-16', 62500], ['2026-08-22', 62500], ['2026-08-29', 62500], ['2026-09-05', 62500], ['2026-09-13', 62500], ['2026-09-21', 62500], ['2026-09-28', 62500], ['2026-10-05', 62500], ['2026-10-12', 62500], ['2026-10-19', 62500], ['2026-10-26', 62500], ['2026-11-02', 121500]]),
            8 => $rows([['2026-05-01', 2900000]]),
            9 => $rows([['2026-02-28', 105000], ['2026-03-28', 105000], ['2026-04-28', 105000]]),
            10 => $rows([['2026-06-28', 187000], ['2026-07-28', 187000], ['2026-08-28', 106000], ['2026-09-28', 227500], ['2026-10-30', 227500]]),
            11 => $rows([['2026-07-07', 525000]]),
            12 => $rows([['2026-08-03', 1100000], ['2026-09-03', 1100000], ['2026-10-02', 1100000]]),
            13 => $rows([['2026-08-04', 1075000], ['2026-09-04', 1075000]]),
            14 => $rows([['2026-07-08', 1000000], ['2026-07-13', 210000]]),
            15 => $rows([['2026-08-11', 550000]]),
            16 => $rows([['2026-08-14', 189000], ['2026-09-14', 189000], ['2026-10-14', 189000]]),
            17 => $rows([['2026-08-16', 5500000]]),
            18 => $rows([['2026-09-10', 3300000]]),
            19 => $rows([['2026-09-14', 500000], ['2026-10-13', 420000]]),
            20 => $rows([['2026-08-28', 105000]]),
            21 => $rows([['2026-08-28', 55000], ['2026-09-28', 55000]]),
            22 => $rows([['2026-09-28', 55000], ['2026-10-24', 55000]]),
            23 => $rows([['2026-10-03', 1100000]]),
            24 => $rows([['2026-09-28', 110000], ['2026-10-30', 110000]]),
            26 => $rows([['2026-10-30', 110000]]),
            27 => $rows([['2026-04-10', 100000], ['2026-04-17', 100000], ['2026-07-24', 100000], ['2026-05-01', 100000], ['2026-05-09', 100000], ['2026-05-15', 100000], ['2026-05-22', 100000], ['2026-05-26', 1000000], ['2026-05-28', 100000], ['2026-06-05', 100000], ['2026-08-28', 100000], ['2026-09-07', 100000], ['2026-09-12', 100000], ['2026-09-19', 100000], ['2026-09-26', 100000], ['2026-10-03', 100000], ['2026-10-10', 100000], ['2026-10-19', 100000]]),
            28 => $rows([['2026-05-08', 1500000], ['2026-07-23', 800000], ['2026-10-06', 2500000]]),
            29 => $rows([['2026-07-19', 600000], ['2026-08-22', 160000], ['2026-09-23', 160000]]),
            30 => $rows([['2026-07-28', 233000], ['2026-08-28', 233000], ['2026-09-28', 233000], ['2026-10-30', 233000]]),
            31 => $rows([['2026-09-01', 890000], ['2026-10-01', 890000]]),
            32 => $rows([['2026-09-08', 100000]]),
            33 => $rows([['2026-09-19', 847500], ['2026-10-19', 847500]]),
            34 => $rows([['2026-08-24', 50000], ['2026-09-24', 50000], ['2026-09-24', 150000]]),
            35 => $rows([['2026-09-24', 1100000], ['2026-10-22', 1100000]]),
            36 => $rows([['2026-10-13', 550000]]),
            38 => $rows([['2026-11-04', 1128800]]),
            40 => $rows([['2026-10-30', 110000]]),
        ];
    }

    /** @return array<string, LoanProductVersion> */
    private function products(User $importer): array
    {
        LoanProduct::query()->where('code', 'MYCASH-3-5PCT')->update(['code' => 'MYCASH-3PCT']);
        $definitions = [
            ['rate' => 3.0, 'name' => 'Mkopo Mdogo', 'description' => 'Mkopo rahisi kwa mahitaji madogo na ya kila siku.'],
            ['rate' => 5.0, 'name' => 'Mkopo wa Biashara', 'description' => 'Mtaji wa biashara, kuongeza bidhaa na kupanua shughuli zako.'],
            ['rate' => 10.0, 'name' => 'Mkopo wa Dharura', 'description' => 'Fedha ya haraka kwa mahitaji ya dharura yasiyoweza kusubiri.'],
        ];
        $versions = collect($definitions)->mapWithKeys(function (array $definition) use ($importer): array {
            $rate = $definition['rate'];
            $rateLabel = rtrim(rtrim(number_format($rate, 1), '0'), '.');
            $product = LoanProduct::query()->updateOrCreate(
                ['code' => 'MYCASH-'.str_replace('.', '-', $rateLabel).'PCT'],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'is_active' => true,
                ],
            );
            $version = LoanProductVersion::query()->updateOrCreate(
                ['loan_product_id' => $product->id, 'version' => 1],
                [
                    'minimum_amount' => 100000,
                    'maximum_amount' => 7000000,
                    'minimum_term' => 1,
                    'maximum_term' => 10,
                    'term_unit' => 'Months',
                    'interest_method' => 'Reducing Balance',
                    'interest_rate' => $rate / 100,
                    'assurance_percent' => 0,
                    'rate_basis' => 'Monthly',
                    'repayment_frequency' => 'Monthly',
                    'principal_grace_period' => 0,
                    'interest_grace_period' => 0,
                    'required_guarantors' => 0,
                    'requires_collateral' => false,
                    'maximum_installment_ratio' => 1,
                    'effective_from' => '2026-01-01',
                    'effective_to' => null,
                    'created_by' => $importer->id,
                ],
            );
            ProductFee::query()->updateOrCreate(
                ['loan_product_version_id' => $version->id, 'name' => 'Processing Fee'],
                ['calculation_method' => 'Fixed', 'value' => 15000, 'collection_point' => 'Installment', 'is_financed' => true],
            );

            return [(string) $rate => $version];
        })->all();
        $historicalRateVersion = LoanProductVersion::query()->updateOrCreate(
            ['loan_product_id' => $versions['3']->loan_product_id, 'version' => 0],
            [
                'minimum_amount' => 100000,
                'maximum_amount' => 7000000,
                'minimum_term' => 1,
                'maximum_term' => 10,
                'term_unit' => 'Months',
                'interest_method' => 'Reducing Balance',
                'interest_rate' => 0.035,
                'assurance_percent' => 0,
                'rate_basis' => 'Monthly',
                'repayment_frequency' => 'Monthly',
                'principal_grace_period' => 0,
                'interest_grace_period' => 0,
                'required_guarantors' => 0,
                'requires_collateral' => false,
                'maximum_installment_ratio' => 1,
                'effective_from' => '2026-01-01',
                'effective_to' => '2026-12-31',
                'created_by' => $importer->id,
            ],
        );
        ProductFee::query()->updateOrCreate(
            ['loan_product_version_id' => $historicalRateVersion->id, 'name' => 'Processing Fee'],
            ['calculation_method' => 'Fixed', 'value' => 15000, 'collection_point' => 'Installment', 'is_financed' => true],
        );
        $versions['3.5'] = $historicalRateVersion;

        return $versions;
    }

    /** @param array{name: string, phone: ?string, amount: int, date: string, rate: float, term: int, condition: string} $loan */
    private function customer(Branch $branch, User $importer, string $customerKey, array $loan, int $sequence): Customer
    {
        $nameParts = preg_split('/\s+/', $customerKey) ?: [];
        $firstName = array_shift($nameParts) ?: 'Unknown';
        $lastName = count($nameParts) > 0 ? array_pop($nameParts) : 'Customer';

        return Customer::query()->updateOrCreate(
            ['customer_number' => sprintf('MYC-CUS-%04d', $sequence)],
            [
                'branch_id' => $branch->id,
                'assigned_officer_id' => $importer->id,
                'type' => 'Individual',
                'first_name' => Str::title(Str::lower($firstName)),
                'middle_name' => $nameParts === [] ? null : Str::title(Str::lower(implode(' ', $nameParts))),
                'last_name' => Str::title(Str::lower($lastName)),
                'phone' => $loan['phone'] ?: sprintf('000000%04d', $sequence),
                'status' => CustomerStatus::Active,
                'kyc_risk_rating' => 'Medium',
                'verified_at' => CarbonImmutable::createFromFormat('d/m/Y', $loan['date']),
            ],
        );
    }

    private function customerKey(string $name): string
    {
        $normalized = Str::of($name)->upper()->replaceMatches('/[^A-Z0-9 ]/', ' ')->squish()->toString();

        return [
            'NOEL JEREMIA LIWALO' => 'NOEL JEREMIA LIWOLA',
            'ROY WILLBROD KARAZE' => 'ROY KARAZE',
            'ROBERT' => 'ROBERT MABULA',
            'ROBERT MABULA BODABODA' => 'ROBERT MABULA',
        ][$normalized] ?? $normalized;
    }

    /** @return array<int, array{installment: int, paid_summary: ?int}> */
    private function loanLedgerData(): array
    {
        $installments = [296500, 394700, 175000, 105000, 663000, 7245000, 258500, 2970000, 105000, 187000, 525000, 1100000, 1075000, 1210000, 550000, 189000, 5500000, 3300000, 460000, 105000, 55000, 55000, 1100000, 110000, 2200000, 110000, 400000, 5500000, 446000, 233000, 890000, 1100000, 847500, 550000, 1100000, 6050000, 285000, 1128800, 843800, 110000, 2420000];
        $paid = [1779000, 2368200, 2150000, 315000, 1989000, 7245000, 1187500, 2970000, 315000, 374000, 525000, 1100000, 1075000, 1210000, 550000, 189000, 5500000, null, null, 105000, 55000, null, null, null, null, null, 2000000, 2300000, 800000, 233000, null, null, null, null, null, null, null, null, null, null, null];

        return collect($installments)->mapWithKeys(
            fn (int $installment, int $index): array => [$index + 1 => ['installment' => $installment, 'paid_summary' => $paid[$index]]],
        )->all();
    }

    /** @return array<int, array{name: string, phone: ?string, amount: int, date: string, rate: float, term: int, condition: string}> */
    private function loans(): array
    {
        return [
            ['name' => 'KONDO SALUMU SAIDI', 'phone' => '0659764760', 'amount' => 1500000, 'date' => '09/04/2026', 'rate' => 5.0, 'term' => 6, 'condition' => 'COMPLETED'],
            ['name' => 'RASULI YAHAYA NYUNDO', 'phone' => '0742715273', 'amount' => 2000000, 'date' => '09/04/2026', 'rate' => 5.0, 'term' => 6, 'condition' => 'COMPLETED'],
            ['name' => 'RABAEL DANIEL MBOMA', 'phone' => '0797184991', 'amount' => 2000000, 'date' => '13/04/2026', 'rate' => 5.0, 'term' => 2, 'condition' => 'COMPLETED'],
            ['name' => 'NOEL JEREMIA LIWALO', 'phone' => '0788455574', 'amount' => 300000, 'date' => '07/03/2026', 'rate' => 5.0, 'term' => 3, 'condition' => 'COMPLETED'],
            ['name' => 'GETRUDA SHIMA', 'phone' => '0755115562', 'amount' => 1800000, 'date' => '14/02/2026', 'rate' => 3.5, 'term' => 3, 'condition' => 'COMPLETED'],
            ['name' => 'GETRUDA SHIMA', 'phone' => '0755115562', 'amount' => 7000000, 'date' => '28/04/2026', 'rate' => 3.5, 'term' => 1, 'condition' => 'COMPLETED'],
            ['name' => 'JANI CHUGULU', 'phone' => '0741197844', 'amount' => 1500000, 'date' => '17/04/2026', 'rate' => 5.0, 'term' => 7, 'condition' => 'COMPLETED'],
            ['name' => 'MORNING STAR', 'phone' => null, 'amount' => 2700000, 'date' => '02/04/2026', 'rate' => 10.0, 'term' => 1, 'condition' => 'COMPLETED'],
            ['name' => 'MASHAKA BUGUMBA', 'phone' => null, 'amount' => 300000, 'date' => '17/02/2026', 'rate' => 5.0, 'term' => 3, 'condition' => 'COMPLETED'],
            ['name' => 'NOEL JEREMIA LIWOLA', 'phone' => '0788455574', 'amount' => 800000, 'date' => '04/06/2026', 'rate' => 5.0, 'term' => 5, 'condition' => 'COMPLETED'],
            ['name' => 'MWL ELIYA', 'phone' => '0717696903', 'amount' => 500000, 'date' => '08/06/2026', 'rate' => 5.0, 'term' => 1, 'condition' => 'COMPLETED'],
            ['name' => 'RABAEL DANIEL MBOMA', 'phone' => '0797184991', 'amount' => 3000000, 'date' => '03/07/2026', 'rate' => 5.0, 'term' => 3, 'condition' => 'COMPLETED'],
            ['name' => 'MARIAM NAPANDILWA', 'phone' => null, 'amount' => 2000000, 'date' => '04/07/2026', 'rate' => 5.0, 'term' => 2, 'condition' => 'COMPLETED'],
            ['name' => 'MTEJA WA MWANZA', 'phone' => null, 'amount' => 1100000, 'date' => '02/07/2026', 'rate' => 10.0, 'term' => 1, 'condition' => 'COMPLETED'],
            ['name' => 'MAGRETH KUBEHWA', 'phone' => '0757293828', 'amount' => 500000, 'date' => '11/07/2026', 'rate' => 10.0, 'term' => 1, 'condition' => 'COMPLETED'],
            ['name' => 'JEMSI MWASHAMBWA', 'phone' => '0655264478', 'amount' => 500000, 'date' => '14/07/2026', 'rate' => 5.0, 'term' => 3, 'condition' => 'COMPLETED'],
            ['name' => 'ROY WILLBROD KARAZE', 'phone' => '0769527152', 'amount' => 5000000, 'date' => '16/07/2026', 'rate' => 10.0, 'term' => 1, 'condition' => 'COMPLETED'],
            ['name' => 'IPYANA SEMU MWASANU', 'phone' => null, 'amount' => 3000000, 'date' => '09/08/2026', 'rate' => 5.0, 'term' => 1, 'condition' => 'COMPLETED'],
            ['name' => 'MAGRETH KUBEHWA', 'phone' => '0757293828', 'amount' => 800000, 'date' => '13/08/2026', 'rate' => 10.0, 'term' => 2, 'condition' => 'COMPLETED'],
            ['name' => 'KALUNDE RAMADHAN', 'phone' => null, 'amount' => 100000, 'date' => '13/08/2026', 'rate' => 5.0, 'term' => 1, 'condition' => 'COMPLETED'],
            ['name' => 'EDWARD KUSENHA', 'phone' => null, 'amount' => 100000, 'date' => '19/08/2026', 'rate' => 5.0, 'term' => 2, 'condition' => 'COMPLETED'],
            ['name' => 'ELIZABETH MWASHAMBWA', 'phone' => null, 'amount' => 100000, 'date' => '03/09/2026', 'rate' => 5.0, 'term' => 2, 'condition' => 'COMPLETED'],
            ['name' => 'ELIYA MAHENDE', 'phone' => null, 'amount' => 1000000, 'date' => '03/09/2026', 'rate' => 10.0, 'term' => 1, 'condition' => 'COMPLETED'],
            ['name' => 'RICHARD MWASOMBA', 'phone' => null, 'amount' => 200000, 'date' => '03/09/2026', 'rate' => 5.0, 'term' => 2, 'condition' => 'COMPLETED'],
            ['name' => 'RIZIKI TWAHA', 'phone' => '0785943034', 'amount' => 2000000, 'date' => '23/10/2026', 'rate' => 10.0, 'term' => 1, 'condition' => 'ON PROGRESS'],
            ['name' => 'EDWARD KUSENHA', 'phone' => null, 'amount' => 200000, 'date' => '12/10/2026', 'rate' => 5.0, 'term' => 2, 'condition' => 'ON PROGRESS'],
            ['name' => 'RESTITUTA MBOYA', 'phone' => '0650441112', 'amount' => 3000000, 'date' => '23/03/2026', 'rate' => 5.0, 'term' => 10, 'condition' => 'ON PROGRESS'],
            ['name' => 'ROY KARAZE', 'phone' => '0769527152', 'amount' => 5000000, 'date' => '05/04/2026', 'rate' => 10.0, 'term' => 1, 'condition' => 'ON PROGRESS'],
            ['name' => 'ROBERT', 'phone' => '0769527152', 'amount' => 2000000, 'date' => '19/06/2026', 'rate' => 10.0, 'term' => 6, 'condition' => 'ON PROGRESS'],
            ['name' => 'AYOUB ISRAEL KAIJANANTE', 'phone' => '0694210990', 'amount' => 1000000, 'date' => '07/07/2026', 'rate' => 5.0, 'term' => 5, 'condition' => 'ON PROGRESS'],
            ['name' => 'RASULI YAHAYA NYUNDO', 'phone' => '0742715273', 'amount' => 5000000, 'date' => '01/08/2026', 'rate' => 5.0, 'term' => 6, 'condition' => 'ON PROGRESS'],
            ['name' => 'ROBERT MABULA', 'phone' => '0769527152', 'amount' => 1000000, 'date' => '06/08/2026', 'rate' => 10.0, 'term' => 1, 'condition' => 'ON PROGRESS'],
            ['name' => 'VERONICA HENERIKO', 'phone' => '0788107412', 'amount' => 3000000, 'date' => '19/08/2026', 'rate' => 5.0, 'term' => 4, 'condition' => 'ON PROGRESS'],
            ['name' => 'ROBERT MABULA(BODABODA)', 'phone' => '0769527152', 'amount' => 500000, 'date' => '24/08/2026', 'rate' => 10.0, 'term' => 1, 'condition' => 'ON PROGRESS'],
            ['name' => 'DEVOTHA MWENISONGOLE', 'phone' => '07180776468', 'amount' => 3000000, 'date' => '24/08/2026', 'rate' => 5.0, 'term' => 3, 'condition' => 'ON PROGRESS'],
            ['name' => 'IPYANA SEMU MWASANU', 'phone' => null, 'amount' => 5500000, 'date' => '13/09/2026', 'rate' => 10.0, 'term' => 1, 'condition' => 'ON PROGRESS'],
            ['name' => 'CATHERINE BUROGWA', 'phone' => '0718468746', 'amount' => 500000, 'date' => '01/10/2026', 'rate' => 10.0, 'term' => 2, 'condition' => 'ON PROGRESS'],
            ['name' => 'RABAEL DANIEL MBOMA', 'phone' => '0718076468', 'amount' => 4000000, 'date' => '04/10/2026', 'rate' => 5.0, 'term' => 4, 'condition' => 'ON PROGRESS'],
            ['name' => 'MAGRETH KUBEHWA', 'phone' => '0787100598', 'amount' => 2800000, 'date' => '13/10/2026', 'rate' => 5.0, 'term' => 4, 'condition' => 'ON PROGRESS'],
            ['name' => 'ELIZABETH MWASHAMBWA', 'phone' => null, 'amount' => 200000, 'date' => '16/10/2026', 'rate' => 5.0, 'term' => 2, 'condition' => 'ON PROGRESS'],
            ['name' => 'ROSEMARY STEPHANO', 'phone' => null, 'amount' => 2200000, 'date' => '30/10/2026', 'rate' => 10.0, 'term' => 1, 'condition' => 'ON PROGRESS'],
        ];
    }
}

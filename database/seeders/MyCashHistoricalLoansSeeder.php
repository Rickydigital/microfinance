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

    /** @param array{name: string, phone: ?string, amount: int, date: string, rate: float, term: int, condition: string} $source */
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
                'arrears_amount' => $isCompleted ? 0 : $source['amount'],
                'days_past_due' => $daysPastDue,
                'status' => $isCompleted ? LoanStatus::Settled : ($daysPastDue > 0 ? LoanStatus::InArrears : LoanStatus::Active),
                'disbursed_at' => $loanDate,
                'maturity_date' => $maturityDate,
                'imported_at' => now(),
                'imported_by' => $importer->id,
                'historical_net_disbursement' => $source['amount'] - 15000,
                'historical_upfront_income' => 15000,
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
                'deducted_fees' => 15000,
                'net_amount' => $source['amount'] - 15000,
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

        $principalPerInstallment = round($source['amount'] / $source['term'], 2);
        $interestPerInstallment = round($source['amount'] * ($source['rate'] / 100), 2);
        for ($number = 1; $number <= $source['term']; $number++) {
            $principal = $number === $source['term']
                ? $source['amount'] - ($principalPerInstallment * ($source['term'] - 1))
                : $principalPerInstallment;
            LoanInstallment::query()->updateOrCreate(
                ['loan_id' => $loan->id, 'installment_number' => $number],
                [
                    'due_date' => $loanDate->addMonths($number),
                    'principal_due' => $principal,
                    'interest_due' => $interestPerInstallment,
                    'fees_due' => 0,
                    'penalties_due' => 0,
                    'principal_paid' => $isCompleted ? $principal : 0,
                    'interest_paid' => $isCompleted ? $interestPerInstallment : 0,
                    'fees_paid' => 0,
                    'penalties_paid' => 0,
                    'status' => $isCompleted ? 'Paid' : 'Pending',
                ],
            );
        }

        if ($isCompleted) {
            Repayment::query()->updateOrCreate(
                ['receipt_number' => sprintf('MYC-RCP-2026-%04d', $sequence)],
                [
                    'loan_id' => $loan->id,
                    'amount' => $source['amount'] + ($interestPerInstallment * $source['term']),
                    'status' => 'Posted',
                    'payment_method' => 'Historical import',
                    'external_reference' => sprintf('MYCASH-WORKBOOK-%04d', $sequence),
                    'paid_at' => $maturityDate,
                    'received_by' => $importer->id,
                    'idempotency_key' => sprintf('mycash-historical-%04d', $sequence),
                    'created_at' => $maturityDate,
                    'updated_at' => $maturityDate,
                ],
            );
        } else {
            Repayment::query()->where('receipt_number', sprintf('MYC-RCP-2026-%04d', $sequence))->delete();
        }
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
                    'interest_method' => 'Flat',
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
        $versions['3.5'] = $versions['3'];

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

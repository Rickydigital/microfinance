<?php

namespace App\Services;

use App\LoanApplicationStatus;
use App\LoanStatus;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanProductVersion;
use App\Models\NumberSequence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class HistoricalLoanImportService
{
    public const HEADERS = ['loan_number', 'customer_number', 'product_code', 'product_version', 'principal_amount', 'net_disbursement', 'upfront_income', 'disbursed_at', 'installment_number', 'due_date', 'principal_due', 'interest_due', 'fees_due', 'penalties_due', 'principal_paid', 'interest_paid', 'fees_paid', 'penalties_paid'];

    /** @return list<array<string, mixed>> */
    public function read(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path, ['Xlsx', 'Csv']);
        $sheets = $reader->listWorksheetInfo($path);
        if (count($sheets) !== 1 || $sheets[0]['totalRows'] > 2001 || $sheets[0]['totalColumns'] > count(self::HEADERS)) {
            throw ValidationException::withMessages(['file' => 'Use one worksheet with the template columns and at most 2,000 installment rows.']);
        }
        $book = $reader->setReadDataOnly(true)->load($path);
        try {
            $rows = $book->getActiveSheet()->toArray(null, false, false, false);
        } finally {
            $book->disconnectWorksheets();
        }
        $headers = array_map(fn ($value): string => trim((string) $value), array_shift($rows) ?? []);
        if ($headers !== self::HEADERS) {
            throw ValidationException::withMessages(['file' => 'Column headings must match the downloaded template in the same order.']);
        }
        $result = [];
        foreach ($rows as $index => $values) {
            if (count(array_filter($values, fn ($value): bool => $value !== null && $value !== '')) === 0) {
                continue;
            }
            $row = array_combine(self::HEADERS, array_pad($values, count(self::HEADERS), null));
            foreach (['disbursed_at', 'due_date'] as $column) {
                if (is_numeric($row[$column])) {
                    $row[$column] = Date::excelToDateTimeObject((float) $row[$column])->format('Y-m-d');
                }
            }
            $row['_row'] = $index + 2;
            $result[] = $row;
        }

        return $result;
    }

    /** @param list<array<string, mixed>> $rows @return array<string, list<array<string, mixed>>> */
    public function validate(array $rows, User $user): array
    {
        if ($rows === [] || count($rows) > 2000) {
            throw ValidationException::withMessages(['file' => 'Include between 1 and 2,000 installment rows.']);
        }
        $groups = [];
        foreach ($rows as $row) {
            $rules = [
                'loan_number' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9][A-Za-z0-9_\/-]*$/', 'unique:loans,loan_number'],
                'customer_number' => ['required', 'string'], 'product_code' => ['required', 'string'], 'product_version' => ['required', 'integer', 'min:1'],
                'principal_amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999999999.99'],
                'net_disbursement' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'lte:principal_amount'],
                'upfront_income' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99'],
                'disbursed_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
                'due_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:disbursed_at'],
                'installment_number' => ['required', 'integer', 'min:1', 'max:2000'],
            ];
            foreach (['principal', 'interest', 'fees', 'penalties'] as $component) {
                $rules[$component.'_due'] = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99'];
                $rules[$component.'_paid'] = ['required', 'numeric', 'decimal:0,2', 'min:0', 'lte:'.$component.'_due'];
            }
            $validator = Validator::make($row, $rules);
            if ($validator->fails()) {
                throw ValidationException::withMessages(['file' => 'Row '.$row['_row'].': '.implode(' ', $validator->errors()->all())]);
            }
            $customer = Customer::query()->where('customer_number', $row['customer_number'])
                ->when($user->branch_id, fn ($query, $branch) => $query->where('branch_id', $branch))->first();
            $version = LoanProductVersion::query()->where('version', $row['product_version'])->whereHas('product', fn ($query) => $query->where('code', $row['product_code']))->first();
            if ($customer === null || $version === null) {
                throw ValidationException::withMessages(['file' => 'Row '.$row['_row'].': customer must exist in your branch and product version must exist.']);
            }
            $row['customer_id'] = $customer->id;
            $row['branch_id'] = $customer->branch_id;
            $row['version_id'] = $version->id;
            $row['repayment_frequency'] = $version->repayment_frequency;
            $groups[$row['loan_number']][] = $row;
        }
        foreach ($groups as $number => $installments) {
            $first = $installments[0];
            foreach ($installments as $row) {
                foreach (array_slice(self::HEADERS, 0, 8) as $column) {
                    if ((string) $row[$column] !== (string) $first[$column]) {
                        throw ValidationException::withMessages(['file' => "Loan {$number}: loan details must be identical on every installment row."]);
                    }
                }
            }
            $sequence = collect($installments)->pluck('installment_number')->map(fn ($value): int => (int) $value)->sort()->values()->all();
            if ($sequence !== range(1, count($installments))) {
                throw ValidationException::withMessages(['file' => "Loan {$number}: installment numbers must be unique and sequential, starting at 1."]);
            }
            $principal = collect($installments)->sum(fn ($row): int => (int) round((float) $row['principal_due'] * 100));
            if ($principal !== (int) round((float) $first['principal_amount'] * 100)) {
                throw ValidationException::withMessages(['file' => "Loan {$number}: installment principal must add up to the original principal."]);
            }
            if ((float) $first['upfront_income'] < round((float) $first['principal_amount'] - (float) $first['net_disbursement'], 2)) {
                throw ValidationException::withMessages(['file' => "Loan {$number}: upfront income must include fees deducted from principal."]);
            }
        }

        return $groups;
    }

    /** @param list<array<string, mixed>> $rows */
    public function import(array $rows, User $user): int
    {
        abort_unless($user->can('loan.import'), 403);

        return DB::transaction(function () use ($rows, $user): int {
            $groups = $this->validate($rows, $user);
            foreach ($groups as $number => $installments) {
                $first = $installments[0];
                $application = LoanApplication::query()->create([
                    'branch_id' => $first['branch_id'], 'customer_id' => $first['customer_id'], 'loan_product_version_id' => $first['version_id'],
                    'created_by' => $user->id, 'application_number' => NumberSequence::issue('application'), 'source' => 'historical_import',
                    'requested_amount' => $first['principal_amount'], 'approved_amount' => $first['principal_amount'], 'requested_term' => count($installments),
                    'repayment_frequency' => $first['repayment_frequency'], 'purpose' => 'Historical loan import: '.$number, 'status' => LoanApplicationStatus::ConvertedToLoan,
                ]);
                $principalPaid = collect($installments)->sum('principal_paid');
                $remaining = collect($installments)->sum(fn ($row): float => (float) $row['principal_due'] + (float) $row['interest_due'] + (float) $row['fees_due'] + (float) $row['penalties_due'] - (float) $row['principal_paid'] - (float) $row['interest_paid'] - (float) $row['fees_paid'] - (float) $row['penalties_paid']);
                $loan = Loan::query()->create([
                    'branch_id' => $first['branch_id'], 'customer_id' => $first['customer_id'], 'loan_application_id' => $application->id,
                    'loan_number' => $number, 'principal_amount' => $first['principal_amount'], 'outstanding_principal' => round((float) $first['principal_amount'] - $principalPaid, 2),
                    'status' => round($remaining, 2) > 0 ? LoanStatus::Active : LoanStatus::Settled, 'disbursed_at' => $first['disbursed_at'], 'maturity_date' => collect($installments)->max('due_date'),
                    'imported_at' => now(), 'imported_by' => $user->id, 'historical_net_disbursement' => $first['net_disbursement'], 'historical_upfront_income' => $first['upfront_income'],
                ]);
                $arrears = 0;
                $daysPastDue = 0;
                foreach ($installments as $row) {
                    $data = array_intersect_key($row, array_flip(array_slice(self::HEADERS, 8)));
                    $installment = $loan->installments()->create($data);
                    $outstanding = collect(['principal', 'interest', 'fees', 'penalties'])->sum(fn ($component): int => $installment->outstandingFor($component));
                    $overdue = $outstanding > 0 && $installment->due_date->lt(today());
                    $installment->update(['status' => $outstanding === 0 ? 'Paid' : ($overdue ? 'Overdue' : 'Pending')]);
                    if ($overdue) {
                        $arrears += $outstanding;
                        $daysPastDue = max($daysPastDue, (int) CarbonImmutable::parse($row['due_date'])->diffInDays(today()));
                    }
                }
                $loan->update(['arrears_amount' => $arrears / 100, 'days_past_due' => $daysPastDue, 'status' => $arrears > 0 ? LoanStatus::InArrears : $loan->status]);
            }

            return count($groups);
        });
    }
}

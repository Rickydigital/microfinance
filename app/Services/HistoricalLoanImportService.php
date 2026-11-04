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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class HistoricalLoanImportService
{
    public const HEADERS = ['loan_number', 'customer_number', 'product_code', 'product_version', 'principal_amount', 'net_disbursement', 'upfront_income', 'disbursed_at', 'total_paid', 'status'];

    /** @return list<array<string, mixed>> */
    public function read(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path, ['Xlsx', 'Csv']);
        $sheets = $reader->listWorksheetInfo($path);
        if (count($sheets) !== 1 || $sheets[0]['totalRows'] > 2001 || $sheets[0]['totalColumns'] > count(self::HEADERS)) {
            throw ValidationException::withMessages(['file' => 'Use one worksheet with one row per loan and at most 2,000 loans.']);
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
            foreach (['disbursed_at'] as $column) {
                if (is_numeric($row[$column])) {
                    $row[$column] = Date::excelToDateTimeObject((float) $row[$column])->format('Y-m-d');
                }
            }
            $row['_row'] = $index + 2;
            $result[] = $row;
        }

        return $result;
    }

    /** @param list<array<string, mixed>> $rows @return array<string, array<string, mixed>> */
    public function validate(array $rows, User $user): array
    {
        if ($rows === [] || count($rows) > 2000) {
            throw ValidationException::withMessages(['file' => 'Include between 1 and 2,000 loan rows.']);
        }
        $loans = [];
        foreach ($rows as $row) {
            $rules = [
                'loan_number' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9][A-Za-z0-9_\/-]*$/', 'unique:loans,loan_number'],
                'customer_number' => ['required', 'string'], 'product_code' => ['required', 'string'], 'product_version' => ['required', 'integer', 'min:1'],
                'principal_amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999999999.99'],
                'net_disbursement' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'lte:principal_amount'],
                'upfront_income' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99'],
                'disbursed_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
                'total_paid' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'lte:principal_amount'],
                'status' => ['required', 'in:Active,In Arrears,Settled'],
            ];
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
            if (isset($loans[$row['loan_number']])) {
                throw ValidationException::withMessages(['file' => "Row {$row['_row']}: loan number must be unique in the file."]);
            }
            if ($row['status'] === 'Settled' && (float) $row['total_paid'] < (float) $row['principal_amount']) {
                throw ValidationException::withMessages(['file' => "Row {$row['_row']}: a settled loan must have total paid equal to principal."]);
            }
            $loans[$row['loan_number']] = $row;
        }

        return $loans;
    }

    /** @param list<array<string, mixed>> $rows */
    public function import(array $rows, User $user): int
    {
        abort_unless($user->can('loan.import'), 403);

        return DB::transaction(function () use ($rows, $user): int {
            $groups = $this->validate($rows, $user);
            foreach ($groups as $number => $first) {
                $application = LoanApplication::query()->create([
                    'branch_id' => $first['branch_id'], 'customer_id' => $first['customer_id'], 'loan_product_version_id' => $first['version_id'],
                    'created_by' => $user->id, 'application_number' => NumberSequence::issue('application'), 'source' => 'historical_import',
                    'requested_amount' => $first['principal_amount'], 'approved_amount' => $first['principal_amount'], 'requested_term' => 1,
                    'repayment_frequency' => $first['repayment_frequency'], 'purpose' => 'Historical loan import: '.$number, 'status' => LoanApplicationStatus::ConvertedToLoan,
                ]);
                $loan = Loan::query()->create([
                    'branch_id' => $first['branch_id'], 'customer_id' => $first['customer_id'], 'loan_application_id' => $application->id,
                    'loan_number' => $number, 'principal_amount' => $first['principal_amount'], 'outstanding_principal' => round((float) $first['principal_amount'] - (float) $first['total_paid'], 2),
                    'status' => LoanStatus::from($first['status']), 'disbursed_at' => $first['disbursed_at'], 'maturity_date' => $first['disbursed_at'],
                    'imported_at' => now(), 'imported_by' => $user->id, 'historical_net_disbursement' => $first['net_disbursement'], 'historical_upfront_income' => $first['upfront_income'],
                ]);
                $loan->installments()->create(['installment_number' => 1, 'due_date' => today(), 'principal_due' => $first['principal_amount'], 'interest_due' => 0, 'fees_due' => 0, 'penalties_due' => 0, 'principal_paid' => $first['total_paid'], 'interest_paid' => 0, 'fees_paid' => 0, 'penalties_paid' => 0, 'status' => $first['status'] === 'Settled' ? 'Paid' : 'Pending']);
            }

            return count($groups);
        });
    }
}

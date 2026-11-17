<?php

namespace App\Services;

use App\CustomerStatus;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\NumberSequence;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CustomerImportService
{
    public const HEADERS = ['full_name', 'phone', 'gender', 'date_of_birth', 'email', 'customer_type'];

    /** @return list<array<string, mixed>> */
    public function read(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path, ['Xlsx', 'Csv']);
        $info = $reader->listWorksheetInfo($path);

        if (count($info) !== 1 || $info[0]['totalRows'] > 2001 || $info[0]['totalColumns'] > count(self::HEADERS)) {
            throw ValidationException::withMessages(['file' => 'Use one worksheet with up to 2,000 customers.']);
        }

        $book = $reader->load($path);
        try {
            $sheet = $book->getActiveSheet();
            $headings = array_map(
                static fn ($value): string => strtolower(trim((string) $value)),
                $sheet->rangeToArray('A1:F1')[0]
            );

            if ($headings !== self::HEADERS) {
                throw ValidationException::withMessages(['file' => 'The column headings must match the downloaded template.']);
            }

            $rows = [];
            for ($number = 2; $number <= $sheet->getHighestDataRow(); $number++) {
                $values = [];
                foreach (range('A', 'F') as $column) {
                    $cell = $sheet->getCell($column.$number);
                    // Displayed text retains leading zeroes in Excel cells formatted as text.
                    $values[] = trim((string) $cell->getFormattedValue());
                }

                if (count(array_filter($values, static fn ($value) => $value !== '')) === 0) {
                    continue;
                }

                $row = array_combine(self::HEADERS, $values);
                $row['_row'] = $number;
                $rows[] = $row;
            }

            return $rows;
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function normalizePhone(string $value): ?string
    {
        $digits = preg_replace('/[\s().-]+/', '', trim($value));
        if (str_starts_with($digits, '+')) {
            $digits = substr($digits, 1);
        }
        if (preg_match('/^0[67]\d{8}$/', $digits)) {
            return '255'.substr($digits, 1);
        }
        if (preg_match('/^[67]\d{8}$/', $digits)) {
            return '255'.$digits;
        }

        return preg_match('/^255[67]\d{8}$/', $digits) ? $digits : null;
    }

    /** @return array{first_name:string,middle_name:?string,last_name:string}|null */
    private function splitName(string $fullName): ?array
    {
        $parts = preg_split('/\s+/u', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);
        if (count($parts) < 2) {
            return null;
        }

        return [
            'first_name' => array_shift($parts),
            'last_name' => array_pop($parts),
            'middle_name' => $parts ? implode(' ', $parts) : null,
        ];
    }

    /** @param list<array<string,mixed>> $rows @return array{valid:list<array<string,mixed>>,failed:list<array<string,mixed>>} */
    public function preview(array $rows, User $user): array
    {
        if (count($rows) === 0 || count($rows) > 2000) {
            throw ValidationException::withMessages(['file' => 'Provide between 1 and 2,000 customer rows.']);
        }

        $types = CustomerType::query()->where('is_active', true)->pluck('code')->all();
        $valid = [];
        $failed = [];
        $seenPhones = [];
        $seenEmails = [];

        foreach ($rows as $row) {
            $name = $this->splitName((string) $row['full_name']);
            $phone = $this->normalizePhone((string) $row['phone']);
            $email = strtolower(trim((string) $row['email']));
            $gender = ucfirst(strtolower(trim((string) $row['gender'])));
            $type = trim((string) $row['customer_type']) ?: ($types[0] ?? '');
            $date = trim((string) $row['date_of_birth']);

            if (is_numeric($date) && (float) $date > 1000) {
                $date = Date::excelToDateTimeObject((float) $date)->format('Y-m-d');
            }

            $candidate = [
                ...($name ?? ['first_name' => '', 'middle_name' => null, 'last_name' => '']),
                'phone' => $phone,
                'gender' => $gender === '' ? null : $gender,
                'date_of_birth' => $date === '' ? null : $date,
                'email' => $email === '' ? null : $email,
                'type' => $type,
            ];

            $validator = Validator::make($candidate, [
                'first_name' => ['required', 'string', 'max:100'],
                'middle_name' => ['nullable', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'phone' => ['required', 'string', 'size:12'],
                'gender' => ['nullable', Rule::in(['Male', 'Female', 'Other'])],
                'date_of_birth' => ['nullable', 'date_format:Y-m-d', 'before:today'],
                'email' => ['nullable', 'email', 'max:255'],
                'type' => ['required', Rule::in($types)],
            ]);

            $errors = $validator->errors()->all();
            if ($name === null) {
                $errors[] = 'Full name must contain at least first and last names.';
            }
            if ($phone === null) {
                $errors[] = 'Enter a valid Tanzanian mobile number.';
            } else {
                if (isset($seenPhones[$phone])) {
                    $errors[] = 'Duplicate phone in file (row '.$seenPhones[$phone].').';
                }
                $seenPhones[$phone] ??= $row['_row'];
                $local = '0'.substr($phone, 3);
                $international = '+'.$phone;
                if (Customer::query()->whereIn('phone', [$phone, $local, $international])->exists()) {
                    $errors[] = 'Phone number already exists.';
                }
            }
            if ($email !== '') {
                if (isset($seenEmails[$email])) {
                    $errors[] = 'Duplicate email in file (row '.$seenEmails[$email].').';
                }
                $seenEmails[$email] ??= $row['_row'];
                if (Customer::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
                    $errors[] = 'Email already exists.';
                }
            }

            if ($errors) {
                $failed[] = [...$row, 'errors' => implode(' ', array_unique($errors))];
            } else {
                $valid[] = ['_row' => $row['_row'], ...$candidate];
            }
        }

        return compact('valid', 'failed');
    }

    /** @param list<array<string,mixed>> $rows */
    public function import(array $rows, User $user): int
    {
        // Recheck against current database state immediately before writing.
        return DB::transaction(function () use ($rows, $user): int {
            $count = 0;
            foreach ($rows as $row) {
                $exists = Customer::query()->whereIn('phone', [
                    $row['phone'],
                    '0'.substr($row['phone'], 3),
                    '+'.$row['phone'],
                ])->exists();
                if ($exists || ($row['email'] && Customer::query()->whereRaw('LOWER(email) = ?', [$row['email']])->exists())) {
                    throw ValidationException::withMessages(['file' => 'A customer was added after preview. Upload again to review duplicates.']);
                }

                unset($row['_row']);
                Customer::create([
                    ...$row,
                    'branch_id' => $user->branch_id,
                    'customer_number' => NumberSequence::issue('customer'),
                    'status' => CustomerStatus::PendingVerification,
                ]);
                $count++;
            }

            return $count;
        });
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerExportController extends Controller
{
    private function scoped(Request $request)
    {
        return Customer::query()->with(['branch', 'assignedOfficer'])
            ->when($request->user()->branch_id !== null, fn ($q) => $q->where('branch_id', $request->user()->branch_id));
    }

    private function filtered(Request $request)
    {
        return $this->scoped($request)
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim((string) $request->input('search'));
                $q->where(function ($sub) use ($term) {
                    foreach (['customer_number', 'first_name', 'middle_name', 'last_name', 'phone', 'identification_number'] as $column) {
                        $sub->orWhere($column, 'like', '%'.$term.'%');
                    }
                });
            })
            ->when($request->input('kyc') === 'verified', fn ($q) => $q->whereNotNull('verified_at'))
            ->when($request->input('kyc') === 'pending', fn ($q) => $q->whereNull('verified_at'))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->input('status') === 'active', fn ($q) => $q->where('status', 'Active'))
            ->when($request->input('status') === 'other', fn ($q) => $q->where('status', '!=', 'Active'));
    }

    public function index(Request $request, string $format)
    {
        abort_unless($request->user()->can('customer.view'), 403);
        abort_unless(in_array($format, ['pdf', 'xlsx'], true), 404);
        // Hard cap prevents excessive memory use in synchronous exports.
        $query = $this->filtered($request)->orderBy('id');
        abort_if((clone $query)->limit(5001)->count() > 5000, 422, 'Export is limited to 5,000 borrowers. Narrow your filters.');
        $customers = $query->get();
        $rows = $customers->map(fn ($c) => $this->customerRow($c))->all();
        $headers = $this->headers();
        if ($format === 'pdf') {
            return Pdf::loadView('customers.exports.list', compact('rows', 'headers'))
                ->setPaper('a4', 'landscape')->download('borrowers-'.now()->format('Ymd-His').'.pdf');
        }

        return $this->xlsx($headers, $rows, 'borrowers-'.now()->format('Ymd-His').'.xlsx');
    }

    public function show(Request $request, Customer $customer, string $format)
    {
        abort_unless($request->user()->can('customer.view'), 403);
        abort_unless(in_array($format, ['pdf', 'xlsx'], true), 404);
        abort_if($request->user()->branch_id !== null && (string) $customer->branch_id !== (string) $request->user()->branch_id, 403);
        $customer->load(['branch', 'assignedOfficer', 'loans.repayments', 'kycDecisions.reviewer']);
        $headers = $this->headers();
        $rows = [$this->customerRow($customer)];
        $loans = $customer->loans->map(fn ($loan) => [
            (string) $loan->loan_number, (string) ($loan->status?->value ?? $loan->status),
            (float) $loan->principal_amount, (float) $loan->outstanding_principal,
            (float) $loan->arrears_amount,
        ])->all();
        $loanHeaders = ['Loan number', 'Status', 'Principal (TZS)', 'Outstanding (TZS)', 'Arrears (TZS)'];
        $decisions = $customer->kycDecisions->sortByDesc('id')->map(fn ($d) => [
            ucfirst($d->decision), (string) $d->risk_rating, (string) $d->reason,
            (string) ($d->reviewer?->full_name ?? ''), (string) $d->created_at,
        ])->all();
        $decisionHeaders = ['Decision', 'Risk', 'Reason', 'Reviewer', 'Date'];
        $name = 'borrower-'.Str::slug((string) $customer->customer_number).'-'.now()->format('Ymd-His');
        if ($format === 'pdf') {
            return Pdf::loadView('customers.exports.profile', compact('customer', 'headers', 'rows', 'loans', 'loanHeaders', 'decisions', 'decisionHeaders'))
                ->setPaper('a4', 'landscape')->download($name.'.pdf');
        }

        return $this->xlsx($headers, $rows, $name.'.xlsx', [
            ['Loans', $loanHeaders, $loans], ['KYC decisions', $decisionHeaders, $decisions],
        ]);
    }

    private function headers(): array
    {
        return ['Member ID', 'Name', 'Phone', 'Email', 'National ID', 'Branch', 'Type', 'Status', 'KYC', 'Joined'];
    }

    private function customerRow(Customer $c): array
    {
        return [
            (string) $c->customer_number, trim($c->first_name.' '.$c->middle_name.' '.$c->last_name),
            (string) $c->phone, (string) $c->email, (string) $c->identification_number,
            (string) ($c->branch?->name ?? ''), (string) $c->type,
            (string) ($c->status?->value ?? $c->status), $c->verified_at ? 'Verified' : 'Unverified',
            (string) ($c->created_at?->format('Y-m-d') ?? ''),
        ];
    }

    private function xlsx(array $headers, array $rows, string $filename, array $extra = []): BinaryFileResponse
    {
        $book = new Spreadsheet;
        $sections = array_merge([['Borrowers', $headers, $rows]], $extra);
        foreach ($sections as $index => [$title, $cols, $data]) {
            $sheet = $index === 0 ? $book->getActiveSheet() : $book->createSheet();
            $sheet->setTitle($title);
            $sheet->fromArray($cols, null, 'A1');
            $sheet->getStyle('1:1')->getFont()->setBold(true);
            foreach ($data as $i => $row) {
                foreach (array_values($row) as $j => $value) {
                    // Explicit strings prevent member numbers, phones and IDs losing leading zeroes.
                    $sheet->setCellValueExplicit([$j + 1, $i + 2], (string) $value, DataType::TYPE_STRING);
                }
            }
            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:'.$sheet->getCell([count($cols), 1])->getCoordinate());
            foreach (range(1, count($cols)) as $col) {
                $sheet->getColumnDimensionByColumn($col)->setWidth(22);
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'customer-export-');
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();

        return response()->download($path, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }
}

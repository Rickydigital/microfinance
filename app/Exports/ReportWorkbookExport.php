<?php

namespace App\Exports;

use App\Models\CollectionCase;
use App\Models\JournalLine;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\Repayment;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Activitylog\Models\Activity;

class ReportWorkbookExport
{
    private const PRIMARY = '17365D';

    private const SECONDARY = 'E8EEF8';

    public function __construct(
        private readonly string $report,
        private readonly ?int $branchId,
        private readonly ?CarbonImmutable $from,
        private readonly ?CarbonImmutable $to,
        private readonly string $generatedBy,
    ) {}

    public function writeTo(string $destination): void
    {
        $writer = new Xlsx($this->spreadsheet());
        $writer->setPreCalculateFormulas(true);
        $writer->save($destination);
    }

    public function spreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);
        $details = match ($this->report) {
            'operational' => $this->operationalRows(),
            'financial' => $this->financialRows(),
            'collections' => $this->collectionRows(),
            'audit' => $this->auditRows(),
            default => $this->portfolioRows(),
        };
        $detailSheet = $this->addDataSheet($spreadsheet, $details['sheet'], $details['headers'], $details['rows'], $details['money_columns'] ?? []);

        if ($this->report === 'collections') {
            $cases = $this->collectionCaseRows();
            $this->addDataSheet($spreadsheet, 'Collection Cases', $cases['headers'], $cases['rows'], $cases['money_columns']);
        }

        $this->addOverviewSheet($spreadsheet, $detailSheet, count($details['rows']), $details['totals'] ?? []);
        $this->addChecksSheet($spreadsheet, $detailSheet, count($details['rows']), $details['checks'] ?? []);
        $spreadsheet->setActiveSheetIndexByName('Overview');
        $spreadsheet->getProperties()->setCreator($this->generatedBy)->setTitle(ucfirst($this->report).' report')->setDescription('Generated from the microfinance management system.');

        return $spreadsheet;
    }

    /** @return array<string, mixed> */
    private function portfolioRows(): array
    {
        $loans = Loan::query()->with(['branch', 'customer', 'application.productVersion.product'])
            ->when($this->branchId, fn (Builder $query, int $branchId) => $query->where('branch_id', $branchId))
            ->when($this->from, fn (Builder $query, CarbonImmutable $from) => $query->whereDate('disbursed_at', '>=', $from))
            ->when($this->to, fn (Builder $query, CarbonImmutable $to) => $query->whereDate('disbursed_at', '<=', $to))
            ->orderBy('loan_number')->get();

        return [
            'sheet' => $this->report === 'risk' ? 'Risk Portfolio' : 'Loan Portfolio',
            'headers' => ['Loan Number', 'Customer Number', 'Customer', 'Branch', 'Product', 'Status', 'Principal', 'Outstanding Principal', 'Arrears', 'Days Past Due', 'PAR Bucket', 'Disbursed Date', 'Maturity Date'],
            'rows' => $loans->map(fn (Loan $loan): array => [$loan->loan_number, $loan->customer->customer_number, $loan->customer->first_name.' '.$loan->customer->last_name, $loan->branch->name, $loan->application?->productVersion?->product?->name ?? 'Not available', $this->value($loan->status), (float) $loan->principal_amount, (float) $loan->outstanding_principal, (float) $loan->arrears_amount, $loan->days_past_due, $this->parBucket($loan->days_past_due), $loan->disbursed_at, $loan->maturity_date])->all(),
            'money_columns' => [7, 8, 9],
            'totals' => ['Principal' => 7, 'Outstanding principal' => 8, 'Arrears' => 9],
        ];
    }

    /** @return array<string, mixed> */
    private function operationalRows(): array
    {
        $applications = LoanApplication::query()->with(['branch', 'customer', 'productVersion.product', 'creator'])
            ->when($this->branchId, fn (Builder $query, int $branchId) => $query->where('branch_id', $branchId))
            ->when($this->from, fn (Builder $query, CarbonImmutable $from) => $query->whereDate('created_at', '>=', $from))
            ->when($this->to, fn (Builder $query, CarbonImmutable $to) => $query->whereDate('created_at', '<=', $to))
            ->latest()->get();

        return [
            'sheet' => 'Applications',
            'headers' => ['Application Number', 'Customer Number', 'Customer', 'Branch', 'Product', 'Requested Amount', 'Requested Term', 'Frequency', 'Status', 'Created By', 'Created Date', 'Submitted Date', 'Decision Date', 'Approved Amount'],
            'rows' => $applications->map(fn (LoanApplication $application): array => [$application->application_number, $application->customer->customer_number, $application->customer->first_name.' '.$application->customer->last_name, $application->branch->name, $application->productVersion->product->name, (float) $application->requested_amount, $application->requested_term.' '.$application->productVersion->term_unit, $application->repayment_frequency, $this->value($application->status), $application->creator->full_name, $application->created_at, $application->submitted_at, $application->decided_at, $application->approved_amount === null ? null : (float) $application->approved_amount])->all(),
            'money_columns' => [6, 14],
            'totals' => ['Requested amount' => 6, 'Approved amount' => 14],
        ];
    }

    /** @return array<string, mixed> */
    private function financialRows(): array
    {
        $lines = JournalLine::query()->with(['entry', 'account', 'branch'])
            ->when($this->branchId, fn (Builder $query, int $branchId) => $query->where('branch_id', $branchId))
            ->when($this->from, fn (Builder $query, CarbonImmutable $from) => $query->whereHas('entry', fn (Builder $entry) => $entry->whereDate('posting_date', '>=', $from)))
            ->when($this->to, fn (Builder $query, CarbonImmutable $to) => $query->whereHas('entry', fn (Builder $entry) => $entry->whereDate('posting_date', '<=', $to)))
            ->orderBy('journal_entry_id')->orderBy('id')->get();

        return [
            'sheet' => 'General Ledger',
            'headers' => ['Posting Date', 'Journal Number', 'Branch', 'Account Code', 'Account Name', 'Account Type', 'Description', 'Debit', 'Credit', 'Journal Status', 'Source Type', 'Source ID'],
            'rows' => $lines->map(fn (JournalLine $line): array => [$line->entry->posting_date, $line->entry->journal_number, $line->branch->name, $line->account->code, $line->account->name, $line->account->type, $line->description ?: $line->entry->description, (float) $line->debit, (float) $line->credit, $line->entry->status, $line->entry->source_type ? class_basename($line->entry->source_type) : 'Manual', $line->entry->source_id])->all(),
            'money_columns' => [8, 9],
            'totals' => ['Total debits' => 8, 'Total credits' => 9],
            'checks' => [['label' => 'Debits equal credits', 'actual_column' => 8, 'expected_column' => 9]],
        ];
    }

    /** @return array<string, mixed> */
    private function collectionRows(): array
    {
        $repayments = Repayment::query()->with(['loan.customer', 'loan.branch', 'receiver'])
            ->when($this->branchId, fn (Builder $query, int $branchId) => $query->whereHas('loan', fn (Builder $loan) => $loan->where('branch_id', $branchId)))
            ->when($this->from, fn (Builder $query, CarbonImmutable $from) => $query->whereDate('paid_at', '>=', $from))
            ->when($this->to, fn (Builder $query, CarbonImmutable $to) => $query->whereDate('paid_at', '<=', $to))
            ->latest('paid_at')->get();

        return [
            'sheet' => 'Repayments',
            'headers' => ['Paid Date', 'Receipt Number', 'Loan Number', 'Customer Number', 'Customer', 'Branch', 'Amount', 'Payment Method', 'External Reference', 'Status', 'Received By', 'Reversal Reason'],
            'rows' => $repayments->map(fn (Repayment $repayment): array => [$repayment->paid_at, $repayment->receipt_number, $repayment->loan->loan_number, $repayment->loan->customer->customer_number, $repayment->loan->customer->first_name.' '.$repayment->loan->customer->last_name, $repayment->loan->branch->name, (float) $repayment->amount, $repayment->payment_method, $repayment->external_reference, $this->value($repayment->status), $repayment->receiver->full_name, $repayment->reversal_reason])->all(),
            'money_columns' => [7],
            'totals' => ['Collections' => 7],
        ];
    }

    /** @return array<string, mixed> */
    private function collectionCaseRows(): array
    {
        $cases = CollectionCase::query()->with(['loan.customer', 'loan.branch', 'assignee'])
            ->when($this->branchId, fn (Builder $query, int $branchId) => $query->whereHas('loan', fn (Builder $loan) => $loan->where('branch_id', $branchId)))
            ->when($this->from, fn (Builder $query, CarbonImmutable $from) => $query->whereDate('created_at', '>=', $from))
            ->when($this->to, fn (Builder $query, CarbonImmutable $to) => $query->whereDate('created_at', '<=', $to))
            ->latest()->get();

        return [
            'headers' => ['Case Number', 'Loan Number', 'Customer', 'Branch', 'Loan Arrears', 'Days Past Due', 'Case Status', 'Assigned To', 'Promise Date', 'Promise Amount', 'Last Action'],
            'rows' => $cases->map(fn (CollectionCase $case): array => [$case->case_number, $case->loan->loan_number, $case->loan->customer->first_name.' '.$case->loan->customer->last_name, $case->loan->branch->name, (float) $case->loan->arrears_amount, $case->loan->days_past_due, $case->status, $case->assignee?->full_name ?? 'Unassigned', $case->promise_to_pay_date, $case->promise_to_pay_amount === null ? null : (float) $case->promise_to_pay_amount, $case->last_action_at]),
            'money_columns' => [5, 10],
        ];
    }

    /** @return array<string, mixed> */
    private function auditRows(): array
    {
        $activities = Activity::query()->with('causer')
            ->when($this->branchId, fn (Builder $query, int $branchId) => $query->where('causer_type', User::class)->whereIn('causer_id', User::query()->where('branch_id', $branchId)->select('id')))
            ->when($this->from, fn (Builder $query, CarbonImmutable $from) => $query->whereDate('created_at', '>=', $from))
            ->when($this->to, fn (Builder $query, CarbonImmutable $to) => $query->whereDate('created_at', '<=', $to))
            ->latest()->get();

        return [
            'sheet' => 'Audit Log',
            'headers' => ['Date and Time', 'Event', 'Description', 'Employee', 'Subject Type', 'Subject ID', 'Properties'],
            'rows' => $activities->map(fn (Activity $activity): array => [$activity->created_at, $activity->event, $activity->description, $activity->causer?->full_name ?? 'System', $activity->subject_type ? class_basename($activity->subject_type) : 'Not available', $activity->subject_id, json_encode($activity->properties, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)])->all(),
            'money_columns' => [],
        ];
    }

    /** @param array<int, string> $headers @param array<int, array<int, mixed>>|Collection<int, array<int, mixed>> $rows @param array<int, int> $moneyColumns */
    private function addDataSheet(Spreadsheet $spreadsheet, string $name, array $headers, array|Collection $rows, array $moneyColumns): Worksheet
    {
        $rows = collect($rows)->values();
        $sheet = new Worksheet($spreadsheet, $name);
        $spreadsheet->addSheet($sheet);
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->fromArray($headers, null, 'A1');

        if ($rows->isNotEmpty()) {
            $sheet->fromArray($rows->map(fn (array $row): array => array_map(fn (mixed $value): mixed => $this->value($value), $row))->all(), null, 'A2');
        }

        $lastRow = max(2, $rows->count() + 1);
        $this->styleTable($sheet, $lastColumn, $lastRow);
        foreach ($moneyColumns as $column) {
            $letter = Coordinate::stringFromColumnIndex($column);
            $sheet->getStyle("{$letter}2:{$letter}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00;[Red](#,##0.00);-');
        }

        for ($column = 1; $column <= count($headers); $column++) {
            $letter = Coordinate::stringFromColumnIndex($column);
            $width = min(38, max(12, strlen($headers[$column - 1]) + 3));
            $sheet->getColumnDimension($letter)->setWidth($width);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");
        $sheet->setShowGridlines(false);

        return $sheet;
    }

    /** @param array<string, int> $totals */
    private function addOverviewSheet(Spreadsheet $spreadsheet, Worksheet $detailSheet, int $rowCount, array $totals): void
    {
        $sheet = new Worksheet($spreadsheet, 'Overview');
        $spreadsheet->addSheet($sheet, 0);
        $sheet->mergeCells('A1:H2');
        $sheet->setCellValue('A1', ucfirst($this->report).' Report');
        $sheet->getStyle('A1:H2')->applyFromArray(['font' => ['bold' => true, 'size' => 20, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::PRIMARY]], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]]);
        $sheet->fromArray([['Generated by', $this->generatedBy], ['Generated at', now()->format('d M Y H:i')], ['Branch scope', $this->branchId ? 'Assigned branch' : 'All branches'], ['Date range', ($this->from?->format('d M Y') ?? 'Beginning').' to '.($this->to?->format('d M Y') ?? 'Today')]], null, 'A4');
        $sheet->setCellValue('A9', 'Record count')->setCellValue('B9', $rowCount);
        $row = 10;
        $lastDataRow = max(2, $rowCount + 1);
        foreach ($totals as $label => $column) {
            $letter = Coordinate::stringFromColumnIndex($column);
            $sheet->setCellValue("A{$row}", $label)->setCellValue("B{$row}", "=SUM('{$detailSheet->getTitle()}'!{$letter}2:{$letter}{$lastDataRow})");
            $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('#,##0.00;[Red](#,##0.00);-');
            $row++;
        }
        $sheet->getStyle("A9:B{$row}")->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D7DEEA']]]]);
        $sheet->getStyle("A9:A{$row}")->applyFromArray(['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::SECONDARY]]]);
        $sheet->setCellValue('A16', 'Workbook contents')->setCellValue('A17', 'Overview')->setCellValue('B17', 'Executive totals and report metadata')->setCellValue('A18', $detailSheet->getTitle())->setCellValue('B18', 'Filterable source transaction records')->setCellValue('A19', 'Checks')->setCellValue('B19', 'Completeness and reconciliation controls');
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(42);
        $sheet->setShowGridlines(false);
        $sheet->freezePane('A4');
    }

    /** @param array<int, array<string, mixed>> $checks */
    private function addChecksSheet(Spreadsheet $spreadsheet, Worksheet $detailSheet, int $rowCount, array $checks): void
    {
        $sheet = new Worksheet($spreadsheet, 'Checks');
        $spreadsheet->addSheet($sheet);
        $sheet->fromArray(['Check', 'Actual', 'Expected', 'Difference', 'Status', 'Notes'], null, 'A1');
        $sheet->fromArray(['Exported record count', $rowCount, '>= 0', 0, 'OK', 'Confirms the detail sheet was generated.'], null, 'A2');
        $row = 3;
        $lastDataRow = max(2, $rowCount + 1);
        foreach ($checks as $check) {
            $actualLetter = Coordinate::stringFromColumnIndex($check['actual_column']);
            $expectedLetter = Coordinate::stringFromColumnIndex($check['expected_column']);
            $sheet->setCellValue("A{$row}", $check['label']);
            $sheet->setCellValue("B{$row}", "=SUM('{$detailSheet->getTitle()}'!{$actualLetter}2:{$actualLetter}{$lastDataRow})");
            $sheet->setCellValue("C{$row}", "=SUM('{$detailSheet->getTitle()}'!{$expectedLetter}2:{$expectedLetter}{$lastDataRow})");
            $sheet->setCellValue("D{$row}", "=B{$row}-C{$row}");
            $sheet->setCellValue("E{$row}", "=IF(ABS(D{$row})<0.01,\"OK\",\"REVIEW\")");
            $sheet->setCellValue("F{$row}", 'Difference must be zero.');
            $row++;
        }
        $this->styleTable($sheet, 'F', max(2, $row - 1));
        foreach (range('B', 'D') as $column) {
            $sheet->getStyle("{$column}2:{$column}{$row}")->getNumberFormat()->setFormatCode('#,##0.00;[Red](#,##0.00);-');
        }
        foreach (['A' => 30, 'B' => 18, 'C' => 18, 'D' => 18, 'E' => 14, 'F' => 36] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->freezePane('A2');
        $sheet->setShowGridlines(false);
    }

    private function styleTable(Worksheet $sheet, string $lastColumn, int $lastRow): void
    {
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::PRIMARY]], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'D7DEEA']]], 'alignment' => ['vertical' => Alignment::VERTICAL_TOP]]);
        $sheet->getStyle("A2:{$lastColumn}{$lastRow}")->getAlignment()->setWrapText(true);
    }

    private function parBucket(int $daysPastDue): string
    {
        return match (true) {
            $daysPastDue <= 0 => 'Current',
            $daysPastDue <= 7 => 'PAR 1-7',
            $daysPastDue <= 30 => 'PAR 8-30',
            $daysPastDue <= 60 => 'PAR 31-60',
            $daysPastDue <= 90 => 'PAR 61-90',
            default => 'PAR 90+',
        };
    }

    private function value(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return $value;
    }
}

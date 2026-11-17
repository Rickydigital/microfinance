<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BotReturnWorkbookExport
{
    /** @param array<string, mixed> $return */
    public function __construct(private readonly array $return) {}

    public function writeTo(string $path): void
    {
        (new Xlsx($this->spreadsheet()))->save($path);
    }

    public function spreadsheet(): Spreadsheet
    {
        $book = new Spreadsheet;
        $book->removeSheetByIndex(0);
        $this->sheet($book, 'Balance Sheet', ['Code', 'Account', 'Type', 'Balance'], $this->return['balance_sheet']['accounts']->map(fn (array $row): array => [$row['code'], $row['name'], $row['type'], $row['balance']])->all());
        $this->sheet($book, 'Income Statement', ['Code', 'Account', 'Type', 'Quarter amount'], $this->return['income_statement']['rows']->map(fn (array $row): array => [$row['code'], $row['name'], $row['type'], $row['amount']])->all());
        $this->sheet($book, 'Portfolio & Aging', ['Classification', 'Borrowers', 'Loans', 'Outstanding', 'Provision rate', 'Required provision'], $this->return['aging']->map(fn (array $row): array => [$row['label'], $row['borrowers'], $row['loans'], $row['outstanding'], $row['rate'] / 100, $row['provision']])->all());
        $this->sheet($book, 'Loan Detail', ['Loan', 'Borrower', 'Gender', 'Sector', 'Product', 'Principal', 'Outstanding', 'Days past due'], $this->return['portfolio']->map(fn (array $row): array => [$row['loan_number'], $row['borrower'], $row['gender'], $row['sector'], $row['product'], $row['principal'], $row['outstanding'], $row['days_past_due']])->all());
        $this->sheet($book, 'Liquid Assets', ['Code', 'Account', 'Balance'], $this->return['liquid_assets']->map(fn (array $row): array => [$row['code'], $row['name'], $row['balance']])->all());
        $this->sheet($book, 'Interest Rates', ['Product', 'Loans', 'Minimum rate', 'Maximum rate', 'Weighted average'], $this->return['interest_rates']->map(fn (array $row): array => [$row['product'], $row['loans'], $row['minimum'], $row['maximum'], $row['weighted_average']])->all());
        $this->sheet($book, 'Validations', ['Check', 'Reported', 'Control', 'Difference', 'Status'], collect($this->return['validations'])->map(fn (array $row): array => [$row['label'], $row['left'], $row['right'], $row['difference'], $row['status']])->all());
        $book->setActiveSheetIndex(0);

        return $book;
    }

    /** @param list<string> $headings @param list<list<mixed>> $rows */
    private function sheet(Spreadsheet $book, string $title, array $headings, array $rows): void
    {
        $sheet = $book->createSheet();
        $sheet->setTitle($title)->fromArray($headings, null, 'A1')->fromArray($rows, null, 'A2');
        $lastColumn = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF176656');
        $sheet->setAutoFilter("A1:{$lastColumn}".$sheet->getHighestRow())->freezePane('A2');
        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }
}

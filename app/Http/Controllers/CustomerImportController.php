<?php

namespace App\Http\Controllers;

use App\Models\CustomerType;
use App\Services\CustomerImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CustomerImportController extends Controller
{
    public function index(): View
    {
        return view('customers.import');
    }

    public function template(): StreamedResponse
    {
        $types = CustomerType::query()->where('is_active', true)->pluck('code')->all();

        return response()->streamDownload(function () use ($types): void {
            $book = new Spreadsheet;
            $sheet = $book->getActiveSheet();
            $sheet->fromArray([CustomerImportService::HEADERS], null, 'A1');
            $sheet->setCellValue('A2', 'Asha Mwajuma Mrema');
            $sheet->setCellValueExplicit('B2', '0712345678', DataType::TYPE_STRING);
            $sheet->setCellValue('C2', 'Female');
            $sheet->setCellValue('D2', '1998-08-20');
            $sheet->setCellValue('E2', '');
            $sheet->setCellValue('F2', $types[0] ?? '');
            $sheet->getStyle('B2:B2001')->getNumberFormat()->setFormatCode('@');
            $sheet->getStyle('A1:F1')->getFont()->setBold(true);
            $sheet->freezePane('A2');

            $gender = new DataValidation;
            $gender->setType(DataValidation::TYPE_LIST);
            $gender->setErrorStyle(DataValidation::STYLE_STOP);
            $gender->setAllowBlank(true);
            $gender->setShowErrorMessage(true);
            $gender->setFormula1('"Male,Female,Other"');
            $sheet->setDataValidation('C2:C2001', $gender);

            if ($types && strlen(implode(',', $types)) < 240) {
                $typeValidation = new DataValidation;
                $typeValidation->setType(DataValidation::TYPE_LIST);
                $typeValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $typeValidation->setAllowBlank(false);
                $typeValidation->setShowErrorMessage(true);
                $typeValidation->setFormula1('"'.implode(',', $types).'"');
                $sheet->setDataValidation('F2:F2001', $typeValidation);
            }

            foreach (range('A', 'F') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, 'customer-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function store(Request $request, CustomerImportService $service): View|RedirectResponse
    {
        if ($request->input('action') === 'confirm') {
            $request->validate(['token' => ['required', 'uuid']]);
            $preview = $request->session()->get('customer_import_preview');
            if (! $preview || ! hash_equals($preview['token'], $request->input('token')) ||
                $preview['user_id'] !== $request->user()->id || $preview['expires_at'] < time()) {
                throw ValidationException::withMessages(['file' => 'Preview expired. Please upload the file again.']);
            }

            $count = $service->import($preview['valid'], $request->user());
            $request->session()->forget('customer_import_preview');

            return redirect()->route('customers.index')->with('success', "Successfully imported {$count} customers.");
        }

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:5120']]);
        try {
            $rows = $service->read($request->file('file')->getRealPath());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['file' => 'Unable to read the spreadsheet. Use the downloaded XLSX or CSV template.']);
        }

        $results = $service->preview($rows, $request->user());
        $token = (string) Str::uuid();
        $request->session()->put('customer_import_preview', [
            'token' => $token,
            'user_id' => $request->user()->id,
            'expires_at' => time() + 1800,
            ...$results,
        ]);

        return view('customers.import', ['results' => $results, 'token' => $token]);
    }

    public function errors(Request $request): StreamedResponse
    {
        $preview = $request->session()->get('customer_import_preview');
        abort_unless($preview && $preview['user_id'] === $request->user()->id && $preview['expires_at'] >= time(), 404);

        return response()->streamDownload(function () use ($preview): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['row', ...CustomerImportService::HEADERS, 'errors']);
            foreach ($preview['failed'] as $row) {
                // Prevent spreadsheet formula injection in exported error reports.
                $values = array_map(static function ($value): string {
                    $text = (string) $value;

                    return preg_match('/^[\s]*[=+@\-]/', $text) ? "'".$text : $text;
                }, array_map(static fn ($key) => $row[$key] ?? '', CustomerImportService::HEADERS));
                fputcsv($handle, [$row['_row'], ...$values, $row['errors']]);
            }
            fclose($handle);
        }, 'customer-import-errors.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

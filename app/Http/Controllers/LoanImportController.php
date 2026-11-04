<?php

namespace App\Http\Controllers;

use App\Services\HistoricalLoanImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LoanImportController extends Controller
{
    public function index(): View
    {
        return view('loans.import');
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $book = new Spreadsheet;
            $book->getActiveSheet()->fromArray(HistoricalLoanImportService::HEADERS);
            $book->getActiveSheet()->freezePane('A2');
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, 'historical-loans-template.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function store(Request $request, HistoricalLoanImportService $service): View|RedirectResponse
    {
        if ($request->input('action') === 'confirm') {
            $data = $request->validate(['token' => ['required', 'uuid']]);
            $preview = $request->session()->get('loan_import_preview');
            abort_unless($preview && hash_equals($preview['token'], $data['token']) && $preview['user_id'] === $request->user()->id && $preview['expires_at'] >= time(), 422, 'Preview expired. Upload the file again.');
            $count = $service->import($preview['rows'], $request->user());
            $request->session()->forget('loan_import_preview');

            return redirect()->route('loans.index')->with('success', "Imported {$count} historical loans with their original installment balances.");
        }
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:5120']]);
        try {
            $rows = $service->read($request->file('file')->getRealPath());
        } catch (Exception $exception) {
            throw ValidationException::withMessages(['file' => 'Unable to read this spreadsheet. Upload the XLSX template or a CSV file.']);
        }
        $groups = $service->validate($rows, $request->user());
        $token = (string) Str::uuid();
        $request->session()->put('loan_import_preview', ['token' => $token, 'user_id' => $request->user()->id, 'expires_at' => time() + 1800, 'rows' => $rows]);
        $preview = collect($groups)->map(fn ($item): array => [
            'customer' => $item['customer_number'], 'principal' => $item['principal_amount'],
            'principal_paid' => $item['total_paid'], 'status' => $item['status'],
        ]);

        return view('loans.import', compact('preview', 'token'));
    }
}

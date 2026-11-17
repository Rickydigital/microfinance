<?php

namespace App\Http\Controllers;

use App\Services\SecurityRegisterImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecurityRegisterImportController extends Controller
{
    public function template(string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['guarantors', 'collaterals'], true), 404);
        abort_unless(request()->user()->can($type === 'guarantors' ? 'guarantor.manage' : 'collateral.manage'), 403);
        $headers = $type === 'guarantors' ? SecurityRegisterImportService::GUARANTOR_HEADERS : SecurityRegisterImportService::COLLATERAL_HEADERS;

        return response()->streamDownload(function () use ($headers): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            fputcsv($handle, array_fill(0, count($headers), ''));
            fclose($handle);
        }, "{$type}-import-sample.csv", ['Content-Type' => 'text/csv']);
    }

    public function store(Request $request, string $type, SecurityRegisterImportService $service): RedirectResponse
    {
        abort_unless(in_array($type, ['guarantors', 'collaterals'], true), 404);
        abort_unless($request->user()->can($type === 'guarantors' ? 'guarantor.manage' : 'collateral.manage'), 403);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $count = $service->import($type, $request->file('file')->getRealPath(), $request->user());

        return back()->with('success', "Successfully imported {$count} records.");
    }
}

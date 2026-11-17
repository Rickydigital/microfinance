<?php

namespace App\Http\Controllers;

use App\Models\LoanProductVersion;
use App\Services\LoanQuotationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class LoanCalculatorController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:loan.application.view')];
    }

    public function __invoke(Request $request, LoanQuotationService $quotationService): View
    {
        $versions = LoanProductVersion::query()
            ->with(['product', 'fees', 'penaltyRules'])
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->where(function ($query): void {
                $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', today());
            })
            ->where(function ($query): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', today());
            })
            ->orderByDesc('effective_from')
            ->get()
            ->unique('loan_product_id')
            ->sortBy('product.name')
            ->values();

        $validated = $request->validate([
            'loan_product_version_id' => ['nullable', 'integer', 'exists:loan_product_versions,id'],
            'amount' => ['nullable', 'numeric', 'gt:0'],
            'term' => ['nullable', 'integer', 'gt:0'],
            'monthly_income' => ['nullable', 'numeric', 'min:0'],
            'monthly_expenses' => ['nullable', 'numeric', 'min:0'],
            'existing_debt_payment' => ['nullable', 'numeric', 'min:0'],
        ]);

        $selectedVersion = isset($validated['loan_product_version_id'])
            ? $versions->firstWhere('id', (int) $validated['loan_product_version_id'])
            : $versions->first();
        $quote = null;

        if ($selectedVersion && isset($validated['amount'], $validated['term'])) {
            $quote = $quotationService->quote(
                $selectedVersion,
                (float) $validated['amount'],
                (int) $validated['term'],
                (float) ($validated['monthly_income'] ?? 0),
                (float) ($validated['monthly_expenses'] ?? 0),
                (float) ($validated['existing_debt_payment'] ?? 0),
            );
        }

        return view('loan-calculator.index', compact('versions', 'selectedVersion', 'quote'));
    }
}

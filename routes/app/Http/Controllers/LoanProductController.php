<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\LoanProduct;
use App\Models\LoanProductVersion;
use App\Models\ProductFee;
use App\Models\ProductPenaltyRule;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoanProductController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:loan-product.view', only: ['index', 'show']),
            new Middleware('can:loan-product.create', only: ['create', 'store']),
            new Middleware('can:loan-product.update', only: ['edit', 'update']),
            new Middleware('can:loan-product.activate', only: ['destroy']),
            new Middleware('can:loan-product.version.create', only: ['storeVersion']),
            new Middleware('can:loan-product.fee.manage', only: ['storeFee', 'destroyFee', 'storePenalty', 'destroyPenalty']),
            new Middleware('can:loan-product.accounting.manage', only: ['storeAccountMappings']),
        ];
    }

    public function index(Request $request): View
    {
        $loanProducts = LoanProduct::query()->withCount('versions')
            ->when($request->string('search')->toString(), fn ($query, string $search) => $query->where(fn ($query) => $query->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('loan-products.index', compact('loanProducts'));
    }

    public function create(): View
    {
        return view('loan-products.create', ['loanProduct' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string', 'max:2000']]);
        $product = DB::transaction(function () use ($validated): LoanProduct {
            $lastNumber = LoanProduct::query()->where('code', 'like', 'MY-CASH-%')->lockForUpdate()->get('code')
                ->map(fn (LoanProduct $product): int => preg_match('/^MY-CASH-(\d+)$/', $product->code, $matches) ? (int) $matches[1] : 0)
                ->max() ?? 0;

            return LoanProduct::query()->create($validated + ['code' => 'MY-CASH-'.str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT), 'is_active' => true]);
        });

        return redirect()->route('loan-products.show', $product)->with('success', 'Loan product created. Add its first effective version.');
    }

    public function show(LoanProduct $loanProduct): View
    {
        $loanProduct->load(['versions' => fn ($query) => $query->with(['fees', 'penaltyRules', 'accountMappings.account'])->latest('version'), 'approvalRules']);
        $accounts = ChartOfAccount::query()->where('is_active', true)->orderBy('code')->get();

        return view('loan-products.show', compact('loanProduct', 'accounts'));
    }

    public function edit(LoanProduct $loanProduct): View
    {
        return view('loan-products.edit', compact('loanProduct'));
    }

    public function update(Request $request, LoanProduct $loanProduct): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:30', Rule::unique('loan_products')->ignore($loanProduct)], 'name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string', 'max:2000']]);
        $loanProduct->update($validated);

        return redirect()->route('loan-products.show', $loanProduct)->with('success', 'Product identity updated. Financial terms remain version controlled.');
    }

    public function destroy(LoanProduct $loanProduct): RedirectResponse
    {
        $loanProduct->update(['is_active' => ! $loanProduct->is_active]);

        return back()->with('success', $loanProduct->is_active ? 'Product activated.' : 'Product deactivated.');
    }

    public function storeVersion(Request $request, LoanProduct $loanProduct): RedirectResponse
    {
        $validated = $request->validate([
            'minimum_amount' => ['required', 'numeric', 'gt:0'], 'maximum_amount' => ['required', 'numeric', 'gt:minimum_amount'],
            'minimum_term' => ['required', 'integer', 'min:1'], 'maximum_term' => ['required', 'integer', 'gte:minimum_term'],
            'term_unit' => ['required', 'in:Days,Weeks,Months'], 'interest_method' => ['required', 'in:Flat,Reducing Balance'],
            'interest_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'], 'rate_basis' => ['required', 'in:Annual,Monthly,Whole Term'],
            'repayment_frequency' => ['required', 'in:Daily,Weekly,Biweekly,Monthly'], 'principal_grace_period' => ['required', 'integer', 'min:0'],
            'interest_grace_period' => ['required', 'integer', 'min:0'], 'required_guarantors' => ['required', 'integer', 'min:0', 'max:10'],
            'requires_collateral' => ['nullable', 'boolean'], 'maximum_installment_ratio_percent' => ['required', 'numeric', 'gt:0', 'max:100'],
            'effective_from' => ['required', 'date'],
            'assurance_percent' => ['sometimes', 'required', 'numeric', 'decimal:0,4', 'min:0', 'max:100'],
            'processing_fee' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0', 'lt:maximum_amount'],
        ]);

        DB::transaction(function () use ($request, $loanProduct, $validated): void {
            $previous = $loanProduct->versions()->lockForUpdate()->latest('version')->first();
            $effectiveFrom = CarbonImmutable::parse($validated['effective_from']);

            abort_if($previous !== null && $effectiveFrom->lte($previous->effective_from), 422, 'A new version must start after the previous version effective date.');

            if ($previous !== null && ($previous->effective_to === null || $previous->effective_to->gte($effectiveFrom))) {
                $previous->update(['effective_to' => $effectiveFrom->subDay()]);
            }

            $version = $loanProduct->versions()->create([
                'assurance_percent' => $validated['assurance_percent'] ?? 3,
                'version' => ($previous?->version ?? 0) + 1, 'minimum_amount' => $validated['minimum_amount'], 'maximum_amount' => $validated['maximum_amount'],
                'minimum_term' => $validated['minimum_term'], 'maximum_term' => $validated['maximum_term'], 'term_unit' => $validated['term_unit'],
                'interest_method' => $validated['interest_method'], 'interest_rate' => (float) $validated['interest_rate_percent'] / 100,
                'rate_basis' => $validated['rate_basis'], 'repayment_frequency' => $validated['repayment_frequency'],
                'principal_grace_period' => $validated['principal_grace_period'], 'interest_grace_period' => $validated['interest_grace_period'],
                'required_guarantors' => $validated['required_guarantors'], 'requires_collateral' => $request->boolean('requires_collateral'),
                'maximum_installment_ratio' => (float) $validated['maximum_installment_ratio_percent'] / 100,
                'effective_from' => $effectiveFrom, 'created_by' => $request->user()->id,
            ]);
            if ((float) ($validated['processing_fee'] ?? 0) > 0) {
                $version->fees()->create(['name' => 'Processing fee', 'calculation_method' => 'Fixed', 'value' => $validated['processing_fee'], 'collection_point' => 'Disbursement', 'is_financed' => false]);
            }
        });

        return back()->with('success', 'A new immutable product version was created.');
    }

    public function storeFee(Request $request, LoanProductVersion $loanProductVersion): RedirectResponse
    {
        abort_if($loanProductVersion->loanApplications()->exists(), 422, 'Create a new product version to change fees used by applications.');
        $validated = $request->validate(['name' => ['required', 'string', 'max:150'], 'calculation_method' => ['required', 'in:Fixed,Percentage'], 'value' => ['required', 'numeric', 'gt:0'], 'collection_point' => ['required', 'in:Disbursement,Installment,Upfront'], 'is_financed' => ['nullable', 'boolean']]);
        $validated['name'] = trim($validated['name']);
        if ($loanProductVersion->fees()->whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])->exists()) {
            throw ValidationException::withMessages(['name' => 'This fee is already configured. Remove the existing fee before replacing it.']);
        }
        $loanProductVersion->fees()->create($validated + ['is_financed' => $request->boolean('is_financed')]);

        return back()->with('success', 'Product fee configured.');
    }

    public function destroyFee(ProductFee $productFee): RedirectResponse
    {
        abort_if($productFee->productVersion->loanApplications()->exists(), 422, 'A fee used by an application cannot be deleted. Create a new product version.');
        $productFee->delete();

        return back()->with('success', 'Unused product fee removed.');
    }

    public function storePenalty(Request $request, LoanProductVersion $loanProductVersion): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:150'], 'calculation_method' => ['required', 'in:Fixed,Percentage'], 'calculation_basis' => ['required', 'in:Overdue Principal,Overdue Installment,Total Arrears'], 'value' => ['required', 'numeric', 'gt:0'], 'grace_days' => ['required', 'integer', 'min:0'], 'frequency' => ['required', 'in:Daily,One-time'], 'maximum_amount' => ['nullable', 'numeric', 'gt:0']]);
        $loanProductVersion->penaltyRules()->create($validated + ['is_active' => true]);

        return back()->with('success', 'Automatic penalty rule configured.');
    }

    public function editPenalty(ProductPenaltyRule $productPenaltyRule): View
    {
        $productPenaltyRule->load('productVersion.product');

        return view('loan-products.penalty-edit', compact('productPenaltyRule'));
    }

    public function updatePenalty(Request $request, ProductPenaltyRule $productPenaltyRule): RedirectResponse
    {
        abort_if($productPenaltyRule->charges()->exists(), 422, 'A penalty rule already used by a loan cannot be edited. Deactivate it and create a new product version or rule instead.');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'calculation_method' => ['required', 'in:Fixed,Percentage'],
            'calculation_basis' => ['required', 'in:Overdue Principal,Overdue Installment,Total Arrears'],
            'value' => ['required', 'numeric', 'gt:0'],
            'grace_days' => ['required', 'integer', 'min:0'],
            'frequency' => ['required', 'in:One-time,Daily'],
            'maximum_amount' => ['nullable', 'numeric', 'gt:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $productPenaltyRule->update($validated + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('loan-products.show', $productPenaltyRule->productVersion->product)->with('success', 'Penalty rule updated.');
    }

    public function destroyPenalty(ProductPenaltyRule $productPenaltyRule): RedirectResponse
    {
        if ($productPenaltyRule->charges()->exists()) {
            return back()->withErrors(['penalty' => 'This penalty rule has already produced loan charges and cannot be removed. Deactivate it to preserve the audit history.']);
        }

        $productPenaltyRule->charges()->exists() ? $productPenaltyRule->update(['is_active' => false]) : $productPenaltyRule->delete();

        return back()->with('success', 'Penalty rule deactivated or removed safely.');
    }

    public function updatePenaltyStatus(ProductPenaltyRule $productPenaltyRule): RedirectResponse
    {
        $productPenaltyRule->update(['is_active' => ! $productPenaltyRule->is_active]);

        return back()->with('success', $productPenaltyRule->is_active ? 'Penalty rule activated.' : 'Penalty rule deactivated. Existing loan charges remain unchanged.');
    }

    public function storeAccountMappings(Request $request, LoanProductVersion $loanProductVersion): RedirectResponse
    {
        $purposes = ['cash_account', 'bank_account', 'mobile_money_account', 'loan_receivable', 'interest_income', 'fee_income', 'penalty_income'];
        $validated = $request->validate(['accounts' => ['required', 'array'], 'accounts.*' => ['required', 'exists:chart_of_accounts,id']]);

        foreach ($purposes as $purpose) {
            abort_unless(isset($validated['accounts'][$purpose]), 422, "Missing {$purpose} account mapping.");
            $loanProductVersion->accountMappings()->updateOrCreate(['purpose' => $purpose], ['chart_of_account_id' => $validated['accounts'][$purpose]]);
        }

        return back()->with('success', 'Product accounting mappings updated.');
    }
}

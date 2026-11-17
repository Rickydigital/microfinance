<?php

namespace App\Http\Controllers;

use App\LoanApplicationStatus;
use App\Models\Collateral;
use App\Models\LoanApplication;
use App\Models\NumberSequence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CollateralController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:collateral.view', only: ['index']), new Middleware('can:collateral.manage', only: ['store', 'attach']), new Middleware('can:collateral.verify', only: ['verify'])];
    }

    public function index(Request $request): View
    {
        $collaterals = Collateral::query()->with('customer')->when($request->user()->branch_id, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->when($request->string('search')->toString(), fn ($query, string $search) => $query->where(fn ($query) => $query->where('collateral_number', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")->orWhere('ownership_reference', 'like', "%{$search}%")))
            ->latest()->paginate(20)->withQueryString();

        return view('collaterals.index', compact('collaterals'));
    }

    public function store(Request $request, LoanApplication $loanApplication): RedirectResponse
    {
        $this->ensureApplicationCanReceiveSecurity($request, $loanApplication);
        $validated = $request->validate(['type' => ['required', 'string', 'max:100'], 'description' => ['required', 'string', 'max:500'], 'ownership_reference' => ['required', 'string', 'max:255'], 'market_value' => ['required', 'numeric', 'gt:0'], 'forced_sale_value' => ['required', 'numeric', 'gt:0', 'lte:market_value'], 'pledged_value' => ['required', 'numeric', 'gt:0', 'lte:forced_sale_value'], 'valuer' => ['required', 'string', 'max:255'], 'valuation_date' => ['required', 'date', 'before_or_equal:today']]);
        $collateral = Collateral::query()->create(collect($validated)->except('pledged_value')->all() + ['customer_id' => $loanApplication->customer_id, 'branch_id' => $loanApplication->branch_id, 'collateral_number' => NumberSequence::issue('collateral'), 'status' => 'Proposed']);
        $collateral->pledges()->create(['loan_application_id' => $loanApplication->id, 'pledged_value' => $validated['pledged_value'], 'status' => 'Pending Verification']);

        return back()->with('success', 'Collateral created for this application and sent for verification.');
    }

    public function attach(Request $request, Collateral $collateral): RedirectResponse
    {
        $validated = $request->validate(['loan_application_id' => ['required', 'exists:loan_applications,id'], 'pledged_value' => ['required', 'numeric', 'gt:0']]);
        abort_unless($collateral->status === 'Verified', 422, 'Only verified collateral can be pledged.');
        abort_if((float) $validated['pledged_value'] > (float) $collateral->forced_sale_value, 422, 'Pledged value cannot exceed forced-sale value.');
        $collateral->pledges()->updateOrCreate(['loan_application_id' => $validated['loan_application_id']], ['pledged_value' => $validated['pledged_value'], 'status' => 'Pledged']);

        return back()->with('success', 'Collateral pledged to application.');
    }

    public function verify(Request $request, Collateral $collateral): RedirectResponse
    {
        abort_if($request->user()->branch_id !== null && $collateral->branch_id !== $request->user()->branch_id, 403);
        $collateral->update(['status' => 'Verified', 'verified_by' => $request->user()->id, 'verified_at' => now()]);
        $collateral->pledges()->where('status', 'Pending Verification')->update(['status' => 'Pledged']);

        return back()->with('success', 'Collateral verified.');
    }

    private function ensureApplicationCanReceiveSecurity(Request $request, LoanApplication $application): void
    {
        abort_if($request->user()->branch_id !== null && $application->branch_id !== $request->user()->branch_id, 403);
        abort_unless(in_array($application->status, [LoanApplicationStatus::Draft, LoanApplicationStatus::Submitted, LoanApplicationStatus::UnderAssessment, LoanApplicationStatus::Recommended, LoanApplicationStatus::PendingApproval], true), 422, 'Security cannot be added at this application stage.');
    }
}

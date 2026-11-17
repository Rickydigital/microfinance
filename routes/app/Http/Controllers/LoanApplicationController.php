<?php

namespace App\Http\Controllers;

use App\CustomerStatus;
use App\Http\Requests\StoreLoanApplicationRequest;
use App\Http\Requests\UpdateLoanApplicationRequest;
use App\LoanApplicationStatus;
use App\Models\Customer;
use App\Models\Guarantor;
use App\Models\IdentificationType;
use App\Models\LoanApplication;
use App\Models\LoanProductVersion;
use App\Models\NumberSequence;
use App\Services\CustomerCommunicationService;
use App\Services\CustomerKycReadinessService;
use App\Services\LoanApplicationPresentationService;
use App\Services\LoanApplicationWorkflowService;
use App\Services\LoanQuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class LoanApplicationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:loan.application.view', only: ['index', 'show']),
            new Middleware('can:loan.application.create', only: ['create', 'store']),
            new Middleware('can:loan.application.create', only: ['quote']),
            new Middleware('can:loan.application.update', only: ['edit', 'update']),
            new Middleware('can:loan.application.cancel', only: ['destroy']),
            new Middleware('can:loan.application.submit', only: ['submit']),
            new Middleware('can:loan.checklist.manage', only: ['completeChecklist', 'storeCondition']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $applications = LoanApplication::query()->with(['customer', 'productVersion.product'])
            ->when($request->user()->branch_id, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->when($request->string('search')->toString(), fn ($query, string $search) => $query->where(fn ($query) => $query->where('application_number', 'like', "%{$search}%")->orWhereHas('customer', fn ($customer) => $customer->where('customer_number', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))))
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status))
            ->latest()->paginate(20)->withQueryString();

        return view('loan-applications.index', compact('applications'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $customers = Customer::query()->with(['economicProfiles' => fn ($query) => $query->whereNull('effective_to')])->where('branch_id', $request->user()->branch_id)->where('status', CustomerStatus::Active)->orderBy('first_name')->get();
        $productVersions = LoanProductVersion::query()
            ->with(['product', 'fees'])
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->whereDate('effective_from', '<=', today())
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', today()))
            ->orderBy('loan_product_id')
            ->orderByDesc('version')
            ->get();

        return view('loan-applications.create', compact('customers', 'productVersions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLoanApplicationRequest $request): RedirectResponse
    {
        $customer = Customer::query()->where('branch_id', $request->user()->branch_id)->where('status', CustomerStatus::Active)->findOrFail($request->integer('customer_id'));
        $version = LoanProductVersion::query()->whereHas('product', fn ($query) => $query->where('is_active', true))->findOrFail($request->integer('loan_product_version_id'));
        $validated = $request->validated();

        if ((float) $validated['requested_amount'] < (float) $version->minimum_amount || (float) $validated['requested_amount'] > (float) $version->maximum_amount) {
            return back()->withInput()->withErrors(['requested_amount' => 'Amount must be within the selected product limits.']);
        }

        if ((int) $validated['requested_term'] < $version->minimum_term || (int) $validated['requested_term'] > $version->maximum_term) {
            return back()->withInput()->withErrors(['requested_term' => 'Term must be within the selected product limits.']);
        }

        $profile = $customer->economicProfiles()->whereNull('effective_to')->latest('effective_from')->first();
        $validated['repayment_frequency'] = $version->repayment_frequency;
        $validated['declared_monthly_income'] = $profile?->monthly_income ?? $validated['declared_monthly_income'];
        $validated['declared_monthly_expenses'] = $profile?->monthly_expenses ?? $validated['declared_monthly_expenses'];
        $validated['existing_debt_payment'] = $profile?->existing_debt_payment ?? $validated['existing_debt_payment'];

        $application = LoanApplication::create($validated + [
            'branch_id' => $customer->branch_id,
            'created_by' => $request->user()->id,
            'application_number' => NumberSequence::issue('application'),
            'source' => 'branch',
            'status' => LoanApplicationStatus::Draft,
        ]);

        return redirect()->route('loan-applications.show', $application)->with('success', 'Loan application saved as draft.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, LoanApplication $loanApplication, LoanQuotationService $quotation, CustomerKycReadinessService $kycReadiness, LoanApplicationPresentationService $presentation): View
    {
        abort_if($request->user()->branch_id !== null && $loanApplication->branch_id !== $request->user()->branch_id, 403);
        $loanApplication->load([
            'branch', 'creator', 'productVersion.product', 'productVersion.fees',
            'customer.branch', 'customer.assignedOfficer', 'customer.addresses', 'customer.identifications.verifier',
            'customer.documents.uploader', 'customer.economicProfiles', 'customer.relationships',
            'customer.loans', 'customer.loanApplications', 'assessments.assessor', 'approvals.decisionMaker',
            'checklistItems.completedBy', 'guarantors.guarantor.verifier', 'collaterals.collateral.verifier',
            'disbursement.creator', 'disbursement.authorizer', 'loan.installments',
        ]);

        $latestAssessment = $loanApplication->assessments->sortByDesc('assessed_at')->first();
        $nextApproval = $loanApplication->approvals
            ->where('status', 'Pending')
            ->sortBy('sequence')
            ->first();
        $canApproveNextLevel = $nextApproval !== null && ($request->user()->hasRole($nextApproval->required_role) || $request->user()->hasRole('System Administrator'));
        $pendingApproval = $canApproveNextLevel ? $nextApproval : null;
        $quote = $quotation->quote($loanApplication->productVersion->loadMissing('fees'), (float) $loanApplication->requested_amount, $loanApplication->requested_term, (float) $loanApplication->declared_monthly_income, (float) $loanApplication->declared_monthly_expenses, (float) $loanApplication->existing_debt_payment);
        $approvedQuote = $loanApplication->approved_amount
            ? $quotation->quote($loanApplication->productVersion, (float) $loanApplication->approved_amount, $loanApplication->requested_term, (float) $loanApplication->declared_monthly_income, (float) $loanApplication->declared_monthly_expenses, (float) $loanApplication->existing_debt_payment)
            : null;
        $kyc = $kycReadiness->evaluate($loanApplication->customer);
        $dossier = $presentation->build($loanApplication, $approvedQuote ?? $quote, $kyc);
        $identificationTypes = IdentificationType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        $availableGuarantors = Guarantor::query()
            ->where('status', 'Verified')
            ->whereHas('principalCustomer', function ($query) use ($loanApplication) {
                $query->where('branch_id', $loanApplication->branch_id);
            })
            ->whereDoesntHave('guarantees', function ($query) use ($loanApplication) {
                $query->where('loan_application_id', $loanApplication->id);
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'phone']);

        return view('loan-applications.show', compact('loanApplication', 'latestAssessment', 'pendingApproval', 'quote', 'approvedQuote', 'kyc', 'dossier', 'identificationTypes', 'availableGuarantors'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, LoanApplication $loanApplication): View
    {
        abort_if($request->user()->branch_id !== null && $loanApplication->branch_id !== $request->user()->branch_id, 403);
        abort_unless(in_array($loanApplication->status, [LoanApplicationStatus::Draft, LoanApplicationStatus::UnderAssessment], true), 422, 'Only draft or returned applications can be edited.');
        $customers = Customer::query()->with(['economicProfiles' => fn ($query) => $query->whereNull('effective_to')])->where('branch_id', $loanApplication->branch_id)->where('status', CustomerStatus::Active)->orderBy('first_name')->get();
        $productVersions = LoanProductVersion::query()
            ->with(['product', 'fees'])
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->whereDate('effective_from', '<=', today())
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', today()))
            ->orderBy('loan_product_id')
            ->orderByDesc('version')
            ->get();

        return view('loan-applications.edit', compact('loanApplication', 'customers', 'productVersions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLoanApplicationRequest $request, LoanApplication $loanApplication): RedirectResponse
    {
        abort_if($request->user()->branch_id !== null && $loanApplication->branch_id !== $request->user()->branch_id, 403);
        $customer = Customer::query()->where('branch_id', $loanApplication->branch_id)->where('status', CustomerStatus::Active)->findOrFail($request->integer('customer_id'));
        $version = LoanProductVersion::query()->whereHas('product', fn ($query) => $query->where('is_active', true))->findOrFail($request->integer('loan_product_version_id'));
        $validated = $request->validated();

        if ((float) $validated['requested_amount'] < (float) $version->minimum_amount || (float) $validated['requested_amount'] > (float) $version->maximum_amount) {
            return back()->withInput()->withErrors(['requested_amount' => 'Amount must be within the selected product limits.']);
        }

        if ((int) $validated['requested_term'] < $version->minimum_term || (int) $validated['requested_term'] > $version->maximum_term) {
            return back()->withInput()->withErrors(['requested_term' => 'Term must be within the selected product limits.']);
        }

        $validated['repayment_frequency'] = $version->repayment_frequency;
        $profile = $customer->economicProfiles()->whereNull('effective_to')->latest('effective_from')->first();
        $validated['declared_monthly_income'] = $profile?->monthly_income ?? $validated['declared_monthly_income'];
        $validated['declared_monthly_expenses'] = $profile?->monthly_expenses ?? $validated['declared_monthly_expenses'];
        $validated['existing_debt_payment'] = $profile?->existing_debt_payment ?? $validated['existing_debt_payment'];
        $loanApplication->update($validated);

        return redirect()->route('loan-applications.show', $loanApplication)->with('success', 'Loan application updated and quotation recalculated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LoanApplication $loanApplication): RedirectResponse
    {
        abort_unless($loanApplication->status === LoanApplicationStatus::Draft, 422, 'Only drafts can be deleted.');
        $loanApplication->delete();

        return redirect()->route('loan-applications.index')->with('success', 'Draft deleted.');
    }

    public function submit(LoanApplication $loanApplication, LoanApplicationWorkflowService $workflow, CustomerCommunicationService $communication, CustomerKycReadinessService $readiness, LoanQuotationService $quotation): RedirectResponse
    {
        $kyc = $readiness->evaluate($loanApplication->customer);
        $quote = $quotation->quote($loanApplication->productVersion->loadMissing('fees'), (float) $loanApplication->requested_amount, $loanApplication->requested_term, (float) $loanApplication->declared_monthly_income, (float) $loanApplication->declared_monthly_expenses, (float) $loanApplication->existing_debt_payment);

        if (! $kyc['ready']) {
            return back()->withErrors(['application' => 'Complete and verify all configured KYC requirements before submission.']);
        }

        if (! $quote['affordable']) {
            return back()->withErrors(['application' => 'The requested amount exceeds affordability policy. Reduce it to the recommended amount or update the verified economic profile.']);
        }

        $acceptedGuarantors = $loanApplication->guarantors()->where('status', 'Accepted')->whereHas('guarantor', fn ($query) => $query->where('status', 'Verified'))->count();

        if ($acceptedGuarantors < $loanApplication->productVersion->required_guarantors) {
            return back()->withErrors(['application' => "This product requires {$loanApplication->productVersion->required_guarantors} verified guarantor(s). Currently accepted: {$acceptedGuarantors}."]);
        }

        $hasVerifiedCollateral = $loanApplication->collaterals()->where('status', 'Pledged')->whereHas('collateral', fn ($query) => $query->where('status', 'Verified'))->exists();

        if ($loanApplication->productVersion->requires_collateral && ! $hasVerifiedCollateral) {
            return back()->withErrors(['application' => 'This product requires verified and pledged collateral before submission.']);
        }

        $workflow->submit($loanApplication);
        $communication->applicationSubmitted($loanApplication->customer, $loanApplication->application_number);

        return back()->with('success', 'Application submitted for assessment.');
    }

    public function completeChecklist(
        Request $request,
        LoanApplication $loanApplication
    ): RedirectResponse {
        $validated = $request->validate([
            'items' => ['present', 'array'],
            'items.*' => ['integer', 'exists:application_checklists,id'],
        ]);

        // Convert submitted checkbox IDs from strings to integers.
        $selectedIds = collect($validated['items'])
            ->map(fn ($id) => (int) $id)
            ->all();

        $loanApplication->checklistItems()->each(
            function ($item) use ($request, $selectedIds): void {

                $completed = in_array(
                    (int) $item->id,
                    $selectedIds,
                    true
                );

                $item->update([
                    'is_completed' => $completed,
                    'completed_by' => $completed
                        ? $request->user()->id
                        : null,
                    'completed_at' => $completed
                        ? now()
                        : null,
                ]);
            }
        );

        return back()->with(
            'success',
            'Pre-disbursement checklist updated.'
        );
    }

    public function storeCondition(Request $request, LoanApplication $loanApplication): RedirectResponse
    {
        abort_if($request->user()->branch_id !== null && $loanApplication->branch_id !== $request->user()->branch_id, 403);
        $validated = $request->validate(['label' => ['required', 'string', 'min:5', 'max:255'], 'notes' => ['nullable', 'string', 'max:2000'], 'is_required' => ['nullable', 'boolean']]);
        $loanApplication->checklistItems()->create(['code' => 'manual-'.str()->uuid(), 'label' => $validated['label'], 'notes' => $validated['notes'] ?? null, 'is_required' => $request->boolean('is_required'), 'is_completed' => false]);

        return back()->with('success', 'Loan condition added to this application.');
    }

    public function quote(Request $request, LoanQuotationService $quotation): JsonResponse
    {
        $validated = $request->validate([
            'loan_product_version_id' => ['required', 'exists:loan_product_versions,id'],
            'requested_amount' => ['required', 'numeric', 'gt:0'],
            'requested_term' => ['required', 'integer', 'min:1'],
            'monthly_income' => ['required', 'numeric', 'min:0'],
            'monthly_expenses' => ['required', 'numeric', 'min:0'],
            'existing_debt_payment' => ['required', 'numeric', 'min:0'],
        ]);
        $version = LoanProductVersion::query()->with('fees')->findOrFail($validated['loan_product_version_id']);

        return response()->json($quotation->quote(
            $version,
            (float) $validated['requested_amount'],
            (int) $validated['requested_term'],
            (float) $validated['monthly_income'],
            (float) $validated['monthly_expenses'],
            (float) $validated['existing_debt_payment'],
        ));
    }
}

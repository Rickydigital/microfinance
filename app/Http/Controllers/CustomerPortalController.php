<?php

namespace App\Http\Controllers;

use App\CustomerStatus;
use App\LoanApplicationStatus;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanProductVersion;
use App\Models\NumberSequence;
use App\Models\User;
use App\Services\CustomerCommunicationService;
use App\Services\LoanQuotationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerPortalController extends Controller
{
    public function dashboard(Request $request): View
    {
        $customer = $request->user('customer');
        $applications = $customer->loanApplications()->with('productVersion.product')->latest()->limit(5)->get();
        $loans = $customer->loans()->with('installments')->latest()->get();

        return view('portal.dashboard', compact('customer', 'applications', 'loans'));
    }

    public function products(): View
    {
        $versions = $this->availableProducts()->with(['product', 'fees', 'penaltyRules'])->get();

        return view('portal.products', compact('versions'));
    }

    public function applications(Request $request): View
    {
        $applications = $request->user('customer')->loanApplications()->with(['productVersion.product', 'loan'])->latest()->paginate(15);

        return view('portal.applications.index', compact('applications'));
    }

    public function createApplication(Request $request): View
    {
        $customer = $request->user('customer');
        $versions = $this->availableProducts()->with(['product', 'fees'])->get();
        $profile = $customer->economicProfiles()->whereNull('effective_to')->latest('effective_from')->first();

        return view('portal.applications.create', compact('customer', 'versions', 'profile'));
    }

    public function storeApplication(Request $request, LoanQuotationService $quotation, CustomerCommunicationService $communication): RedirectResponse
    {
        $customer = $request->user('customer');
        abort_unless($customer->status === CustomerStatus::Active && $customer->verified_at !== null, 403, 'Staff KYC verification is required.');
        $validated = $request->validate(['loan_product_version_id' => ['required', 'integer'], 'requested_amount' => ['required', 'numeric', 'gt:0'], 'requested_term' => ['required', 'integer', 'min:1'], 'purpose' => ['required', 'string', 'min:10', 'max:2000']]);
        $version = $this->availableProducts()->with('fees')->findOrFail($validated['loan_product_version_id']);
        $profile = $customer->economicProfiles()->whereNull('effective_to')->latest('effective_from')->first();

        if ($profile === null) {
            return back()->withInput()->withErrors(['application' => 'Your verified economic profile is missing. Contact your branch before applying.']);
        }

        if ((float) $validated['requested_amount'] < (float) $version->minimum_amount || (float) $validated['requested_amount'] > (float) $version->maximum_amount) {
            return back()->withInput()->withErrors(['requested_amount' => 'Requested amount is outside the selected product limits.']);
        }

        if ($validated['requested_term'] < $version->minimum_term || $validated['requested_term'] > $version->maximum_term) {
            return back()->withInput()->withErrors(['requested_term' => 'Requested term is outside the selected product limits.']);
        }

        $quote = $quotation->quote($version, (float) $validated['requested_amount'], $validated['requested_term'], (float) $profile->monthly_income, (float) $profile->monthly_expenses, (float) $profile->existing_debt_payment);
        if (! $quote['affordable']) {
            return back()->withInput()->withErrors(['requested_amount' => 'This request exceeds affordability policy. Recommended maximum: '.number_format($quote['recommended_amount'], 2).'.']);
        }

        $staffCreator = $customer->assignedOfficer ?: User::query()->where('branch_id', $customer->branch_id)->where('is_active', true)->first();
        if ($staffCreator === null) {
            return back()->withInput()->withErrors(['application' => 'Your branch has no available officer. Contact the branch for assistance.']);
        }

        $application = DB::transaction(fn () => LoanApplication::query()->create(['branch_id' => $customer->branch_id, 'customer_id' => $customer->id, 'loan_product_version_id' => $version->id, 'created_by' => $staffCreator->id, 'portal_submitted_by' => $customer->id, 'application_number' => NumberSequence::issue('application'), 'source' => 'customer_portal', 'requested_amount' => $validated['requested_amount'], 'requested_term' => $validated['requested_term'], 'repayment_frequency' => $version->repayment_frequency, 'purpose' => $validated['purpose'], 'declared_monthly_income' => $profile->monthly_income, 'declared_monthly_expenses' => $profile->monthly_expenses, 'existing_debt_payment' => $profile->existing_debt_payment, 'status' => LoanApplicationStatus::Submitted, 'submitted_at' => now()]));
        $communication->applicationSubmitted($customer, $application->application_number);

        return redirect()->route('portal.applications.show', $application)->with('success', 'Loan application submitted for staff assessment.');
    }

    public function showApplication(Request $request, LoanApplication $loanApplication, LoanQuotationService $quotation): View
    {
        abort_unless($loanApplication->customer_id === $request->user('customer')->id, 404);
        $loanApplication->load(['productVersion.product', 'productVersion.fees', 'assessments', 'approvals', 'loan']);
        $quote = $quotation->quote($loanApplication->productVersion, (float) ($loanApplication->approved_amount ?: $loanApplication->requested_amount), $loanApplication->requested_term, (float) $loanApplication->declared_monthly_income, (float) $loanApplication->declared_monthly_expenses, (float) $loanApplication->existing_debt_payment);

        return view('portal.applications.show', compact('loanApplication', 'quote'));
    }

    public function loans(Request $request): View
    {
        $loans = $request->user('customer')->loans()->with('application.productVersion.product')->latest()->paginate(15);

        return view('portal.loans.index', compact('loans'));
    }

    public function payments(Request $request): View
    {
        $customer = $request->user('customer');
        $loans = $customer->loans()->with('application.productVersion.product')
            ->whereIn('status', ['Active', 'In Arrears'])->latest()->get();
        $applications = $customer->loanApplications()->with('productVersion.product')
            ->where('status', LoanApplicationStatus::Approved)->get()
            ->filter(fn (LoanApplication $application): bool => $application->assuranceAmount() > 0);

        return view('portal.payments', compact('loans', 'applications'));
    }

    public function showLoan(Request $request, Loan $loan): View
    {
        abort_unless($loan->customer_id === $request->user('customer')->id, 404);
        $loan->load(['application.productVersion.product', 'installments', 'repayments.allocations']);

        return view('portal.loans.show', compact('loan'));
    }

    private function availableProducts()
    {
        return LoanProductVersion::query()->whereHas('product', fn ($query) => $query->where('is_active', true))->whereDate('effective_from', '<=', today())->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', today()));
    }
}

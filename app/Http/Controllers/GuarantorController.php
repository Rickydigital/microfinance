<?php

namespace App\Http\Controllers;

use App\LoanApplicationStatus;
use App\Models\Customer;
use App\Models\Guarantor;
use App\Models\IdentificationType;
use App\Models\LoanApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class GuarantorController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:guarantor.view', only: ['index']), new Middleware('can:guarantor.manage', only: ['store', 'storeRegister', 'updateRegister', 'setStatus', 'attach', 'attachExisting']), new Middleware('can:guarantor.verify', only: ['verify'])];
    }

    public function index(Request $request): View
    {
        $baseQuery = Guarantor::query()
            ->when($request->user()->branch_id, fn ($query, $branchId) => $query->whereHas('principalCustomer', fn ($customer) => $customer->where('branch_id', $branchId)))
            ->when($request->string('search')->toString(), fn ($query, string $search) => $query->where(fn ($query) => $query->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('identification_number', 'like', "%{$search}%")))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')));

        $metrics = [
            'total' => (clone $baseQuery)->count(),
            'verified' => (clone $baseQuery)->where('status', 'Verified')->count(),
            'pending' => (clone $baseQuery)->whereNotIn('status', ['Verified', 'Inactive'])->count(),
            'active' => (clone $baseQuery)->where('status', '!=', 'Inactive')->count(),
            'exposure' => (clone $baseQuery)->withSum('guarantees', 'guaranteed_amount')->get()->sum('guarantees_sum_guaranteed_amount'),
        ];
        $guarantors = (clone $baseQuery)->with(['principalCustomer', 'guarantees.application'])->withSum('guarantees', 'guaranteed_amount')
            ->latest()->paginate(20)->withQueryString();

        $customers = Customer::query()->when($request->user()->branch_id, fn ($query, $branchId) => $query->where('branch_id', $branchId))->orderBy('first_name')->get(['id', 'customer_number', 'first_name', 'last_name']);
        $identificationTypes = IdentificationType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['code', 'name']);
        $loanApplications = LoanApplication::query()
            ->with('customer:id,first_name,last_name,customer_number')
            ->when($request->user()->branch_id, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->whereIn('status', [LoanApplicationStatus::Draft, LoanApplicationStatus::Submitted, LoanApplicationStatus::UnderAssessment, LoanApplicationStatus::Recommended, LoanApplicationStatus::PendingApproval])
            ->latest()->get(['id', 'customer_id', 'application_number', 'requested_amount', 'status']);

        return view('guarantors.index', compact('guarantors', 'metrics', 'customers', 'identificationTypes', 'loanApplications'));
    }

    public function storeRegister(Request $request): RedirectResponse
    {
        $validated = $request->validate(['principal_customer_id' => ['required', 'exists:customers,id'], 'first_name' => ['required', 'string', 'max:100'], 'middle_name' => ['nullable', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'], 'phone' => ['required', 'string', 'max:30'], 'identification_type' => ['required', 'exists:identification_types,code'], 'identification_number' => ['required', 'string', 'max:100', 'unique:guarantors'], 'occupation' => ['required', 'string', 'max:150'], 'monthly_income' => ['required', 'numeric', 'min:0']]);
        $customer = Customer::query()->findOrFail($validated['principal_customer_id']);
        abort_if($request->user()->branch_id && $customer->branch_id !== $request->user()->branch_id, 403);
        Guarantor::query()->create($validated + ['status' => 'Pending Verification']);

        return back()->with('success', 'Guarantor added to the register.');
    }

    public function updateRegister(Request $request, Guarantor $guarantor): RedirectResponse
    {
        abort_if($request->user()->branch_id && $guarantor->principalCustomer?->branch_id !== $request->user()->branch_id, 403);
        $validated = $request->validate(['principal_customer_id' => ['required', 'exists:customers,id'], 'first_name' => ['required', 'string', 'max:100'], 'middle_name' => ['nullable', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'], 'phone' => ['required', 'string', 'max:30'], 'identification_type' => ['required', 'exists:identification_types,code'], 'identification_number' => ['required', 'string', 'max:100', 'unique:guarantors,identification_number,'.$guarantor->id], 'occupation' => ['required', 'string', 'max:150'], 'monthly_income' => ['required', 'numeric', 'min:0']]);
        $customer = Customer::query()->findOrFail($validated['principal_customer_id']);
        abort_if($request->user()->branch_id && $customer->branch_id !== $request->user()->branch_id, 403);
        $guarantor->update($validated);

        return back()->with('success', 'Guarantor updated successfully.');
    }

    public function setStatus(Request $request, Guarantor $guarantor): RedirectResponse
    {
        abort_if($request->user()->branch_id && $guarantor->principalCustomer?->branch_id !== $request->user()->branch_id, 403);
        $validated = $request->validate(['active' => ['required', 'boolean']]);
        $guarantor->update(['status' => $validated['active'] ? 'Pending Verification' : 'Inactive', 'verified_by' => null, 'verified_at' => null]);

        return back()->with('success', $validated['active'] ? 'Guarantor activated and returned for verification.' : 'Guarantor deactivated.');
    }

    public function store(Request $request, LoanApplication $loanApplication): RedirectResponse
    {
        $this->ensureApplicationCanReceiveSecurity($request, $loanApplication);
        $validated = $request->validate(['first_name' => ['required', 'string', 'max:100'], 'middle_name' => ['nullable', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'], 'phone' => ['required', 'string', 'max:30'], 'identification_type' => ['required', 'exists:identification_types,code'], 'identification_number' => ['required', 'string', 'max:100', 'unique:guarantors,identification_number'], 'occupation' => ['nullable', 'string', 'max:150'], 'monthly_income' => ['required', 'numeric', 'gt:0'], 'guaranteed_amount' => ['required', 'numeric', 'gt:0'], 'consent_received' => ['required', 'accepted']]);
        abort_if((float) $validated['guaranteed_amount'] > (float) $loanApplication->requested_amount, 422, 'Guaranteed amount cannot exceed the requested loan amount.');
        $guarantor = Guarantor::query()->create(collect($validated)->except(['guaranteed_amount', 'consent_received'])->all() + ['principal_customer_id' => $loanApplication->customer_id, 'status' => 'Pending Verification']);
        $guarantor->guarantees()->create(['loan_application_id' => $loanApplication->id, 'guaranteed_amount' => $validated['guaranteed_amount'], 'status' => 'Pending Verification', 'consent_received' => true, 'consented_at' => now()]);

        return back()->with('success', 'Guarantor created for this application and sent for verification.');
    }

    /** Attach a verified guarantor already held in the register to this application. */
    public function attachExisting(Request $request, LoanApplication $loanApplication): RedirectResponse
    {
        $this->ensureApplicationCanReceiveSecurity($request, $loanApplication);

        $validated = $request->validate([
            'guarantor_id' => ['required', 'integer', 'exists:guarantors,id'],
            'guaranteed_amount' => ['required', 'numeric', 'gt:0'],
            'consent_received' => ['required', 'accepted'],
        ]);

        $guarantor = Guarantor::query()
            ->whereKey($validated['guarantor_id'])
            ->where('status', 'Verified')
            ->whereHas('principalCustomer', function ($query) use ($loanApplication) {
                $query->where('branch_id', $loanApplication->branch_id);
            })
            ->first();

        if (! $guarantor) {
            return back()->withErrors([
                'guarantor_id' => 'Choose a verified guarantor from this branch.',
            ])->withInput();
        }

        if ((float) $validated['guaranteed_amount'] > (float) $loanApplication->requested_amount) {
            return back()->withErrors([
                'guaranteed_amount' => 'The guarantee cannot exceed the requested principal.',
            ])->withInput();
        }

        if ($loanApplication->guarantors()->where('guarantor_id', $guarantor->id)->exists()) {
            return back()->withErrors([
                'guarantor_id' => 'This guarantor is already attached to the application.',
            ]);
        }

        $loanApplication->guarantors()->create([
            'guarantor_id' => $guarantor->id,
            'guaranteed_amount' => $validated['guaranteed_amount'],
            'status' => 'Accepted',
            'consent_received' => true,
            'consented_at' => now(),
        ]);

        return back()->with('success', 'Existing verified guarantor attached successfully.');
    }

    public function attach(Request $request, Guarantor $guarantor): RedirectResponse
    {
        $validated = $request->validate(['loan_application_id' => ['required', 'exists:loan_applications,id'], 'guaranteed_amount' => ['required', 'numeric', 'gt:0'], 'consent_received' => ['required', 'accepted'], 'notes' => ['nullable', 'string', 'max:1000']]);
        abort_unless($guarantor->status === 'Verified', 422, 'Only a verified guarantor can secure an application.');
        $loanApplication = LoanApplication::query()->findOrFail($validated['loan_application_id']);
        $this->ensureApplicationCanReceiveSecurity($request, $loanApplication);
        abort_if($guarantor->principal_customer_id !== $loanApplication->customer_id, 422, 'This guarantor is registered for a different borrower.');
        abort_if((float) $validated['guaranteed_amount'] > (float) $loanApplication->requested_amount, 422, 'Guaranteed amount cannot exceed the requested loan amount.');
        $guarantor->guarantees()->updateOrCreate(['loan_application_id' => $loanApplication->id], ['guaranteed_amount' => $validated['guaranteed_amount'], 'status' => 'Accepted', 'consent_received' => true, 'consented_at' => now(), 'notes' => $validated['notes'] ?? null]);

        return back()->with('success', 'Guarantor attached to application.');
    }

    public function verify(Request $request, Guarantor $guarantor): RedirectResponse
    {
        abort_if($request->user()->branch_id !== null && $guarantor->principalCustomer?->branch_id !== $request->user()->branch_id, 403);
        abort_if($guarantor->status === 'Inactive', 422, 'Activate this guarantor before verification.');
        $guarantor->update(['status' => 'Verified', 'verified_by' => $request->user()->id, 'verified_at' => now()]);
        $guarantor->guarantees()->where('status', 'Pending Verification')->update(['status' => 'Accepted']);

        return back()->with('success', 'Guarantor verified.');
    }

    private function ensureApplicationCanReceiveSecurity(Request $request, LoanApplication $application): void
    {
        abort_if($request->user()->branch_id !== null && $application->branch_id !== $request->user()->branch_id, 403);
        abort_unless(in_array($application->status, [LoanApplicationStatus::Draft, LoanApplicationStatus::Submitted, LoanApplicationStatus::UnderAssessment, LoanApplicationStatus::Recommended, LoanApplicationStatus::PendingApproval], true), 422, 'Security cannot be added at this application stage.');
    }
}

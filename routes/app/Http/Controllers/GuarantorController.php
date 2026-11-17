<?php

namespace App\Http\Controllers;

use App\LoanApplicationStatus;
use App\Models\Guarantor;
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
        return [new Middleware('can:guarantor.view', only: ['index']), new Middleware('can:guarantor.manage', only: ['store', 'attach', 'attachExisting']), new Middleware('can:guarantor.verify', only: ['verify'])];
    }

    public function index(Request $request): View
    {
        $guarantors = Guarantor::query()->with('principalCustomer')->withSum('guarantees', 'guaranteed_amount')
            ->when($request->user()->branch_id, fn ($query, $branchId) => $query->whereHas('principalCustomer', fn ($customer) => $customer->where('branch_id', $branchId)))
            ->when($request->string('search')->toString(), fn ($query, string $search) => $query->where(fn ($query) => $query->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('identification_number', 'like', "%{$search}%")))
            ->latest()->paginate(20)->withQueryString();

        return view('guarantors.index', compact('guarantors'));
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
        $validated = $request->validate(['loan_application_id' => ['required', 'exists:loan_applications,id'], 'guaranteed_amount' => ['required', 'numeric', 'gt:0'], 'consent_received' => ['required', 'accepted']]);
        abort_unless($guarantor->status === 'Verified', 422, 'Only a verified guarantor can secure an application.');
        $guarantor->guarantees()->updateOrCreate(['loan_application_id' => $validated['loan_application_id']], ['guaranteed_amount' => $validated['guaranteed_amount'], 'status' => 'Accepted', 'consent_received' => true, 'consented_at' => now()]);

        return back()->with('success', 'Guarantor attached to application.');
    }

    public function verify(Request $request, Guarantor $guarantor): RedirectResponse
    {
        abort_if($request->user()->branch_id !== null && $guarantor->principalCustomer?->branch_id !== $request->user()->branch_id, 403);
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

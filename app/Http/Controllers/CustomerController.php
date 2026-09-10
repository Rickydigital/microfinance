<?php

namespace App\Http\Controllers;

use App\CustomerStatus;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\DocumentType;
use App\Models\IdentificationType;
use App\Models\NumberSequence;
use App\Models\Region;
use App\Models\User;
use App\Services\CustomerCommunicationService;
use App\Services\CustomerKycReadinessService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class CustomerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:customer.view', only: ['index', 'show']),
            new Middleware('can:customer.create', only: ['create', 'store']),
            new Middleware('can:customer.update', only: ['edit', 'update', 'resetPortalPassword']),
            new Middleware('can:customer.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $customers = Customer::query()->with(['branch', 'assignedOfficer'])
            ->when($request->user()->branch_id, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('customer_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })->latest()->paginate(20)->withQueryString();

        return view('customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $officers = User::query()->where('branch_id', $request->user()->branch_id)->where('is_active', true)->orderBy('first_name')->get();

        return view('customers.create', [
            'officers' => $officers,
            ...$this->referenceOptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request, CustomerCommunicationService $communication): RedirectResponse
    {
        $customer = Customer::create($request->validated() + [
            'branch_id' => $request->user()->branch_id,
            'customer_number' => NumberSequence::issue('customer'),
            'status' => CustomerStatus::PendingVerification,
        ]);

        $communication->customerRegistered($customer);

        return redirect()->route('customers.show', $customer)->with('success', 'Customer created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Customer $customer, CustomerKycReadinessService $readiness): View
    {
        abort_if($request->user()->branch_id !== null && $customer->branch_id !== $request->user()->branch_id, 403);
        $customer->load(['branch', 'assignedOfficer', 'loanApplications.productVersion.product', 'loans', 'addresses.verifier', 'identifications.verifier', 'documents.uploader', 'economicProfiles', 'relationships', 'registeredGuarantors', 'collaterals']);

        $identificationTypes = IdentificationType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $documentTypes = DocumentType::query()->where('is_active', true)->where(fn ($query) => $query->whereNull('customer_type_code')->orWhere('customer_type_code', $customer->type))->orderBy('sort_order')->get();
        $regions = Region::query()->orderBy('name')->get(['id', 'name']);
        $kycReadiness = $readiness->evaluate($customer);

        return view('customers.show', compact('customer', 'identificationTypes', 'documentTypes', 'regions', 'kycReadiness'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Customer $customer): View
    {
        abort_if($request->user()->branch_id !== null && $customer->branch_id !== $request->user()->branch_id, 403);
        $officers = User::query()->where('branch_id', $request->user()->branch_id)->where('is_active', true)->orderBy('first_name')->get();

        return view('customers.edit', [
            'customer' => $customer,
            'officers' => $officers,
            ...$this->referenceOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        abort_if($request->user()->branch_id !== null && $customer->branch_id !== $request->user()->branch_id, 403);
        $customer->update($request->validated());

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated successfully.');
    }

    public function resetPortalPassword(Request $request, Customer $customer, CustomerCommunicationService $communication): RedirectResponse
    {
        abort_if($request->user()->branch_id !== null && $customer->branch_id !== $request->user()->branch_id, 403);
        abort_unless($customer->status === CustomerStatus::Active && $customer->verified_at !== null, 422, 'Activate and verify the customer before enabling portal login.');
        $validated = $request->validate([
            'reset_mode' => ['required', 'in:generate,manual'],
            'password' => ['nullable', 'required_if:reset_mode,manual', 'confirmed', Password::min(8)->letters()->numbers()],
            'notify_customer' => ['nullable', 'boolean'],
        ]);
        $temporaryPassword = $validated['reset_mode'] === 'generate' ? Str::password(12, true, true, false, false) : $validated['password'];

        if (($validated['reset_mode'] === 'generate' || $request->boolean('notify_customer')) && ! $customer->email && ! $customer->phone) {
            return back()->withErrors(['password' => 'Add an email address or phone number before sending a temporary portal password.']);
        }

        $customer->update(['password' => $temporaryPassword, 'must_change_password' => true]);

        if ($validated['reset_mode'] === 'generate' || $request->boolean('notify_customer')) {
            $communication->portalPasswordReset($customer, $temporaryPassword);
        }

        return back()->with('success', $validated['reset_mode'] === 'generate' ? 'Temporary password generated and sent to the customer.' : 'Customer portal password reset. The customer must change it after login.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Customer $customer): RedirectResponse
    {
        abort_if($request->user()->branch_id !== null && $customer->branch_id !== $request->user()->branch_id, 403);
        abort_if($customer->loans()->exists() || $customer->loanApplications()->exists(), 422, 'A customer with financial records cannot be deleted.');
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }

    /** @return array{customerTypes: Collection<int, CustomerType>, identificationTypes: Collection<int, IdentificationType>} */
    private function referenceOptions(): array
    {
        return [
            'customerTypes' => CustomerType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'identificationTypes' => IdentificationType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ];
    }
}

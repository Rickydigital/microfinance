<?php

namespace App\Http\Controllers;

use App\CustomerStatus;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\LoanApplicationStatus;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\District;
use App\Models\DocumentType;
use App\Models\IdentificationType;
use App\Models\NumberSequence;
use App\Models\Region;
use App\Models\Street;
use App\Models\User;
use App\Models\Ward;
use App\Services\CustomerCommunicationService;
use App\Services\CustomerKycReadinessService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:customer.view', only: ['index', 'show']),
            new Middleware('can:customer.create', only: ['create', 'store']),
            new Middleware('can:customer.update', only: ['edit', 'update', 'resetPortalPassword', 'setStatus']),
            new Middleware('can:customer.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $base = Customer::query()
            ->when($request->user()->branch_id, fn ($query, $branchId) => $query->where('branch_id', $branchId));
        $metrics = [
            'total' => (clone $base)->count(),
            'verified' => (clone $base)->whereNotNull('verified_at')->count(),
            'pending' => (clone $base)->whereNull('verified_at')->count(),
            'active' => (clone $base)->where('status', CustomerStatus::Active->value)->count(),
            'rejected' => (clone $base)->whereNull('verified_at')->whereHas('kycDecisions', fn ($q) => $q->where('decision', 'rejected'))->count(),
        ];
        $customers = (clone $base)->with(['addresses', 'economicProfiles', 'relationships', 'loans', 'branch', 'assignedOfficer', 'kycDecisions' => fn ($q) => $q->latest('id')->limit(1)])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $term = trim($request->string('search')->toString());
                foreach (preg_split('/\s+/', $term) as $word) {
                    $like = '%'.addcslashes($word, '%_\\').'%';
                    $query->where(function ($sub) use ($like): void {
                        $sub->where('customer_number', 'like', $like)
                            ->orWhere('first_name', 'like', $like)
                            ->orWhere('middle_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('phone', 'like', $like)
                            ->orWhere('identification_number', 'like', $like);
                    });
                }
            })
            ->when($request->input('kyc') === 'verified', fn ($q) => $q->whereNotNull('verified_at'))
            ->when($request->input('kyc') === 'pending', fn ($q) => $q->whereNull('verified_at'))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->input('status') === 'active', fn ($q) => $q->where('status', CustomerStatus::Active->value))
            ->when($request->input('status') === 'other', fn ($q) => $q->where('status', '!=', CustomerStatus::Active->value))
            ->latest()->paginate(20)->withQueryString();
        $officers = User::query()->when($request->user()->branch_id, fn ($q, $id) => $q->where('branch_id', $id))
            ->where('is_active', true)->orderBy('first_name')->get();

        return view('customers.index', [
            'customers' => $customers,
            'metrics' => $metrics,
            'officers' => $officers,
            'regions' => Region::orderBy('name')->get(),
            ...$this->referenceOptions(),
        ]);
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
        // Documents are stored privately. A successful upload never approves KYC.
        $storedPaths = [];
        try {
            $customer = DB::transaction(function () use ($request, &$storedPaths): Customer {
                $customer = Customer::create($request->safe()->except(['kyc_documents', 'address', 'economic', 'kin']) + [
                    'branch_id' => $request->user()->branch_id,
                    'customer_number' => NumberSequence::issue('customer'),
                    'status' => CustomerStatus::PendingVerification,
                ]);
                $this->saveAdditionalDetails($customer, $request);
                foreach ($request->file('kyc_documents', []) as $index => $item) {
                    if (! $request->hasFile("kyc_documents.{$index}.file")) {
                        continue;
                    }
                    $type = DocumentType::query()->where('code', $request->input("kyc_documents.{$index}.type"))
                        ->where('is_active', true)->firstOrFail();
                    if ($type->customer_type_code !== null && $type->customer_type_code !== $customer->type) {
                        throw ValidationException::withMessages(['kyc_documents' => 'A document type is not valid for this borrower.']);
                    }
                    $file = $request->file("kyc_documents.{$index}.file");
                    $path = $file->store("customers/{$customer->id}", 'local');
                    if (! $path) {
                        throw ValidationException::withMessages(['kyc_documents' => 'Unable to store the uploaded file.']);
                    }
                    $storedPaths[] = $path;
                    $customer->documents()->create([
                        'type' => $type->code,
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'uploaded_by' => $request->user()->id,
                    ]);
                }

                return $customer;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        $communication->customerRegistered($customer);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Borrower registered. Uploaded KYC documents await verification.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Customer $customer, CustomerKycReadinessService $readiness): View
    {
        abort_if($request->user()->branch_id !== null && $customer->branch_id !== $request->user()->branch_id, 403);
        $customer->load(['branch', 'assignedOfficer', 'loanApplications.productVersion.product', 'loans.repayments', 'loans.application.productVersion.product', 'kycDecisions.reviewer', 'addresses.verifier', 'identifications.verifier', 'documents.uploader', 'economicProfiles', 'relationships', 'registeredGuarantors', 'collaterals']);

        $identificationTypes = IdentificationType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $documentTypes = DocumentType::query()->where('is_active', true)->where(fn ($query) => $query->whereNull('customer_type_code')->orWhere('customer_type_code', $customer->type))->orderBy('sort_order')->get();
        $regions = Region::query()->orderBy('name')->get(['id', 'name']);
        $kycReadiness = $readiness->evaluate($customer);
        $loans = $customer->loans;
        $portfolio = [
            'total_loans' => $loans->count(),
            'active_loans' => $loans->filter(fn ($loan) => in_array($loan->status?->value ?? (string) $loan->status, ['Active', 'In Arrears', 'Restructured'], true))->count(),
            'principal' => $loans->sum(fn ($loan) => (float) $loan->principal_amount),
            'outstanding' => $loans->sum(fn ($loan) => (float) $loan->outstanding_principal),
            'arrears' => $loans->sum(fn ($loan) => (float) $loan->arrears_amount),
            'repayments' => $loans->flatMap->repayments->filter(fn ($payment) => $payment->reversed_repayment_id === null && ! in_array(strtolower((string) $payment->status), ['reversed', 'void', 'failed'], true))->sum(fn ($payment) => (float) $payment->amount),
        ];
        $recentRepayments = $loans->flatMap->repayments->sortByDesc('paid_at')->take(15);

        return view('customers.show', compact('customer', 'identificationTypes', 'documentTypes', 'regions', 'kycReadiness', 'portfolio', 'recentRepayments'));
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
        DB::transaction(function () use ($customer, $request): void {
            $customer->update(collect($request->validated())->except(['address', 'economic', 'kin', 'kyc_documents'])->all());
            $this->saveAdditionalDetails($customer, $request);
        });

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated successfully.');
    }

    public function setStatus(Request $request, Customer $customer): RedirectResponse
    {
        abort_if($request->user()->branch_id !== null && $customer->branch_id !== $request->user()->branch_id, 403);
        $validated = $request->validate(['status' => ['required', 'in:Active,Suspended']]);
        if ($validated['status'] === 'Active' && $customer->verified_at === null) {
            return back()->withErrors(['status' => 'Approve customer KYC before activating the account.']);
        }
        $customer->update(['status' => CustomerStatus::from($validated['status'])]);

        return back()->with('success', $validated['status'] === 'Active' ? 'Customer activated.' : 'Customer deactivated.');
    }

    private function saveAdditionalDetails(Customer $customer, Request $request): void
    {
        if ($request->filled('identification_type') && $request->filled('identification_number')) {
            $customer->identifications()->updateOrCreate(
                [
                    'type' => $request->string('identification_type')->toString(),
                    'number' => $request->string('identification_number')->toString(),
                ],
                [
                    'issuing_country' => 'TZ',
                    'verified_by' => $request->user()->id,
                    'verified_at' => now(),
                ],
            );
        }

        if ($request->filled('address.region_id') && $request->filled('address.district_id') && $request->filled('address.ward_id') && $request->filled('address.street_id')) {
            $a = $request->input('address');
            $region = Region::findOrFail($a['region_id']);
            $district = District::where('region_id', $region->id)->findOrFail($a['district_id']);
            $ward = Ward::where('district_id', $district->id)->findOrFail($a['ward_id']);
            $street = Street::where('ward_id', $ward->id)->findOrFail($a['street_id']);
            $addressAttributes = [
                'type' => 'Residential',
                'region_id' => $region->id,
                'district_id' => $district->id,
                'ward_id' => $ward->id,
                'street_id' => $street->id,
                'region' => $region->name,
                'district' => $district->name,
                'ward' => $ward->name,
                'street' => $street->name,
                'description' => $a['description'] ?? null,
                'is_primary' => true,
            ];
            $primaryAddress = $customer->addresses()->where('is_primary', true)->latest('id')->first();
            $customer->addresses()->when($primaryAddress, fn ($query) => $query->whereKeyNot($primaryAddress->id))->update(['is_primary' => false]);

            if ($primaryAddress) {
                $primaryAddress->update($addressAttributes + ['verified_by' => null, 'verified_at' => null]);
            } else {
                $customer->addresses()->create($addressAttributes);
            }
        }

        $economicAttributes = collect($request->input('economic', []))
            ->only(['occupation', 'employer_or_business', 'income_source', 'monthly_income', 'monthly_expenses', 'existing_debt_payment', 'business_turnover'])
            ->all();

        if (collect($economicAttributes)->contains(fn ($value): bool => $value !== null && $value !== '')) {
            $currentProfile = $customer->economicProfiles()->whereNull('effective_to')->latest('id')->first();
            $defaults = ['monthly_income' => 0, 'monthly_expenses' => 0, 'existing_debt_payment' => 0, 'business_turnover' => 0];

            if ($currentProfile) {
                $currentProfile->update($economicAttributes);
            } else {
                $customer->economicProfiles()->create($defaults + $economicAttributes + ['effective_from' => today()]);
            }

            $customer->loanApplications()
                ->whereIn('status', [LoanApplicationStatus::Draft->value, LoanApplicationStatus::Submitted->value, LoanApplicationStatus::UnderAssessment->value])
                ->update([
                    'declared_monthly_income' => $economicAttributes['monthly_income'] ?? $currentProfile?->monthly_income ?? 0,
                    'declared_monthly_expenses' => $economicAttributes['monthly_expenses'] ?? $currentProfile?->monthly_expenses ?? 0,
                    'existing_debt_payment' => $economicAttributes['existing_debt_payment'] ?? $currentProfile?->existing_debt_payment ?? 0,
                ]);
        }

        if ($request->has('kin')) {
            $keptRelationshipIds = [];

            foreach ($request->input('kin', []) as $kin) {
                if (empty($kin['full_name'])) {
                    continue;
                }

                $relationshipId = isset($kin['id'])
                    ? $customer->relationships()->where('type', 'Next of Kin')->whereKey($kin['id'])->value('id')
                    : null;
                $attributes = collect($kin)->only(['full_name', 'phone', 'address', 'identification_number'])->all();
                $relationship = $relationshipId
                    ? $customer->relationships()->whereKey($relationshipId)->firstOrFail()
                    : $customer->relationships()->make(['type' => 'Next of Kin']);
                $relationship->fill($attributes)->save();
                $keptRelationshipIds[] = $relationship->id;
            }

            $customer->relationships()->where('type', 'Next of Kin')
                ->when($keptRelationshipIds !== [], fn ($query) => $query->whereNotIn('id', $keptRelationshipIds))
                ->delete();
        }
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
            'documentTypes' => DocumentType::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ];
    }
}

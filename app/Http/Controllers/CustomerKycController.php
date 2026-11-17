<?php

namespace App\Http\Controllers;

use App\CustomerStatus;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\District;
use App\Models\DocumentType;
use App\Models\IdentificationType;
use App\Models\Region;
use App\Models\Street;
use App\Models\Ward;
use App\Services\CustomerCommunicationService;
use App\Services\CustomerKycReadinessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerKycController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:customer.verify', only: ['verify', 'reject', 'verifyAddress', 'verifyIdentification', 'verifyDocument']),
            new Middleware('can:customer.address.manage', only: ['storeAddress']),
            new Middleware('can:customer.economic-profile.manage', only: ['storeEconomicProfile']),
            new Middleware('can:customer.relationship.manage', only: ['storeRelationship']),
            new Middleware('can:customer.document.create', only: ['storeIdentification', 'storeDocument']),
            new Middleware('can:customer.document.view', only: ['downloadDocument']),
            new Middleware('can:customer.document.delete', only: ['destroyDocument']),
        ];
    }

    public function verify(Request $request, Customer $customer, CustomerCommunicationService $communication, CustomerKycReadinessService $readiness): RedirectResponse
    {
        $this->ensureBranchScope($request, $customer);
        $request->validate(['kyc_risk_rating' => ['required', 'in:Low,Medium,High']]);

        $result = $readiness->evaluate($customer);

        if (! $result['ready']) {
            $missing = collect($result['checks'])->where('passed', false)->pluck('label')->implode(', ');

            return back()->withErrors(['kyc' => 'KYC is incomplete. Missing or unverified: '.$missing.'.']);
        }
        DB::transaction(function () use ($customer, $request): void {
            $customer->update([
                'kyc_risk_rating' => $request->string('kyc_risk_rating')->toString(),
                'status' => CustomerStatus::Active,
                'verified_at' => now(),
            ]);
            $customer->kycDecisions()->create(['decision' => 'approved', 'risk_rating' => $request->input('kyc_risk_rating'), 'decided_by' => $request->user()->id]);
        });
        $communication->send($customer, 'Customer verification completed', "Your customer profile {$customer->customer_number} is now active.", 'kyc_verified');

        return back()->with('success', 'Customer KYC verified successfully.');
    }

    public function reject(Request $request, Customer $customer): RedirectResponse
    {
        $this->ensureBranchScope($request, $customer);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);
        if ($customer->verified_at !== null) {
            throw ValidationException::withMessages(['kyc' => 'Already-approved KYC cannot be rejected using this pending-review action.']);
        }
        DB::transaction(function () use ($customer, $request, $validated): void {
            $customer->update(['status' => CustomerStatus::PendingVerification]);
            $customer->kycDecisions()->create([
                'decision' => 'rejected', 'reason' => $validated['reason'],
                'decided_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', 'KYC rejected. The borrower can provide corrected evidence for a new review.');
    }

    public function storeAddress(Request $request, Customer $customer): RedirectResponse
    {
        $this->ensureBranchScope($request, $customer);
        $validated = $request->validate(['type' => ['required', 'in:Residential,Business,Postal'], 'region_id' => ['required', 'exists:regions,id'], 'district_id' => ['required', 'exists:districts,id'], 'ward_id' => ['required', 'exists:wards,id'], 'street_id' => ['required', 'exists:streets,id'], 'description' => ['nullable', 'string', 'max:1000'], 'is_primary' => ['nullable', 'boolean']]);
        $region = Region::query()->findOrFail($validated['region_id']);
        $district = District::query()->where('region_id', $region->id)->findOrFail($validated['district_id']);
        $ward = Ward::query()->where('district_id', $district->id)->findOrFail($validated['ward_id']);
        $street = Street::query()->where('ward_id', $ward->id)->findOrFail($validated['street_id']);

        if ($request->boolean('is_primary')) {
            $customer->addresses()->update(['is_primary' => false]);
        }

        $customer->addresses()->create($validated + ['region' => $region->name, 'district' => $district->name, 'ward' => $ward->name, 'street' => $street->name, 'is_primary' => $request->boolean('is_primary')]);

        return back()->with('success', 'Customer address saved.');
    }

    public function storeIdentification(Request $request, Customer $customer): RedirectResponse
    {
        $this->ensureBranchScope($request, $customer);
        $validated = $request->validate(['type' => ['required', 'exists:identification_types,code'], 'number' => ['required', 'string', 'max:100', 'unique:customer_identifications,number'], 'issuing_country' => ['required', 'string', 'size:2'], 'issued_at' => ['nullable', 'date'], 'expires_at' => ['nullable', 'date', 'after:today']]);
        $type = IdentificationType::query()->where('code', $validated['type'])->where('is_active', true)->firstOrFail();

        if ($type->customer_type_code !== null && $type->customer_type_code !== $customer->type) {
            return back()->withInput()->withErrors(['type' => 'This identification type is not valid for the selected customer type.']);
        }

        if ($type->requires_expiry && empty($validated['expires_at'])) {
            return back()->withInput()->withErrors(['expires_at' => 'An expiry date is required for this identification type.']);
        }
        $customer->identifications()->create($validated);

        return back()->with('success', 'Identification record saved.');
    }

    public function verifyAddress(Request $request, Customer $customer, int $address): RedirectResponse
    {
        $this->ensureBranchScope($request, $customer);
        $record = $customer->addresses()->findOrFail($address);
        $record->update(['verified_by' => $request->user()->id, 'verified_at' => now()]);

        return back()->with('success', 'Address evidence verified.');
    }

    public function verifyIdentification(Request $request, Customer $customer, int $identification): RedirectResponse
    {
        $this->ensureBranchScope($request, $customer);
        $record = $customer->identifications()->findOrFail($identification);
        $record->update(['verified_by' => $request->user()->id, 'verified_at' => now()]);

        return back()->with('success', 'Identification verified.');
    }

    public function storeEconomicProfile(Request $request, Customer $customer): RedirectResponse
    {
        $this->ensureBranchScope($request, $customer);
        $validated = $request->validate(['occupation' => ['nullable', 'string', 'max:150'], 'employer_or_business' => ['nullable', 'string', 'max:255'], 'monthly_income' => ['required', 'numeric', 'min:0'], 'monthly_expenses' => ['required', 'numeric', 'min:0'], 'existing_debt_payment' => ['required', 'numeric', 'min:0'], 'business_turnover' => ['nullable', 'numeric', 'min:0'], 'income_source' => ['required', 'string', 'max:1000']]);
        $customer->economicProfiles()->whereNull('effective_to')->update(['effective_to' => today()->subDay()]);
        $customer->economicProfiles()->create($validated + ['effective_from' => today()]);

        return back()->with('success', 'Economic profile saved.');
    }

    public function storeRelationship(Request $request, Customer $customer): RedirectResponse
    {
        $this->ensureBranchScope($request, $customer);
        $validated = $request->validate(['type' => ['required', 'in:Next of Kin,Spouse,Parent,Child,Business Partner'], 'full_name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:30'], 'identification_number' => ['nullable', 'string', 'max:100'], 'address' => ['nullable', 'string', 'max:500']]);
        $customer->relationships()->create($validated);

        return back()->with('success', 'Customer relationship saved.');
    }

    public function storeDocument(Request $request, Customer $customer): RedirectResponse
    {
        $this->ensureBranchScope($request, $customer);
        $validated = $request->validate(['type' => ['required', 'exists:document_types,code'], 'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240']]);
        $documentType = DocumentType::query()->where('code', $validated['type'])->where('is_active', true)->firstOrFail();
        abort_if($documentType->customer_type_code !== null && $documentType->customer_type_code !== $customer->type, 422, 'Document type does not apply to this customer.');
        $file = $validated['document'];
        $path = $file->store("customers/{$customer->id}", 'local');
        $customer->documents()->create(['type' => $validated['type'], 'disk' => 'local', 'path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize(), 'uploaded_by' => $request->user()->id]);

        return back()->with('success', 'KYC document uploaded securely.');
    }

    public function verifyDocument(Request $request, CustomerDocument $document): RedirectResponse
    {
        $this->ensureBranchScope($request, $document->customer);
        $document->update(['verified_by' => $request->user()->id, 'verified_at' => now()]);

        return back()->with('success', 'Document verified.');
    }

    public function downloadDocument(Request $request, CustomerDocument $document): StreamedResponse
    {
        $this->ensureBranchScope($request, $document->customer);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function destroyDocument(Request $request, CustomerDocument $document): RedirectResponse
    {
        $this->ensureBranchScope($request, $document->customer);
        abort_if($document->verified_at !== null, 422, 'Verified documents cannot be deleted; upload a replacement instead.');
        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        return back()->with('success', 'Unverified document removed.');
    }

    private function ensureBranchScope(Request $request, Customer $customer): void
    {
        abort_if($request->user()->branch_id !== null && $customer->branch_id !== $request->user()->branch_id, 403);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\CustomerType;
use App\Models\DocumentType;
use App\Models\IdentificationType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReferenceDataController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:admin.configuration.update'];
    }

    public function index(): View
    {
        return view('administration.reference-data', [
            'customerTypes' => CustomerType::query()->orderBy('sort_order')->orderBy('name')->get(),
            'identificationTypes' => IdentificationType::query()->orderBy('sort_order')->orderBy('name')->get(),
            'documentTypes' => DocumentType::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function storeCustomerType(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:customer_types,code'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_legal_entity' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        CustomerType::query()->create($validated + [
            'is_legal_entity' => $request->boolean('is_legal_entity'),
            'is_active' => true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Customer type added.');
    }

    public function updateCustomerType(Request $request, CustomerType $customerType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_legal_entity' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);
        $customerType->update($validated + [
            'is_legal_entity' => $request->boolean('is_legal_entity'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Customer type updated.');
    }

    public function storeIdentificationType(Request $request): RedirectResponse
    {
        $validated = $this->validateIdentificationType($request);
        IdentificationType::query()->create($validated + ['is_active' => true]);

        return back()->with('success', 'Identification type added.');
    }

    public function updateIdentificationType(Request $request, IdentificationType $identificationType): RedirectResponse
    {
        $validated = $this->validateIdentificationType($request, $identificationType);
        $identificationType->update($validated + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Identification type updated.');
    }

    public function storeDocumentType(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:document_types,code'],
            'name' => ['required', 'string', 'max:100'],
            'customer_type_code' => ['nullable', 'exists:customer_types,code'],
            'is_required_for_kyc' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);
        DocumentType::query()->create($validated + ['is_required_for_kyc' => $request->boolean('is_required_for_kyc'), 'is_active' => true, 'sort_order' => $validated['sort_order'] ?? 0]);

        return back()->with('success', 'Document type added.');
    }

    public function updateDocumentType(Request $request, DocumentType $documentType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'customer_type_code' => ['nullable', 'exists:customer_types,code'],
            'is_required_for_kyc' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);
        $documentType->update($validated + ['is_required_for_kyc' => $request->boolean('is_required_for_kyc'), 'is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Document type updated.');
    }

    /** @return array<string, mixed> */
    private function validateIdentificationType(Request $request, ?IdentificationType $identificationType = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('identification_types', 'code')->ignore($identificationType)],
            'name' => ['required', 'string', 'max:100'],
            'customer_type_code' => ['nullable', 'exists:customer_types,code'],
            'number_pattern' => ['nullable', 'string', 'max:255'],
            'pattern_hint' => ['nullable', 'string', 'max:255'],
            'requires_expiry' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        return $validated + [
            'requires_expiry' => $request->boolean('requires_expiry'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ];
    }
}

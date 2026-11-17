@php
    $customer = $editingCustomer ?? null;
    $address = $customer?->addresses?->firstWhere('is_primary', true)
        ?? $customer?->addresses?->last();
    $economic = $customer?->economicProfiles?->whereNull('effective_to')->last();
    $kinRecords = $customer?->relationships?->where('type', 'Next of Kin') ?? collect();

    $occupationOptions = ['Small Business', 'Government', 'Private Sector', 'Farming', 'Trading', 'Professional', 'Other'];
    $employmentOptions = ['Employed', 'Business', 'Self-employed', 'Other'];
    $financialFields = [
        'income_source' => 'Income source',
        'monthly_income' => 'Monthly income (TZS)',
        'monthly_expenses' => 'Monthly expenses (TZS)',
        'existing_debt_payment' => 'Existing monthly debt (TZS)',
        'business_turnover' => 'Business turnover (TZS)',
    ];
    $moneyFields = ['monthly_income', 'monthly_expenses', 'existing_debt_payment', 'business_turnover'];
    $locationFields = [
        'region' => ['label' => 'Region', 'options' => $regions ?? collect(), 'parent' => null],
        'district' => ['label' => 'District', 'options' => $districts ?? collect(), 'parent' => 'region_id'],
        'ward' => ['label' => 'Ward', 'options' => $wards ?? collect(), 'parent' => 'district_id'],
        'street' => ['label' => 'Street', 'options' => $streets ?? collect(), 'parent' => 'ward_id'],
    ];
@endphp

<section class="customer-form-section">
    <div class="section-label mb-3">Personal &amp; membership information</div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Customer type <span class="text-danger">*</span></label>
            <select name="type" class="form-select" required>
                <option value="">Choose type</option>
                @foreach ($customerTypes as $type)
                    <option value="{{ $type->code }}" @selected(old('type', $customer?->type) === $type->code)>
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">First name <span class="text-danger">*</span></label>
            <input name="first_name" class="form-control" maxlength="100" required value="{{ old('first_name', $customer?->first_name) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Middle name</label>
            <input name="middle_name" class="form-control" maxlength="100" value="{{ old('middle_name', $customer?->middle_name) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Last name <span class="text-danger">*</span></label>
            <input name="last_name" class="form-control" maxlength="100" required value="{{ old('last_name', $customer?->last_name) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Phone <span class="text-danger">*</span></label>
            <input name="phone" type="tel" class="form-control" required value="{{ old('phone', $customer?->phone) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" value="{{ old('email', $customer?->email) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-select">
                <option value="">Select gender</option>
                @foreach (['Male', 'Female', 'Other'] as $gender)
                    <option value="{{ $gender }}" @selected(old('gender', $customer?->gender) === $gender)>{{ $gender }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Date of birth</label>
            <input name="date_of_birth" type="date" class="form-control" value="{{ old('date_of_birth', $customer?->date_of_birth?->format('Y-m-d')) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Assigned officer</label>
            <select name="assigned_officer_id" class="form-select">
                <option value="">Unassigned</option>
                @foreach ($officers as $officer)
                    <option value="{{ $officer->id }}" @selected((string) old('assigned_officer_id', $customer?->assigned_officer_id) === (string) $officer->id)>
                        {{ $officer->full_name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</section>

<section class="customer-form-section mt-4">
    <div class="section-label mb-3">Identification details</div>
    <p class="small text-muted mb-3">Identification details are collected here. KYC approval is a separate review process.</p>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">ID type</label>
            <select name="identification_type" class="form-select">
                <option value="">Select type</option>
                @foreach ($identificationTypes as $type)
                    <option value="{{ $type->code }}" @selected(old('identification_type', $customer?->identification_type) === $type->code)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">ID number</label>
            <input name="identification_number" class="form-control" value="{{ old('identification_number', $customer?->identification_number) }}">
        </div>
    </div>
</section>

<section class="customer-form-section mt-4">
    <div class="section-label mb-3">Residential location</div>
    <div class="row g-3 customer-location">
        @foreach ($locationFields as $key => $field)
            @php
                $fieldName = "address.{$key}_id";
                $selectedId = old($fieldName, $address?->{$key . '_id'});
            @endphp
            <div class="col-md-6">
                <label class="form-label">{{ $field['label'] }}</label>
                <select name="address[{{ $key }}_id]" class="form-select" data-location="{{ $key }}">
                    <option value="">Select {{ strtolower($field['label']) }}</option>
                    @foreach ($field['options'] as $option)
                        <option
                            value="{{ $option->id }}"
                            @if ($field['parent']) data-parent="{{ $option->{$field['parent']} }}" @endif
                            @selected((string) $selectedId === (string) $option->id)
                        >{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
        <div class="col-12">
            <label class="form-label">Address description</label>
            <input name="address[description]" class="form-control" value="{{ old('address.description', $address?->description) }}" placeholder="House number, landmark or additional directions">
        </div>
    </div>
</section>

<section class="customer-form-section mt-4">
    <div class="section-label mb-3">Economic profile</div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Occupation</label>
            @php $selectedOccupation = old('economic.occupation', $economic?->occupation); @endphp
            <select name="economic[occupation]" class="form-select" data-economic-choice>
                <option value="">Select occupation</option>
                @foreach ($occupationOptions as $occupation)
                    <option value="{{ $occupation }}" @selected($selectedOccupation === $occupation)>{{ $occupation }}</option>
                @endforeach
                @if ($selectedOccupation && !in_array($selectedOccupation, $occupationOptions, true))
                    <option value="{{ $selectedOccupation }}" selected>{{ $selectedOccupation }}</option>
                @endif
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Employment / business type</label>
            @php $selectedEmployment = old('economic.employer_or_business', $economic?->employer_or_business); @endphp
            <select name="economic[employer_or_business]" class="form-select" data-economic-choice>
                <option value="">Select employment type</option>
                @foreach ($employmentOptions as $employment)
                    <option value="{{ $employment }}" @selected($selectedEmployment === $employment)>{{ $employment }}</option>
                @endforeach
                @if ($selectedEmployment && !in_array($selectedEmployment, $employmentOptions, true))
                    <option value="{{ $selectedEmployment }}" selected>{{ $selectedEmployment }}</option>
                @endif
            </select>
        </div>
        @foreach ($financialFields as $fieldName => $label)
            @php $isMoney = in_array($fieldName, $moneyFields, true); @endphp
            <div class="col-md-6">
                <label class="form-label">{{ $label }}</label>
                <input
                    name="economic[{{ $fieldName }}]"
                    class="form-control"
                    type="{{ $isMoney ? 'number' : 'text' }}"
                    @if ($isMoney) min="0" step="0.01" @endif
                    value="{{ old('economic.' . $fieldName, $economic?->{$fieldName}) }}"
                >
            </div>
        @endforeach
    </div>
</section>

<section class="customer-form-section mt-4">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <div class="section-label mb-0">Next of kin</div>
        <button type="button" class="btn btn-sm btn-outline-success" data-add-kin>Add contact</button>
    </div>
    <div class="customer-kin-list">
        @foreach ($kinRecords->isNotEmpty() ? $kinRecords : [null] as $index => $kin)
            <div class="customer-kin-row border rounded-4 p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <strong class="small">Next of kin {{ $loop->iteration }}</strong>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-remove-kin aria-label="Remove next of kin">Remove</button>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full name</label>
                        <input name="kin[{{ $index }}][full_name]" class="form-control" value="{{ old("kin.{$index}.full_name", $kin?->full_name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input name="kin[{{ $index }}][phone]" type="tel" class="form-control" value="{{ old("kin.{$index}.phone", $kin?->phone) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Address</label>
                        <input name="kin[{{ $index }}][address]" class="form-control" value="{{ old("kin.{$index}.address", $kin?->address) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Identification number</label>
                        <input name="kin[{{ $index }}][identification_number]" class="form-control" value="{{ old("kin.{$index}.identification_number", $kin?->identification_number) }}">
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

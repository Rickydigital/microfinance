<div class="guarantor-form-section">
    <div class="section-label mb-3">Borrower relationship</div>
    <label class="form-label">Borrower being guaranteed <span class="text-danger">*</span></label>
    <select class="form-select enhanced-select guarantor-customer-select" name="principal_customer_id" data-placeholder="Search customer number or borrower name" required>
        <option value=""></option>
        @foreach ($customers as $customer)
            <option value="{{ $customer->id }}" @selected(old('principal_customer_id', $guarantor?->principal_customer_id) == $customer->id)>{{ $customer->customer_number }} — {{ $customer->first_name }} {{ $customer->last_name }}</option>
        @endforeach
    </select>
    <div class="form-text">This is the borrower whose loan the guarantor will support.</div>
</div>

<div class="guarantor-form-section mt-4">
    <div class="section-label mb-3">Guarantor identity</div>
    @php $occupations = ['Business owner', 'Civil servant', 'Farmer', 'Teacher', 'Health worker', 'Employed - private sector', 'Self-employed', 'Retired', 'Other']; @endphp
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">First name <span class="text-danger">*</span></label><input class="form-control" name="first_name" value="{{ old('first_name', $guarantor?->first_name) }}" required></div>
        <div class="col-md-4"><label class="form-label">Middle name</label><input class="form-control" name="middle_name" value="{{ old('middle_name', $guarantor?->middle_name) }}"></div>
        <div class="col-md-4"><label class="form-label">Last name <span class="text-danger">*</span></label><input class="form-control" name="last_name" value="{{ old('last_name', $guarantor?->last_name) }}" required></div>
        <div class="col-md-6"><label class="form-label">Phone <span class="text-danger">*</span></label><input class="form-control" name="phone" value="{{ old('phone', $guarantor?->phone) }}" required></div>
        <div class="col-md-6"><label class="form-label">Occupation <span class="text-danger">*</span></label><select class="form-select" name="occupation" data-placeholder="Select occupation" required><option value=""></option>@if ($guarantor?->occupation && ! in_array($guarantor->occupation, $occupations, true))<option value="{{ $guarantor->occupation }}" selected>{{ $guarantor->occupation }}</option>@endif @foreach ($occupations as $occupation)<option value="{{ $occupation }}" @selected(old('occupation', $guarantor?->occupation) === $occupation)>{{ $occupation }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Identification type <span class="text-danger">*</span></label><select class="form-select" name="identification_type" data-placeholder="Select ID type" required><option value=""></option>@foreach ($identificationTypes as $type)<option value="{{ $type->code }}" @selected(old('identification_type', $guarantor?->identification_type) === $type->code)>{{ $type->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Identification number <span class="text-danger">*</span></label><input class="form-control" name="identification_number" value="{{ old('identification_number', $guarantor?->identification_number) }}" required></div>
    </div>
</div>

<div class="guarantor-form-section mt-4">
    <div class="section-label mb-3">Financial capacity</div>
    <div class="row g-3"><div class="col-md-6"><label class="form-label">Monthly income (TZS) <span class="text-danger">*</span></label><input class="form-control register-money-input" type="text" inputmode="decimal" name="monthly_income" value="{{ old('monthly_income', $guarantor?->monthly_income) }}" placeholder="0" required><div class="form-text">Used when assessing whether the guarantee is financially reasonable.</div></div></div>
</div>

@include('partials.alerts')

<div class="row g-3">
    <div class="col-md-4"><label class="form-label" for="type">Customer type</label><select class="form-select enhanced-select" id="type" name="type" data-customer-type data-placeholder="Select customer type" required>
            <option value=""></option>@foreach ($customerTypes as $customerType)<option value="{{ $customerType->code }}" @selected(old('type', $customer?->type) === $customerType->code)>{{
                $customerType->name }}</option>@endforeach
        </select></div>
    <div class="col-md-4"><label class="form-label" for="first_name">First or registered name</label><input class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $customer?->first_name) }}" required></div>
    <div class="col-md-4"><label class="form-label" for="middle_name">Middle or trading name</label><input class="form-control" id="middle_name" name="middle_name" value="{{ old('middle_name', $customer?->middle_name) }}"></div>
    <div class="col-md-4"><label class="form-label" for="last_name">Surname or entity suffix</label><input class="form-control" id="last_name" name="last_name" value="{{ old('last_name', $customer?->last_name) }}" required></div>
    <div class="col-md-4"><label class="form-label" for="gender">Gender</label><select class="form-select enhanced-select" id="gender" name="gender" data-placeholder="Select gender">
            <option value=""></option>@foreach (['Male', 'Female', 'Other'] as $value)<option @selected(old('gender', $customer?->gender) === $value)>{{ $value }}</option>@endforeach
        </select></div>
    <div class="col-md-4"><label class="form-label" for="date_of_birth">Date of birth or registration</label><input class="form-control" id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', $customer?->date_of_birth?->format('Y-m-d')) }}"></div>
    <div class="col-md-4"><label class="form-label" for="phone">Phone</label><input class="form-control" id="phone" name="phone" value="{{ old('phone', $customer?->phone) }}" required></div>
    <div class="col-md-4"><label class="form-label" for="email">Email</label><input class="form-control" id="email" type="email" name="email" value="{{ old('email', $customer?->email) }}"></div>
    <div class="col-md-4"><label class="form-label" for="identification_type">Identification type</label><select class="form-select enhanced-select" id="identification_type" name="identification_type" data-identification-type data-placeholder="Select identification type">
            <option value=""></option>@foreach ($identificationTypes as $identificationType)<option value="{{ $identificationType->code }}" data-customer-type="{{ $identificationType->customer_type_code }}" @selected(old('identification_type', $customer?->identification_type) === $identificationType->code)>{{ $identificationType->name }}</option>
            @endforeach
        </select></div>
    <div class="col-md-4"><label class="form-label" for="identification_number">Identification number</label><input class="form-control" id="identification_number" name="identification_number" value="{{ old('identification_number', $customer?->identification_number) }}"></div>
    <div class="col-md-4"><label class="form-label" for="kyc_risk_rating">KYC risk</label><select class="form-select enhanced-select" id="kyc_risk_rating" name="kyc_risk_rating" data-placeholder="Select risk">
            <option value=""></option>@foreach (['Low', 'Medium', 'High'] as $value)<option @selected(old('kyc_risk_rating', $customer?->kyc_risk_rating) === $value)>{{ $value }}</option>
            @endforeach
        </select></div>
    <div class="col-md-4"><label class="form-label" for="assigned_officer_id">Assigned officer</label><select class="form-select enhanced-select" id="assigned_officer_id" name="assigned_officer_id" data-placeholder="Select officer">
            <option value=""></option>@foreach ($officers as $officer)<option value="{{ $officer->id }}" @selected((string) old('assigned_officer_id', $customer?->assigned_officer_id) === (string)
                $officer->id)>{{ $officer->full_name }}</option>@endforeach
        </select></div>
</div>

<div class="mt-4 d-flex gap-2"><button class="btn btn-primary">Save customer</button><a class="btn btn-light" href="{{ route('customers.index') }}">Cancel</a></div>

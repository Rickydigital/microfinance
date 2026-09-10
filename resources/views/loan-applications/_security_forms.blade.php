@php($securityCanBeAdded = in_array($loanApplication->status, [\App\LoanApplicationStatus::Draft, \App\LoanApplicationStatus::Submitted, \App\LoanApplicationStatus::UnderAssessment, \App\LoanApplicationStatus::Recommended, \App\LoanApplicationStatus::PendingApproval], true))

@if ($securityCanBeAdded)
    <div class="mt-4" id="application-security-entry"><h2 class="h4">Application security entry</h2><p class="text-muted">Guarantors and collateral can only be created here, for this specific application. The central registers remain read-only.</p></div>
    @can('guarantor.manage')
        <form class="card card-body border-0 shadow-sm mb-4" method="POST" action="{{ route('loan-applications.guarantors.store', $loanApplication) }}">
            @csrf
            <h3 class="h5">Add guarantor</h3>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">First name</label><input class="form-control" name="first_name" value="{{ old('first_name') }}" required></div>
                <div class="col-md-4"><label class="form-label">Middle name</label><input class="form-control" name="middle_name" value="{{ old('middle_name') }}"></div>
                <div class="col-md-4"><label class="form-label">Last name</label><input class="form-control" name="last_name" value="{{ old('last_name') }}" required></div>
                <div class="col-md-4"><label class="form-label">Phone</label><input class="form-control" name="phone" value="{{ old('phone') }}" required></div>
                <div class="col-md-4"><label class="form-label">Identification type</label><select class="form-select" name="identification_type" required><option value="">Select type</option>@foreach ($identificationTypes as $type)<option value="{{ $type->code }}" @selected(old('identification_type') === $type->code)>{{ $type->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Identification number</label><input class="form-control" name="identification_number" value="{{ old('identification_number') }}" required></div>
                <div class="col-md-4"><label class="form-label">Occupation</label><input class="form-control" name="occupation" value="{{ old('occupation') }}"></div>
                <div class="col-md-4"><label class="form-label">Monthly income</label><input class="form-control" type="number" min="0.01" step="0.01" name="monthly_income" value="{{ old('monthly_income') }}" required></div>
                <div class="col-md-4"><label class="form-label">Guaranteed amount</label><input class="form-control" type="number" min="0.01" max="{{ $loanApplication->requested_amount }}" step="0.01" name="guaranteed_amount" value="{{ old('guaranteed_amount') }}" required></div>
                <div class="col-12 form-check ms-2"><input class="form-check-input" type="checkbox" id="consent_received" name="consent_received" value="1" required><label class="form-check-label" for="consent_received">Signed guarantor consent received</label></div>
            </div>
            <button class="btn btn-primary align-self-start mt-3">Add for verification</button>
        </form>
    @endcan
    @can('collateral.manage')
        <form class="card card-body border-0 shadow-sm mb-4" method="POST" action="{{ route('loan-applications.collaterals.store', $loanApplication) }}">
            @csrf
            <h3 class="h5">Add collateral</h3>
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Type</label><input class="form-control" name="type" value="{{ old('type') }}" required></div>
                <div class="col-md-9"><label class="form-label">Description</label><input class="form-control" name="description" value="{{ old('description') }}" required></div>
                <div class="col-md-4"><label class="form-label">Ownership reference</label><input class="form-control" name="ownership_reference" value="{{ old('ownership_reference') }}" required></div>
                <div class="col-md-4"><label class="form-label">Independent valuer</label><input class="form-control" name="valuer" value="{{ old('valuer') }}" required></div>
                <div class="col-md-4"><label class="form-label">Valuation date</label><input class="form-control" type="date" name="valuation_date" value="{{ old('valuation_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required></div>
                <div class="col-md-4"><label class="form-label">Market value</label><input class="form-control" type="number" min="0.01" step="0.01" name="market_value" value="{{ old('market_value') }}" required></div>
                <div class="col-md-4"><label class="form-label">Forced-sale value</label><input class="form-control" type="number" min="0.01" step="0.01" name="forced_sale_value" value="{{ old('forced_sale_value') }}" required></div>
                <div class="col-md-4"><label class="form-label">Pledged value</label><input class="form-control" type="number" min="0.01" step="0.01" name="pledged_value" value="{{ old('pledged_value') }}" required></div>
            </div>
            <button class="btn btn-primary align-self-start mt-3">Add for verification</button>
        </form>
    @endcan
@endif

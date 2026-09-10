@extends('layouts.app')

@section('title', $loanProduct->name)

@section('content')
    @include('partials.alerts')

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 mb-0">{{ $loanProduct->name }}</h1>
                <span class="badge {{ $loanProduct->is_active ? 'bg-success' : 'bg-secondary' }}">
                    {{ $loanProduct->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <p class="text-muted mt-2 mb-0">{{ $loanProduct->code }} · {{ $loanProduct->description ?: 'No description provided.' }}</p>
        </div>
        <div class="d-flex gap-2">
            @can('loan-product.update')
                <a class="btn btn-outline-primary" href="{{ route('loan-products.edit', $loanProduct) }}">Edit product</a>
            @endcan
            <a class="btn btn-outline-secondary" href="{{ route('loan-products.index') }}">Back</a>
        </div>
    </div>

    <div class="alert alert-info">
        <strong>Application visibility rule:</strong> this product must be active, and a version must have an effective-from date on or before today. Its effective-to date must be empty or today/later.
    </div>

    @can('loan-product.version.create')
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div><h2 class="h5 mb-1">Create a new product version</h2><small class="text-muted">Existing applications and loans retain their original version.</small></div>
                <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#new-product-version">Configure version</button>
            </div>
            <div class="collapse" id="new-product-version">
                <form class="card-body" method="POST" action="{{ route('loan-products.versions.store', $loanProduct) }}">
                    @csrf
                    <h3 class="h6 text-primary">Loan limits and term</h3>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3"><label class="form-label">Minimum amount</label><input class="form-control" type="number" min="0.01" step="0.01" name="minimum_amount" required></div>
                        <div class="col-md-3"><label class="form-label">Maximum amount</label><input class="form-control" type="number" min="0.01" step="0.01" name="maximum_amount" required></div>
                        <div class="col-md-2"><label class="form-label">Minimum term</label><input class="form-control" type="number" min="1" name="minimum_term" required></div>
                        <div class="col-md-2"><label class="form-label">Maximum term</label><input class="form-control" type="number" min="1" name="maximum_term" required></div>
                        <div class="col-md-2"><label class="form-label">Term unit</label><select class="form-select" name="term_unit" required><option>Days</option><option>Weeks</option><option selected>Months</option></select></div>
                    </div>

                    <h3 class="h6 text-primary">Interest and repayment</h3>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3"><label class="form-label">Interest method</label><select class="form-select" name="interest_method" required><option>Reducing Balance</option><option>Flat</option></select></div>
                        <div class="col-md-3"><label class="form-label">Interest rate (%)</label><input class="form-control" type="number" min="0" max="100" step="0.0001" name="interest_rate_percent" required><small class="text-muted">Enter 24 for 24%.</small></div>
                        <div class="col-md-3"><label class="form-label">Rate basis</label><select class="form-select" name="rate_basis" required><option value="Annual">Annual rate</option><option value="Whole Term">Whole-term rate</option></select></div>
                        <div class="col-md-3"><label class="form-label">Repayment frequency</label><select class="form-select" name="repayment_frequency" required><option>Daily</option><option>Weekly</option><option>Biweekly</option><option selected>Monthly</option></select></div>
                        <div class="col-md-3"><label class="form-label">Maximum installment ratio (%)</label><input class="form-control" type="number" min="0.01" max="100" step="0.01" name="maximum_installment_ratio_percent" value="50" required></div>
                        <div class="col-md-3"><label class="form-label">Principal grace periods</label><input class="form-control" type="number" min="0" name="principal_grace_period" value="0" required></div>
                        <div class="col-md-3"><label class="form-label">Interest grace periods</label><input class="form-control" type="number" min="0" name="interest_grace_period" value="0" required></div>
                    </div>

                    <h3 class="h6 text-primary">Security and activation</h3>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4"><label class="form-label">Assurance (%)</label><input class="form-control" type="number" name="assurance_percent" min="0" max="100" step="0.0001" value="{{ old('assurance_percent', 3) }}" required><small>Paid separately before disbursement. Non-refundable. Use 0 to disable.</small></div>
                        <div class="col-md-4"><label class="form-label">Processing fee (fixed amount)</label><input class="form-control" type="number" name="processing_fee" min="0" step="0.01" value="{{ old('processing_fee', 0) }}" required><small>Optional: 0 means no processing fee. Deducted from principal at payout.</small></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label">Required guarantors</label><input class="form-control" type="number" min="0" name="required_guarantors" value="0" required></div>
                        <div class="col-md-3"><label class="form-label">Effective from</label><input class="form-control" type="date" name="effective_from" value="{{ today()->toDateString() }}" required></div>
                        <div class="col-md-3 form-check align-self-end mb-2"><input class="form-check-input" id="requires_collateral" type="checkbox" name="requires_collateral" value="1"><label class="form-check-label" for="requires_collateral">Collateral required</label></div>
                    </div>
                    <button class="btn btn-primary mt-4">Create effective version</button>
                </form>
            </div>
        </div>
    @endcan

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h2 class="h4 mb-1">Configured versions</h2><p class="text-muted mb-0">Open a version to manage fees, penalties, and accounting.</p></div>
        <span class="badge bg-light text-dark">{{ $loanProduct->versions->count() }} version(s)</span>
    </div>

    @forelse ($loanProduct->versions->sortByDesc('version') as $version)
        @php($isCurrentlyEffective = $loanProduct->is_active && $version->effective_from->lte(today()) && ($version->effective_to === null || $version->effective_to->gte(today())))
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h3 class="h5 mb-1">Version {{ $version->version }}</h3>
                        <span class="badge {{ $isCurrentlyEffective ? 'bg-success' : 'bg-secondary' }}">{{ $isCurrentlyEffective ? 'Visible in applications' : 'Not currently visible' }}</span>
                        <small class="text-muted ms-2">{{ $version->effective_from->format('d M Y') }} – {{ $version->effective_to?->format('d M Y') ?? 'Open ended' }}</small>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" type="button" data-toggle="collapse" data-target="#version-{{ $version->id }}">Open configuration</button>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-3"><small class="text-muted d-block">Amount range</small><strong>{{ number_format((float) $version->minimum_amount, 2) }} – {{ number_format((float) $version->maximum_amount, 2) }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">Term range</small><strong>{{ $version->minimum_term }} – {{ $version->maximum_term }} {{ $version->term_unit }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">Interest</small><strong>{{ number_format((float) $version->interest_rate * 100, 2) }}% {{ $version->rate_basis }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">Method/frequency</small><strong>{{ $version->interest_method }} · {{ $version->repayment_frequency }}</strong></div>
                </div>
            </div>

            <div class="collapse" id="version-{{ $version->id }}">
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <h4 class="h6">Fees</h4>
                            <p>Assurance: <strong>{{ number_format((float) $version->assurance_percent, 2) }}%</strong>, payable separately before disbursement.</p>
                            <p class="small text-muted">Disbursement fees reduce the cash received, while the full principal remains repayable. For example: 50,000 principal minus 10,000 processing fee = 40,000 received.</p>
                            @forelse ($version->fees as $fee)
                                <div class="border rounded p-2 mb-2 d-flex justify-content-between gap-2"><span><strong>{{ $fee->name }}</strong><br><small>{{ $fee->calculation_method === 'Percentage' ? number_format((float) $fee->value * 100, 2).'%' : number_format((float) $fee->value, 2) }} · {{ $fee->collection_point }} · {{ $fee->is_financed ? 'Financed' : 'Not financed' }}</small></span>@can('loan-product.fee.manage')<form method="POST" action="{{ route('product-fees.destroy', $fee) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger">Remove</button></form>@endcan</div>
                            @empty <p class="text-muted">No fees configured.</p> @endforelse
                            @can('loan-product.fee.manage')
                                <form class="border-top pt-3 mt-3" method="POST" action="{{ route('loan-product-versions.fees.store', $version) }}">@csrf<div class="row g-2"><div class="col-12"><input class="form-control" name="name" placeholder="Fee name" required></div><div class="col-6"><select class="form-select" name="calculation_method"><option>Fixed</option><option>Percentage</option></select></div><div class="col-6"><input class="form-control" type="number" min="0.000001" step="0.000001" name="value" placeholder="Amount or 0.02" required></div><div class="col-7"><select class="form-select" name="collection_point"><option>Disbursement</option><option>Installment</option><option>Upfront</option></select></div><div class="col-5 form-check align-self-center"><input class="form-check-input" id="financed-{{ $version->id }}" type="checkbox" name="is_financed" value="1"><label class="form-check-label" for="financed-{{ $version->id }}">Financed</label></div><div class="col-12"><button class="btn btn-sm btn-primary">Add fee</button></div></div></form>
                            @endcan
                        </div>

                        <div class="col-lg-4">
                            <h4 class="h6">Penalty rules</h4>
                            @forelse ($version->penaltyRules as $penalty)
                                <div class="border rounded p-2 mb-2 d-flex justify-content-between gap-2"><span><strong>{{ $penalty->name }}</strong><br><small>{{ $penalty->calculation_method === 'Percentage' ? number_format((float) $penalty->value * 100, 2).'%' : number_format((float) $penalty->value, 2) }} of {{ $penalty->calculation_basis }} · {{ $penalty->frequency }} · {{ $penalty->grace_days }} grace day(s)</small></span>@can('loan-product.penalty.manage')<form method="POST" action="{{ route('product-penalty-rules.destroy', $penalty) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger">Remove</button></form>@endcan</div>
                            @empty <p class="text-muted">No automatic penalty configured.</p> @endforelse
                            @can('loan-product.fee.manage')
                                <form class="border-top pt-3 mt-3" method="POST" action="{{ route('loan-product-versions.penalties.store', $version) }}">@csrf<div class="row g-2"><div class="col-12"><input class="form-control" name="name" placeholder="Penalty name" required></div><div class="col-6"><select class="form-select" name="calculation_method"><option>Fixed</option><option>Percentage</option></select></div><div class="col-6"><input class="form-control" type="number" min="0.000001" step="0.000001" name="value" placeholder="Amount or 0.01" required></div><div class="col-6"><select class="form-select" name="calculation_basis"><option>Overdue Principal</option><option>Overdue Installment</option><option>Total Arrears</option></select></div><div class="col-6"><select class="form-select" name="frequency"><option>One-time</option><option>Daily</option></select></div><div class="col-6"><input class="form-control" type="number" min="0" name="grace_days" value="0" placeholder="Grace days" required></div><div class="col-6"><input class="form-control" type="number" min="0.01" step="0.01" name="maximum_amount" placeholder="Maximum amount"></div><div class="col-12"><button class="btn btn-sm btn-primary">Add penalty rule</button></div></div></form>
                            @endcan
                        </div>

                        <div class="col-lg-4">
                            <h4 class="h6">Accounting mappings</h4>
                            <p class="small text-muted">These accounts control disbursement, repayment, fee, interest, and penalty journals.</p>
                            @can('loan-product.accounting.manage')
                                <form method="POST" action="{{ route('loan-product-versions.account-mappings.update', $version) }}">@csrf @method('PUT')
                                    @foreach (['cash_account' => 'Cash', 'bank_account' => 'Bank', 'mobile_money_account' => 'Mobile money', 'loan_receivable' => 'Loan receivable', 'interest_income' => 'Interest income', 'fee_income' => 'Fee income', 'penalty_income' => 'Penalty income'] as $purpose => $label)
                                        <div class="mb-2"><label class="form-label small mb-1">{{ $label }}</label><select class="form-select form-select-sm" name="mappings[{{ $purpose }}]" required><option value="">Select account</option>@foreach ($accounts as $account)<option value="{{ $account->id }}" @selected($version->accountMappings->firstWhere('purpose', $purpose)?->chart_of_account_id === $account->id)>{{ $account->code }} — {{ $account->name }}</option>@endforeach</select></div>
                                    @endforeach
                                    <button class="btn btn-sm btn-primary mt-2">Save mappings</button>
                                </form>
                            @else
                                @foreach ($version->accountMappings as $mapping)<div class="small border-bottom py-1">{{ str($mapping->purpose)->replace('_', ' ')->title() }}: {{ $mapping->account->code }} — {{ $mapping->account->name }}</div>@endforeach
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-warning">This product has no configured version and therefore cannot appear in loan applications.</div>
    @endforelse
    @can('loan-product.fee.manage')
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white"><h2 class="h5 mb-1">Penalty rule management</h2><small class="text-muted">Unused rules can be edited or permanently removed. Rules already applied to loans are retained for audit history.</small></div>
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Version</th><th>Rule</th><th>Calculation</th><th>Usage</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($loanProduct->versions->sortByDesc('version') as $version)
                        @foreach ($version->penaltyRules as $penalty)
                            @php($chargeCount = $penalty->charges()->count())
                            <tr>
                                <td>Version {{ $version->version }}</td>
                                <td>{{ $penalty->name }}</td>
                                <td>{{ $penalty->calculation_method === 'Percentage' ? number_format((float) $penalty->value * 100, 2).'%' : number_format((float) $penalty->value, 2) }} · {{ $penalty->calculation_basis }}</td>
                                <td>{{ $chargeCount }} charge(s)</td>
                                <td>{{ $penalty->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end">
                                    @if ($chargeCount === 0)
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('product-penalty-rules.edit', $penalty) }}">Edit</a>
                                        <form class="d-inline" method="POST" action="{{ route('product-penalty-rules.destroy', $penalty) }}" onsubmit="return confirm('Remove this unused penalty rule permanently?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
                                    @else
                                        <span class="small text-muted me-2">Calculation locked</span>
                                    @endif
                                    <form class="d-inline" method="POST" action="{{ route('product-penalty-rules.status', $penalty) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-secondary">{{ $penalty->is_active ? 'Deactivate' : 'Activate' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                    @endforelse
                </tbody>
            </table></div>
        </div>
    @endcan
@endsection

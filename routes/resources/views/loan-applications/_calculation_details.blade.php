@php($calculationQuotes = collect(['Requested calculation' => $quote])->when($approvedQuote, fn ($quotes) => $quotes->put('Final approved calculation', $approvedQuote)))
<div class="card card-body border-0 shadow-sm mt-4" id="interest-calculation-details">
    <h2 class="h4">Interest and repayment calculation</h2>
    <p class="text-muted">The requested calculation supports assessment. Once an amount is approved, the approved calculation becomes the contractual basis used at disbursement.</p>
    @foreach ($calculationQuotes as $calculationLabel => $calculation)
        <div class="border rounded p-3 mb-4">
            <h3 class="h5">{{ $calculationLabel }}</h3>
            <div class="row g-3 mb-3">
                <div class="col-md-2"><small class="text-muted d-block">Principal</small><strong>{{ number_format($calculation['principal'], 2) }}</strong></div>
                <div class="col-md-2"><small class="text-muted d-block">Configured rate</small><strong>{{ number_format($calculation['annual_interest_rate'] * 100, 2) }}% {{ $calculation['rate_basis'] }}</strong></div>
                <div class="col-md-2"><small class="text-muted d-block">Periodic rate</small><strong>{{ number_format($calculation['periodic_rate'] * 100, 6) }}%</strong></div>
                <div class="col-md-2"><small class="text-muted d-block">Total interest</small><strong>{{ number_format($calculation['total_interest'], 2) }}</strong></div>
                <div class="col-md-2"><small class="text-muted d-block">Total fees</small><strong>{{ number_format($calculation['total_fees'], 2) }}</strong></div>
                <div class="col-md-2"><small class="text-muted d-block">Amount customer receives</small><strong>{{ number_format($calculation['estimated_net_disbursement'], 2) }}</strong></div>
            </div>
            <p><strong>Method:</strong> {{ $calculation['interest_method'] }}. <strong>Formula:</strong> {{ $calculation['formula'] }}</p>
            <div class="table-responsive"><table class="table table-sm align-middle">
                <thead><tr><th>#</th><th>Opening principal</th><th>Interest calculation</th><th>Principal</th><th>Interest</th><th>Fees</th><th>Total due</th><th>Closing principal</th></tr></thead>
                <tbody>@foreach ($calculation['projected_schedule'] as $row)<tr><td>{{ $row['number'] }}</td><td>{{ number_format($row['opening_principal'], 2) }}</td><td class="text-nowrap">{{ $row['interest_calculation'] }}</td><td>{{ number_format($row['principal'], 2) }}</td><td>{{ number_format($row['interest'], 2) }}</td><td>{{ number_format($row['fees'], 2) }}</td><td><strong>{{ number_format($row['installment'], 2) }}</strong></td><td>{{ number_format($row['closing_principal'], 2) }}</td></tr>@endforeach</tbody>
                <tfoot><tr><th colspan="3">Totals</th><th>{{ number_format(collect($calculation['projected_schedule'])->sum('principal'), 2) }}</th><th>{{ number_format(collect($calculation['projected_schedule'])->sum('interest'), 2) }}</th><th>{{ number_format(collect($calculation['projected_schedule'])->sum('fees'), 2) }}</th><th>{{ number_format(collect($calculation['projected_schedule'])->sum('installment'), 2) }}</th><th></th></tr></tfoot>
            </table></div>
        </div>
    @endforeach
</div>

@can('loan.checklist.manage')
    <form class="card card-body border-0 shadow-sm mt-4" method="POST" action="{{ route('loan-applications.conditions.store', $loanApplication) }}">
        @csrf
        <h2 class="h5">Add a condition to this loan application</h2>
        <p class="text-muted">Required conditions block disbursement until an authorized employee marks them complete.</p>
        <div class="row g-3"><div class="col-md-5"><label class="form-label">Condition</label><input class="form-control" name="label" required></div><div class="col-md-5"><label class="form-label">Evidence or instructions</label><input class="form-control" name="notes"></div><div class="col-md-2 form-check align-self-end mb-2"><input class="form-check-input" id="manual_condition_required" type="checkbox" name="is_required" value="1" checked><label class="form-check-label" for="manual_condition_required">Required</label></div></div>
        <button class="btn btn-primary align-self-start mt-3">Add condition</button>
    </form>
@endcan

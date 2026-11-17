@extends('layouts.app')
@section('title', 'Receive payment')
@section('content')
    @include('partials.alerts')
    <div class="mb-4"><h1 class="h3 mb-1">Receive loan payment</h1><p class="text-muted">Only loans with an outstanding principal, interest, fee, or penalty balance are shown.</p></div>
    <form class="card card-body border-0 shadow-sm mb-4" method="GET"><div class="row g-2"><div class="col-md-10"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search loan number, customer number, name or phone"></div><div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Search loans</button></div></div></form>
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>Loan/customer</th><th>Principal balance</th><th>Interest due</th><th>Fees</th><th>Penalties</th><th>Total payable</th><th></th></tr></thead>
        <tbody>@forelse ($loans as $loan)
            @php($principal = $loan->installments->sum(fn ($item) => max(0, (float) $item->principal_due - (float) $item->principal_paid)))
            @php($interest = $loan->installments->sum(fn ($item) => max(0, (float) $item->interest_due - (float) $item->interest_paid)))
            @php($fees = $loan->installments->sum(fn ($item) => max(0, (float) $item->fees_due - (float) $item->fees_paid)))
            @php($penalties = $loan->installments->sum(fn ($item) => max(0, (float) $item->penalties_due - (float) $item->penalties_paid)))
            @php($totalPayable = $principal + $interest + $fees + $penalties)
            <tr><td><strong>{{ $loan->loan_number }}</strong><br><small>{{ $loan->customer->first_name }} {{ $loan->customer->last_name }} · {{ $loan->customer->phone }}</small></td><td>{{ number_format($principal, 2) }}</td><td>{{ number_format($interest, 2) }}</td><td>{{ number_format($fees, 2) }}</td><td>{{ number_format($penalties, 2) }}</td><td><strong>{{ number_format($totalPayable, 2) }}</strong></td><td><button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#pay-{{ $loan->id }}">Pay</button></td></tr>
            <tr class="collapse" id="pay-{{ $loan->id }}"><td colspan="7"><form class="p-3 bg-light" method="POST" action="{{ route('repayments.store') }}">@csrf<input type="hidden" name="loan_id" value="{{ $loan->id }}"><div class="row g-3 align-items-end"><div class="col-md-3"><label class="form-label">Payment amount</label><input class="form-control" type="number" min="0.01" max="{{ $totalPayable }}" step="0.01" name="amount" required></div><div class="col-md-2"><label class="form-label">Method</label><select class="form-select" name="payment_method" required>@foreach (['Cash','Bank','Mobile Money'] as $method)<option>{{ $method }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">External reference</label><input class="form-control" name="external_reference"></div><div class="col-md-3"><label class="form-label">Paid at</label><input class="form-control" type="datetime-local" name="paid_at" value="{{ now()->format('Y-m-d\TH:i') }}" required></div><div class="col-md-1"><button class="btn btn-success">Post</button></div></div><p class="small text-muted mt-2 mb-0">Allocation order: penalties, fees, interest, then principal. The receipt shows the exact allocation.</p></form></td></tr>
        @empty<tr><td colspan="7" class="text-center py-5 text-muted">No unpaid loans match this search. Settled loans are intentionally hidden.</td></tr>@endforelse</tbody>
    </table></div><div class="card-body">{{ $loans->links() }}</div></div>
@endsection

@extends('layouts.app')
@section('title', 'Repayments')
@section('content')
    @include('partials.alerts')
    @can('repayment.approve')<a class="btn btn-outline-primary mb-3" href="{{ route('payment-submissions.index') }}">Customer payment approvals</a>@endcan
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h3 mb-1">Repayments</h1><p class="text-muted mb-0">Posted customer loan receipts</p></div>
        <a class="btn btn-primary" href="{{ route('repayments.create') }}">Receive payment</a>
    </div>
    <form class="card card-body border-0 shadow-sm mb-4" method="GET"><div class="row g-2"><div class="col-md-10"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Receipt, external reference, loan, customer or phone"></div><div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Search repayments</button></div></div></form>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr><th>Receipt</th><th>Customer</th><th>Loan</th><th>Amount</th><th>Method</th><th>Received</th><th></th></tr></thead>
            <tbody>
                @forelse ($repayments as $repayment)
                    <tr>
                        <td>{{ $repayment->receipt_number }}</td>
                        <td>{{ $repayment->loan->customer->first_name }} {{ $repayment->loan->customer->last_name }}</td>
                        <td>{{ $repayment->loan->loan_number }}</td>
                        <td>{{ config('microfinance.currency') }} {{ number_format((float) $repayment->amount, 2) }}</td>
                        <td>{{ $repayment->payment_method }}</td>
                        <td>{{ $repayment->paid_at->format('d M Y H:i') }}</td>
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('repayments.show', $repayment) }}">Receipt</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No repayments posted.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="card-body">{{ $repayments->links() }}</div>
    </div>
@endsection

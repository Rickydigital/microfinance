@extends('layouts.portal')
@section('title','My loans')
@section('content')

<div class="page-head">
    <div>
        <span class="eyebrow">Loan accounts</span>
        <h1>My loans</h1>
        <p>Active and historical loan accounts, with outstanding balance and arrears at a glance.</p>
    </div>
</div>

<div class="card">
    @if($loans->count())
        <div class="table-wrap">
            <table class="ledger">
                <thead>
                    <tr><th>Loan</th><th>Product</th><th>Principal</th><th>Outstanding</th><th>Arrears</th><th>Status</th></tr>
                </thead>
                <tbody>
                @foreach($loans as $loan)
                    @php
                        $hasArrears = (float) $loan->arrears_amount > 0;
                        $rowPill = $hasArrears ? 'pill--bad' : match(strtolower($loan->status->value)) {
                            'active' => 'pill--good',
                            'closed','settled' => 'pill--muted',
                            default => 'pill--info',
                        };
                    @endphp
                    <tr>
                        <td><a class="ref" href="{{ route('portal.loans.show',$loan) }}">{{ $loan->loan_number }}</a></td>
                        <td>{{ $loan->application->productVersion->product->name }}</td>
                        <td class="money">{{ number_format((float)$loan->principal_amount,2) }}</td>
                        <td class="money">{{ number_format((float)$loan->outstanding_principal,2) }}</td>
                        <td class="money" style="{{ $hasArrears ? 'color:var(--danger);' : '' }}">{{ number_format((float)$loan->arrears_amount,2) }}</td>
                        <td><span class="pill {{ $rowPill }}">{{ $hasArrears ? 'In arrears' : $loan->status->value }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-pad" style="padding-top:14px;">{{ $loans->links() }}</div>
    @else
        <div class="empty">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/></svg>
            <h3>No loan account yet</h3>
            <p>Once an application is approved and disbursed, your loan account and repayment ledger will appear here.</p>
            <a href="{{ route('portal.applications.create') }}" class="btn btn-primary">Apply for a loan</a>
        </div>
    @endif
</div>

@endsection
@extends('layouts.portal')
@section('title', 'Payments')
@section('content')
    <div class="page-head"><div><span class="eyebrow">Customer payments</span><h1>Add payment</h1><p>Select the loan or assurance payment below, then enter the amount, reference and receipt attachment. Staff approval is required before it counts.</p></div></div>

    <div class="section-title">Loan repayments</div>
    <div class="grid grid-2">
        @forelse ($loans as $loan)
            <div class="card card-pad">
                <h2>{{ $loan->loan_number }}</h2>
                <p>{{ $loan->application->productVersion->product->name }}</p>
                <p>Outstanding principal: {{ config('microfinance.currency') }} {{ number_format((float) $loan->outstanding_principal, 2) }}</p>
                <a class="btn btn-accent" href="{{ route('portal.loans.show', $loan) }}#payment-form">Add payment</a>
            </div>
        @empty
            <div class="card card-pad"><p>No loans are currently accepting repayments. Your loan will appear here after disbursement.</p></div>
        @endforelse
    </div>

    <div class="section-title">Assurance payments</div>
    <div class="grid grid-2">
        @forelse ($applications as $application)
            <div class="card card-pad">
                <h2>{{ $application->application_number }}</h2>
                <p>{{ $application->productVersion->product->name }}</p>
                <p>Required assurance: {{ config('microfinance.currency') }} {{ number_format($application->assuranceAmount(), 2) }}</p>
                <a class="btn btn-primary" href="{{ route('portal.applications.show', $application) }}#payment-form">Record assurance payment</a>
            </div>
        @empty
            <div class="card card-pad"><p>No approved applications currently require assurance. Assurance becomes payable after application approval.</p></div>
        @endforelse
    </div>
@endsection

@extends('layouts.portal')
@section('title','Dashboard')
@section('content')

@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $activeLoans = $loans->whereIn('status.value', ['Active', 'In Arrears'])->count();
    $outstanding = (float) $loans->sum('outstanding_principal');
    $kyc = strtolower($customer->status->value);
    $kycPill = match($kyc) {
        'verified', 'active' => 'pill--good',
        'pending', 'under_review' => 'pill--warn',
        'rejected' => 'pill--bad',
        default => 'pill--muted',
    };
@endphp

<div class="page-head">
    <div>
        <span class="eyebrow">{{ $greeting }}</span>
        <h1>{{ $customer->first_name }}</h1>
        <p>Customer {{ $customer->customer_number }} &nbsp;·&nbsp; KYC <span class="pill {{ $kycPill }}" style="margin-left:2px;">{{ $customer->status->value }}</span></p>
    </div>
    <a href="{{ route('portal.applications.create') }}" class="btn btn-accent">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Apply for a loan
    </a>
</div>

<div style="margin-bottom:22px;">
    <a href="{{ route('portal.payments.index') }}" class="btn btn-primary">Add payment</a>
</div>

<div class="grid grid-3">
    <div class="stat-card">
        <span class="eyebrow">Applications</span>
        <strong>{{ $customer->loanApplications()->count() }}</strong>
        <div class="stat-hint">Submitted since you joined</div>
    </div>
    <div class="stat-card">
        <span class="eyebrow">Active loans</span>
        <strong>{{ $activeLoans }}</strong>
        <div class="stat-hint">{{ $activeLoans > 0 ? 'Currently being repaid' : 'None running right now' }}</div>
    </div>
    <div class="stat-card">
        <span class="eyebrow">Outstanding principal</span>
        <strong class="money">{{ config('microfinance.currency') }} {{ number_format($outstanding,2) }}</strong>
        <div class="stat-hint">Across all active accounts</div>
    </div>
</div>

<div class="section-title">Recent applications</div>
<div class="card">
    @if($applications->count())
        <div class="table-wrap">
            <table class="ledger">
                <thead>
                    <tr><th>Reference</th><th>Product</th><th>Requested</th><th>Approved</th><th>Status</th></tr>
                </thead>
                <tbody>
                @foreach($applications as $application)
                    @php
                        $rowPill = match(strtolower($application->status->value)) {
                            'approved','disbursed','active' => 'pill--good',
                            'rejected','declined' => 'pill--bad',
                            'pending','under_review','submitted' => 'pill--warn',
                            default => 'pill--muted',
                        };
                    @endphp
                    <tr>
                        <td><a class="ref" href="{{ route('portal.applications.show',$application) }}">{{ $application->application_number }}</a></td>
                        <td>{{ $application->productVersion->product->name }}</td>
                        <td class="money">{{ number_format((float)$application->requested_amount,2) }}</td>
                        <td class="money">{{ $application->approved_amount ? number_format((float)$application->approved_amount,2) : '—' }}</td>
                        <td><span class="pill {{ $rowPill }}">{{ $application->status->value }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6"><path d="M12 3 3 8l9 5 9-5-9-5Z"/><path d="M3 13l9 5 9-5"/></svg>
            <h3>No applications yet</h3>
            <p>Your first application takes about five minutes — we'll walk you through the numbers before you submit.</p>
            <a href="{{ route('portal.applications.create') }}" class="btn btn-primary">Start an application</a>
        </d
        iv>
    @endif
</div>

@endsection

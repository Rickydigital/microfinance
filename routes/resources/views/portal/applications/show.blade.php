@extends('layouts.portal')
@section('title', $loanApplication->application_number)
@section('content')

@php
    $stages = ['Submitted', 'Assessed', 'Approved', 'Disbursed'];
    $reached = 1;
    if ($loanApplication->assessments->count()) $reached = 2;
    if ($loanApplication->approved_amount || $loanApplication->approvals->contains(fn($a) => strtolower($a->status) === 'approved')) $reached = 3;
    if ($loanApplication->loan) $reached = 4;
@endphp

<div class="page-head">
    <div>
        <span class="eyebrow">Application</span>
        <h1>{{ $loanApplication->application_number }}</h1>
        <p>{{ $loanApplication->productVersion->product->name }} · {{ $loanApplication->status->value }}</p>
    </div>
    @if($loanApplication->loan)
        <a href="{{ route('portal.loans.show',$loanApplication->loan) }}" class="btn btn-accent">View loan account</a>
    @endif
</div>

<div class="card card-pad" style="margin-bottom:22px;">
    <div class="stepper">
        @foreach($stages as $i => $label)
            <div class="step {{ $i < $reached ? 'done' : ($i == $reached ? 'current' : '') }}">
                <div class="step-dot">
                    @if($i < $reached)
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg>
                    @endif
                </div>
                <div class="step-label">{{ $label }}</div>
            </div>
        @endforeach
    </div>
</div>

<div class="grid grid-4">
    <div class="stat-card">
        <span class="eyebrow">Requested</span>
        <strong class="money" style="font-size:19px;">{{ number_format((float)$loanApplication->requested_amount,2) }}</strong>
    </div>
    <div class="stat-card">
        <span class="eyebrow">Approved</span>
        <strong class="money" style="font-size:19px;">{{ $loanApplication->approved_amount ? number_format((float)$loanApplication->approved_amount,2) : 'Pending' }}</strong>
    </div>
    <div class="stat-card">
        <span class="eyebrow">Total interest</span>
        <strong class="money" style="font-size:19px;">{{ number_format($quote['total_interest'],2) }}</strong>
    </div>
    <div class="stat-card">
        <span class="eyebrow">Expected net proceeds</span>
        <strong class="money" style="font-size:19px;">{{ number_format($quote['estimated_net_disbursement'],2) }}</strong>
    </div>
</div>

<div class="section-title">Calculation</div>
<div class="card card-pad" style="margin:22px 0;">
    <h2>Assurance: {{ number_format($quote['assurance_percent'], 2) }}%</h2>
    <p>{{ number_format($quote['assurance_amount'], 2) }} included in loan installments without extra interest. No separate payment is required before disbursement.</p>
    <p>Approved assurance payments: {{ number_format((float) $loanApplication->paymentSubmissions()->where('purpose', 'Assurance')->where('status', 'Approved')->sum('amount'), 2) }}</p>
</div>

@include('portal.payment-submissions', ['paymentSubmissions' => $loanApplication->paymentSubmissions()->where('purpose', 'Assurance')->latest()->get()])
<div class="card card-pad" style="margin-bottom:6px;">
    <p style="font-weight:600;font-size:15px;margin-bottom:6px;">{{ number_format($quote['annual_interest_rate']*100,2) }}% {{ $quote['rate_basis'] }} · {{ $quote['interest_method'] }}</p>
    <p style="color:var(--ink-soft);font-size:13.5px;margin-bottom:16px;">{{ $quote['formula'] }}</p>
    <div class="grid grid-3">
        <div><span class="eyebrow">Total fees</span><div class="money">{{ number_format($quote['total_fees'],2) }}</div></div>
        <div><span class="eyebrow">Maximum installment</span><div class="money">{{ number_format($quote['installment'],2) }}</div></div>
        <div><span class="eyebrow">Total customer cost</span><div class="money">{{ number_format($quote['total_customer_cost'],2) }}</div></div>
    </div>
</div>

<div class="section-title">Progress notes</div>
<div class="card card-pad">
    @forelse($loanApplication->assessments as $assessment)
        <div style="padding:10px 0;border-bottom:1px solid var(--line);">
            <span class="pill pill--info">Assessment</span>
            <span style="margin-left:8px;font-size:13.5px;">{{ $assessment->recommendation }} · Risk grade {{ $assessment->risk_grade }}</span>
        </div>
    @empty
        <p style="color:var(--ink-faint);font-size:13.5px;">Awaiting staff risk assessment.</p>
    @endforelse
    @foreach($loanApplication->approvals as $approval)
        <div style="padding:10px 0;border-bottom:1px solid var(--line);">
            <span class="pill pill--good">Approval level {{ $approval->sequence }}</span>
            <span style="margin-left:8px;font-size:13.5px;">{{ $approval->status }}</span>
        </div>
    @endforeach
</div>

@endsection

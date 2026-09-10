@extends('layouts.portal')
@section('title', $loan->loan_number)
@section('content')

@php
    $principal = (float) $loan->principal_amount;
    $outstanding = (float) $loan->outstanding_principal;
    $paid = max(0, $principal - $outstanding);
    $percent = $principal > 0 ? min(100, round(($paid / $principal) * 100)) : 0;
    $hasArrears = (float) $loan->arrears_amount > 0;
@endphp

<div class="page-head">
    <div>
        <span class="eyebrow">Loan account</span>
        <h1>{{ $loan->loan_number }}</h1>
        <p>{{ $loan->application->productVersion->product->name }} · {{ $hasArrears ? 'In arrears' : $loan->status->value }}</p>
    </div>
</div>

<div class="grid grid-4">
    <div class="stat-card">
        <span class="eyebrow">Original principal</span>
        <strong class="money" style="font-size:19px;">{{ number_format($principal,2) }}</strong>
    </div>
    <div class="stat-card">
        <span class="eyebrow">Outstanding principal</span>
        <strong class="money" style="font-size:19px;">{{ number_format($outstanding,2) }}</strong>
    </div>
    <div class="stat-card">
        <span class="eyebrow">Arrears</span>
        <strong class="money" style="font-size:19px;{{ $hasArrears ? 'color:var(--danger);' : '' }}">{{ number_format((float)$loan->arrears_amount,2) }}</strong>
    </div>
    <div class="stat-card">
        <span class="eyebrow">Days past due</span>
        <strong style="font-size:19px;font-family:'Fraunces',serif;">{{ $loan->days_past_due }}</strong>
    </div>
</div>

<div class="card card-pad" style="margin:22px 0;">
    <span class="eyebrow">Repayment journey</span>
    <div class="journey">
        <div class="journey-fill" style="width:{{ $percent }}%;"></div>
        <div class="journey-marker" style="left:{{ $percent }}%;"></div>
    </div>
    <div class="journey-labels">
        <span>Disbursed</span>
        <span>{{ $percent }}% repaid · {{ config('microfinance.currency') }} {{ number_format($paid,2) }} of {{ number_format($principal,2) }}</span>
        <span>Settled</span>
    </div>
</div>

<div class="section-title">Repayment schedule</div>
<div class="card" style="margin-bottom:22px;">
    <div class="table-wrap">
        <table class="ledger">
            <thead>
                <tr><th>#</th><th>Due date</th><th>Principal</th><th>Interest</th><th>Fees</th><th>Penalties</th><th>Paid</th><th>Status</th></tr>
            </thead>
            <tbody>
            @foreach($loan->installments as $item)
                @php
                    $paidTotal = (float)$item->principal_paid + (float)$item->interest_paid + (float)$item->fees_paid + (float)$item->penalties_paid;
                    $stPill = match(strtolower($item->status)) {
                        'paid','settled' => 'pill--good',
                        'overdue','defaulted' => 'pill--bad',
                        'partial' => 'pill--warn',
                        default => 'pill--muted',
                    };
                @endphp
                <tr>
                    <td>{{ $item->installment_number }}</td>
                    <td>{{ $item->due_date->format('d M Y') }}</td>
                    <td class="money">{{ number_format((float)$item->principal_due,2) }}</td>
                    <td class="money">{{ number_format((float)$item->interest_due,2) }}</td>
                    <td class="money">{{ number_format((float)$item->fees_due,2) }}</td>
                    <td class="money">{{ number_format((float)$item->penalties_due,2) }}</td>
                    <td class="money">{{ number_format($paidTotal,2) }}</td>
                    <td><span class="pill {{ $stPill }}">{{ $item->status }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="section-title">Payment history</div>
@if (in_array($loan->status, [\App\LoanStatus::Active, \App\LoanStatus::InArrears], true))
    @include('portal.payment-form', ['paymentTitle' => 'Record a payment', 'paymentAction' => route('portal.payments.store', $loan)])
@endif
@include('portal.payment-submissions', ['paymentSubmissions' => $loan->application->paymentSubmissions()->with('repayment')->where('purpose', 'Repayment')->latest()->get()])
<div class="card card-pad">
    @forelse($loan->repayments->sortByDesc('paid_at') as $payment)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--line);">
            <div>
                <a class="ref" style="display:block;">{{ $payment->receipt_number }}</a>
                <span style="font-size:12px;color:var(--ink-faint);">{{ $payment->paid_at->format('d M Y H:i') }} · {{ $payment->payment_method }} · {{ $payment->status }}</span>
            </div>
            <div class="money">{{ number_format((float)$payment->amount,2) }}</div>
        </div>
    @empty
        <div class="empty" style="padding:30px 10px;">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <h3>No payments posted</h3>
            <p>Payments will appear here as soon as they're recorded against this loan.</p>
        </div>
    @endforelse
</div>

@endsection

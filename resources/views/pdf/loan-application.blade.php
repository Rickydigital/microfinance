@extends('pdf.layout')
@section('document-title', 'Loan Application Dossier')
@section('document-tag', 'Confidential — Credit File')
@section('document-ref', 'Ref: '.$loanApplication->application_number)
@section('content')

@php
    $statusBadge = match(strtolower($loanApplication->status->value)) {
        'approved', 'disbursed', 'active' => 'badge-green',
        'rejected', 'declined', 'defaulted' => 'badge-red',
        'pending', 'under_review', 'submitted' => 'badge-amber',
        default => 'badge-gray',
    };
    $contractQuote = $approvedQuote ?? $requestedQuote;
@endphp

<h2>Application &amp; Customer</h2>
<table class="summary">
    <tr>
        <td><strong>Application No.</strong>{{ $loanApplication->application_number }}</td>
        <td><strong>Status</strong><span class="badge {{ $statusBadge }}">{{ str_replace('_',' ', $loanApplication->status->value) }}</span></td>
        <td><strong>Customer</strong>{{ $loanApplication->customer->first_name }} {{ $loanApplication->customer->last_name }}</td>
        <td><strong>Customer No.</strong>{{ $loanApplication->customer->customer_number }}</td>
    </tr>
    <tr>
        <td><strong>Product</strong>{{ $loanApplication->productVersion->product->name }}</td>
        <td><strong>Term</strong>{{ $loanApplication->requested_term }} {{ $loanApplication->productVersion->term_unit }}</td>
        <td><strong>Requested</strong>{{ config('microfinance.currency') }} {{ number_format((float)$loanApplication->requested_amount,2) }}</td>
        <td><strong>Approved</strong>{{ $loanApplication->approved_amount ? config('microfinance.currency').' '.number_format((float)$loanApplication->approved_amount,2) : 'Pending' }}</td>
    </tr>
</table>

<h2>Contractual Calculation</h2>
<table class="grid">
    <tr><th>Rate</th><th>Method</th><th>Total Interest</th><th>Total Fees</th><th>Net Proceeds</th><th>Total Customer Cost</th></tr>
    <tr>
        <td>{{ number_format($contractQuote['annual_interest_rate']*100,2) }}% {{ $contractQuote['rate_basis'] }}</td>
        <td>{{ $contractQuote['interest_method'] }}</td>
        <td class="number">{{ number_format($contractQuote['total_interest'],2) }}</td>
        <td class="number">{{ number_format($contractQuote['total_fees'],2) }}</td>
        <td class="number">{{ number_format($contractQuote['estimated_net_disbursement'],2) }}</td>
        <td class="number">{{ number_format($contractQuote['total_customer_cost'],2) }}</td>
    </tr>
</table>
<p><strong>Formula:</strong> {{ $contractQuote['formula'] }}</p>

<h2>Fees</h2>
<table class="grid">
    <tr><th>Fee</th><th>Calculation</th><th>Collection</th><th>Financed</th><th>Amount</th></tr>
    @forelse($contractQuote['fees'] as $fee)
        <tr>
            <td>{{ $fee['name'] }}</td>
            <td>{{ $fee['amount'] }}</td>
            <td>{{ $fee['collection_point'] }}</td>
            <td>{{ $fee['is_financed'] ? 'Yes' : 'No' }}</td>
            <td class="number">{{ number_format($fee['amount'],2) }}</td>
        </tr>
    @empty
        <tr><td colspan="5">No configured fees.</td></tr>
    @endforelse
</table>

<h2>Assessment &amp; Approvals</h2>
<table class="grid">
    <tr><th>Stage</th><th>Decision</th><th>Amount / Risk</th><th>Officer</th><th>Comments</th></tr>
    @foreach($loanApplication->assessments as $item)
        <tr>
            <td>Assessment</td>
            <td>{{ $item->recommendation }}</td>
            <td>{{ $item->risk_grade }} / {{ number_format((float)$item->recommended_amount,2) }}</td>
            <td>{{ $item->assessor->full_name }}</td>
            <td>{{ $item->rationale }}</td>
        </tr>
    @endforeach
    @foreach($loanApplication->approvals as $item)
        <tr>
            <td>Approval {{ $item->sequence }}</td>
            <td>{{ $item->status }}</td>
            <td>{{ $item->approved_amount ? number_format((float)$item->approved_amount,2) : '—' }}</td>
            <td>{{ $item->decisionMaker?->full_name ?? 'Pending' }}</td>
            <td>{{ $item->comments }}</td>
        </tr>
    @endforeach
</table>

<div class="page-break"></div>

<h2>Repayment Schedule</h2>
<table class="grid">
    <tr><th>#</th><th>Due Date</th><th>Opening</th><th>Principal</th><th>Interest</th><th>Fees</th><th>Total</th><th>Closing</th></tr>
    @foreach($dossier['schedule'] as $row)
        <tr>
            <td>{{ $row['number'] }}</td>
            <td>{{ $row['due_date'] ?? 'At disbursement' }}</td>
            <td class="number">{{ $row['opening_principal'] === null ? '—' : number_format($row['opening_principal'],2) }}</td>
            <td class="number">{{ number_format($row['principal'],2) }}</td>
            <td class="number">{{ number_format($row['interest'],2) }}</td>
            <td class="number">{{ number_format($row['fees'],2) }}</td>
            <td class="number">{{ number_format($row['installment'],2) }}</td>
            <td class="number">{{ $row['closing_principal'] === null ? '—' : number_format($row['closing_principal'],2) }}</td>
        </tr>
    @endforeach
</table>

<h2>Security &amp; Conditions</h2>
<table class="grid">
    <tr><th>Type</th><th>Details</th><th>Value</th><th>Status</th></tr>
    @foreach($loanApplication->guarantors as $link)
        <tr>
            <td>Guarantor</td>
            <td>{{ $link->guarantor->first_name }} {{ $link->guarantor->last_name }}</td>
            <td>{{ number_format((float)$link->guaranteed_amount,2) }}</td>
            <td>{{ $link->status }}</td>
        </tr>
    @endforeach
    @foreach($loanApplication->collaterals as $link)
        <tr>
            <td>Collateral</td>
            <td>{{ $link->collateral->description }}</td>
            <td>{{ number_format((float)$link->pledged_value,2) }}</td>
            <td>{{ $link->status }}</td>
        </tr>
    @endforeach
    @foreach($loanApplication->checklistItems as $item)
        <tr>
            <td>Condition</td>
            <td>{{ $item->label }}</td>
            <td>{{ $item->is_required ? 'Required' : 'Optional' }}</td>
            <td>{{ $item->is_completed ? 'Complete' : 'Outstanding' }}</td>
        </tr>
    @endforeach
</table>

@endsection
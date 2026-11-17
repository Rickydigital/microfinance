@extends('pdf.layout')
@section('document-title', $definition['label'].' Report')
@section('document-tag', $definition['group_label'])
@section('document-ref', 'As at '.now()->format('d M Y'))
@section('content')

<h2>Report Summary</h2>
<table class="grid">
    <tr>
        <th>Active Loans</th>
        <th>Outstanding Principal</th>
        <th>Posted Collections</th>
        <th>Open Collection Cases</th>
        <th>Debits</th>
        <th>Credits</th>
    </tr>
    <tr>
        <td>{{ number_format($metrics['active_loans']) }}</td>
        <td class="number">{{ config('microfinance.currency') }} {{ number_format((float)$metrics['outstanding_principal'],2) }}</td>
        <td class="number">{{ config('microfinance.currency') }} {{ number_format((float)$metrics['collections'],2) }}</td>
        <td>{{ number_format($metrics['open_cases']) }}</td>
        <td class="number">{{ config('microfinance.currency') }} {{ number_format((float)$metrics['debits'],2) }}</td>
        <td class="number">{{ config('microfinance.currency') }} {{ number_format((float)$metrics['credits'],2) }}</td>
    </tr>
</table>

<p style="color:#333;font-style:italic;">
    This report contains live values calculated from posted operational and accounting records at generation time and is intended for internal management use only.
</p>

<h2>Portfolio Status Breakdown</h2>
<table class="grid">
    <tr><th>Status</th><th>Number of loans</th><th>Outstanding principal</th></tr>
    @forelse($statusDistribution as $item)
        <tr><td>{{ $item->status }}</td><td>{{ number_format($item->total) }}</td><td class="number">{{ config('microfinance.currency') }} {{ number_format((float)$item->outstanding,2) }}</td></tr>
    @empty
        <tr><td colspan="3">No loan records available.</td></tr>
    @endforelse
</table>
<p><strong>Report period:</strong> {{ $from?->format('d M Y') ?? 'Beginning' }} to {{ $to?->format('d M Y') ?? 'Today' }}</p>

<h2>{{ $definition['label'] }} Records</h2>
@if($definition['dataset'] === 'collections')
    <table class="grid">
        <thead><tr><th>Date</th><th>Receipt</th><th>Loan</th><th>Customer</th><th>Method</th><th>Status</th><th>Received by</th><th>Amount</th></tr></thead>
        <tbody>
        @forelse($records as $repayment)
            <tr>
                <td>{{ $repayment->paid_at?->format('d M Y') }}</td><td>{{ $repayment->receipt_number }}</td><td>{{ $repayment->loan->loan_number }}</td>
                <td>{{ trim($repayment->loan->customer->first_name.' '.$repayment->loan->customer->last_name) }}</td><td>{{ $repayment->payment_method }}</td>
                <td>{{ $repayment->status }}</td><td>{{ $repayment->receiver?->full_name ?? 'System' }}</td>
                <td class="number">{{ config('microfinance.currency') }} {{ number_format((float) $repayment->amount, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="8">No repayment records were found for this period.</td></tr>
        @endforelse
        </tbody>
    </table>
@elseif($definition['dataset'] === 'operational')
    <table class="grid">
        <thead><tr><th>Application</th><th>Date</th><th>Customer</th><th>Product</th><th>Status</th><th>Term</th><th>Requested</th><th>Approved</th></tr></thead>
        <tbody>
        @forelse($records as $application)
            <tr>
                <td>{{ $application->application_number }}</td><td>{{ $application->created_at?->format('d M Y') }}</td>
                <td>{{ trim($application->customer->first_name.' '.$application->customer->last_name) }}</td><td>{{ $application->productVersion?->product?->name ?? '—' }}</td>
                <td>{{ $application->status->value ?? $application->status }}</td><td>{{ $application->requested_term }} {{ $application->productVersion?->term_unit }}</td>
                <td class="number">{{ config('microfinance.currency') }} {{ number_format((float) $application->requested_amount, 2) }}</td>
                <td class="number">{{ config('microfinance.currency') }} {{ number_format((float) $application->approved_amount, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="8">No application records were found for this period.</td></tr>
        @endforelse
        </tbody>
    </table>
@else
    <table class="grid">
        <thead><tr><th>Loan</th><th>Disbursed</th><th>Customer</th><th>Product</th><th>Status</th><th>Maturity</th><th>Principal</th><th>Outstanding</th><th>Days late</th></tr></thead>
        <tbody>
        @forelse($records as $loan)
            <tr>
                <td>{{ $loan->loan_number }}</td><td>{{ $loan->disbursed_at?->format('d M Y') }}</td><td>{{ trim($loan->customer->first_name.' '.$loan->customer->last_name) }}</td>
                <td>{{ $loan->application?->productVersion?->product?->name ?? '—' }}</td><td>{{ $loan->status->value ?? $loan->status }}</td><td>{{ $loan->maturity_date?->format('d M Y') }}</td>
                <td class="number">{{ config('microfinance.currency') }} {{ number_format((float) $loan->principal_amount, 2) }}</td>
                <td class="number">{{ config('microfinance.currency') }} {{ number_format((float) $loan->outstanding_principal, 2) }}</td><td>{{ number_format($loan->days_past_due) }}</td>
            </tr>
        @empty
            <tr><td colspan="9">No loan records were found for this period.</td></tr>
        @endforelse
        </tbody>
    </table>
@endif

@endsection

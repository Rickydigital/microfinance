@extends('pdf.layout')
@section('document-title', 'Payment Receipt')
@section('document-tag', 'Official Receipt')
@section('document-ref', 'Receipt: '.$repayment->receipt_number)
@section('content')

<table class="summary">
    <tr>
        <td><strong>Customer</strong>{{ $repayment->loan->customer->first_name }} {{ $repayment->loan->customer->last_name }}</td>
        <td><strong>Loan No.</strong>{{ $repayment->loan->loan_number }}</td>
        <td><strong>Status</strong><span class="badge badge-green">Paid</span></td>
    </tr>
    <tr>
        <td><strong>Method</strong>{{ ucfirst(str_replace('_',' ', $repayment->payment_method)) }}</td>
        <td><strong>Received</strong>{{ $repayment->paid_at->format('d M Y H:i') }}</td>
        <td><strong>Received By</strong>{{ $repayment->receiver->full_name }}</td>
    </tr>
</table>

<table class="grid" style="margin-top:4px">
    <tr>
        <td style="background:#0f2942;color:#fff;text-align:right;font-size:16px;font-weight:bold;border:none;padding:12px 14px;">
            {{ config('microfinance.currency') }} {{ number_format((float)$repayment->amount,2) }}
        </td>
    </tr>
</table>

<h2>Allocation</h2>
<table class="grid">
    <tr><th>Installment</th><th>Component</th><th>Amount</th></tr>
    @foreach($repayment->allocations as $allocation)
        <tr>
            <td>{{ $allocation->installment->installment_number }}</td>
            <td>{{ ucfirst($allocation->component) }}</td>
            <td class="number">{{ number_format((float)$allocation->amount,2) }}</td>
        </tr>
    @endforeach
</table>

<p style="margin-top:14px;color:#64748b;font-style:italic;">
    Thank you for your payment. This receipt confirms funds received against the loan account referenced above and forms part of the official transaction record of {{ config('microfinance.institution_name') }}.
</p>

@endsection
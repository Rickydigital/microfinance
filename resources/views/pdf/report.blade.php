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

<p style="color:#64748b;font-style:italic;">
    This report contains live values calculated from posted operational and accounting records at generation time and is intended for internal management use only.
</p>

@endsection

@extends('layouts.app')
@section('title', 'Journal entries')
@section('content')
    <div class="mb-4"><h1 class="h3 mb-1">Journal entries</h1><p class="text-muted mb-0">Posted accounting activity with balanced debit and credit totals.</p></div>
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Journal</th><th>Date</th><th>Description</th><th>Source</th><th>Branch</th><th>Debit</th><th>Credit</th><th>Status</th></tr></thead><tbody>@forelse ($entries as $entry)<tr><td class="reference">{{ $entry->journal_number }}</td><td>{{ $entry->posting_date->format('d M Y') }}</td><td>{{ $entry->description }}</td><td>{{ $entry->source_type ?: 'Manual' }}</td><td>{{ $entry->branch?->name ?? 'Head office' }}</td><td>{{ number_format((float) $entry->lines_sum_debit, 2) }}</td><td>{{ number_format((float) $entry->lines_sum_credit, 2) }}</td><td>{{ $entry->status }}</td></tr>@empty<tr><td colspan="8" class="text-center py-4 text-muted">No journal entries posted.</td></tr>@endforelse</tbody></table></div><div class="card-body">{{ $entries->links() }}</div></div>
@endsection

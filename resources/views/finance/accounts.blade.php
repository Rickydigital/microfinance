@extends('layouts.app')
@section('title', 'Chart of accounts')
@section('content')
    @include('partials.alerts')
    <div class="mb-4"><h1 class="h3 mb-1">Chart of accounts</h1><p class="text-muted mb-0">Ledger structure, posting controls, normal balances, and product mapping source accounts.</p></div>
    @can('accounting.account.manage')
        <form class="card card-body border-0 shadow-sm mb-4" method="POST" action="{{ route('finance.accounts.store') }}">
            @csrf
            <h2 class="h5">Create ledger account</h2>
            <div class="row g-3">
                <div class="col-md-2"><input class="form-control" name="code" placeholder="Account code" required></div>
                <div class="col-md-3"><input class="form-control" name="name" placeholder="Account name" required></div>
                <div class="col-md-2"><select class="form-select" name="type" required>@foreach (['Asset','Liability','Equity','Income','Expense'] as $type)<option>{{ $type }}</option>@endforeach</select></div>
                <div class="col-md-2"><select class="form-select" name="normal_balance" required><option>Debit</option><option>Credit</option></select></div>
                <div class="col-md-3"><select class="form-select" name="parent_id"><option value="">No parent</option>@foreach ($accounts as $parent)<option value="{{ $parent->id }}">{{ $parent->code }} — {{ $parent->name }}</option>@endforeach</select></div>
                <div class="col-md-3 form-check ms-2"><input class="form-check-input" id="manual_posting" type="checkbox" name="allows_manual_posting" value="1"><label class="form-check-label" for="manual_posting">Allow manual posting</label></div>
                <div class="col-md-3 form-check"><input class="form-check-input" id="control_account" type="checkbox" name="is_control_account" value="1"><label class="form-check-label" for="control_account">Control account</label></div>
            </div>
            <button class="btn btn-primary align-self-start mt-3">Create account</button>
        </form>
    @endcan
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Code</th><th>Account</th><th>Type</th><th>Parent</th><th>Debit</th><th>Credit</th><th>Normal balance</th><th>Status</th><th></th></tr></thead>
        <tbody>@forelse ($accounts as $account)
            @php($balance = $account->normal_balance === 'Credit' ? (float) $account->journal_lines_sum_credit - (float) $account->journal_lines_sum_debit : (float) $account->journal_lines_sum_debit - (float) $account->journal_lines_sum_credit)
            <tr><td class="reference">{{ $account->code }}</td><td>{{ $account->name }}</td><td>{{ $account->type }}</td><td>{{ $account->parent?->name ?? '—' }}</td><td>{{ number_format((float) $account->journal_lines_sum_debit, 2) }}</td><td>{{ number_format((float) $account->journal_lines_sum_credit, 2) }}</td><td>{{ number_format($balance, 2) }} {{ $account->normal_balance }}</td><td><span class="badge {{ $account->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $account->is_active ? 'Active' : 'Inactive' }}</span><br><small>{{ $account->allows_manual_posting ? 'Manual' : 'System only' }}</small></td><td>@can('accounting.account.manage')<div class="d-flex gap-2"><a class="btn btn-sm btn-outline-primary" href="{{ route('finance.accounts.edit', $account) }}">Edit</a><form method="POST" action="{{ route('finance.accounts.destroy', $account) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-secondary">{{ $account->is_active ? 'Deactivate' : 'Activate' }}</button></form></div>@endcan</td></tr>
        @empty<tr><td colspan="9" class="text-center py-4 text-muted">No ledger accounts configured.</td></tr>@endforelse</tbody>
    </table></div></div>
@endsection

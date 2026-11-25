@extends('layouts.app')

@section('title', 'Approval rules')

@section('content')
    @include('administration._experience')
    @include('partials.alerts')

    <div class="admin-shell">
    <div class="admin-hero d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="small text-uppercase fw-bold opacity-75 mb-2">Administration · Credit governance</div><h1 class="h2 mb-1">Approval rules</h1>
            <p class="mb-0">Configure amount bands and sequential maker-checker authority.</p>
        </div>
        @can('admin.configuration.update')
            <button class="btn btn-light px-4" data-bs-toggle="modal" data-bs-target="#create-approval-rule">
                <i class="mdi mdi-plus me-1"></i>Create rule
            </button>
        @endcan
    </div>

    <div class="row g-3 mb-4"><div class="col-md-4"><div class="admin-stat"><span>Total rules</span><strong>{{ $approvalRules->total() }}</strong></div></div><div class="col-md-4"><div class="admin-stat"><span>Active on page</span><strong>{{ $approvalRules->getCollection()->where('is_active', true)->count() }}</strong></div></div><div class="col-md-4"><div class="admin-stat"><span>Approval roles</span><strong>{{ $approvalRules->getCollection()->pluck('role_name')->unique()->count() }}</strong></div></div></div>
    <div class="admin-panel">
        <div class="card-body border-bottom">
            <form class="row g-2 align-items-end" method="GET">
                <div class="col-sm-4">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All rules</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-sm-auto"><button class="btn btn-outline-primary">Filter</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Scope</th><th>Amount band</th><th>Risk</th><th>Level</th><th>Approver</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($approvalRules as $approvalRule)
                        <tr>
                            <td><strong>{{ $approvalRule->product?->name ?? 'All products' }}</strong><small class="d-block text-muted">{{ $approvalRule->branch?->name ?? 'All branches' }}</small></td>
                            <td>{{ number_format((float) $approvalRule->minimum_amount, 2) }} – {{ $approvalRule->maximum_amount ? number_format((float) $approvalRule->maximum_amount, 2) : 'No limit' }}</td>
                            <td>{{ $approvalRule->risk_grade ?: 'Any' }}</td>
                            <td>{{ $approvalRule->sequence }}</td>
                            <td>{{ $approvalRule->role_name }}</td>
                            <td><span class="badge {{ $approvalRule->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $approvalRule->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('approval-rules.show', $approvalRule) }}">View</a>
                                @can('admin.configuration.update')
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('approval-rules.edit', $approvalRule) }}">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td class="text-center text-muted py-5" colspan="7">No approval rules have been configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($approvalRules->hasPages())
            <div class="card-footer">{{ $approvalRules->links() }}</div>
        @endif
    </div>
    </div>

    <div class="modal fade admin-modal" id="create-approval-rule" tabindex="-1" aria-labelledby="create-approval-rule-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-xl"><form class="modal-content" method="POST" action="{{ route('approval-rules.store') }}">@csrf<div class="modal-header"><div><h2 class="h4 mb-1" id="create-approval-rule-title">Create approval rule</h2><p class="small text-muted mb-0">Define scope, amount band and required authority.</p></div><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div><div class="modal-body">@include('approval-rules._form', ['approvalRule' => null])</div></form></div></div>
@endsection

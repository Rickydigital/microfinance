@extends('layouts.app')

@section('title', 'Approval rules')

@section('content')
    @include('partials.alerts')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Approval rules</h1>
            <p class="text-muted mb-0">Configure amount bands and sequential maker-checker authority.</p>
        </div>
        @can('admin.configuration.update')
            <a class="btn btn-primary" href="{{ route('approval-rules.create') }}">
                <i class="mdi mdi-plus me-1"></i>Create rule
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
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
@endsection

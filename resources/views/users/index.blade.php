@extends('layouts.app')

@section('title', 'Employees')

@section('content')
    @include('administration._experience')
    @include('partials.alerts')

    <div class="admin-shell">
    <div class="admin-hero d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="small text-uppercase fw-bold opacity-75 mb-2">Administration · Access control</div>
            <h1 class="h2 mb-1">Employees and access</h1>
            <p class="mb-0">Manage employee profiles, branches, departments and roles.</p>
        </div>
        <button class="btn btn-light px-4" data-bs-toggle="modal" data-bs-target="#create-employee">
            <i class="mdi mdi-account-plus-outline me-1"></i>
            Create employee
        </button>
    </div>

    <div class="row g-3 mb-4"><div class="col-md-4"><div class="admin-stat"><span>Total employees</span><strong>{{ $users->total() }}</strong></div></div><div class="col-md-4"><div class="admin-stat"><span>Active on page</span><strong>{{ $users->getCollection()->where('is_active', true)->count() }}</strong></div></div><div class="col-md-4"><div class="admin-stat"><span>Roles represented</span><strong>{{ $users->getCollection()->flatMap->roles->unique('id')->count() }}</strong></div></div></div>

    <div class="admin-panel">
        <div class="card-body border-bottom">
            <form class="row g-2" method="GET">
                <div class="col-md-6">
                    <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search staff number, name or email">
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-primary" type="submit">Search</button>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Branch</th>
                        <th>Department</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $employee)
                        <tr>
                            <td>
                                <strong>{{ $employee->full_name }}</strong>
                                <div class="small text-muted">{{ $employee->staff_number }} · {{ $employee->email }}</div>
                            </td>
                            <td>{{ $employee->branch?->name ?? 'Institution-wide' }}</td>
                            <td>{{ $employee->department?->name ?? '—' }}</td>
                            <td>{{ $employee->getRoleNames()->join(', ') ?: 'No role' }}</td>
                            <td>
                                <span class="badge {{ $employee->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $employee->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('users.show', $employee) }}">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No employees found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $users->links() }}</div>
    </div>
    </div>

    <div class="modal fade admin-modal" id="create-employee" tabindex="-1" aria-labelledby="create-employee-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-xl"><form class="modal-content" method="POST" action="{{ route('users.store') }}">@csrf<div class="modal-header"><div><h2 class="h4 mb-1" id="create-employee-title">Create employee</h2><p class="small text-muted mb-0">Identity, workplace assignment and sign-in access.</p></div><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div><div class="modal-body">@include('users._form', ['user' => null])</div></form></div></div>
@endsection

@extends('layouts.app')

@section('title', 'Employees')

@section('content')
    @include('partials.alerts')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Employees and access</h1>
            <p class="text-muted mb-0">Manage employee profiles, branches, departments and roles.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('users.create') }}">
            <i class="mdi mdi-account-plus-outline me-1"></i>
            Create employee
        </a>
    </div>

    <div class="card border-0 shadow-sm">
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
@endsection

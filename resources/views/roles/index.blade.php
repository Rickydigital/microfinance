@extends('layouts.app')

@section('title', 'Roles and permissions')

@section('content')
    @include('partials.alerts')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h3 mb-1">Roles and permissions</h1><p class="text-muted mb-0">Control employee responsibilities and system access.</p></div>
        @can('admin.role.create')<a class="btn btn-primary" href="{{ route('roles.create') }}">Create role</a>@endcan
    </div>
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>Role</th><th>Employees</th><th>Permissions</th><th></th></tr></thead>
        <tbody>
            @foreach ($roles as $role)
                <tr><td><strong>{{ $role->name }}</strong></td><td>{{ $role->users_count }}</td><td>{{ $role->permissions_count }}</td><td class="text-end">@can('admin.role.update')<a class="btn btn-sm btn-outline-primary" href="{{ route('roles.edit', $role) }}">Manage</a>@endcan</td></tr>
            @endforeach
        </tbody>
    </table></div></div>
@endsection

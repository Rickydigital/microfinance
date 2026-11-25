@extends('layouts.app')

@section('title', 'Roles and permissions')

@section('content')
    @include('administration._experience')
    @include('partials.alerts')
    <div class="admin-shell">
    <div class="admin-hero d-flex justify-content-between align-items-center mb-4">
        <div><div class="small text-uppercase fw-bold opacity-75 mb-2">Administration · Security</div><h1 class="h2 mb-1">Roles and permissions</h1><p class="mb-0">Control employee responsibilities and system access.</p></div>
        @can('admin.role.create')<button class="btn btn-light px-4" data-bs-toggle="modal" data-bs-target="#create-role">Create role</button>@endcan
    </div>
    <div class="row g-3 mb-4"><div class="col-md-4"><div class="admin-stat"><span>Roles</span><strong>{{ $roles->count() }}</strong></div></div><div class="col-md-4"><div class="admin-stat"><span>Assigned employees</span><strong>{{ $roles->sum('users_count') }}</strong></div></div><div class="col-md-4"><div class="admin-stat"><span>Permission assignments</span><strong>{{ $roles->sum('permissions_count') }}</strong></div></div></div>
    <div class="admin-panel"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>Role</th><th>Employees</th><th>Permissions</th><th></th></tr></thead>
        <tbody>
            @foreach ($roles as $role)
                <tr><td><strong>{{ $role->name }}</strong></td><td>{{ $role->users_count }}</td><td>{{ $role->permissions_count }}</td><td class="text-end">@can('admin.role.update')<a class="btn btn-sm btn-outline-primary" href="{{ route('roles.edit', $role) }}">Manage</a>@endcan</td></tr>
            @endforeach
        </tbody>
    </table></div></div>
    </div>
    <div class="modal fade admin-modal" id="create-role" tabindex="-1" aria-labelledby="create-role-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-xl"><form class="modal-content" method="POST" action="{{ route('roles.store') }}">@csrf<div class="modal-header"><div><h2 class="h4 mb-1" id="create-role-title">Create role</h2><p class="small text-muted mb-0">Group permissions into a reusable employee role.</p></div><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div><div class="modal-body">@include('roles._form', ['role' => null])</div></form></div></div>
@endsection

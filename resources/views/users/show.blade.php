@extends('layouts.app')

@section('title', $user->full_name)

@section('content')
    @include('partials.alerts')
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div><h1 class="h3 mb-1">{{ $user->full_name }}</h1><p class="text-muted mb-0">{{ $user->staff_number }}</p></div>
        <a class="btn btn-outline-primary" href="{{ route('users.edit', $user) }}">Edit employee</a>
    </div>
    <div class="card card-body border-0 shadow-sm">
        <dl class="row mb-0">
            <dt class="col-md-3">Role</dt><dd class="col-md-9">{{ $user->getRoleNames()->join(', ') ?: 'No role assigned' }}</dd>
            <dt class="col-md-3">Branch</dt><dd class="col-md-9">{{ $user->branch?->name ?? 'Institution-wide' }}</dd>
            <dt class="col-md-3">Department</dt><dd class="col-md-9">{{ $user->department?->name ?? '—' }}</dd>
            <dt class="col-md-3">Email</dt><dd class="col-md-9">{{ $user->email }}</dd>
            <dt class="col-md-3">Phone</dt><dd class="col-md-9">{{ $user->phone ?: '—' }}</dd>
            <dt class="col-md-3">Last login</dt><dd class="col-md-9">{{ $user->last_login_at?->format('d M Y H:i') ?? 'Never' }}</dd>
        </dl>
    </div>
@endsection

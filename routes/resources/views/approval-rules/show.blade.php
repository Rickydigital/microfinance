@extends('layouts.app')

@section('title', 'Approval rule')

@section('content')
    @include('partials.alerts')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h3 mb-1">Approval rule #{{ $approvalRule->id }}</h1><p class="text-muted mb-0">{{ $approvalRule->product?->name ?? 'All products' }} · {{ $approvalRule->branch?->name ?? 'All branches' }}</p></div>
        @can('admin.configuration.update')<a class="btn btn-primary" href="{{ route('approval-rules.edit', $approvalRule) }}">Edit rule</a>@endcan
    </div>
    <div class="card border-0 shadow-sm"><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Amount band</dt><dd class="col-sm-8">{{ number_format((float) $approvalRule->minimum_amount, 2) }} – {{ $approvalRule->maximum_amount ? number_format((float) $approvalRule->maximum_amount, 2) : 'No limit' }}</dd>
            <dt class="col-sm-4">Risk grade</dt><dd class="col-sm-8">{{ $approvalRule->risk_grade ?: 'Any grade' }}</dd>
            <dt class="col-sm-4">Approval level</dt><dd class="col-sm-8">{{ $approvalRule->sequence }}</dd>
            <dt class="col-sm-4">Required role</dt><dd class="col-sm-8">{{ $approvalRule->role_name }}</dd>
            <dt class="col-sm-4">Status</dt><dd class="col-sm-8">{{ $approvalRule->is_active ? 'Active' : 'Inactive' }}</dd>
        </dl>
    </div></div>
@endsection

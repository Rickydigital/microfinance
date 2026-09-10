@extends('layouts.app')

@section('title', 'Approval queue')

@section('content')
    @include('partials.alerts')
    <div class="mb-4"><h1 class="h3 mb-1">Loan approval queue</h1><p class="text-muted mb-0">Applications awaiting a decision within your delegated role.</p></div>
    <div class="row g-4">
        @forelse ($approvals as $approval)
            <div class="col-xl-6"><div class="card card-body border-0 shadow-sm h-100"><div class="d-flex justify-content-between"><div><h2 class="h5">{{ $approval->application->application_number }}</h2><p class="text-muted">{{ $approval->application->customer->first_name }} {{ $approval->application->customer->last_name }}</p></div><span class="badge bg-warning text-dark align-self-start">Level {{ $approval->sequence }}</span></div><dl class="row"><dt class="col-5">Product</dt><dd class="col-7">{{ $approval->application->productVersion->product->name }}</dd><dt class="col-5">Requested</dt><dd class="col-7">{{ number_format((float) $approval->application->requested_amount, 2) }}</dd><dt class="col-5">Required role</dt><dd class="col-7">{{ $approval->required_role }}</dd></dl><form method="POST" action="{{ route('loan-approvals.update', $approval) }}">@csrf @method('PUT')<div class="mb-3"><label class="form-label">Decision</label><select class="form-select" name="decision" required><option>Approved</option><option>Returned</option><option>Rejected</option></select></div><div class="mb-3"><label class="form-label">Conditions</label><textarea class="form-control" name="conditions" rows="2"></textarea></div><div class="mb-3"><label class="form-label">Comments and rationale</label><textarea class="form-control" name="comments" rows="3" required></textarea></div><button class="btn btn-primary">Record decision</button></form></div></div>
        @empty
            <div class="col-12"><div class="card card-body border-0 shadow-sm text-center text-muted py-5">No approvals are waiting for your role.</div></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $approvals->links() }}</div>
@endsection

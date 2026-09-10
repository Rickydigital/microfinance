@extends('layouts.app')
@section('title', 'Payment approvals')
@section('content')
    @include('partials.alerts')
    <h1 class="h3">Customer payment approvals</h1>
    <p>Verify the reference and evidence against the actual money received before approving. Approval posts the payment.</p>
    @forelse ($submissions as $submission)
        <div class="card card-body mb-3">
            <h2 class="h5">{{ $submission->customer->first_name }} {{ $submission->customer->last_name }} · {{ $submission->loan?->loan_number ?? $submission->application->application_number }}</h2>
            <p>{{ $submission->purpose }} · {{ number_format((float) $submission->amount, 2) }} · {{ $submission->payment_method }} · {{ $submission->paid_at->format('d M Y') }}</p>
            <p>Reference: <strong>{{ $submission->external_reference }}</strong> · Status: {{ $submission->repayment?->status === 'Reversed' ? 'Reversed' : $submission->status }}</p>
            @if ($submission->attachment_path)
                <p><a href="{{ route('payment-submissions.attachment', $submission) }}">Download payment evidence</a></p>
            @else
                <p class="text-muted">No payment evidence attached.</p>
            @endif
            @if ($submission->status === 'Pending')
                <form method="POST" action="{{ route('payment-submissions.review', $submission) }}">
                    @csrf
                    <label class="form-label">Review notes (required for rejection)</label>
                    <textarea class="form-control mb-2" name="review_notes" maxlength="2000"></textarea>
                    <button class="btn btn-success" name="decision" value="Approved">Approve and post</button>
                    <button class="btn btn-outline-danger" name="decision" value="Rejected">Reject</button>
                </form>
            @else
                <p>{{ $submission->review_notes }}</p>
                @if ($submission->repayment_id && auth()->user()->can('repayment.view'))<a href="{{ route('repayments.show', $submission->repayment_id) }}">View receipt</a>@endif
            @endif
        </div>
    @empty
        <p>No submitted payments.</p>
    @endforelse
    {{ $submissions->links() }}
@endsection

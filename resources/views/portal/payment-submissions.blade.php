<div class="card card-pad" style="margin:22px 0;">
    <h2>Submitted payments</h2>
    @forelse ($paymentSubmissions as $submission)
        <div style="padding:12px 0;border-bottom:1px solid var(--line);">
            <strong>{{ number_format((float) $submission->amount, 2) }}</strong>
            {{ $submission->purpose }} · {{ $submission->external_reference }} · {{ $submission->paid_at->format('d M Y') }}
            <span class="pill">{{ $submission->repayment?->status === 'Reversed' ? 'Reversed' : $submission->status }}</span>
            <a href="{{ route('portal.payments.attachment', $submission) }}">Download evidence</a>
            @if ($submission->review_notes)<p>{{ $submission->review_notes }}</p>@endif
        </div>
    @empty
        <p>No payment submissions yet.</p>
    @endforelse
</div>

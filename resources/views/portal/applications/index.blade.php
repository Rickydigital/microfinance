@extends('layouts.portal')
@section('title','My applications')
@section('content')

<div class="page-head">
    <div>
        <span class="eyebrow">Applications</span>
        <h1>My loan applications</h1>
        <p>Follow every application from submission through assessment, approval, and disbursement.</p>
    </div>
    <a href="{{ route('portal.applications.create') }}" class="btn btn-accent">New application</a>
</div>

<div class="card">
    @if($applications->count())
        <div class="table-wrap">
            <table class="ledger">
                <thead>
                    <tr><th>Reference</th><th>Product</th><th>Requested</th><th>Approved</th><th>Submitted</th><th>Status</th></tr>
                </thead>
                <tbody>
                @foreach($applications as $application)
                    @php
                        $rowPill = match(strtolower($application->status->value)) {
                            'approved','disbursed','active' => 'pill--good',
                            'rejected','declined' => 'pill--bad',
                            'pending','under_review','submitted' => 'pill--warn',
                            default => 'pill--muted',
                        };
                    @endphp
                    <tr>
                        <td><a class="ref" href="{{ route('portal.applications.show',$application) }}">{{ $application->application_number }}</a></td>
                        <td>{{ $application->productVersion->product->name }}</td>
                        <td class="money">{{ number_format((float)$application->requested_amount,2) }}</td>
                        <td class="money">{{ $application->approved_amount ? number_format((float)$application->approved_amount,2) : '—' }}</td>
                        <td>{{ $application->submitted_at?->format('d M Y') ?? '—' }}</td>
                        <td><span class="pill {{ $rowPill }}">{{ $application->status->value }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-pad" style="padding-top:14px;">{{ $applications->links() }}</div>
    @else
        <div class="empty">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6"><path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4"/><path d="M9.5 13h5M9.5 16.5h5"/></svg>
            <h3>No applications submitted</h3>
            <p>Bado hujaomba mkopo. Your first application takes about five minutes to complete.</p>
            <a href="{{ route('portal.applications.create') }}" class="btn btn-primary">Start an application</a>
        </div>
    @endif
</div>

@endsection
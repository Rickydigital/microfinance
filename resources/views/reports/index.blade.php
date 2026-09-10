@extends('layouts.app')
@section('title', $definition['label'].' report')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <span class="text-muted small">{{ $definition['group_label'] }}</span>
            <h1 class="h3 mb-1">{{ $definition['label'] }}</h1>
            <p class="text-muted mb-0">Formal reports with branch-scoped exports for regulatory, financial, loan, customer, operational, and audit needs.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('reports.pdf', $report) }}"><i class="mdi mdi-file-pdf mr-1"></i>Download A4 PDF</a>
            <button class="btn btn-success" type="submit" form="excel-export-form"><i class="mdi mdi-file-excel mr-1"></i>Export Excel</button>
        </div>
    </div>

    <div class="card card-body border-0 shadow-sm mb-4">
        <form id="excel-export-form" class="row g-3 align-items-end" method="GET" action="{{ route('reports.export', $report) }}">
            <div class="col-md-4">
                <label class="form-label" for="from">From date</label>
                <input class="form-control" id="from" type="date" name="from" value="{{ request('from') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="to">To date</label>
                <input class="form-control" id="to" type="date" name="to" value="{{ request('to') }}">
            </div>
            <div class="col-md-4">
                <small class="text-muted">The workbook respects your branch access and contains Overview, detailed records, and Checks sheets.</small>
            </div>
        </form>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4"><div class="card card-body border-0 shadow-sm"><small class="text-muted">Active loans</small><strong class="h4 mt-2">{{ number_format($metrics['active_loans']) }}</strong></div></div>
        <div class="col-md-4"><div class="card card-body border-0 shadow-sm"><small class="text-muted">Outstanding principal</small><strong class="h4 mt-2">{{ number_format((float) $metrics['outstanding_principal'], 2) }}</strong></div></div>
        <div class="col-md-4"><div class="card card-body border-0 shadow-sm"><small class="text-muted">Posted collections</small><strong class="h4 mt-2">{{ number_format((float) $metrics['collections'], 2) }}</strong></div></div>
        <div class="col-md-4"><div class="card card-body border-0 shadow-sm"><small class="text-muted">Open collection cases</small><strong class="h4 mt-2">{{ number_format($metrics['open_cases']) }}</strong></div></div>
        <div class="col-md-4"><div class="card card-body border-0 shadow-sm"><small class="text-muted">Ledger debits</small><strong class="h4 mt-2">{{ number_format((float) $metrics['debits'], 2) }}</strong></div></div>
        <div class="col-md-4"><div class="card card-body border-0 shadow-sm"><small class="text-muted">Ledger credits</small><strong class="h4 mt-2">{{ number_format((float) $metrics['credits'], 2) }}</strong></div></div>
    </div>

    <div class="row g-4">
        @foreach ($reportGroups as $group)
            @continue(auth()->user()->cannot($group['permission']))
            <div class="col-xl-4 col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5 mb-3">{{ $group['label'] }}</h2>
                        <div class="list-group list-group-flush">
                            @foreach ($group['reports'] as $slug => $item)
                                @php($permission = $item['permission'] ?? $group['permission'])
                                @continue(auth()->user()->cannot($permission))
                                <div class="list-group-item px-0 d-flex justify-content-between align-items-center gap-3 {{ $slug === $report ? 'fw-semibold' : '' }}">
                                    <a href="{{ route('reports.index', ['report' => $slug]) }}">{{ $item['label'] }}</a>
                                    <span class="text-nowrap">
                                        <a class="btn btn-sm btn-link" href="{{ route('reports.pdf', ['report' => $slug]) }}" aria-label="Download {{ $item['label'] }} PDF"><i class="mdi mdi-file-pdf"></i></a>
                                        <a class="btn btn-sm btn-link text-success" href="{{ route('reports.export', ['report' => $slug]) }}" aria-label="Export {{ $item['label'] }} Excel"><i class="mdi mdi-file-excel"></i></a>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

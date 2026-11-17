@extends('layouts.app')

@section('title', 'Executive Dashboard')

@section('content')
    <div class="page-heading">
        <div><span class="eyebrow">Institution overview</span><h1>Executive dashboard</h1><p>Live portfolio, collection, risk and workflow indicators.</p></div>
        <div class="heading-actions">@can('report.portfolio')<a class="btn btn-light" href="{{ route('reports.index', ['report' => 'portfolio-summary']) }}"><i class="mdi mdi-chart-bar"></i> Reports</a>@endcan @can('customer.create')<a class="btn btn-primary" href="{{ route('customers.create') }}"><i class="mdi mdi-plus"></i> New customer</a>@endcan</div>
    </div>

    <section class="metric-grid" aria-label="Key performance indicators">
        @php
            $cards = [
                ['label' => 'Gross loan portfolio', 'value' => number_format($metrics['portfolio']), 'prefix' => config('microfinance.currency'), 'icon' => 'mdi-finance', 'tone' => 'blue'],
                ['label' => 'Active borrowers', 'value' => number_format($metrics['activeBorrowers']), 'icon' => 'mdi-account-multiple-outline', 'tone' => 'violet'],
                ['label' => 'Disbursed this month', 'value' => number_format($metrics['disbursedThisMonth']), 'prefix' => config('microfinance.currency'), 'icon' => 'mdi-bank-transfer-out', 'tone' => 'green'],
                ['label' => 'Collected today', 'value' => number_format($metrics['collectedToday']), 'prefix' => config('microfinance.currency'), 'icon' => 'mdi-cash-check', 'tone' => 'cyan'],
                ['label' => 'Portfolio at risk 30', 'value' => number_format($metrics['par30'], 2).'%', 'icon' => 'mdi-chart-timeline-variant-shimmer', 'tone' => 'orange'],
                ['label' => 'Pending approvals', 'value' => number_format($metrics['pendingApprovals']), 'icon' => 'mdi-file-sign', 'tone' => 'yellow'],
                ['label' => 'Loans in arrears', 'value' => number_format($metrics['loansInArrears']), 'icon' => 'mdi-calendar-alert', 'tone' => 'red'],
                ['label' => 'Total arrears', 'value' => number_format($metrics['arrearsAmount']), 'prefix' => config('microfinance.currency'), 'icon' => 'mdi-alert-circle-outline', 'tone' => 'rose'],
            ];
        @endphp
        @foreach ($cards as $card)
            <article class="metric-card tone-{{ $card['tone'] }}">
                <div class="metric-top"><span class="metric-icon"><i class="mdi {{ $card['icon'] }}"></i></span><span class="metric-more"><i class="mdi mdi-dots-horizontal"></i></span></div>
                <span class="metric-label">{{ $card['label'] }}</span>
                <strong class="metric-value">@isset($card['prefix'])<small>{{ $card['prefix'] }}</small>@endisset {{ $card['value'] }}</strong>
                <span class="metric-caption">Updated from posted transactions</span>
            </article>
        @endforeach
    </section>

    <div class="dashboard-grid">
        @if(auth()->user()->can('dashboard.executive') || auth()->user()->can('report.financial'))
        <section class="panel" style="grid-column:1/-1;">
            <div class="panel-header"><h2>Investment and lending returns to date</h2></div>
            <div class="metric-grid">
                @foreach (['totalLoans' => 'Total loans', 'totalPrincipal' => 'Total principal lent', 'cashDisbursed' => 'Actual cash disbursed', 'principalRecovered' => 'Principal recovered', 'totalRepaid' => 'Total repayments received', 'loanIncome' => 'Loan income earned', 'recordedExpenses' => 'Recorded expenses', 'netLendingProfit' => 'Net lending profit', 'recordedCapital' => 'Recorded owner capital', 'capitalWithProfit' => 'Recorded capital + lending profit'] as $key => $label)
                    <article class="metric-card"><span class="metric-label">{{ $label }}</span><strong class="metric-value">{{ $key === 'totalLoans' ? number_format($metrics[$key]) : number_format($metrics[$key], 2) }}</strong></article>
                @endforeach
            </div>
            <div class="p-3 text-muted">
                <p>Loan income = paid interest, fees and penalties + deducted disbursement fees + approved assurance. Net lending profit = loan income minus recorded expenses. Pending payments and future interest are excluded; reversals reduce the totals.</p>
                <p>Total principal and cash disbursed are cumulative lending activity, including reinvested money. Owner capital comes from posted equity accounts. Capital plus lending profit is complete only when capital, withdrawals and expenses have been recorded; it excludes other business income. Historical imports contribute their recorded payments and upfront income.</p>
            </div>
        </section>
        @endif
        <section class="panel portfolio-panel">
            <div class="panel-header"><div><span class="eyebrow">Portfolio quality</span><h2>Risk distribution</h2></div><button class="btn btn-sm btn-light">View report</button></div>
            <div class="risk-overview">
                <div class="donut" style="--value: {{ min($metrics['par30'], 100) }}"><div><strong>{{ number_format($metrics['par30'], 1) }}%</strong><span>PAR 30</span></div></div>
                <div class="risk-copy"><h3>Portfolio health</h3><p>PAR 30 is calculated from outstanding principal on loans overdue by 30 days or more.</p><div class="risk-legend"><span><i class="good"></i> Performing portfolio</span><span><i class="risk"></i> At risk 30+</span></div></div>
            </div>
        </section>

        <section class="panel quick-panel">
            <div class="panel-header"><div><span class="eyebrow">Shortcuts</span><h2>Quick actions</h2></div></div>
            <div class="quick-grid">
                @can('repayment.approve')<a class="quick-action" href="{{ route('payment-submissions.index') }}"><i class="mdi mdi-cash-check"></i><span>Review customer payments</span></a>@endcan
                @can('loan.import')<a class="quick-action" href="{{ route('loan-imports.index') }}"><i class="mdi mdi-file-excel-outline"></i><span>Import historical loans</span></a>@endcan
                @foreach ([['New customer','mdi-account-plus-outline'],['Loan application','mdi-file-document-edit-outline'],['Receive payment','mdi-cash-plus'],['Open till','mdi-cash-register'],['Approval queue','mdi-check-decagram-outline'],['Arrears queue','mdi-calendar-alert']] as [$label, $icon])
                    <button type="button" class="quick-action"><i class="mdi {{ $icon }}"></i><span>{{ $label }}</span></button>
                @endforeach
            </div>
        </section>
    </div>

    <section class="panel table-panel" aria-label="Portfolio analytics">
        <div class="panel-header">
            <div><span class="eyebrow">Business intelligence</span><h2>Portfolio indicators</h2></div>
        </div>
        <div class="p-4">
            @php
                $riskIndicators = [
                    ['label' => 'Portfolio at risk (30+ days)', 'value' => min(100, max(0, (float) $metrics['par30'])), 'tone' => 'danger'],
                    ['label' => 'Portfolio not at risk (30+ days)', 'value' => 100 - min(100, max(0, (float) $metrics['par30'])), 'tone' => 'success'],
                ];
            @endphp
            @foreach ($riskIndicators as $indicator)
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>{{ $indicator['label'] }}</span>
                        <strong>{{ number_format($indicator['value'], 1) }}%</strong>
                    </div>
                    <div class="progress" role="progressbar" aria-label="{{ $indicator['label'] }}"
                         aria-valuenow="{{ $indicator['value'] }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-{{ $indicator['tone'] }}"
                             style="width: {{ $indicator['value'] }}%"></div>
                    </div>
                </div>
            @endforeach
            <small class="text-muted">Calculated from the current portfolio-at-risk metric. This is not a historical trend.</small>
        </div>
    </section>

    <section class="panel table-panel">
        <div class="panel-header"><div><span class="eyebrow">Collections watchlist</span><h2>Priority delinquent loans</h2></div><button class="btn btn-sm btn-light">Open arrears queue</button></div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Loan</th><th>Customer</th><th>Outstanding</th><th>Arrears</th><th>Days past due</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($watchlist as $loan)
                        <tr><td><span class="reference">{{ $loan->loan_number }}</span></td><td><strong>{{ $loan->customer->first_name }} {{ $loan->customer->last_name }}</strong><small>{{ $loan->customer->customer_number }}</small></td><td>{{ config('microfinance.currency') }} {{ number_format((float) $loan->outstanding_principal) }}</td><td>{{ config('microfinance.currency') }} {{ number_format((float) $loan->arrears_amount) }}</td><td><strong>{{ $loan->days_past_due }} days</strong></td><td><span class="status-badge danger">{{ $loan->status->value }}</span></td></tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><i class="mdi mdi-shield-check-outline"></i><strong>No delinquent loans</strong><span>The watchlist will populate automatically from active loan transactions.</span></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

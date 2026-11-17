@extends('layouts.app')
@section('title', $definition['label'].' report')
@section('content')
    @if ($report === 'bot-periodic-return')
        <div class="alert border-0 shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4" style="background:#eaf6f2;color:#174e43;border-radius:16px">
            <div><strong class="d-block">BOT quarterly compliance workspace is available</strong><span>Calculate quarter-end stocks, quarterly flows, PAR provisions, borrower breakdowns and validation checks.</span></div>
            <a class="btn btn-primary" href="{{ route('bot-returns.index') }}">Open BOT Returns <i class="mdi mdi-arrow-right"></i></a>
        </div>
    @endif
    <header class="report-hero mb-4">
        <div><span class="report-eyebrow">{{ $definition['group_label'] }} · LIVE ANALYTICS</span><h1>{{ $definition['label'] }}</h1><p>Decision-ready performance, risk and financial insights with auditable PDF and Excel exports.</p><div class="report-period-chip"><i class="mdi mdi-calendar-range"></i>{{ $reportInsights['period_label'] }}</div></div>
        <div class="d-flex gap-2 report-actions">
            <a class="btn btn-outline-primary" target="_blank" rel="noopener" href="{{ route('reports.pdf', ['report' => $report, 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}"><i class="mdi mdi-file-pdf mr-1"></i>View full PDF</a>
            <button class="btn btn-success" type="submit" form="excel-export-form"><i class="mdi mdi-file-excel mr-1"></i>Export Excel</button>
        </div>
    </header>

    <details class="report-picker mb-4">
        <summary><i class="mdi mdi-chart-box-outline"></i><span><small>Selected report</small><strong>{{ $definition['label'] }}</strong></span><i class="mdi mdi-chevron-down"></i></summary>
        <div class="report-picker-menu"><label><i class="mdi mdi-magnify"></i><input type="search" placeholder="Search reports" data-report-search></label><div class="report-picker-grid">
            @foreach($reportGroups as $group)
                @continue(auth()->user()->cannot($group['permission']))
                <section><h3>{{ $group['label'] }}</h3>@foreach($group['reports'] as $slug=>$item)@php($permission=$item['permission']??$group['permission'])@continue(auth()->user()->cannot($permission))<a href="{{ route('reports.index',['report'=>$slug]) }}" data-report-name="{{ Str::lower($item['label']) }}" class="{{ $slug===$report?'active':'' }}"><i class="mdi mdi-file-chart-outline"></i>{{ $item['label'] }}</a>@endforeach</section>
            @endforeach
        </div></div>
    </details>

    <div class="report-filter mb-4">
        <form id="excel-export-form" class="row g-3 align-items-end" method="GET" action="{{ route('reports.export', $report) }}">
            <div class="col-xl-3 col-md-6"><label class="form-label" for="period">Reporting period</label><select class="form-select" id="period" name="period" data-report-period>@foreach(['all-time'=>'All available data','today'=>'Today','last-7-days'=>'Last 7 days','last-30-days'=>'Last 30 days','this-quarter'=>'This quarter','this-year'=>'This financial year','last-12-months'=>'Last 12 months','custom'=>'Custom date range'] as $value=>$label)<option value="{{ $value }}" @selected($period===$value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-xl-2 col-md-6" data-report-custom>
                <label class="form-label" for="from">From date</label>
                <input class="form-control" id="from" type="date" name="from" value="{{ $from->format('Y-m-d') }}">
            </div>
            <div class="col-xl-2 col-md-6" data-report-custom>
                <label class="form-label" for="to">To date</label>
                <input class="form-control" id="to" type="date" name="to" value="{{ $to->format('Y-m-d') }}">
            </div>
            <div class="col-xl-3 col-md-6"><label class="form-label">Current scope</label><div class="scope-display"><i class="mdi mdi-office-building-marker-outline"></i>{{ auth()->user()->branch?->name ?? 'All branches · Consolidated' }}</div></div>
            <div class="col-xl-2 col-md-6"><button class="btn btn-primary w-100" type="submit" formaction="{{ route('reports.index', ['report' => $report]) }}"><i class="mdi mdi-chart-box-plus-outline"></i> Run report</button></div>
        </form>
    </div>

    <div class="report-kpis mb-4">@foreach([
        ['Active loans',number_format($metrics['active_loans']),'mdi-finance','#256fbd','Open loan accounts today'],
        ['Outstanding principal','TZS '.number_format((float)$metrics['outstanding_principal'],2),'mdi-wallet-outline','#6c55ce','Principal still owed'],
        ['Disbursed','TZS '.number_format((float)$metrics['disbursements'],2),'mdi-cash-fast','#7959d1',number_format($metrics['disbursement_count']).' loans in period'],
        ['Collections','TZS '.number_format((float)$metrics['collections'],2),'mdi-cash-check','#12966f','Posted in selected period'],
        ['Repayments',number_format($metrics['repayment_count']),'mdi-receipt-text-check-outline','#078a9c','Posted repayment records'],
        ['Collection rate',number_format($reportInsights['collection_rate'],1).'%','mdi-chart-areaspline','#d18116','Collections against disbursements'],
        ['PAR over 30 days',number_format($reportInsights['portfolio_at_risk'],1).'%','mdi-alert-decagram-outline','#d14858','Outstanding principal at risk'],
        ['Open cases',number_format($metrics['open_cases']),'mdi-briefcase-clock-outline','#4d6a75','Unresolved collection cases'],
    ] as [$label,$value,$icon,$tone,$caption])<article style="--tone:{{ $tone }}"><i class="mdi {{ $icon }}"></i><span>{{ $label }}</span><strong>{{ $value }}</strong><small>{{ $caption }}</small></article>@endforeach</div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="chart-heading"><div><span>PERFORMANCE FLOW</span><h2>Disbursed vs collected</h2><p>Monthly cash movement inside the selected period.</p></div><div><span class="chart-key disbursed"></span>Disbursed <span class="chart-key collected"></span>Collected</div></div>
                    @if ($cashflowTrend->isEmpty())
                        <div class="report-empty"><i class="mdi mdi-chart-bell-curve-cumulative"></i><strong>No transaction flow in this period</strong><span>Choose a wider date range or verify disbursement and repayment dates.</span></div>
                    @else
                        <div class="market-chart" role="img" aria-label="Monthly disbursement and collection market trend">
                            <div class="market-axis"><span>TZS {{ number_format($maximumCashflow,0) }}</span><span>TZS {{ number_format($maximumCashflow/2,0) }}</span><span>TZS 0</span></div>
                            <svg viewBox="0 0 1000 250" preserveAspectRatio="none" aria-hidden="true"><defs><linearGradient id="collectionArea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#12a27a" stop-opacity=".28"/><stop offset="1" stop-color="#12a27a" stop-opacity="0"/></linearGradient></defs><polygon points="0,250 {{ $trendPoints['collections'] }} 1000,250" fill="url(#collectionArea)"/><polyline points="{{ $trendPoints['disbursements'] }}" class="line-disbursed"/><polyline points="{{ $trendPoints['collections'] }}" class="line-collected"/></svg>
                            <div class="market-labels">@foreach($cashflowTrend as $month)<span>{{ \Carbon\CarbonImmutable::parse($month->period.'-01')->format('M y') }}</span>@endforeach</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-1">Loan portfolio mix</h2>
                    <p class="text-muted small">Number of loans by recorded status.</p>
                    @forelse ($loanStatusDistribution as $item)
                        @php($share = $totalLoans > 0 ? (float) $item->total / $totalLoans * 100 : 0)
                        <div class="d-flex justify-content-between small mb-1">
                            <span>{{ $item->status }}</span>
                            <strong>{{ number_format($item->total) }} ({{ number_format($share, 1) }}%)</strong>
                        </div>
                        <div class="progress mb-3" style="height: 10px" role="progressbar"
                             aria-valuenow="{{ round($share) }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: {{ $share }}%"></div>
                        </div>
                    @empty
                        <div class="alert alert-light">No loans available for this branch.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="insight-grid mb-4">
        <section class="insight-card"><div class="insight-title"><div><span>PRODUCT PERFORMANCE</span><h2>Disbursement by loan product</h2><p>Products ranked by principal released during the selected period.</p></div><i class="mdi mdi-package-variant-closed"></i></div>@forelse($productPerformance as $item)<div class="rank-row"><div><strong>{{ $item->product }}</strong><span>{{ number_format($item->loans) }} loans · Outstanding TZS {{ number_format($item->outstanding,0) }}</span></div><b>TZS {{ number_format($item->disbursed,0) }}</b></div><div class="rank-bar"><i style="width:{{ $item->disbursed/$maximumProduct*100 }}%"></i></div>@empty<div class="mini-empty">No product disbursement data in this period.</div>@endforelse</section>
        <section class="insight-card"><div class="insight-title"><div><span>CREDIT HEALTH</span><h2>Portfolio aging exposure</h2><p>Current outstanding principal grouped by days past due.</p></div><i class="mdi mdi-shield-chart-outline"></i></div>@php($riskTotal=max(1,$riskBuckets->sum('outstanding')))<div class="risk-stack">@foreach($riskBuckets as $bucket)<i style="width:{{ $bucket['outstanding']/$riskTotal*100 }}%;background:{{ $bucket['color'] }}" title="{{ $bucket['label'] }}: TZS {{ number_format($bucket['outstanding'],2) }}"></i>@endforeach</div><div class="risk-list">@foreach($riskBuckets as $bucket)<div><i style="background:{{ $bucket['color'] }}"></i><span>{{ $bucket['label'] }}</span><strong>TZS {{ number_format($bucket['outstanding'],0) }}</strong><small>{{ number_format($bucket['loans']) }} loans</small></div>@endforeach</div></section>
        <section class="insight-card"><div class="insight-title"><div><span>PAYMENT CHANNELS</span><h2>Repayment method mix</h2><p>Posted repayment value and transaction count by channel.</p></div><i class="mdi mdi-bank-transfer"></i></div>@php($methodTotal=max(1,(float)$repaymentMethods->sum('amount')))@forelse($repaymentMethods as $method)<div class="method-row"><div><strong>{{ $method->payment_method ?: 'Not specified' }}</strong><span>{{ number_format($method->total) }} repayments</span></div><b>{{ number_format((float)$method->amount/$methodTotal*100,1) }}%</b></div><div class="method-bar"><i style="width:{{ (float)$method->amount/$methodTotal*100 }}%"></i></div>@empty<div class="mini-empty">No posted repayments in this period.</div>@endforelse</section>
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
<style>
.report-picker{position:relative;width:min(420px,100%)}.report-picker>summary{display:flex;align-items:center;gap:11px;background:#fff;border:1px solid #dfe6e1;border-radius:15px;padding:12px 15px;cursor:pointer;list-style:none;box-shadow:0 6px 18px rgba(25,48,40,.06)}.report-picker>summary>i:first-child{display:grid;place-items:center;width:38px;height:38px;border-radius:11px;background:#e7f5f1;color:#176656;font-size:20px}.report-picker>summary span{flex:1}.report-picker>summary small,.report-picker>summary strong{display:block}.report-picker>summary small{color:#8793a1}.report-picker-menu{position:absolute;z-index:30;top:66px;width:min(820px,90vw);background:#fff;border:1px solid #dfe6e1;border-radius:18px;padding:17px;box-shadow:0 22px 55px rgba(20,38,32,.18)}.report-picker-menu>label{position:relative;display:block;margin-bottom:14px}.report-picker-menu>label i{position:absolute;left:13px;top:10px;color:#8793a1;font-size:20px}.report-picker-menu input{width:100%;border:1px solid #dce4df;border-radius:11px;padding:10px 12px 10px 40px}.report-picker-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}.report-picker-grid h3{text-transform:uppercase;color:#8793a1;font-size:10px;letter-spacing:.1em}.report-picker-grid a{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:9px;color:#344557;text-decoration:none}.report-picker-grid a:hover,.report-picker-grid a.active{background:#eaf6f2;color:#176656;font-weight:700}.card{border-radius:17px!important}.progress-bar{background:#176656}.btn-primary{background:#176656;border-color:#176656}@media(max-width:650px){.report-picker-grid{grid-template-columns:1fr}.report-picker-menu{position:fixed;left:5vw;top:15vh;max-height:70vh;overflow:auto}}
</style>
<style>
.report-hero{display:flex;align-items:center;justify-content:space-between;gap:25px;padding:27px 30px;border-radius:22px;background:linear-gradient(125deg,#123f38,#176656 68%,#29957c);color:#fff;box-shadow:0 18px 42px #173f3525}.report-eyebrow{font-size:10px;font-weight:800;letter-spacing:.14em;color:#b9e3d8}.report-hero h1{margin:5px 0;font:700 32px Georgia,serif}.report-hero p{color:#d5ebe5;margin:0}.report-period-chip{display:inline-flex;align-items:center;gap:7px;margin-top:14px;padding:7px 11px;border:1px solid #ffffff42;border-radius:20px;background:#ffffff14;font-size:12px}.report-actions .btn{white-space:nowrap}.report-actions .btn-outline-primary{border-color:#d5ebe5;color:#fff}.report-actions .btn-outline-primary:hover{background:#fff;color:#176656}.report-filter{padding:18px 20px;background:#fff;border:1px solid #dfe7e2;border-radius:18px;box-shadow:0 8px 25px #1c3c3212}.report-filter .form-label{font-size:11px;font-weight:800;text-transform:uppercase;color:#738179}.report-filter .form-control,.report-filter .form-select,.scope-display{min-height:45px;border:1px solid #d9e2dd;border-radius:11px}.scope-display{display:flex;align-items:center;gap:8px;padding:10px 12px;background:#f7f9f8;color:#52625a}.report-kpis{display:grid;grid-template-columns:repeat(6,1fr);gap:13px}.report-kpis article{position:relative;padding:17px;background:#fff;border:1px solid #dde6e1;border-top:3px solid var(--tone);border-radius:16px;box-shadow:0 8px 22px #1c3c320e}.report-kpis article>i{position:absolute;right:13px;top:13px;display:grid;place-items:center;width:35px;height:35px;border-radius:10px;background:color-mix(in srgb,var(--tone) 11%,white);color:var(--tone);font-size:19px}.report-kpis span,.report-kpis small,.report-kpis strong{display:block}.report-kpis span{max-width:70%;color:#75827c;font-size:11px;font-weight:700}.report-kpis strong{margin:17px 0 5px;font-size:18px}.report-kpis small{color:#8a9690;font-size:10px}.chart-heading{display:flex;justify-content:space-between;gap:15px}.chart-heading>div:first-child>span{font-size:10px;font-weight:800;letter-spacing:.12em;color:#176656}.chart-heading h2{font-size:20px;margin:3px 0}.chart-heading p{color:#7d8983;font-size:12px}.chart-heading>div:last-child{font-size:11px;color:#6d7973}.chart-key{display:inline-block;width:9px;height:9px;border-radius:50%;margin:0 4px 0 10px}.chart-key.disbursed,.flow-chart i.disbursed{background:#765bd4}.chart-key.collected,.flow-chart i.collected{background:#13a27a}.flow-chart{display:flex;align-items:flex-end;gap:8px;height:240px;overflow:auto;padding:12px 4px 0;border-bottom:1px solid #e2e8e4;background:repeating-linear-gradient(to top,transparent 0,transparent 54px,#eef2f0 55px)}.flow-month{flex:1;min-width:48px;text-align:center}.flow-month>div{height:202px;display:flex;align-items:flex-end;justify-content:center;gap:4px}.flow-month i{display:block;width:34%;max-width:24px;border-radius:7px 7px 0 0}.flow-month span{display:block;margin-top:7px;font-size:10px;color:#78857f}.report-empty{min-height:230px;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#84918b;text-align:center}.report-empty i{font-size:38px}.report-empty strong,.report-empty span{display:block}.report-empty span{font-size:12px}.report-picker{margin-top:-2px}@media(max-width:1250px){.report-kpis{grid-template-columns:repeat(3,1fr)}}@media(max-width:760px){.report-hero,.chart-heading{display:block}.report-actions{margin-top:18px}.report-kpis{grid-template-columns:1fr}.report-hero{padding:22px}.flow-chart{height:210px}}
.report-hero,.report-hero h1,.report-hero p,.report-hero .report-eyebrow,.report-hero .report-period-chip{color:#fff!important}.report-hero .btn-success{background:#10594c;border-color:#10594c;color:#fff}
.market-chart{position:relative;height:270px;margin-top:4px;padding-left:58px;background:repeating-linear-gradient(to bottom,#edf2ef 0,#edf2ef 1px,transparent 1px,transparent 62px)}.market-chart svg{width:calc(100% - 58px);height:220px;overflow:visible}.market-chart polyline{fill:none;stroke-width:5;vector-effect:non-scaling-stroke;stroke-linejoin:round;stroke-linecap:round}.line-disbursed{stroke:#765bd4}.line-collected{stroke:#13a27a}.market-axis{position:absolute;left:0;top:0;height:220px;display:flex;flex-direction:column;justify-content:space-between;color:#89958f;font-size:9px}.market-labels{display:flex;justify-content:space-between;margin-left:0;width:calc(100% - 58px);overflow:hidden}.market-labels span{color:#7a8780;font-size:9px;white-space:nowrap}.insight-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.insight-card{min-width:0;padding:22px;background:#fff;border:1px solid #dde6e1;border-radius:18px;box-shadow:0 9px 27px #1c3c3210}.insight-title{display:flex;justify-content:space-between;gap:12px;margin-bottom:18px}.insight-title>div>span{color:#176656;font-size:9px;font-weight:800;letter-spacing:.12em}.insight-title h2{margin:3px 0;font-size:18px}.insight-title p{margin:0;color:#7b8881;font-size:11px}.insight-title>i{display:grid;place-items:center;flex:0 0 40px;height:40px;border-radius:11px;background:#eaf6f2;color:#176656;font-size:21px}.rank-row,.method-row{display:flex;justify-content:space-between;gap:12px;align-items:flex-end}.rank-row strong,.rank-row span,.method-row strong,.method-row span{display:block}.rank-row strong,.method-row strong{font-size:12px}.rank-row span,.method-row span{color:#89958f;font-size:9px}.rank-row>b,.method-row>b{font-size:11px;white-space:nowrap}.rank-bar,.method-bar{height:6px;margin:7px 0 14px;border-radius:8px;background:#edf1ef;overflow:hidden}.rank-bar i,.method-bar i{display:block;height:100%;border-radius:8px;background:linear-gradient(90deg,#176656,#28a98d)}.method-bar i{background:linear-gradient(90deg,#7458d0,#a18de4)}.risk-stack{display:flex;height:15px;border-radius:10px;overflow:hidden;background:#edf1ef;margin:27px 0 20px}.risk-stack i{display:block;height:100%}.risk-list{display:grid;grid-template-columns:1fr 1fr;gap:12px}.risk-list>div{display:grid;grid-template-columns:9px 1fr;column-gap:7px}.risk-list>div>i{width:9px;height:9px;border-radius:50%;margin-top:3px}.risk-list span,.risk-list strong,.risk-list small{font-size:10px}.risk-list strong,.risk-list small{grid-column:2}.risk-list small{color:#8b9791}.mini-empty{display:grid;place-items:center;min-height:170px;color:#89958f;font-size:12px;text-align:center}@media(max-width:1200px){.insight-grid{grid-template-columns:1fr 1fr}.report-kpis{grid-template-columns:repeat(4,1fr)}}@media(max-width:760px){.insight-grid{grid-template-columns:1fr}.market-chart{padding-left:45px}.market-chart svg,.market-labels{width:calc(100% - 45px)}}
</style>
<script>document.addEventListener('DOMContentLoaded',function(){const search=document.querySelector('[data-report-search]');search?.addEventListener('input',function(){document.querySelectorAll('[data-report-name]').forEach(function(link){link.hidden=!link.dataset.reportName.includes(search.value.toLowerCase())})});const period=document.querySelector('[data-report-period]');const sync=function(){document.querySelectorAll('[data-report-custom]').forEach(function(field){field.hidden=period?.value!=='custom'})};period?.addEventListener('change',sync);sync()});</script>
@endsection

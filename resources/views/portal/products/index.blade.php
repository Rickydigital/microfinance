@extends('layouts.portal')
@section('title','Loan products')
@section('content')

<div class="page-head">
    <div>
        <span class="eyebrow">Catalogue</span>
        <h1>Available loan products</h1>
        <p>Rates, fees, terms, security, and penalties, exactly as configured by {{ config('microfinance.institution_name') }} — no surprises after you apply.</p>
    </div>
    <a href="{{ route('portal.applications.create') }}" class="btn btn-accent">Apply now</a>
</div>

@if($versions->isEmpty())
    <div class="card">
        <div class="empty">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
            <h3>Nothing open for applications right now</h3>
            <p>Your branch will notify you as soon as a product opens. In the meantime, feel free to contact us.</p>
        </div>
    </div>
@else
    <div class="grid grid-2">
        @foreach($versions as $version)
            <div class="card card-pad">
                <span class="eyebrow">Loan product</span>
                <h2 style="font-size:20px;margin:4px 0 6px;">{{ $version->product->name }}</h2>
                <p style="color:var(--ink-soft);font-size:13.5px;margin:0 0 16px;">{{ $version->product->description }}</p>

                <div class="grid grid-2" style="gap:12px;margin-bottom:16px;">
                    <div>
                        <span class="eyebrow">Amount</span>
                        <div class="money" style="font-size:14px;">{{ number_format((float)$version->minimum_amount,0) }} – {{ number_format((float)$version->maximum_amount,0) }}</div>
                    </div>
                    <div>
                        <span class="eyebrow">Term</span>
                        <div style="font-size:14px;font-weight:600;">{{ $version->minimum_term }}–{{ $version->maximum_term }} {{ $version->term_unit }}</div>
                    </div>
                    <div>
                        <span class="eyebrow">Interest</span>
                        <div style="font-size:14px;font-weight:600;">{{ number_format((float)$version->interest_rate*100,2) }}% {{ $version->rate_basis }}</div>
                    </div>
                    <div>
                        <span class="eyebrow">Method</span>
                        <div style="font-size:14px;font-weight:600;">{{ $version->interest_method }} · {{ $version->repayment_frequency }}</div>
                    </div>
                    <div>
                        <span class="eyebrow">Guarantors</span>
                        <div style="font-size:14px;font-weight:600;">{{ $version->required_guarantors }} required</div>
                    </div>
                    <div>
                        <span class="eyebrow">Collateral</span>
                        <div style="font-size:14px;font-weight:600;">{{ $version->requires_collateral ? 'Required' : 'Not required' }}</div>
                    </div>
                </div>

                <div style="border-top:1px solid var(--line);padding-top:14px;">
                    <span class="eyebrow">Fees</span>
                    @forelse($version->fees as $fee)
                        <div style="display:flex;justify-content:space-between;font-size:13px;padding:5px 0;">
                            <span>{{ $fee->name }} <span style="color:var(--ink-faint);">· {{ $fee->collection_point }}</span></span>
                            <span class="mono">{{ $fee->calculation_method === 'Percentage' ? number_format((float)$fee->value*100,2).'%' : number_format((float)$fee->value,2) }}</span>
                        </div>
                    @empty
                        <p style="color:var(--ink-faint);font-size:13px;margin:6px 0 0;">No configured fees.</p>
                    @endforelse
                </div>

                <div style="border-top:1px solid var(--line);padding-top:14px;margin-top:10px;">
                    <span class="eyebrow">Late-payment penalties</span>
                    @forelse($version->penaltyRules->where('is_active',true) as $rule)
                        <div style="display:flex;justify-content:space-between;font-size:13px;padding:5px 0;">
                            <span>{{ $rule->name }} <span style="color:var(--ink-faint);">· after {{ $rule->grace_days }} grace day(s)</span></span>
                            <span class="mono">{{ $rule->calculation_method === 'Percentage' ? number_format((float)$rule->value*100,2).'%' : number_format((float)$rule->value,2) }}</span>
                        </div>
                    @empty
                        <p style="color:var(--ink-faint);font-size:13px;margin:6px 0 0;">No active penalty.</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection
@extends('layouts.app')
@section('title', 'Loan calculator')
@section('content')
<div class="calc-page">
    @include('partials.alerts')
    <header class="calc-hero">
        <div><span>Planning workspace</span><h1>Loan calculator</h1><p>Price a loan with the same product rules used during application and review affordability before you proceed.</p></div>
        <div class="calc-hero-badge"><i class="mdi mdi-shield-check-outline"></i><span><strong>Policy-driven</strong><small>Rates, fees and limits are read from the active product version.</small></span></div>
    </header>

    @if($versions->isEmpty())
        <div class="alert alert-info">No active loan product version is available. Activate a product before preparing a quotation.</div>
    @else
    <div class="calc-layout">
        <form class="calc-panel calc-form" method="GET" action="{{ route('loan-calculator.index') }}" data-auto-calculator>
            <div class="calc-heading"><span>01</span><div><h2>Loan request</h2><p>Choose the product, amount and repayment term.</p></div></div>
            <label>Loan product
                <select name="loan_product_version_id" id="calculator-product" required>
                    @foreach($versions as $version)
                        <option value="{{ $version->id }}" @selected((int)request('loan_product_version_id', $selectedVersion?->id) === $version->id)
                            data-min="{{ $version->minimum_amount }}" data-max="{{ $version->maximum_amount }}" data-min-term="{{ $version->minimum_term }}" data-max-term="{{ $version->maximum_term }}" data-term-unit="{{ $version->term_unit }}">
                            {{ $version->product->name }} · v{{ $version->version }}
                        </option>
                    @endforeach
                </select>
            </label>
            <div class="calc-fields">
                <label>Principal amount (TZS)<input type="number" name="amount" value="{{ old('amount', request('amount')) }}" min="1" step="1000" required><small id="amount-policy"></small></label>
                <label>Duration<input type="number" name="term" value="{{ old('term', request('term')) }}" min="1" required><small id="term-policy"></small></label>
            </div>
            <div class="calc-heading calc-affordability"><span>02</span><div><h2>Affordability</h2><p>Optional, but recommended for a responsible quotation.</p></div></div>
            <div class="calc-fields">
                <label>Monthly income (TZS)<input type="number" name="monthly_income" value="{{ old('monthly_income', request('monthly_income', 0)) }}" min="0" step="1000"></label>
                <label>Monthly expenses (TZS)<input type="number" name="monthly_expenses" value="{{ old('monthly_expenses', request('monthly_expenses', 0)) }}" min="0" step="1000"></label>
                <label>Existing debt payment (TZS)<input type="number" name="existing_debt_payment" value="{{ old('existing_debt_payment', request('existing_debt_payment', 0)) }}" min="0" step="1000"></label>
            </div>
            <div class="calc-auto"><i class="mdi mdi-lightning-bolt"></i><span><strong>Automatic calculation</strong><small>Results refresh after you finish changing a value.</small></span></div>
            <button class="calc-button" type="submit"><i class="mdi mdi-calculator-variant-outline"></i> Recalculate now</button>
        </form>

        <section class="calc-panel calc-results">
            @if($quote)
                <div class="calc-result-head"><div><span>Estimated quotation</span><h2>TZS {{ number_format((float)$quote['installment'], 2) }}</h2><p>Highest {{ strtolower($quote['repayment_frequency']) }} instalment · {{ $quote['periods'] }} payments</p></div><div class="calc-status {{ $quote['affordable'] ? 'good' : 'warn' }}"><i class="mdi {{ $quote['affordable'] ? 'mdi-check-circle-outline' : 'mdi-alert-circle-outline' }}"></i>{{ $quote['affordable'] ? 'Within product and affordability rules' : 'Review limits or affordability' }}</div></div>
                <div class="calc-metrics">
                    <article><span>Principal</span><strong>TZS {{ number_format((float)$quote['principal'], 2) }}</strong></article>
                    <article><span>Total interest</span><strong>TZS {{ number_format((float)$quote['total_interest'], 2) }}</strong></article>
                    <article><span>Fees & assurance</span><strong>TZS {{ number_format((float)$quote['total_fees'], 2) }}</strong></article>
                    <article><span>Total repayable</span><strong>TZS {{ number_format((float)$quote['total_repayment'], 2) }}</strong></article>
                    <article><span>Estimated cash received</span><strong>TZS {{ number_format((float)$quote['estimated_net_disbursement'], 2) }}</strong></article>
                    <article><span>Maximum affordable instalment</span><strong>TZS {{ number_format((float)$quote['maximum_installment'], 2) }}</strong></article>
                </div>
                <div class="calc-breakdown">
                    <div><h3>How this quotation works</h3><p>{{ $quote['formula'] }}</p></div>
                    <dl><div><dt>Interest method</dt><dd>{{ $quote['interest_method'] }}</dd></div><div><dt>Rate basis</dt><dd>{{ $quote['rate_basis'] }}</dd></div><div><dt>Configured rate</dt><dd>{{ number_format((float)$quote['annual_interest_rate'] * 100, 2) }}%</dd></div><div><dt>Disposable income</dt><dd>TZS {{ number_format((float)$quote['disposable_income'], 2) }}</dd></div></dl>
                </div>
                @if(count($quote['fees']))<div class="calc-fees"><h3>Charges included</h3>@foreach($quote['fees'] as $fee)<div><span>{{ $fee['name'] }} <small>{{ $fee['collection_point'] }}</small></span><strong>TZS {{ number_format((float)$fee['amount'], 2) }}</strong></div>@endforeach</div>@endif
            @else
                <div class="calc-empty"><i class="mdi mdi-finance"></i><h2>Your quotation will appear here</h2><p>Enter the request details and calculate to see instalments, total interest, charges, net proceeds and affordability.</p></div>
            @endif
        </section>
    </div>

    @if($quote)
    <section class="calc-panel calc-schedule">
        <div class="calc-heading"><span>03</span><div><h2>Projected repayment schedule</h2><p>A transparent period-by-period estimate. Final dates are assigned at disbursement.</p></div></div>
        <div class="table-responsive"><table><thead><tr><th>Instalment</th><th>Opening principal</th><th>Principal</th><th>Interest</th><th>Fees</th><th>Amount due</th><th>Closing principal</th></tr></thead><tbody>
            @foreach($quote['projected_schedule'] as $row)<tr><td>#{{ $row['number'] }}</td><td>TZS {{ number_format((float)$row['opening_principal'], 2) }}</td><td>TZS {{ number_format((float)$row['principal'], 2) }}</td><td>TZS {{ number_format((float)$row['interest'], 2) }}</td><td>TZS {{ number_format((float)$row['fees'], 2) }}</td><td><strong>TZS {{ number_format((float)$row['installment'], 2) }}</strong></td><td>TZS {{ number_format((float)$row['closing_principal'], 2) }}</td></tr>@endforeach
        </tbody></table></div>
    </section>
    @endif
    @endif
</div>
<style>
.calc-page{color:#17271f}.calc-hero{display:flex;justify-content:space-between;align-items:center;gap:25px;margin-bottom:22px;padding:28px 30px;border-radius:22px;background:linear-gradient(125deg,#123f38,#176656 65%,#2b947d);color:#fff;box-shadow:0 18px 42px #173f3525}.calc-hero>div:first-child>span{color:#c2e8df;font-size:10px;font-weight:800;letter-spacing:.15em;text-transform:uppercase}.calc-hero h1{margin:5px 0;color:#fff!important;font:700 34px Georgia,serif}.calc-hero p{max-width:720px;margin:0;color:#e0f2ed}.calc-hero-badge{display:flex;align-items:center;gap:11px;max-width:290px;padding:13px 15px;border:1px solid #ffffff42;border-radius:15px;background:#ffffff12}.calc-hero-badge>i{font-size:28px}.calc-hero-badge span,.calc-hero-badge strong,.calc-hero-badge small{display:block}.calc-hero-badge small{color:#d8ece7}.calc-layout{display:grid;grid-template-columns:minmax(340px,.82fr) minmax(480px,1.18fr);gap:20px}.calc-panel{background:#fff;border:1px solid #dfe7e2;border-radius:20px;box-shadow:0 10px 28px #1c3c3210}.calc-form,.calc-results{padding:24px}.calc-heading{display:flex;align-items:flex-start;gap:11px;margin-bottom:18px}.calc-heading>span{display:grid;place-items:center;flex:0 0 34px;height:34px;border-radius:10px;background:#e7f5f1;color:#176656;font-weight:800}.calc-heading h2{margin:0;font-size:17px}.calc-heading p{margin:2px 0 0;color:#7c8982;font-size:11px}.calc-affordability{margin-top:25px}.calc-form label{display:block;margin-bottom:13px;color:#526159;font-size:12px;font-weight:750}.calc-form input,.calc-form select{display:block;width:100%;min-height:48px;margin-top:7px;padding:10px 12px;border:1px solid #d4dfd8;border-radius:12px;background:#fff;color:#17271f}.calc-form label small{display:block;margin-top:5px;color:#87938d;font-weight:500}.calc-fields{display:grid;grid-template-columns:1fr 1fr;gap:0 12px}.calc-auto{display:flex;align-items:center;gap:9px;margin:5px 0 12px;padding:10px 12px;border-radius:11px;background:#fff7df;color:#855900}.calc-auto>i{font-size:20px}.calc-auto span,.calc-auto strong,.calc-auto small{display:block}.calc-auto strong{font-size:11px}.calc-auto small{font-size:9px}.calc-button{width:100%;padding:13px;border:0;border-radius:12px;background:#176656;color:#fff;font-weight:800}.calc-result-head{display:flex;justify-content:space-between;gap:20px}.calc-result-head>div:first-child>span{color:#718078;font-size:10px;font-weight:800;text-transform:uppercase}.calc-result-head h2{margin:4px 0 2px;font-size:30px}.calc-result-head p{color:#7f8c85;font-size:11px}.calc-status{align-self:flex-start;padding:8px 10px;border-radius:12px;font-size:10px;font-weight:750}.calc-status.good{background:#e8f8ef;color:#117246}.calc-status.warn{background:#fff3dc;color:#a66000}.calc-metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:20px 0}.calc-metrics article{padding:14px;border-radius:13px;background:#f5f8f6}.calc-metrics span,.calc-metrics strong{display:block}.calc-metrics span{color:#78857e;font-size:9px;font-weight:750;text-transform:uppercase}.calc-metrics strong{margin-top:6px;font-size:13px}.calc-breakdown{padding:16px;border:1px solid #e1e8e4;border-radius:14px}.calc-breakdown h3,.calc-fees h3{font-size:13px}.calc-breakdown p{color:#6f7d75;font-size:10px}.calc-breakdown dl{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:12px 0 0}.calc-breakdown dl>div{display:flex;justify-content:space-between;gap:8px;font-size:10px}.calc-breakdown dt{color:#849089}.calc-breakdown dd{margin:0;font-weight:750}.calc-fees{margin-top:16px}.calc-fees>div{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #edf1ef;font-size:11px}.calc-fees small{color:#8b9791}.calc-empty{display:grid;place-items:center;min-height:470px;text-align:center;color:#7f8c85}.calc-empty i{font-size:60px;color:#61a795}.calc-empty h2{margin:0;color:#25372e}.calc-empty p{max-width:460px}.calc-schedule{margin-top:20px;padding:23px}.calc-schedule table{width:100%;border-collapse:collapse;font-size:11px}.calc-schedule th,.calc-schedule td{padding:12px;border-bottom:1px solid #e8eeea;text-align:right;white-space:nowrap}.calc-schedule th:first-child,.calc-schedule td:first-child{text-align:left}.calc-schedule th{color:#6f7d75;font-size:9px;text-transform:uppercase}.calc-schedule tbody tr:hover{background:#f8faf9}@media(max-width:1000px){.calc-layout{grid-template-columns:1fr}.calc-empty{min-height:280px}}@media(max-width:650px){.calc-hero,.calc-result-head{display:block}.calc-hero-badge{margin-top:18px}.calc-fields,.calc-metrics{grid-template-columns:1fr}.calc-breakdown dl{grid-template-columns:1fr}}
</style>
<script>document.addEventListener('DOMContentLoaded',function(){const form=document.querySelector('[data-auto-calculator]');const product=document.getElementById('calculator-product');const amount=document.querySelector('[name="amount"]');const term=document.querySelector('[name="term"]');let timer;const sync=function(){const option=product?.selectedOptions[0];if(!option)return;amount.min=option.dataset.min;amount.max=option.dataset.max;term.min=option.dataset.minTerm;term.max=option.dataset.maxTerm;document.getElementById('amount-policy').textContent='Allowed: TZS '+Number(option.dataset.min).toLocaleString()+' – '+Number(option.dataset.max).toLocaleString();document.getElementById('term-policy').textContent='Allowed: '+option.dataset.minTerm+' – '+option.dataset.maxTerm+' '+option.dataset.termUnit.toLowerCase()};const calculate=function(){clearTimeout(timer);timer=setTimeout(function(){if(amount.value&&term.value&&form.checkValidity())form.requestSubmit()},650)};form?.querySelectorAll('input,select').forEach(function(field){field.addEventListener(field.tagName==='SELECT'?'change':'input',function(){sync();calculate()})});sync()});</script>
@endsection

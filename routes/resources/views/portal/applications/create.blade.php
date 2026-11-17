@extends('layouts.portal')
@section('title','Apply for a loan')
@section('content')

<div class="page-head">
    <div>
        <span class="eyebrow">New application</span>
        <h1>Self-service loan application</h1>
        <p>Your verified economic profile is used to check affordability. Submission goes to our staff for risk assessment and approval — nothing is disbursed automatically.</p>
    </div>
</div>

@if(!$profile)
    <div class="alert alert-danger">No current economic profile is available for your account. Contact your branch before applying.</div>
@endif

<div class="grid" style="grid-template-columns:1.5fr 1fr;align-items:start;">
    <form class="card card-pad" method="POST" action="{{ route('portal.applications.store') }}">
        @csrf

        <div class="field">
            <label for="portal-product">Loan product</label>
            <select class="input" id="portal-product" name="loan_product_version_id" required>
                <option value="">Select product</option>
                @foreach($versions as $version)
                    <option value="{{ $version->id }}"
                        data-min="{{ $version->minimum_amount }}" data-max="{{ $version->maximum_amount }}"
                        data-min-term="{{ $version->minimum_term }}" data-max-term="{{ $version->maximum_term }}"
                        data-unit="{{ $version->term_unit }}" data-rate="{{ $version->interest_rate }}">
                        {{ $version->product->name }} · {{ number_format((float)$version->interest_rate*100,2) }}% {{ $version->rate_basis }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-2">
            <div class="field">
                <label for="portal-amount">Requested amount</label>
                <input class="input" id="portal-amount" type="number" min="0.01" step="0.01" name="requested_amount" value="{{ old('requested_amount') }}" required>
                <div class="hint" id="portal-amount-help">Select a product to see the allowed range.</div>
            </div>
            <div class="field">
                <label for="portal-term">Requested term</label>
                <input class="input" id="portal-term" type="number" min="1" name="requested_term" value="{{ old('requested_term') }}" required>
                <div class="hint" id="portal-term-help">Select a product to see the allowed range.</div>
            </div>
        </div>

        <div class="field">
            <label for="portal-purpose">Loan purpose</label>
            <textarea class="input" id="portal-purpose" name="purpose" rows="4" minlength="10" required placeholder="Tell us what the loan is for — e.g. restocking inventory, buying equipment, expanding a stall.">{{ old('purpose') }}</textarea>
        </div>

        <button class="btn btn-accent" style="margin-top:6px;" @disabled(!$profile || $versions->isEmpty())>
            Submit for assessment
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
    </form>

    <div class="card card-pad" style="position:sticky;top:24px;">
        <span class="eyebrow">Indicative estimate</span>
        <p style="font-size:12.5px;color:var(--ink-faint);margin:6px 0 16px;">A rough guide only — your actual quote, with fees and exact schedule, is confirmed after submission.</p>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <div>
                <span class="eyebrow">Product</span>
                <div id="est-product" style="font-weight:600;font-size:14px;">—</div>
            </div>
            <div>
                <span class="eyebrow">Estimated total interest</span>
                <div id="est-interest" class="money" style="font-size:18px;color:var(--primary);">—</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var p = document.getElementById('portal-product'),
        a = document.getElementById('portal-amount'),
        t = document.getElementById('portal-term'),
        amountHelp = document.getElementById('portal-amount-help'),
        termHelp = document.getElementById('portal-term-help'),
        estProduct = document.getElementById('est-product'),
        estInterest = document.getElementById('est-interest');

    function updateEstimate() {
        var o = p.selectedOptions[0];
        if (!o || !o.value) { estProduct.textContent = '—'; estInterest.textContent = '—'; return; }
        estProduct.textContent = o.textContent.trim();
        var amount = parseFloat(a.value), term = parseFloat(t.value), rate = parseFloat(o.dataset.rate);
        if (amount > 0 && term > 0 && rate >= 0) {
            var interest = amount * rate * (term / 12);
            estInterest.textContent = '{{ config('microfinance.currency') }} ' + interest.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
        } else {
            estInterest.textContent = '—';
        }
    }

    p.addEventListener('change', function () {
        var o = p.selectedOptions[0];
        a.min = o.dataset.min || ''; a.max = o.dataset.max || '';
        t.min = o.dataset.minTerm || ''; t.max = o.dataset.maxTerm || '';
        amountHelp.textContent = 'Allowed: ' + (o.dataset.min || 0) + ' to ' + (o.dataset.max || 0);
        termHelp.textContent = 'Allowed: ' + (o.dataset.minTerm || 0) + ' to ' + (o.dataset.maxTerm || 0) + ' ' + (o.dataset.unit || 'units');
        updateEstimate();
    });
    a.addEventListener('input', updateEstimate);
    t.addEventListener('input', updateEstimate);
});
</script>

@endsection
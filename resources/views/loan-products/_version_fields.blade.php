@php($version = $version ?? null)
<section class="product-form-section">
    <div class="product-section-title"><i class="mdi mdi-cash-sync"></i><div><strong>Loan limits & repayment</strong><small>Amounts, duration and repayment schedule offered to borrowers.</small></div></div>
    <div class="product-form-grid">
        <label>Minimum amount (TZS)<input class="product-money" name="minimum_amount" value="{{ old('minimum_amount', $version?->minimum_amount ?? 50000) }}" inputmode="numeric" required></label>
        <label>Maximum amount (TZS)<input class="product-money" name="maximum_amount" value="{{ old('maximum_amount', $version?->maximum_amount ?? 5000000) }}" inputmode="numeric" required></label>
        <label>Minimum duration<input type="number" name="minimum_term" value="{{ old('minimum_term', $version?->minimum_term ?? 1) }}" min="1" required></label>
        <label>Maximum duration<input type="number" name="maximum_term" value="{{ old('maximum_term', $version?->maximum_term ?? 12) }}" min="1" required></label>
        <label>Duration unit<select name="term_unit" required>@foreach (['Days', 'Weeks', 'Months'] as $option)<option @selected(old('term_unit', $version?->term_unit ?? 'Months') === $option)>{{ $option }}</option>@endforeach</select></label>
        <label>Repayment cycle<select name="repayment_frequency" required>@foreach (['Daily', 'Weekly', 'Biweekly', 'Monthly'] as $option)<option @selected(old('repayment_frequency', $version?->repayment_frequency ?? 'Monthly') === $option)>{{ $option }}</option>@endforeach</select></label>
    </div>
</section>
<section class="product-form-section">
    <div class="product-section-title"><i class="mdi mdi-percent-outline"></i><div><strong>Interest & charges</strong><small>How interest and upfront costs are calculated.</small></div></div>
    <div class="product-form-grid">
        <label>Interest method<select name="interest_method" required>@foreach (['Reducing Balance', 'Flat'] as $option)<option @selected(old('interest_method', $version?->interest_method ?? 'Reducing Balance') === $option)>{{ $option }}</option>@endforeach</select></label>
        <label>Interest rate period<select name="rate_basis" required>@foreach (['Annual', 'Monthly', 'Whole Term'] as $option)<option @selected(old('rate_basis', $version?->rate_basis ?? 'Annual') === $option)>{{ $option === 'Whole Term' ? 'Entire loan term' : $option }}</option>@endforeach</select><small>Defines how often the entered rate applies. Example: 18% Annual means 18% per year.</small></label>
        <label>Interest rate (%)<input type="number" name="interest_rate_percent" value="{{ old('interest_rate_percent', $version ? round((float) $version->interest_rate * 100, 4) : 18) }}" min="0" max="100" step="0.01" required><small>Rate for the selected basis, not a decimal.</small></label>
        <label>Processing fee for the whole loan · TZS<input class="product-money" name="processing_fee" value="15000" inputmode="numeric" readonly required><small>Fixed at TZS 15,000 and distributed once across all instalments. It is not a percentage.</small></label>
        <label>Assurance contribution (%)<input type="number" name="assurance_percent" value="{{ old('assurance_percent', $version?->assurance_percent ?? 3) }}" min="0" max="100" step="0.01" required></label>
        <label>Maximum instalment as % of income<input type="number" name="maximum_installment_ratio_percent" value="{{ old('maximum_installment_ratio_percent', $version ? round((float) $version->maximum_installment_ratio * 100, 2) : 50) }}" min="1" max="100" step="0.01" required><small>Affordability limit. At 50%, a TZS 1,000,000 income allows up to a TZS 500,000 instalment.</small></label>
    </div>
</section>
<section class="product-form-section">
    <div class="product-section-title"><i class="mdi mdi-shield-check-outline"></i><div><strong>Grace periods & security</strong><small>Controls applied before a loan can be approved.</small></div></div>
    <div class="product-form-grid">
        <label>Principal grace period<input type="number" name="principal_grace_period" value="{{ old('principal_grace_period', $version?->principal_grace_period ?? 0) }}" min="0" required><small>Number of repayment cycles.</small></label>
        <label>Interest grace period<input type="number" name="interest_grace_period" value="{{ old('interest_grace_period', $version?->interest_grace_period ?? 0) }}" min="0" required><small>Number of repayment cycles.</small></label>
        <label>Minimum guarantors<input type="number" name="required_guarantors" value="{{ old('required_guarantors', $version?->required_guarantors ?? 0) }}" min="0" max="10" required></label>
        <label>Effective from<input type="date" name="effective_from" value="{{ old('effective_from', $version ? optional($version->effective_from)->addDay()->format('Y-m-d') : today()->format('Y-m-d')) }}" required></label>
        <label class="product-check"><input type="checkbox" name="requires_collateral" value="1" @checked(old('requires_collateral', $version?->requires_collateral ?? false))><span><strong>Collateral required</strong><small>Block approval until eligible collateral is verified.</small></span></label>
    </div>
</section>

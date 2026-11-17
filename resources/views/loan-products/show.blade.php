@extends('layouts.app')
@section('title', $loanProduct->name.' versions')
@section('content')
@php($latestVersion = $loanProduct->versions->sortByDesc('version')->first())
<div id="versions-top" class="version-page">
    @include('partials.alerts')
    <header class="version-hero">
        <a class="version-back" href="{{ route('loan-products.index') }}"><i class="mdi mdi-arrow-left"></i></a>
        <div class="version-product-icon"><i class="mdi mdi-cube-outline"></i></div>
        <div><span>Product configuration</span><h1>{{ $loanProduct->name }}</h1><p>{{ $loanProduct->code }} · {{ $loanProduct->description ?: 'No description provided.' }}</p></div>
        <span class="version-state {{ $loanProduct->is_active ? 'active' : '' }}"><i></i>{{ $loanProduct->is_active ? 'Active' : 'Inactive' }}</span>
        @can('loan-product.version.create')<a class="product-primary" href="#create-version"><i class="mdi mdi-plus"></i> New version</a>@endcan
    </header>

    @if($latestVersion)
    <section class="current-version">
        <div class="current-version-title"><span>Current configuration</span><h2>Version {{ $latestVersion->version }}</h2><p>Effective {{ $latestVersion->effective_from->format('d M Y') }}{{ $latestVersion->effective_to ? ' to '.$latestVersion->effective_to->format('d M Y') : ' · Open ended' }}</p></div>
        <div><small>Amount range</small><strong>TZS {{ number_format((float) $latestVersion->minimum_amount) }}</strong><span>to TZS {{ number_format((float) $latestVersion->maximum_amount) }}</span></div>
        <div><small>Duration</small><strong>{{ $latestVersion->minimum_term }}–{{ $latestVersion->maximum_term }}</strong><span>{{ $latestVersion->term_unit }}</span></div>
        <div><small>Interest</small><strong>{{ rtrim(rtrim(number_format((float) $latestVersion->interest_rate * 100, 2), '0'), '.') }}%</strong><span>{{ $latestVersion->interest_method }} · {{ $latestVersion->rate_basis }}</span></div>
        <div><small>Repayment</small><strong>{{ $latestVersion->repayment_frequency }}</strong><span>{{ $latestVersion->required_guarantors }} guarantor(s) · {{ $latestVersion->requires_collateral ? 'Collateral required' : 'No collateral' }}</span></div>
    </section>
    @else
    <div class="version-warning"><i class="mdi mdi-alert-outline"></i><div><strong>This product is not ready for lending.</strong><span>Create its first financial version to make it available in loan applications.</span></div></div>
    @endif

    <div class="version-section-heading"><div><span>Immutable audit history</span><h2>Product versions</h2><p>Existing applications retain the exact terms they were created with.</p></div><span>{{ $loanProduct->versions->count() }} {{ Str::plural('version', $loanProduct->versions->count()) }}</span></div>
    <div class="version-list">
        @forelse($loanProduct->versions->sortByDesc('version') as $version)
            @php($isEffective = $loanProduct->is_active && $version->effective_from->lte(today()) && ($version->effective_to === null || $version->effective_to->gte(today())))
            <article class="version-card">
                <div class="version-number"><strong>V{{ $version->version }}</strong><span>{{ $version->effective_from->format('d M Y') }}</span></div>
                <div class="version-summary"><h3>Version {{ $version->version }} <span class="version-chip {{ $isEffective ? 'active' : '' }}">{{ $isEffective ? 'Currently offered' : ($version->effective_to ? 'Historical' : 'Scheduled') }}</span></h3><p>{{ $version->effective_from->format('d M Y') }} — {{ $version->effective_to?->format('d M Y') ?? 'Open ended' }}</p></div>
                <div><small>Limits</small><strong>TZS {{ number_format((float) $version->minimum_amount) }}–{{ number_format((float) $version->maximum_amount) }}</strong></div>
                <div><small>Interest</small><strong>{{ rtrim(rtrim(number_format((float) $version->interest_rate * 100, 2), '0'), '.') }}% {{ $version->rate_basis }}</strong></div>
                <div><small>Fees / penalties</small><strong>{{ $version->fees->count() }} / {{ $version->penaltyRules->count() }}</strong></div>
                <div class="version-actions"><a href="#view-version-{{ $version->id }}"><i class="mdi mdi-eye-outline"></i> Details</a>@can('loan-product.fee.manage')<a href="#fee-version-{{ $version->id }}"><i class="mdi mdi-cash-plus"></i> Fee</a><a href="#penalty-version-{{ $version->id }}"><i class="mdi mdi-alert-plus-outline"></i> Penalty</a>@endcan @can('loan-product.accounting.manage')<a href="#accounts-version-{{ $version->id }}"><i class="mdi mdi-book-cog-outline"></i> Accounts</a>@endcan</div>
            </article>
            @include('loan-products._version_modals', ['version' => $version])
        @empty
            <div class="product-empty"><i class="mdi mdi-history"></i><h3>No versions configured</h3><p>Create the first version to define lending terms.</p></div>
        @endforelse
    </div>
</div>

@can('loan-product.version.create')
<div class="product-overlay" id="create-version"><a class="product-backdrop" href="#versions-top" aria-label="Close"></a><section class="product-modal" role="dialog" aria-modal="true"><header><div><h2>Create version {{ ($latestVersion?->version ?? 0) + 1 }}</h2><p>New loans use these terms from the effective date. Existing loans remain unchanged.</p></div><a href="#versions-top"><i class="mdi mdi-close"></i></a></header><div class="product-modal-body"><form id="create-version-form" method="POST" action="{{ route('loan-products.versions.store', $loanProduct) }}">@csrf @include('loan-products._version_fields', ['version' => $latestVersion])</form></div><footer><a class="product-secondary" href="#versions-top">Cancel</a><button class="product-primary" form="create-version-form"><i class="mdi mdi-content-save-outline"></i> Publish version</button></footer></section></div>
@endcan
@include('loan-products._management_styles')
@include('loan-products._version_styles')
<script>document.addEventListener('keydown',function(e){if(e.key==='Escape'&&location.hash.includes('version'))location.hash='versions-top'});</script>
@endsection

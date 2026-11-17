@extends('layouts.app')
@section('title', 'Loan products')
@section('content')
<div id="products-top" class="product-page">
    @include('partials.alerts')
    <header class="product-heading">
        <div class="product-title-icon"><i class="mdi mdi-cube-outline"></i></div>
        <div><span>Loan configuration</span><h1>Loan Products</h1><p>Manage pricing, limits, repayment rules and security requirements.</p></div>
        @can('loan-product.create')<a class="product-primary" href="#create-product"><i class="mdi mdi-plus"></i> New product</a>@endcan
    </header>
    <form class="product-toolbar" method="GET" data-product-filter>
        <label><i class="mdi mdi-magnify"></i><input name="search" value="{{ request('search') }}" placeholder="Search product name or code" autocomplete="off"></label>
        <select name="status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select>
        <span>{{ $loanProducts->total() }} {{ Str::plural('product', $loanProducts->total()) }}</span>
    </form>
    <div class="product-grid">
        @forelse ($loanProducts as $product)
            @php($current = $product->latestVersion)
            <article class="product-card {{ $product->is_active ? '' : 'is-inactive' }}">
                <div class="product-card-head"><div class="product-card-icon"><i class="mdi mdi-cube-outline"></i></div><div><h2>{{ $product->name }}</h2><p>{{ $product->code }} · Version {{ $current?->version ?? 'not configured' }}</p></div><span class="product-status {{ $product->is_active ? 'active' : '' }}"><i></i>{{ $product->is_active ? 'Active' : 'Inactive' }}</span></div>
                <p class="product-description">{{ $product->description ?: 'No description provided for this loan product.' }}</p>
                <div class="product-metrics">
                    <div><small>Amount range</small><strong>{{ $current ? 'TZS '.number_format((float) $current->minimum_amount) : 'Not set' }}</strong><span>{{ $current ? 'to TZS '.number_format((float) $current->maximum_amount) : 'Create the first version' }}</span></div>
                    <div><small>Duration</small><strong>{{ $current ? $current->minimum_term.'–'.$current->maximum_term : '—' }}</strong><span>{{ $current?->term_unit ?? 'Not set' }}</span></div>
                    <div class="accent"><small>Interest</small><strong>{{ $current ? rtrim(rtrim(number_format((float) $current->interest_rate * 100, 2), '0'), '.').'%' : '—' }}</strong><span>{{ $current ? strtolower($current->rate_basis) : 'Not set' }}</span></div>
                    <div><small>Repayment</small><strong>{{ $current?->repayment_frequency ?? '—' }}</strong><span>{{ $current?->interest_method ?? 'Not set' }}</span></div>
                </div>
                <footer><a href="#view-product-{{ $product->id }}"><i class="mdi mdi-eye-outline"></i> View</a>@can('loan-product.update')<a href="#edit-product-{{ $product->id }}"><i class="mdi mdi-pencil-outline"></i> Edit</a>@endcan<a href="{{ route('loan-products.show', $product) }}"><i class="mdi mdi-history"></i> Versions ({{ $product->versions_count }})</a>@can('loan-product.activate')<form method="POST" action="{{ route('loan-products.destroy', $product) }}">@csrf @method('DELETE')<button class="{{ $product->is_active ? 'danger' : '' }}"><i class="mdi {{ $product->is_active ? 'mdi-pause-circle-outline' : 'mdi-play-circle-outline' }}"></i> {{ $product->is_active ? 'Deactivate' : 'Activate' }}</button></form>@endcan</footer>
            </article>
            @include('loan-products._management_modals', ['product' => $product, 'current' => $current])
        @empty
            <div class="product-empty"><i class="mdi mdi-cube-off-outline"></i><h2>No products found</h2><p>Change the filters or create your first loan product.</p></div>
        @endforelse
    </div>
    <div class="product-pagination">{{ $loanProducts->links() }}</div>
</div>
@can('loan-product.create')
<div class="product-overlay" id="create-product"><a class="product-backdrop" href="#products-top" aria-label="Close creator"></a><section class="product-modal" role="dialog" aria-modal="true" aria-labelledby="create-product-title"><header><div><h2 id="create-product-title">New Loan Product</h2><p>Set the rules staff will use when creating a loan.</p></div><a href="#products-top" aria-label="Close"><i class="mdi mdi-close"></i></a></header><div class="product-modal-body"><form id="create-product-form" method="POST" action="{{ route('loan-products.store') }}">@csrf<section class="product-form-section"><div class="product-section-title"><i class="mdi mdi-tag-outline"></i><div><strong>Product identity</strong><small>Use a clear name borrowers and staff will recognize.</small></div></div><div class="product-form-grid"><label>Product name *<input name="name" value="{{ old('name') }}" placeholder="e.g. Business Growth Loan" required></label><label class="wide">Description<textarea name="description" rows="3" placeholder="Briefly explain who this product is for">{{ old('description') }}</textarea></label></div></section>@include('loan-products._version_fields')</form></div><footer><a class="product-secondary" href="#products-top">Cancel</a><button class="product-primary" type="submit" form="create-product-form"><i class="mdi mdi-content-save-outline"></i> Create product</button></footer></section></div>
@endcan
@include('loan-products._management_styles')
<script>
document.addEventListener('DOMContentLoaded', function () {
 document.querySelectorAll('.product-money').forEach(function (input) { const format=function(){const value=input.value.replace(/[^0-9.]/g,'').split('.');value[0]=value[0]?Number(value[0]).toLocaleString('en-US'):'';input.value=value.slice(0,2).join('.')};format();input.addEventListener('input',format);input.form?.addEventListener('submit',function(){input.value=input.value.replace(/,/g,'')}) });
 let timer;document.querySelectorAll('[data-product-filter] input').forEach(function(input){input.addEventListener('input',function(){clearTimeout(timer);timer=setTimeout(function(){input.form.submit()},450)})});document.querySelectorAll('[data-product-filter] select').forEach(function(select){select.addEventListener('change',function(){select.form.submit()})});
 document.addEventListener('keydown',function(event){if(event.key==='Escape'&&location.hash.includes('product'))location.hash='products-top'});
});
</script>
@endsection

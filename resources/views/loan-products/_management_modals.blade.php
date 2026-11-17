<div class="product-overlay" id="view-product-{{ $product->id }}">
    <a class="product-backdrop" href="#products-top" aria-label="Close details"></a>
    <section class="product-modal product-view" role="dialog" aria-modal="true" aria-labelledby="view-title-{{ $product->id }}">
        <header><div class="product-card-icon"><i class="mdi mdi-cube-outline"></i></div><div><h2 id="view-title-{{ $product->id }}">{{ $product->name }}</h2><p>{{ $product->code }} · {{ $product->versions_count }} version(s)</p></div><a href="#products-top" aria-label="Close"><i class="mdi mdi-close"></i></a></header>
        <div class="product-modal-body">
            @if($current)
                <div class="detail-grid">
                    <div><small>Minimum amount</small><strong>TZS {{ number_format((float) $current->minimum_amount) }}</strong></div><div><small>Maximum amount</small><strong>TZS {{ number_format((float) $current->maximum_amount) }}</strong></div>
                    <div><small>Duration</small><strong>{{ $current->minimum_term }}–{{ $current->maximum_term }} {{ strtolower($current->term_unit) }}</strong></div><div><small>Interest</small><strong>{{ rtrim(rtrim(number_format((float) $current->interest_rate * 100, 2), '0'), '.') }}% · {{ $current->rate_basis }}</strong></div>
                    <div><small>Repayment</small><strong>{{ $current->repayment_frequency }}</strong></div><div><small>Method</small><strong>{{ $current->interest_method }}</strong></div>
                    <div><small>Processing fee</small><strong>TZS {{ number_format((float) ($current->fees->firstWhere('name', 'Processing fee')?->value ?? 0)) }}</strong></div><div><small>Assurance</small><strong>{{ number_format((float) $current->assurance_percent, 2) }}%</strong></div>
                    <div><small>Guarantors</small><strong>{{ $current->required_guarantors ?: 'Not required' }}</strong></div><div><small>Collateral</small><strong>{{ $current->requires_collateral ? 'Required' : 'Not required' }}</strong></div>
                    <div><small>Effective from</small><strong>{{ $current->effective_from->format('d M Y') }}</strong></div><div><small>Status</small><strong>{{ $product->is_active ? 'Active' : 'Inactive' }}</strong></div>
                </div>
            @else<div class="product-empty">This product has no financial version yet.</div>@endif
        </div>
        <footer><a class="product-secondary" href="#products-top">Close</a>@can('loan-product.update')<a class="product-primary" href="#edit-product-{{ $product->id }}"><i class="mdi mdi-pencil-outline"></i> Edit product</a>@endcan</footer>
    </section>
</div>
@can('loan-product.update')
<div class="product-overlay" id="edit-product-{{ $product->id }}">
    <a class="product-backdrop" href="#products-top" aria-label="Close editor"></a>
    <section class="product-modal" role="dialog" aria-modal="true" aria-labelledby="edit-title-{{ $product->id }}">
        <header><div><h2 id="edit-title-{{ $product->id }}">Edit {{ $product->name }}</h2><p>Identity changes apply now. Financial changes create version {{ ($current?->version ?? 0) + 1 }} and preserve audit history.</p></div><a href="#products-top" aria-label="Close"><i class="mdi mdi-close"></i></a></header>
        <div class="product-modal-body">
            <form id="identity-{{ $product->id }}" method="POST" action="{{ route('loan-products.update', $product) }}">@csrf @method('PUT')
                <section class="product-form-section"><div class="product-section-title"><i class="mdi mdi-tag-outline"></i><div><strong>Product identity</strong><small>Name and description shown to staff and borrowers.</small></div></div><div class="product-form-grid"><label>Product code<input name="code" value="{{ $product->code }}" required></label><label>Product name<input name="name" value="{{ $product->name }}" required></label><label class="wide">Description<textarea name="description" rows="3">{{ $product->description }}</textarea></label></div></section>
            </form>
            @if($current)<form id="terms-{{ $product->id }}" method="POST" action="{{ route('loan-products.versions.store', $product) }}">@csrf @include('loan-products._version_fields', ['version' => $current])</form>@endif
        </div>
        <footer><a class="product-secondary" href="#products-top">Cancel</a><button class="product-secondary" type="submit" form="identity-{{ $product->id }}">Save identity</button>@if($current)<button class="product-primary" type="submit" form="terms-{{ $product->id }}"><i class="mdi mdi-content-save-outline"></i> Publish new version</button>@endif</footer>
    </section>
</div>
@endcan

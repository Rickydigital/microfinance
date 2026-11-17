@extends('layouts.app')
@section('title', 'Loan products')
@section('content')
    @include('partials.alerts')
    <div class="d-flex justify-content-between mb-4"><div><h1 class="h3 mb-1">Loan products</h1><p class="text-muted mb-0">Version-controlled rates, fees, penalties, security, and accounting.</p></div>@can('loan-product.create')<a class="btn btn-primary" href="{{ route('loan-products.create') }}">Create product</a>@endcan</div>
    <form class="card card-body border-0 shadow-sm mb-4" method="GET"><div class="input-group"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search by product name or code"><button class="btn btn-outline-primary">Search</button></div></form>
    <div class="row g-3">@forelse ($loanProducts as $product)<div class="col-md-6"><div class="card card-body border-0 shadow-sm"><div class="d-flex justify-content-between"><div><h2 class="h5">{{ $product->name }}</h2><p class="text-muted mb-1">{{ $product->code }} · {{ $product->versions_count }} version(s)</p><span class="badge {{ $product->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span></div><a class="btn btn-sm btn-outline-primary align-self-start" href="{{ route('loan-products.show', $product) }}">Configure</a></div></div></div>@empty<p>No products configured.</p>@endforelse</div><div class="mt-4">{{ $loanProducts->links() }}</div>
@endsection

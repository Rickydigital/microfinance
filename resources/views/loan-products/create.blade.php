@extends('layouts.app')
@section('title', 'Create loan product')
@section('content')
<div class="product-page">
    <h1 class="h3 mb-4">Create loan product</h1>
    @include('partials.alerts')
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('loan-products.store') }}">
        @csrf
        <section class="product-form-section"><div class="product-section-title"><i class="mdi mdi-tag-outline"></i><div><strong>Product identity</strong><small>Use a clear name borrowers and staff will recognize.</small></div></div><div class="product-form-grid"><label>Product name *<input name="name" value="{{ old('name') }}" required></label><label class="wide">Description<textarea name="description" rows="3">{{ old('description') }}</textarea></label></div></section>
        @include('loan-products._version_fields')
        <button class="product-primary align-self-start">Create product</button>
    </form>
</div>
@include('loan-products._management_styles')
@endsection

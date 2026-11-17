@extends('layouts.app')
@section('title', 'Edit '.$loanProduct->name)
@section('content')<h1 class="h3 mb-4">Edit product identity</h1>@include('partials.alerts')<div class="alert alert-info">Financial terms are immutable. Create a new version from the product page when rates, limits, fees, penalties, or accounting change.</div><form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('loan-products.update', $loanProduct) }}">@csrf @method('PUT') @include('loan-products._form')<button class="btn btn-primary align-self-start mt-3">Save product</button></form>@endsection

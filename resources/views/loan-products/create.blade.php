@extends('layouts.app')
@section('title', 'Create loan product')
@section('content')<h1 class="h3 mb-4">Create loan product</h1>@include('partials.alerts')<form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('loan-products.store') }}">@csrf @include('loan-products._form')<button class="btn btn-primary align-self-start mt-3">Create product</button></form>@endsection

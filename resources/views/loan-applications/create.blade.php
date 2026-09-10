@extends('layouts.app')
@section('title', 'New loan application')
@section('content')
    <div class="mb-4"><h1 class="h3 mb-1">New loan application</h1><p class="text-muted mb-0">Product policy, verified KYC data, interest, fees, and affordability are calculated transparently.</p></div>
    @include('partials.alerts')
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('loan-applications.store') }}" data-loan-application-form data-quote-url="{{ route('loan-applications.quote') }}">@csrf @include('loan-applications._form')<div class="mt-4"><button class="btn btn-primary">Save draft</button></div></form>
@endsection

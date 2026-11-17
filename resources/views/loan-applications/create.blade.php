@extends('layouts.app')
@section('title', 'New loan application')
@section('content')
    <div class="mb-4"><h1 class="h3 mb-1">New loan application</h1><p class="text-muted mb-0">Product policy, verified KYC data, interest, fees, and affordability are calculated transparently.</p></div>
    @include('partials.alerts')
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('loan-applications.store') }}" data-loan-application-form data-quote-url="{{ route('loan-applications.quote') }}">@csrf <div class="alert alert-info">Start with the customer, loan product, amount and purpose. Financial details and the quotation are calculated from the selected product and verified customer profile. Guarantors and collateral are managed later from the application Security tab.</div> @include('loan-applications._form')<div class="mt-4"><button class="btn btn-primary">Save draft</button></div></form>
@endsection

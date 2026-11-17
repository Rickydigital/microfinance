@extends('layouts.app')
@section('title', 'Edit '.$loanApplication->application_number)
@section('content')
    <div class="mb-4"><h1 class="h3 mb-1">Edit {{ $loanApplication->application_number }}</h1><p class="text-muted mb-0">Correct the application and review the recalculated quotation before submission.</p></div>
    @include('partials.alerts')
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('loan-applications.update', $loanApplication) }}" data-loan-application-form data-quote-url="{{ route('loan-applications.quote') }}">@csrf @method('PUT') <div class="alert alert-info">Start with the customer, loan product, amount and purpose. Financial details and the quotation are calculated from the selected product and verified customer profile. Guarantors and collateral are managed later from the application Security tab.</div> @include('loan-applications._form')<div class="mt-4 d-flex gap-2"><button class="btn btn-primary">Save changes</button><a class="btn btn-light" href="{{ route('loan-applications.show', $loanApplication) }}">Cancel</a></div></form>
@endsection

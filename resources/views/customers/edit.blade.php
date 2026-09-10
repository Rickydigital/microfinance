@extends('layouts.app')
@section('title', 'Edit customer')
@section('content')
    <h1 class="h3 mb-4">Edit customer</h1>
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('customers.update', $customer) }}">@csrf @method('PUT') @include('customers._form')</form>
@endsection

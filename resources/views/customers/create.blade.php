@extends('layouts.app')
@section('title', 'Add customer')
@section('content')
    <h1 class="h3 mb-4">Add customer</h1>
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('customers.store') }}">@csrf @include('customers._form', ['customer' => null])</form>
@endsection

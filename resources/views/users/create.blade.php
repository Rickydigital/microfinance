@extends('layouts.app')

@section('title', 'Create employee')

@section('content')
    <h1 class="h3 mb-4">Create employee</h1>
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('users.store') }}">
        @csrf
        @include('users._form', ['user' => null])
    </form>
@endsection

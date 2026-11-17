@extends('layouts.app')

@section('title', 'Edit employee')

@section('content')
    <h1 class="h3 mb-4">Edit employee</h1>
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')
        @include('users._form')
    </form>
@endsection

@extends('layouts.app')
@section('title', 'Create role')
@section('content')
    <h1 class="h3 mb-4">Create role</h1>
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('roles.store') }}">@csrf @include('roles._form')</form>
@endsection

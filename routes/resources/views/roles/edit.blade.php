@extends('layouts.app')
@section('title', 'Manage role')
@section('content')
    <h1 class="h3 mb-4">Manage {{ $role->name }}</h1>
    @if ($role->name === 'System Administrator')<div class="alert alert-warning">This protected role receives the complete permission catalogue from the seeder.</div>@endif
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('roles.update', $role) }}">@csrf @method('PUT') @include('roles._form')</form>
@endsection

@extends('layouts.app')

@section('title', 'My profile')

@section('content')
    @include('partials.alerts')
    <div class="mb-4"><h1 class="h3 mb-1">My profile</h1><p class="text-muted mb-0">Update your personal contact information.</p></div>
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('profile.update') }}">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="first_name">First name</label><input class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required></div>
            <div class="col-md-4"><label class="form-label" for="middle_name">Middle name</label><input class="form-control" id="middle_name" name="middle_name" value="{{ old('middle_name', $user->middle_name) }}"></div>
            <div class="col-md-4"><label class="form-label" for="last_name">Last name</label><input class="form-control" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required></div>
            <div class="col-md-6"><label class="form-label" for="phone">Phone</label><input class="form-control" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" required></div>
            <div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control" id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required></div>
        </div>
        <div class="mt-4 d-flex gap-2"><button class="btn btn-primary">Save profile</button><a class="btn btn-outline-secondary" href="{{ route('dashboard') }}">Cancel</a></div>
    </form>
@endsection

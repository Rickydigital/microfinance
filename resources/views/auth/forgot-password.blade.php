@extends('layouts.auth')
@section('title', 'Forgot password')
@section('content')
    <span class="eyebrow">Account recovery</span><h2>Reset your password</h2><p class="auth-intro">Enter your staff email and we will send a secure reset link.</p>
    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <form method="POST" action="{{ route('password.email') }}">@csrf<div class="form-group"><label for="email">Email address</label><input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required autofocus>@error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror</div><button class="btn btn-primary btn-block" type="submit">Send reset link</button><a class="btn btn-link btn-block" href="{{ route('login') }}">Back to sign in</a></form>
@endsection

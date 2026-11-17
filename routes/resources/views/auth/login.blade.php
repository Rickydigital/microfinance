@extends('layouts.auth')
@section('title', 'Sign in')
@section('content')
    <span class="eyebrow">Employee portal</span>
    <h2>Welcome back</h2>
    <p class="auth-intro">Use your authorized staff account to continue.</p>
    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <form method="POST" action="{{ route('login') }}">@csrf
        <div class="form-group"><label for="email">Email address</label><input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">@error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror</div>
        <div class="form-group"><div class="d-flex justify-content-between"><label for="password">Password</label><a href="{{ route('password.request') }}">Forgot password?</a></div><input id="password" class="form-control" type="password" name="password" required autocomplete="current-password"></div>
        <div class="custom-control custom-checkbox mb-4"><input id="remember" class="custom-control-input" type="checkbox" name="remember"><label class="custom-control-label" for="remember">Keep me signed in</label></div>
        <button class="btn btn-primary btn-block" type="submit">Sign in securely <i class="mdi mdi-arrow-right"></i></button>
    </form>
    <div class="text-center mt-4 pt-3 border-top">
        <p class="mb-2">Are you a customer?</p>
        <a class="btn btn-outline-primary btn-block" href="{{ route('portal.login') }}">Customer portal login</a>
        <a class="btn btn-link btn-block" href="{{ route('portal.register') }}">Register as a new customer</a>
    </div>
@endsection

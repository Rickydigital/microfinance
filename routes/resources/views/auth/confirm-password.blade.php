@extends('layouts.auth')
@section('title', 'Confirm password')
@section('content')
    <span class="eyebrow">Protected operation</span><h2>Confirm your password</h2><p class="auth-intro">This action affects protected financial or administrative information.</p><form method="POST" action="{{ route('password.confirm') }}">@csrf<div class="form-group"><label for="password">Password</label><input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" required autofocus>@error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror</div><button class="btn btn-primary btn-block" type="submit">Confirm and continue</button></form>
@endsection

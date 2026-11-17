@extends('layouts.auth')
@section('title', 'Reset password')
@section('content')
    <span class="eyebrow">Account recovery</span><h2>Choose a new password</h2><form method="POST" action="{{ route('password.store') }}">@csrf<input type="hidden" name="token" value="{{ $request->route('token') }}"><div class="form-group"><label for="email">Email</label><input id="email" class="form-control" type="email" name="email" value="{{ old('email', $request->email) }}" required></div><div class="form-group"><label for="password">New password</label><input id="password" class="form-control" type="password" name="password" required></div><div class="form-group"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required></div><button class="btn btn-primary btn-block" type="submit">Reset password</button></form>
@endsection

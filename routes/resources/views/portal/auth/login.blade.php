@extends('layouts.portal-auth')
@section('title','Customer login')
@section('auth-headline','Karibu tena. Your ledger is waiting.')
@section('auth-subline','Sign in to track applications, review your loan schedule, and see every payment posted to your account.')
@section('content')

<span class="eyebrow">Sign in</span>
<h1>Customer login</h1>
<p>Sign in after your branch has verified and activated your KYC profile.</p>

<form method="POST" action="{{ route('portal.login.store') }}">
    @csrf

    <div class="field">
        <label for="email">Email</label>
        <input class="input" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
    </div>

    <div class="field">
        <label for="password">Password</label>
        <input class="input" type="password" id="password" name="password" required>
    </div>

    <div class="check-row" style="margin-bottom:20px;">
        <input type="checkbox" id="remember" name="remember" value="1">
        <label for="remember" style="font-size:13px;font-weight:400;text-transform:none;color:var(--ink-soft);margin:0;">Remember me</label>
    </div>

    <button class="btn btn-accent" style="width:100%;justify-content:center;">Sign in</button>

    <p style="margin:20px 0 0;font-size:13.5px;">New customer? <a href="{{ route('portal.register') }}" style="font-weight:600;">Register for verification</a></p>
    <p style="margin:8px 0 0;font-size:12px;color:var(--ink-faint);">Staff member? <a href="{{ route('login') }}">Staff login</a></p>
</form>

@endsection
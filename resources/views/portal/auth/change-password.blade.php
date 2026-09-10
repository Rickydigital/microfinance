@extends('layouts.portal-auth')
@section('title','Change temporary password')
@section('auth-headline','One more step before you\'re in.')
@section('auth-subline','A quick password change keeps your loan and repayment details private to you.')
@section('content')

<span class="eyebrow">Security</span>
<h1>Create a new password</h1>
<p>The temporary password has worked. Replace it before accessing your financial information.</p>

<form method="POST" action="{{ route('portal.password.update') }}">
    @csrf
    @method('PUT')

    <div class="field">
        <label for="password">New password</label>
        <input class="input" type="password" id="password" name="password" required autofocus autocomplete="new-password">
        <div class="hint">At least eight characters, including letters and numbers.</div>
    </div>

    <div class="field">
        <label for="password_confirmation">Confirm new password</label>
        <input class="input" type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
    </div>

    <button class="btn btn-accent" style="width:100%;justify-content:center;margin-top:8px;">Save password and continue</button>
</form>

@endsection
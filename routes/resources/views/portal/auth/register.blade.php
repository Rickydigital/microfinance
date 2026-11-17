@extends('layouts.portal-auth')
@section('title','Customer registration')
@section('auth-headline','Every application starts with a real profile.')
@section('auth-subline','Registration opens the door — your branch still verifies your identity, address, and affordability before the portal unlocks.')
@section('content')

<span class="eyebrow">Get started</span>
<h1>Register as a customer</h1>
<p>Registration does not approve a loan. Your selected branch completes identity, address, document, and affordability verification before portal access is activated.</p>

<form method="POST" action="{{ route('portal.register.store') }}">
    @csrf

    <div class="grid grid-2">
        <div class="field">
            <label for="branch_id">Branch</label>
            <select class="input" id="branch_id" name="branch_id" required>
                <option value="">Select branch</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(old('branch_id')==$branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="type">Customer type</label>
            <select class="input" id="type" name="type" required>
                @foreach($customerTypes as $type)
                    <option value="{{ $type->code }}" @selected(old('type')===$type->code)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="field">
            <label for="first_name">First name</label>
            <input class="input" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
        </div>
        <div class="field">
            <label for="last_name">Last name</label>
            <input class="input" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
        </div>
    </div>

    <div class="field">
        <label for="middle_name">Middle name <span style="text-transform:none;font-weight:400;">(optional)</span></label>
        <input class="input" id="middle_name" name="middle_name" value="{{ old('middle_name') }}">
    </div>

    <div class="grid grid-2">
        <div class="field">
            <label for="gender">Gender</label>
            <select class="input" id="gender" name="gender" required>
                <option>Male</option><option>Female</option><option>Other</option>
            </select>
        </div>
        <div class="field">
            <label for="date_of_birth">Date of birth</label>
            <input class="input" type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}" required>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="field">
            <label for="phone">Phone</label>
            <input class="input" id="phone" name="phone" value="{{ old('phone') }}" required>
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input class="input" type="email" id="email" name="email" value="{{ old('email') }}" required>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="field">
            <label for="password">Password</label>
            <input class="input" type="password" id="password" name="password" required>
        </div>
        <div class="field">
            <label for="password_confirmation">Confirm password</label>
            <input class="input" type="password" id="password_confirmation" name="password_confirmation" required>
        </div>
    </div>

    <button class="btn btn-accent" style="width:100%;justify-content:center;margin-top:8px;">Submit registration</button>
    <p style="margin:16px 0 0;font-size:13px;text-align:center;">Already registered? <a href="{{ route('portal.login') }}" style="font-weight:600;">Sign in</a></p>
</form>

@endsection
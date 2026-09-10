<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('microfinance.institution_name') }}</title>
    @include('layouts.partials.design-system')
</head>
<body>
<div class="auth-shell">

    <div class="auth-brand">
        <div class="brand">
            @php($logoPath = config('microfinance.logo_path'))
            @if($logoPath && file_exists($logoPath))
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('microfinance.institution_name') }} logo">
            @endif
            <div>
                <div class="brand-name">{{ config('microfinance.institution_name') }}</div>
                <div class="brand-tag">{{ config('microfinance.tagline') }}</div>
            </div>
        </div>

        <div>
            <h1 class="auth-headline">@yield('auth-headline', 'Karibu. Your goals, financed properly.')</h1>
            <p class="auth-sub">@yield('auth-subline', 'A transparent account of every shilling — what you borrow, what it costs, and what you owe, laid out clearly at every step.')</p>
            <div class="auth-points">
                <div class="auth-point">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                    Clear rates and fees, shown before you apply
                </div>
                <div class="auth-point">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                    Track every application from submission to disbursement
                </div>
                <div class="auth-point">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                    A full repayment ledger, always in view
                </div>
            </div>
        </div>

        <div class="auth-foot">{{ config('microfinance.institution_name') }} · {{ config('microfinance.phone') }} · {{ config('microfinance.email') }}</div>
    </div>

    <div class="auth-form-side">
        <div class="auth-card">
            @if(session('status'))
                <div class="alert alert-info">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                    @if($errors->count() > 1) &nbsp;(+{{ $errors->count() - 1 }} more) @endif
                </div>
            @endif
            @yield('content')
        </div>
    </div>

</div>
</body>
</html>